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
 * Envío SÍNCRONO al registrarse un estudio. Editable desde el panel
 * (plantilla `bienvenida_estudio`).
 */
class BienvenidaEstudio extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    private function templateData(): array
    {
        $nombre = $this->user->name ?: 'Estudio';
        $url = url('/mi-empresa/bienvenida');

        return EmailTemplate::render('bienvenida_estudio',
            ['name' => $nombre],
            [
                'subject' => __('Bienvenido a Kinvoo 🌿'),
                'greeting' => '¡Hola '.$nombre.'!',
                'body' => __('Gracias por elegir Kinvoo. Completa el perfil de tu estudio para empezar a buscar talento y publicar oportunidades.'),
                'action_label' => __('Completar perfil de mi estudio'),
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
            markdown: 'emails.bienvenida-estudio',
            with: ['user' => $this->user, 'tpl' => $this->templateData()],
        );
    }
}
