<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use \App\Models\Concerns\Auditable;

    /** Solo rastreamos cambios de estado y cancelación en AuditLog. */
    protected function auditableAttributes(): array
    {
        return ['status', 'canceled_at', 'plan_id', 'current_period_end'];
    }

    /** Estados alineados con Stripe: mismo vocabulario para no confundir al integrar. */
    public const STATUS_INCOMPLETE = 'incomplete';
    public const STATUS_TRIALING   = 'trialing';
    public const STATUS_ACTIVE     = 'active';
    public const STATUS_PAST_DUE   = 'past_due';
    public const STATUS_CANCELED   = 'canceled';
    public const STATUS_UNPAID     = 'unpaid';

    protected $fillable = [
        'user_id', 'plan_id', 'provider', 'provider_subscription_id', 'provider_customer_id',
        'status', 'current_period_start', 'current_period_end', 'canceled_at', 'ends_at',
    ];

    protected $casts = [
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'canceled_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * REGLA ÚNICA de "esta suscripción da acceso ahora mismo".
     *
     * Antes esta regla estaba copiada en tres sitios (el middleware, el
     * checkout y este modelo) y las copias no decían lo mismo, así que el
     * panel y la página de membresías contestaban distinto sobre el mismo
     * usuario. Ahora el scope es la verdad y `estaVigente()` es su reflejo
     * en PHP; quien necesite la regla usa una de las dos.
     *
     *  - active / trialing  → sí (el periodo puede estar sin llenar en prueba).
     *  - past_due / canceled → sólo dentro de un periodo ya pagado y con
     *    fecha conocida. Sin exigir la fecha, una past_due sin
     *    `current_period_end` daría acceso para siempre.
     */
    public function scopeVigentes(\Illuminate\Database\Eloquent\Builder $q): \Illuminate\Database\Eloquent\Builder
    {
        return $q->where(function ($w) {
            $w->where(function ($enCurso) {
                $enCurso->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_TRIALING])
                    ->where(function ($p) {
                        $p->whereNull('current_period_end')
                            ->orWhere('current_period_end', '>=', now());
                    });
            })->orWhere(function ($deGracia) {
                $deGracia->whereIn('status', [self::STATUS_PAST_DUE, self::STATUS_CANCELED])
                    ->whereNotNull('current_period_end')
                    ->where('current_period_end', '>=', now());
            });
        });
    }

    /**
     * Suscripción por la que la persona está pagando ahora: lo que impide
     * contratar otra (doble cobro). Una cancelada dentro de su periodo NO
     * entra aquí — conserva el acceso, pero puede volver a contratar.
     */
    public function scopeEnCurso(\Illuminate\Database\Eloquent\Builder $q): \Illuminate\Database\Eloquent\Builder
    {
        return $q->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_TRIALING])
            ->where(function ($p) {
                $p->whereNull('current_period_end')
                    ->orWhere('current_period_end', '>=', now());
            });
    }

    /** ¿La suscripción da acceso ahora mismo? Espejo de `scopeVigentes`. */
    public function estaVigente(): bool
    {
        $periodoAbierto = $this->current_period_end === null
            || $this->current_period_end->gte(now());

        if (in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_TRIALING], true)) {
            return $periodoAbierto;
        }

        // Cobro fallido o cancelada: sólo mientras dure el periodo ya pagado.
        if (in_array($this->status, [self::STATUS_PAST_DUE, self::STATUS_CANCELED], true)) {
            return $this->current_period_end !== null && $this->current_period_end->gte(now());
        }

        return false;
    }
}
