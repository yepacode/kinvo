<?php

namespace App\Mail;

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
 */
class BienvenidaEstudio extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Bienvenido a Kinvoo 🌿'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.bienvenida-estudio',
            with: ['user' => $this->user],
        );
    }
}
