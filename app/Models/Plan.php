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

    /**
     * Monedas admitidas. MercadoPago exige el código ISO exacto y en
     * mayúsculas: rechaza 'mxn', 'MX' o cualquier texto libre con un error
     * 400 que antes sólo aparecía cuando una persona intentaba pagar.
     */
    public const MONEDAS = [
        'MXN' => 'MXN · Peso mexicano',
        'ARS' => 'ARS · Peso argentino',
        'BRL' => 'BRL · Real brasileño',
        'CLP' => 'CLP · Peso chileno',
        'COP' => 'COP · Peso colombiano',
        'PEN' => 'PEN · Sol peruano',
        'UYU' => 'UYU · Peso uruguayo',
    ];

    protected static function booted(): void
    {
        static::saving(function (Plan $p) {
            // Red de seguridad por debajo del formulario: cualquier escritura
            // (importador, seeder, consola) deja la moneda en el formato que
            // la pasarela acepta, o en MXN si llega vacía.
            $p->moneda = strtoupper(trim((string) $p->moneda)) ?: 'MXN';

            // `interval` ya no se edita: se deriva de `periodo` para que la
            // ficha y lo que se le pide a la pasarela no puedan discrepar.
            $p->interval = $p->periodo === 'anual' ? 'year' : 'month';

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
     * ¿El cobro es anual? REGLA ÚNICA de la periodicidad.
     *
     * El plan arrastra dos campos para lo mismo: `periodo` ('mensual'|'anual',
     * que es lo que se enseña y lo que edita el panel arriba) e `interval`
     * ('month'|'year', que es lo que miraba la pasarela, editable en otra
     * sección del mismo formulario). Podían decir cosas distintas: un plan
     * marcado "Anual" en la ficha seguía cobrándose cada mes porque su
     * `interval` se había quedado en 'month'. Y al revés, el alta de un plan
     * gratuito comparaba `periodo === 'year'`, un valor que ese campo no toma
     * nunca, así que un plan anual sin costo sólo daba un mes.
     *
     * Manda `periodo`, que es el que se ve y el que se edita de verdad;
     * `interval` sólo se respeta si `periodo` no dice nada.
     */
    public function esAnual(): bool
    {
        if (in_array($this->periodo, ['anual', 'year', 'yearly', 'annual'], true)) {
            return true;
        }

        if (in_array($this->periodo, ['mensual', 'month', 'monthly'], true)) {
            return false;
        }

        return in_array($this->interval, ['year', 'anual'], true);
    }

    /** Cuánto dura un periodo de este plan a partir de ahora. */
    public function vigenciaDesdeAhora(): \Illuminate\Support\Carbon
    {
        return $this->esAnual() ? now()->addYear() : now()->addMonth();
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
