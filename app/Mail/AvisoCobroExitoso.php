<?php

namespace App\Mail;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Envío SÍNCRONO (mismo patrón que Fase 1, Hostinger compartido no tiene queue).
 */
class AvisoCobroExitoso extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public ?Subscription $subscription) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Recibimos tu pago — Kinvoo'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.cobro-exitoso',
            with: ['user' => $this->user, 'subscription' => $this->subscription],
        );
    }
}
