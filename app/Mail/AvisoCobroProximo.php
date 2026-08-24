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

/** 7 días antes del vencimiento. Editable (plantilla `aviso_cobro_proximo`). */
class AvisoCobroProximo extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public Subscription $subscription) {}

    private function templateData(): array
    {
        $nombre = $this->user->name ?: 'Cliente';
        $plan = $this->subscription->plan?->nombre ?? 'tu membresía';
        $fecha = $this->subscription->current_period_end?->translatedFormat('d M Y') ?? '';
        $url = url('/membresias');

        return EmailTemplate::render('aviso_cobro_proximo',
            ['nombre' => $nombre, 'plan' => $plan, 'fecha' => $fecha],
            [
                'subject' => __('Tu renovación de Kinvoo se acerca'),
                'greeting' => 'Hola '.$nombre.',',
                'body' => 'La renovación de **'.$plan.'** se hará el **'.$fecha.'**. Verifica que tu método de pago esté vigente para que no se interrumpa tu acceso.',
                'action_label' => __('Ver mi membresía'),
                'action_url' => $url,
                'outro' => __('¿No quieres renovar? Puedes cancelar desde tu panel.'),
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
