<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AvisoVencimientoMembresia extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Tu membresía Kinvoo venció'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.vencimiento-membresia',
            with: ['user' => $this->user],
        );
    }
}
