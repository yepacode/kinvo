<?php

namespace App\Models;

use App\Enums\ModalidadTrabajo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class ProfessionalProfile extends Model
{
    protected $fillable = [
        'user_id', 'slug', 'photo_path', 'headline', 'bio',
        'years_experience', 'modalidad', 'location_id', 'phone',
        'socials', 'is_published', 'is_verified', 'verified_at',
    ];

    protected $casts = [
        'socials' => 'array',
        'is_published' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'modalidad' => ModalidadTrabajo::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (ProfessionalProfile $p) {
            if (blank($p->slug)) {
                $base = Str::slug($p->headline ?: ($p->user?->name ?? 'perfil'));
                $slug = $base ?: 'perfil';
                $i = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base.'-'.(++$i);
                }
                $p->slug = $slug;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function disciplines(): BelongsToMany
    {
        return $this->belongsToMany(Discipline::class);
    }

    public function certifications(): BelongsToMany
    {
        return $this->belongsToMany(Certification::class);
    }

    public function contacts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function views(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProfileView::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Checklist de completitud del perfil: etiqueta => ¿está listo? */
    public function checklistPerfil(): array
    {
        return [
            'Foto' => filled($this->photo_path),
            'Titular' => filled($this->headline),
            'Presentación (bio)' => filled($this->bio),
            'Años de experiencia' => ! is_null($this->years_experience),
            'Modalidad' => ! is_null($this->modalidad),
            'Ubicación' => ! is_null($this->location_id),
            'Disciplinas' => $this->disciplines()->exists(),
            'Certificaciones' => $this->certifications()->exists(),
            'Redes o sitio web' => collect($this->socials ?? [])->filter()->isNotEmpty(),
        ];
    }

    /** Porcentaje de perfil completo (0–100). */
    public function porcentajeCompleto(): int
    {
        $items = $this->checklistPerfil();

        return (int) round(count(array_filter($items)) / count($items) * 100);
    }

    /** Campos que aún faltan por llenar. */
    public function faltantesPerfil(): array
    {
        return array_keys(array_filter($this->checklistPerfil(), fn ($listo) => ! $listo));
    }
}
