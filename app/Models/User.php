<?php

namespace App\Models;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    // OJO seguridad: `nivel` y `estado` NO son asignables en masa (fuera de $fillable).
    // Se setean SIEMPRE de forma explícita (registro, aprobación, seeders) para evitar
    // escalado de privilegios si algún endpoint futuro hiciera update($request->all()).

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'nivel' => RolUsuario::class,
            'estado' => EstadoUsuario::class,
            'membership_expires_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        // Las notificaciones son polimórficas (sin FK): limpiarlas al borrar el usuario.
        static::deleting(function (User $user) {
            $user->notifications()->delete();
        });
    }

    /**
     * Solo el owner (rol Admin) accede al panel Filament.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->nivel === RolUsuario::Admin
            && $this->estado === EstadoUsuario::Activo;
    }

    public function esAdmin(): bool
    {
        return $this->nivel === RolUsuario::Admin;
    }

    public function esProfesional(): bool
    {
        return $this->nivel === RolUsuario::Professional;
    }

    public function esContratante(): bool
    {
        return $this->nivel === RolUsuario::Contractor;
    }

    public function estaActivo(): bool
    {
        return $this->estado === EstadoUsuario::Activo;
    }

    public function membershipPlan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Plan::class, 'membership_plan_id');
    }

    /** ¿Tiene una membresía vigente (vence hoy o en el futuro)? */
    public function tieneMembresiaActiva(): bool
    {
        return $this->membership_expires_at !== null
            && $this->membership_expires_at->gte(today());
    }

    public function professionalProfile(): HasOne
    {
        return $this->hasOne(ProfessionalProfile::class);
    }

    public function saves(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Save::class);
    }

    /** ¿El usuario guardó este modelo (perfil, oferta, etc.)? */
    public function haGuardado(\Illuminate\Database\Eloquent\Model $model): bool
    {
        return $this->saves()
            ->where('saveable_type', $model->getMorphClass())
            ->where('saveable_id', $model->getKey())
            ->exists();
    }

    public function companyProfile(): HasOne
    {
        return $this->hasOne(CompanyProfile::class);
    }

    /**
     * Ruta de aterrizaje según rol y estado (usada tras login/registro).
     */
    public function homeRoute(bool $absolute = true): string
    {
        if ($this->esAdmin()) {
            return route('filament.admin.pages.dashboard', absolute: $absolute);
        }

        // Aprobación activada: mientras no esté activo, va al aviso de cuenta pendiente.
        if (! $this->estaActivo()) {
            return route('account.pending', absolute: $absolute);
        }

        // Áreas por rol (buscador del contratante y panel del profesional llegan en F3/F4).
        return route('dashboard', absolute: $absolute);
    }
}
