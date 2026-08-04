<?php

namespace App\Models;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Translation\HasLocalePreference;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, HasLocalePreference
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
            // Datetime (no date) para que un refund a las 14:26 revoque acceso
            // inmediato en vez de darlo hasta el 23:59 (LOW agente 8).
            'membership_expires_at' => 'datetime',
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

    public function estaSuspendido(): bool
    {
        return $this->estado === EstadoUsuario::Suspendido;
    }

    /**
     * Contratistas: cuenta aprobada pero perfil de empresa aún NO revisado
     * por Kinvoo. Puede llenar/editar su perfil, pero no ver el directorio.
     * Ver EstadoUsuario para el flujo completo.
     */
    public function tienePerfilPendiente(): bool
    {
        return $this->estado === EstadoUsuario::PerfilPendiente;
    }

    /**
     * Idioma preferido — usado por Laravel Mail y Notifications para procesar
     * correos en el idioma del receptor aunque la cola se ejecute más tarde
     * (después de que la sesión web / cookie del usuario ya no exista).
     */
    public function preferredLocale(): string
    {
        return in_array($this->locale, ['es', 'en'], true) ? $this->locale : 'es';
    }

    /**
     * Sobreescribe la Notification de reset por defecto de Laravel — la del
     * framework usa strings inglés que no están en nuestros JSON, así que
     * los usuarios ES recibían el correo en inglés. La nuestra pasa todo
     * por `__()` y respeta `preferredLocale`.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordEnEspanol($token));
    }

    /**
     * Marca al usuario como Suspendido y oculta/desverifica su perfil profesional
     * si tiene uno. Fuente única de verdad para las acciones "Suspender" (sobre
     * activos) y "Rechazar" (sobre pendientes) del panel del owner.
     */
    public function suspenderYOcultarPerfil(): void
    {
        $this->forceFill(['estado' => EstadoUsuario::Suspendido])->save();
        $this->professionalProfile?->update([
            'is_published' => false,
            'is_verified' => false,
            'verified_at' => null,
        ]);
    }

    /**
     * Elimina al usuario junto con archivos en disco y notificaciones huérfanas.
     * Reutilizado por la baja de autoservicio (ProfileController) y por la
     * acción "Eliminar cuenta" del panel del admin (UserResource).
     *
     * El borrado de notifications se hace vía el listener `deleting` de $booted:
     * no lo duplicamos aquí para no dividir la fuente de verdad. Los archivos
     * se borran DESPUÉS del delete (fuera de la transacción) para que un fallo
     * de disco no rollbackee el borrado en BD.
     */
    public function deleteConLimpieza(): void
    {
        $publicFiles = collect([
            $this->professionalProfile?->photo_path,
            $this->professionalProfile?->media_path,
            $this->companyProfile?->logo_path,
            $this->companyProfile?->media_path,
        ])->filter()->all();

        $localFiles = collect([
            $this->professionalProfile?->certification_file_path,
        ])->filter()->all();

        \Illuminate\Support\Facades\DB::transaction(fn () => $this->delete());

        foreach ($publicFiles as $path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
        }
        foreach ($localFiles as $path) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($path);
        }
    }

    public function membershipPlan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Plan::class, 'membership_plan_id');
    }

    /** ¿Tiene una membresía vigente (comparación con timestamp exacto)? */
    public function tieneMembresiaActiva(): bool
    {
        return $this->membership_expires_at !== null
            && $this->membership_expires_at->gte(now());
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

    // ============================================================
    // Fase 2 · Relaciones nuevas (pagos, ofertas, expediente, equipo)
    // ============================================================

    public function subscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Ofertas publicadas por el contratante. */
    public function offers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Offer::class, 'contractor_user_id');
    }

    /** Postulaciones del profesional. */
    public function applications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Application::class, 'professional_user_id');
    }

    /** Entradas del expediente del coach. */
    public function wellnessEntries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WellnessEntry::class, 'professional_user_id');
    }

    /** Miembros del equipo (del contratante). */
    public function teamMembers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TeamMember::class, 'contractor_user_id');
    }

    /** Membresías donde el profesional es parte del equipo de un estudio. */
    public function membershipsInTeams(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TeamMember::class, 'professional_user_id');
    }

    /** ¿Es cuenta de demostración (Fase 2)? Emails con prefijo demo.f2.* */
    public function esDemoFase2(): bool
    {
        return str_starts_with((string) $this->email, 'demo.f2.');
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

        // Contratista en PerfilPendiente: cuenta OK pero perfil aún en revisión.
        // Su siguiente paso natural es llenar el perfil de empresa.
        if ($this->esContratante() && $this->tienePerfilPendiente()) {
            return route('company.profile.edit', absolute: $absolute);
        }

        // Aprobación activada: mientras no esté activo, va al aviso de cuenta pendiente.
        if (! $this->estaActivo()) {
            return route('account.pending', absolute: $absolute);
        }

        return route('dashboard', absolute: $absolute);
    }
}
