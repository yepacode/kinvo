<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Envío SÍNCRONO (no ShouldQueue). El correo de bienvenida es corto,
 * el SMTP responde en ~1 s, y así no depende de que el cron de la cola
 * esté procesando jobs — se envía en el mismo request del registro.
 *
 * Editable desde el panel (plantilla `bienvenida_talento`). El subject sale de
 * la plantilla si está activa; si no, del fallback (fijo en la Mailable).
 */
class BienvenidaTalento extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    /** @return array<string, mixed> */
    private function templateData(): array
    {
        $nombre = $this->user->name ?: 'Coach';
        $url = url('/mi-perfil/bienvenida');

        return EmailTemplate::render('bienvenida_talento',
            ['name' => $nombre],
            [
                'subject' => __('Bienvenido a Kinvoo 🌿'),
                'greeting' => '¡Hola '.$nombre.'!',
                'body' => __('Nos alegra tenerte en Kinvoo. Completa tu perfil para que los estudios puedan encontrarte.'),
                'action_label' => __('Completar mi perfil'),
                'action_url' => $url,
                'outro' => __('Cualquier duda, aquí estamos.'),
            ]
        ) + ['action_url' => $url];
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->templateData()['subject']);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.bienvenida-talento',
            with: ['user' => $this->user, 'tpl' => $this->templateData()],
        );
    }
}
