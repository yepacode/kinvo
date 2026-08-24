<?php

namespace App\Mail;

use App\Models\Contact;
use App\Models\EmailTemplate;
use App\Models\ProfessionalProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Correo al owner cuando un estudio contacta a un coach. Editable
 * (plantilla `nuevo_contacto`).
 */
class NuevoContacto extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Contact $contact,
        public ProfessionalProfile $profile,
    ) {}

    private function templateData(): array
    {
        // Nombre viene del formulario público → limpio CRLF y trunco (evita header injection).
        $nombre = trim(preg_replace("/[\r\n]+/", ' ', (string) $this->contact->contact_name));
        $nombre = mb_strimwidth($nombre, 0, 80, '…', 'UTF-8');
        $profesional = $this->profile->user?->name ?? 'un profesional';
        $mensaje = mb_strimwidth((string) $this->contact->message, 0, 500, '…', 'UTF-8');
        $url = url(route('filament.admin.resources.contacts.index', absolute: false));

        return EmailTemplate::render('nuevo_contacto',
            ['estudio' => $nombre, 'profesional' => $profesional, 'mensaje' => $mensaje],
            [
                'subject' => __('Nuevo contacto en Kinvoo — :nombre', ['nombre' => $nombre]),
                'greeting' => __('Hola equipo Kinvoo,'),
                'body' => '**'.$nombre.'** contactó a **'.$profesional.'** en Kinvoo.'."\n\n".'Mensaje:'."\n\n".'> '.$mensaje,
                'action_label' => __('Ver en el panel'),
                'action_url' => $url,
                'outro' => __('Revísalo cuanto antes para dar seguimiento.'),
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
