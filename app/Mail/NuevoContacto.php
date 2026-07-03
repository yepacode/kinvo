<?php

namespace App\Mail;

use App\Models\Contact;
use App\Models\ProfessionalProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NuevoContacto extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Contact $contact,
        public ProfessionalProfile $profile,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nuevo contacto en Kinvoo — '.$this->contact->contact_name,
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.nuevo-contacto');
    }
}
