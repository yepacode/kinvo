<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Fase 2 · Invitación de un user a una sesión. El rsvp_token único
 * se usa para el link firmado "Voy / No puedo" del correo.
 */
class SesionInvitado extends Model
{
    public const RSVP_PENDING  = 'pending';
    public const RSVP_ACCEPTED = 'accepted';
    public const RSVP_DECLINED = 'declined';

    protected $table = 'sesion_invitados';

    protected $fillable = [
        'sesion_id', 'user_id', 'invited_at', 'notified_at',
        'rsvp', 'rsvp_at', 'rsvp_token',
    ];

    protected $casts = [
        'invited_at'  => 'datetime',
        'notified_at' => 'datetime',
        'rsvp_at'     => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $i) {
            if (blank($i->rsvp_token)) {
                $i->rsvp_token = Str::random(48);
            }
            if (blank($i->invited_at)) {
                $i->invited_at = now();
            }
        });
    }

    public function sesion(): BelongsTo
    {
        return $this->belongsTo(Sesion::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
