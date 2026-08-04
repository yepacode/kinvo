<?php

namespace App\Mail;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Se dispara 7 días antes del vencimiento. */
class AvisoCobroProximo extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public Subscription $subscription) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Tu renovación de Kinvoo se acerca'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.cobro-proximo',
            with: ['user' => $this->user, 'subscription' => $this->subscription],
        );
    }
}
