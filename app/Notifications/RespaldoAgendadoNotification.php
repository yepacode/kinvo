<?php

namespace App\Notifications;

use App\Models\BenefitRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * M6 · Aviso al coach cuando el admin agenda su sesión de Respaldo.
 */
class RespaldoAgendadoNotification extends Notification
{
    public function __construct(public BenefitRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        $tipo = $this->request->type === BenefitRequest::TYPE_PHYSIO
            ? 'Fisioterapia' : 'Telemedicina';
        return [
            'tipo'    => 'benefit_scheduled',
            'icono'   => $this->request->type === BenefitRequest::TYPE_PHYSIO ? '💪' : '🩺',
            'titulo'  => $tipo.' agendada',
            'mensaje' => $this->request->scheduled_for?->translatedFormat('d M Y H:i') ?? '',
            'url'     => route('respaldo.index', absolute: false),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tipo = $this->request->type === BenefitRequest::TYPE_PHYSIO
            ? 'Fisioterapia' : 'Telemedicina';
        $cuando = $this->request->scheduled_for?->translatedFormat('l d M Y · H:i') ?? '';
        $coach = $notifiable->name ?: 'Coach';
        $nota = $this->request->admin_note ?: '';
        $url = url(route('respaldo.index', absolute: false));

        // Editable desde el panel (plantilla respaldo_agendado_coach).
        $t = \App\Models\EmailTemplate::render('respaldo_agendado_coach',
            ['coach' => $coach, 'tipo' => $tipo, 'cuando' => $cuando, 'admin_note' => $nota],
            [
                'subject' => 'Kinvoo · Tu sesión de '.$tipo.' está agendada',
                'greeting' => 'Hola '.$coach.',',
                'body' => 'Tu solicitud de **'.$tipo.'** ya fue agendada por Kinvoo.'."\n\n".'**Cuándo:** '.$cuando
                    .($nota ? "\n\n".'**Nota Kinvoo:** '.$nota : ''),
                'action_label' => 'Ver detalle',
                'action_url' => $url,
                'outro' => 'Si necesitas reprogramar, respóndenos a este correo.',
            ]
        );
        $t['action_url'] = $url;

        return \App\Models\EmailTemplate::toMailMessage($t);
    }
}
