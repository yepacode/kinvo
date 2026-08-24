<?php

namespace App\Models;

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
}
