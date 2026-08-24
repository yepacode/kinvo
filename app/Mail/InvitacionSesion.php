<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\Sesion;
use App\Models\SesionInvitado;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Fase 2 · Correo de invitación a una sesión en vivo. Editable
 * (plantilla `invitacion_sesion`). El override que la Sesion tiene en
 * body_override / asuntoCorreo() sigue teniendo prioridad sobre la plantilla:
 * el override es por-sesión, la plantilla es el default global.
 */
class InvitacionSesion extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Sesion $sesion,
        public SesionInvitado $invitado,
    ) {}

    private function templateData(): array
    {
        $nombre = $this->user->name ?: 'Coach';
        $titulo = $this->sesion->title;
        $fecha = $this->sesion->scheduled_at?->translatedFormat('l d M Y · H:i') ?? '';

        // Fallback (usa el override del admin si lo hay).
        $bodyFallback = $this->sesion->body_override
            ?: 'Te invitamos a nuestra próxima sesión en Kinvoo:'."\n\n".'**'.$titulo.'**'."\n".'🗓 '.$fecha;

        $tpl = EmailTemplate::render('invitacion_sesion',
            ['nombre' => $nombre, 'titulo' => $titulo, 'fecha' => $fecha],
            [
                'subject' => $this->sesion->asuntoCorreo(),
                'greeting' => '¡Hola '.$nombre.'!',
                'body' => $bodyFallback,
                'action_label' => null,
                'action_url' => null,
                'outro' => '¿Podrás asistir? Confírmanos para reservarte el cupo:',
            ]
        );

        // Prioridad: override por sesión > plantilla > fallback.
        if ($this->sesion->body_override) {
            $tpl['body'] = $this->sesion->body_override;
        }

        return $tpl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->sesion->asuntoCorreo() ?: $this->templateData()['subject']);
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
                'body'      => $this->sesion->body_override,
                'tpl'       => $this->templateData(),
                'goingUrl'  => route('rsvp.responder', ['token' => $this->invitado->rsvp_token, 'r' => 'accepted']),
                'declineUrl'=> route('rsvp.responder', ['token' => $this->invitado->rsvp_token, 'r' => 'declined']),
            ],
        );
    }
}
