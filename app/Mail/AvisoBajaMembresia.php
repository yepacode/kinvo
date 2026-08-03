<?php

namespace App\Mail;

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

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Confirmación de baja — Kinvoo'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.baja-membresia',
            with: ['user' => $this->user],
        );
    }
}
