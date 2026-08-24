<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Servicio del catálogo (Punto 5-A). El admin los crea/edita; se incluyen por
 * plan (plan_service) y el usuario los solicita (BenefitRequest.service_id).
 */
class Service extends Model
{
    protected $fillable = [
        'nombre', 'slug', 'descripcion', 'icono', 'activo', 'orden',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Service $s) {
            if (blank($s->slug)) {
                $base = Str::slug($s->nombre) ?: 'servicio';
                $slug = $base;
                $i = 2;
                while (static::where('slug', $slug)->where('id', '!=', $s->id)->exists()) {
                    $slug = $base.'-'.$i;
                    $i++;
                }
                $s->slug = $slug;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Planes que incluyen este servicio. */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class);
    }

    /** Solicitudes de este servicio. */
    public function benefitRequests(): HasMany
    {
        return $this->hasMany(BenefitRequest::class);
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }
}
