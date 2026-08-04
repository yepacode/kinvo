<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Fase 2 · Sesión en vivo (webinar/taller/consulta) que Kinvoo agenda
 * y a la que invita coaches y estudios por correo desde el admin.
 */
class Sesion extends Model
{
    public const AUDIENCE_ALL          = 'all';
    public const AUDIENCE_PROFESSIONAL = 'professional';
    public const AUDIENCE_CONTRACTOR   = 'contractor';

    public const TIPO_WEBINAR  = 'webinar';
    public const TIPO_TALLER   = 'taller';
    public const TIPO_CONSULTA = 'consulta';
    public const TIPO_OTRO     = 'otro';

    protected $table = 'sesiones';

    protected $fillable = [
        'title', 'description', 'tipo', 'scheduled_at', 'duration_min',
        'link', 'audience', 'subject_override', 'body_override',
        'notified_at', 'created_by_admin_id',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'notified_at'  => 'datetime',
        'duration_min' => 'integer',
    ];

    public function invitados(): HasMany
    {
        return $this->hasMany(SesionInvitado::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }

    /**
     * Users que cumplen la audiencia (para "invitar a toda la audiencia").
     * El campo real es `nivel` con enum RolUsuario (Professional=1, Contractor=2, Admin=3).
     */
    public function usuariosDeAudiencia()
    {
        return match ($this->audience) {
            self::AUDIENCE_PROFESSIONAL => User::where('nivel', \App\Enums\RolUsuario::Professional->value),
            self::AUDIENCE_CONTRACTOR   => User::where('nivel', \App\Enums\RolUsuario::Contractor->value),
            default                     => User::whereIn('nivel', [
                \App\Enums\RolUsuario::Professional->value,
                \App\Enums\RolUsuario::Contractor->value,
            ]),
        };
    }

    /** Asunto del correo: usa override si existe, si no el default. */
    public function asuntoCorreo(): string
    {
        return $this->subject_override
            ?: "Invitación: {$this->title} · Kinvoo";
    }
}
