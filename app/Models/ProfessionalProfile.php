<?php

namespace App\Models;

use App\Enums\ModalidadTrabajo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class ProfessionalProfile extends Model
{
    use \App\Models\Concerns\HasMediaItems;

    protected $fillable = [
        'user_id', 'slug', 'full_name', 'photo_path', 'headline', 'birthdate', 'bio',
        'years_experience', 'modalidad', 'availability', 'languages',
        'certifications_text', 'certification_file_path', 'media_url', 'media_path', 'media_type',
        'location_id', 'colonia', 'phone',
        'socials', 'is_published', 'is_verified', 'verified_at',
    ];

    protected $casts = [
        'socials' => 'array',
        'availability' => 'array',
        'languages' => 'array',
        'birthdate' => 'date',
        'is_published' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'modalidad' => ModalidadTrabajo::class,
    ];

    /** Días de disponibilidad (clave => etiqueta). */
    public const DIAS = [
        'lun' => 'Lunes',
        'mar' => 'Martes',
        'mie' => 'Miércoles',
        'jue' => 'Jueves',
        'vie' => 'Viernes',
        'fds' => 'Fines de semana',
    ];

    /** Franjas horarias. */
    public const FRANJAS = [
        'am' => 'AM',
        'pm' => 'PM',
    ];

    /** Idiomas soportados (solo inglés y español). */
    public const IDIOMAS = [
        'es' => 'Español',
        'en' => 'Inglés',
    ];

    /** Todas las claves válidas de disponibilidad (lun_am, lun_pm, ...). */
    public static function slotsDisponibilidad(): array
    {
        $slots = [];
        foreach (array_keys(self::DIAS) as $dia) {
            foreach (array_keys(self::FRANJAS) as $franja) {
                $slots[] = $dia.'_'.$franja;
            }
        }

        return $slots;
    }

    /** ¿El profesional marcó este slot (día_franja)? */
    public function tieneDisponibilidad(string $slot): bool
    {
        return in_array($slot, $this->availability ?? [], true);
    }

    /** Etiquetas legibles de los idiomas seleccionados. */
    public function idiomasLegibles(): array
    {
        return collect($this->languages ?? [])
            ->map(fn ($cod) => self::IDIOMAS[$cod] ?? $cod)
            ->all();
    }

    /** Resumen legible de disponibilidad por día: ["Lunes" => "AM · PM", ...]. */
    public function disponibilidadPorDia(): array
    {
        $resumen = [];
        foreach (self::DIAS as $dia => $etiquetaDia) {
            $franjas = [];
            foreach (self::FRANJAS as $franja => $etiquetaFranja) {
                if ($this->tieneDisponibilidad($dia.'_'.$franja)) {
                    $franjas[] = $etiquetaFranja;
                }
            }
            if ($franjas) {
                $resumen[$etiquetaDia] = implode(' · ', $franjas);
            }
        }

        return $resumen;
    }

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

    /** Solo perfiles publicados cuyo dueño está activo (no suspendido/pendiente). */
    public function scopeVisiblePublicamente($query)
    {
        return $query->where('is_published', true)
            ->whereHas('user', fn ($u) => $u->where('estado', \App\Enums\EstadoUsuario::Activo->value));
    }

    /** ¿Este perfil es visible al público (publicado + dueño activo)? */
    public function esVisiblePublicamente(): bool
    {
        return $this->is_published && $this->user?->estaActivo();
    }

    /** Checklist de completitud del perfil: etiqueta => ¿está listo? */
    public function checklistPerfil(): array
    {
        return [
            'Foto' => filled($this->photo_path),
            'Titular' => filled($this->headline),
            'Fecha de nacimiento' => ! is_null($this->birthdate),
            'Presentación (bio)' => filled($this->bio),
            'Años de experiencia' => ! is_null($this->years_experience),
            'Modalidad' => ! is_null($this->modalidad),
            'Disponibilidad' => collect($this->availability ?? [])->isNotEmpty(),
            'Idiomas' => collect($this->languages ?? [])->isNotEmpty(),
            'Ubicación' => ! is_null($this->location_id),
            'Disciplinas' => $this->disciplines()->exists(),
            'Certificaciones' => filled($this->certifications_text),
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
