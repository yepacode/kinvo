<?php

namespace App\Mail;

use App\Models\Contact;
use App\Models\ProfessionalProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Envío SÍNCRONO (no ShouldQueue). En Hostinger compartido la cola
 * depende de un cron + queue:work --stop-when-empty, que a veces falla
 * o no está bien configurado. Enviamos directo en el request para que
 * el correo llegue seguro; SMTP responde en ~1-2 s.
 */
class NuevoContacto extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Contact $contact,
        public ProfessionalProfile $profile,
    ) {}

    public function envelope(): Envelope
    {
        // El nombre viene del formulario público → limpiamos CRLF y truncamos
        // para evitar inyección de cabeceras y asuntos gigantes.
        $nombre = trim(preg_replace("/[\r\n]+/", ' ', (string) $this->contact->contact_name));
        $nombre = mb_strimwidth($nombre, 0, 80, '…', 'UTF-8');

        return new Envelope(
            subject: __('Nuevo contacto en Kinvoo — :nombre', ['nombre' => $nombre]),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.nuevo-contacto');
    }
}
