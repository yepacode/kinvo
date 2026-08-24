<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Editable desde el panel (plantilla `aviso_cobro_exitoso`). */
class AvisoCobroExitoso extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public ?Subscription $subscription) {}

    private function templateData(): array
    {
        $nombre = $this->user->name ?: 'Cliente';
        $plan = $this->subscription?->plan?->nombre ?? 'tu membresía';
        $url = url('/membresias');

        return EmailTemplate::render('aviso_cobro_exitoso',
            ['nombre' => $nombre, 'plan' => $plan],
            [
                'subject' => __('Recibimos tu pago — Kinvoo'),
                'greeting' => 'Hola '.$nombre.',',
                'body' => 'Recibimos correctamente el cobro de **'.$plan.'**. Tu membresía queda activa. ¡Gracias por seguir siendo parte de Kinvoo!',
                'action_label' => __('Ver mi membresía'),
                'action_url' => $url,
                'outro' => __('Guarda este correo como comprobante.'),
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
