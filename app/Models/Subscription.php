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

    /** ¿La suscripción da acceso ahora mismo? */
    public function estaVigente(): bool
    {
        // Active + past_due (dando la gracia mientras se reintenta el cobro fallido).
        if (in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_TRIALING, self::STATUS_PAST_DUE], true)) {
            return $this->current_period_end === null || $this->current_period_end->isFuture();
        }
        // Cancelada pero aún dentro del periodo pagado.
        if ($this->status === self::STATUS_CANCELED && $this->current_period_end?->isFuture()) {
            return true;
        }
        return false;
    }
}
