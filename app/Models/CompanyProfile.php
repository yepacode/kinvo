<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CompanyProfile extends Model
{
    protected $fillable = [
        'user_id', 'company_name', 'slug', 'sector', 'disciplines_text', 'logo_path',
        'description', 'website', 'location_id', 'estado', 'address', 'postal_code', 'colonia', 'show_address',
        'contact_name', 'contact_phone', 'contact_email', 'media_url',
    ];

    protected $casts = [
        'show_address' => 'boolean',
    ];

    /** Estados de México para el selector de ubicación del estudio. */
    public const ESTADOS_MX = [
        'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche',
        'Chiapas', 'Chihuahua', 'Ciudad de México', 'Coahuila', 'Colima',
        'Durango', 'Estado de México', 'Guanajuato', 'Guerrero', 'Hidalgo',
        'Jalisco', 'Michoacán', 'Morelos', 'Nayarit', 'Nuevo León', 'Oaxaca',
        'Puebla', 'Querétaro', 'Quintana Roo', 'San Luis Potosí', 'Sinaloa',
        'Sonora', 'Tabasco', 'Tamaulipas', 'Tlaxcala', 'Veracruz', 'Yucatán',
        'Zacatecas',
    ];

    protected static function booted(): void
    {
        static::creating(function (CompanyProfile $c) {
            if (blank($c->slug)) {
                $base = Str::slug($c->company_name ?: 'estudio') ?: 'estudio';
                $slug = $base;
                $i = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base.'-'.(++$i);
                }
                $c->slug = $slug;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** Solo estudios cuyo dueño está activo (aprobado). */
    public function scopeVisiblePublicamente($query)
    {
        return $query->whereHas('user', fn ($u) => $u->where('estado', \App\Enums\EstadoUsuario::Activo->value));
    }

    /** ¿Este estudio es visible al público (dueño activo)? */
    public function esVisiblePublicamente(): bool
    {
        return (bool) $this->user?->estaActivo();
    }
}
