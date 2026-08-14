<?php

namespace App\Notifications;

use App\Models\BenefitRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * HIGH-37 · Cuando el admin cancela una solicitud de respaldo, avisar al
 * coach (antes se cancelaba en silencio y el coach seguía esperando la
 * sesión agendada, presentándose a una hora que ya no existía).
 */
class RespaldoCanceladoNotification extends Notification
{
    public function __construct(public BenefitRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    private function tipoLabel(): string
    {
        return $this->request->type === BenefitRequest::TYPE_PHYSIO
            ? 'Fisioterapia'
            : 'Telemedicina';
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'respaldo_cancelado',
            'icono' => '⚠️',
            'benefit_request_id' => $this->request->id,
            'titulo' => 'Tu sesión de '.$this->tipoLabel().' fue cancelada',
            'mensaje' => 'Escríbenos si quieres reagendar. No te presentes a la hora original — la sesión ya no está en pie.',
            'url' => route('respaldo.index', absolute: false),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $coach = $notifiable->name ?: 'Coach';
        $tipo  = $this->tipoLabel();
        $url   = url(route('respaldo.index', absolute: false));

        $t = \App\Models\EmailTemplate::render('respaldo_cancelado',
            ['coach' => $coach, 'tipo' => $tipo],
            [
                'subject'      => 'Kinvoo · Tu sesión de '.$tipo.' fue cancelada',
                'greeting'     => 'Hola '.$coach.',',
                'body'         => 'Tu sesión de **'.$tipo.'** fue cancelada por el equipo de Kinvoo.'."\n\n".'**Importante:** no te presentes a la hora original — la sesión ya no está en pie. Puedes solicitar una nueva desde tu panel o escribirnos a hola@gokinvoo.com para reagendar.',
                'action_label' => 'Ir a mi respaldo',
                'action_url'   => $url,
                'outro'        => 'Lamentamos las molestias.',
            ]
        );
        $t['action_url'] = $url;

        $mail = (new MailMessage)->subject($t['subject']);
        if ($t['greeting']) $mail->greeting($t['greeting']);
        foreach (explode("\n\n", $t['body']) as $par) {
            $par = trim($par);
            if ($par !== '') $mail->line($par);
        }
        if ($t['action_label']) $mail->action($t['action_label'], $t['action_url']);
        if ($t['outro']) $mail->line($t['outro']);
        return $mail;
    }
}
