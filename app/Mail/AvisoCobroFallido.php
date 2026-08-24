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

/** Editable desde el panel (plantilla `aviso_cobro_fallido`). */
class AvisoCobroFallido extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public ?Subscription $subscription) {}

    private function templateData(): array
    {
        $nombre = $this->user->name ?: 'Cliente';
        $plan = $this->subscription?->plan?->nombre ?? 'tu membresía';
        $url = url('/membresias');

        return EmailTemplate::render('aviso_cobro_fallido',
            ['nombre' => $nombre, 'plan' => $plan],
            [
                'subject' => __('Tu cobro no se pudo completar — Kinvoo'),
                'greeting' => 'Hola '.$nombre.',',
                'body' => 'No pudimos procesar el cobro de **'.$plan.'**. Revisa tu método de pago desde tu panel; volveremos a intentarlo pronto.',
                'action_label' => __('Actualizar mi pago'),
                'action_url' => $url,
                'outro' => __('Si necesitas ayuda, escríbenos.'),
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
