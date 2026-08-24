<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Editable (plantilla `aviso_vencimiento_membresia`). */
class AvisoVencimientoMembresia extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    private function templateData(): array
    {
        $nombre = $this->user->name ?: 'Cliente';
        $url = url('/membresias');

        return EmailTemplate::render('aviso_vencimiento_membresia',
            ['nombre' => $nombre],
            [
                'subject' => __('Tu membresía Kinvoo venció'),
                'greeting' => 'Hola '.$nombre.',',
                'body' => 'Tu membresía en Kinvoo venció. Los beneficios de tu plan quedan pausados hasta que renueves.'."\n\n".'Puedes renovar en un clic desde tu panel.',
                'action_label' => __('Renovar mi membresía'),
                'action_url' => $url,
                'outro' => __('Tu perfil y datos siguen intactos — puedes retomar cuando quieras.'),
            ]
        ) + ['action_url' => $url];
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->templateData()['subject']);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.tpl-generic', with: ['tpl' => $this->templateData()]);
    }
}
