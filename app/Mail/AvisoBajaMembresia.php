<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AvisoBajaMembresia extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    private function tpl(): array
    {
        return EmailTemplate::render('aviso_baja_membresia', [
            'nombre' => $this->user->name,
        ], [
            'subject'      => __('Confirmación de baja — Kinvoo'),
            'greeting'     => __('Hola :name,', ['name' => $this->user->name]),
            'body'         => __('Recibimos tu solicitud de baja. Tu acceso a Kinvoo se mantiene hasta el final del periodo que ya pagaste.')
                ."\n\n".__('Si fue un error o quieres volver, puedes reactivar tu suscripción cuando gustes:'),
            'action_label' => __('Reactivar mi suscripción'),
            'action_url'   => url('/membresias'),
            'outro'        => __('Gracias por haber sido parte de Kinvoo.'),
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->tpl()['subject']);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tpl-generic',
            with: ['t' => $this->tpl()],
        );
    }
}
