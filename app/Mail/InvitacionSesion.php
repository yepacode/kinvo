<?php

namespace App\Mail;

use App\Models\Sesion;
use App\Models\SesionInvitado;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Fase 2 · Correo de invitación a una sesión en vivo.
 * Síncrono (Hostinger no tiene worker). El asunto y cuerpo usan
 * override de Sesion si existen; si no, la plantilla por default.
 * Los links "Voy / No puedo" apuntan a /rsvp/{token} firmado por invitado.
 */
class InvitacionSesion extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Sesion $sesion,
        public SesionInvitado $invitado,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->sesion->asuntoCorreo());
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invitacion-sesion',
            with: [
                'nombre'    => $this->user->name,
                'title'     => $this->sesion->title,
                'fecha'     => $this->sesion->scheduled_at?->translatedFormat('l d M Y · H:i'),
                'link'      => $this->sesion->link,
                'body'      => $this->sesion->body_override, // puede ser null → default
                'goingUrl'  => route('rsvp.responder', ['token' => $this->invitado->rsvp_token, 'r' => 'accepted']),
                'declineUrl'=> route('rsvp.responder', ['token' => $this->invitado->rsvp_token, 'r' => 'declined']),
            ],
        );
    }
}
