<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Plan extends Model
{
    protected $fillable = [
        'nombre', 'slug', 'audiencia', 'precio', 'moneda', 'periodo',
        'descripcion', 'beneficios', 'cobertura', 'destacado', 'activo', 'orden',
        // Fase 2 · pasarela: ID del price/product en Stripe (o el proveedor
        // que se elija). Sin este valor el CheckoutController no puede crear
        // la sesión de suscripción — el StripeGateway lo exige.
        'provider_price_id', 'is_recurring', 'interval',
    ];

    protected $casts = [
        'beneficios' => 'array',
        'precio' => 'decimal:2',
        'destacado' => 'boolean',
        'activo' => 'boolean',
        'orden' => 'integer',
        'is_recurring' => 'boolean',
    ];

    /** A quién va dirigido el plan. */
    public const AUDIENCIAS = [
        'individual' => 'Individual (persona física)',
        'estudio' => 'Estudios y marcas (persona moral)',
    ];

    /** Periodicidad del cobro. */
    public const PERIODOS = [
        'mensual' => 'Mensual',
        'anual' => 'Anual',
    ];

    protected static function booted(): void
    {
        static::saving(function (Plan $p) {
            if (blank($p->slug)) {
                $base = Str::slug(trim($p->audiencia.' '.$p->nombre)) ?: 'plan';
                $slug = $base;
                $i = 1;
                while (static::where('slug', $slug)->where('id', '!=', $p->id)->exists()) {
                    $slug = $base.'-'.(++$i);
                }
                $p->slug = $slug;
            }
        });
    }

    public function audienciaLabel(): string
    {
        return self::AUDIENCIAS[$this->audiencia] ?? $this->audiencia;
    }

    public function periodoLabel(): string
    {
        return self::PERIODOS[$this->periodo] ?? $this->periodo;
    }

    /** Servicios del catálogo que incluye este plan (Punto 5-A). */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    /**
     * Plan gratuito a propósito: el admin capturó 0 como precio.
     *
     * Se activa al instante sin pasar por la pasarela. Antes (08-10-2026)
     * un plan en 0 se trataba igual que uno sin configurar y el usuario
     * acababa en un mensaje de "escríbenos", sin forma de obtenerlo.
     */
    public function esGratuito(): bool
    {
        return $this->precio !== null && (float) $this->precio === 0.0;
    }

    /**
     * Plan sin precio capturado. Se muestra como "A consultar": son los que
     * se negocian caso por caso, no un error de configuración.
     */
    public function sinPrecio(): bool
    {
        return $this->precio === null;
    }

    /** Requiere pasar por la pasarela de pago. */
    public function requierePago(): bool
    {
        return ! $this->sinPrecio() && (float) $this->precio > 0;
    }

    /**
     * Planes que se pueden contratar solos desde la web: los de precio
     * cobrable y los gratuitos. Los de "A consultar" quedan fuera porque
     * pasan por una conversación con el equipo.
     */
    public function scopeAutoContratable(Builder $query): Builder
    {
        return $query->where('activo', true)->whereNotNull('precio');
    }
}
