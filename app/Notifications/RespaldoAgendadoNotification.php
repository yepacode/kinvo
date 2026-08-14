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

        return (new MailMessage)
            ->subject('Kinvoo · Tu sesión de '.$tipo.' está agendada')
            ->greeting('Hola '.($notifiable->name ?: 'Coach').',')
            ->line('Tu solicitud de **'.$tipo.'** ya fue agendada por Kinvoo.')
            ->line('**Cuándo:** '.$cuando)
            ->when($this->request->admin_note,
                fn ($m) => $m->line('**Nota Kinvoo:** '.$this->request->admin_note))
            ->action('Ver detalle', url(route('respaldo.index', absolute: false)))
            ->line('Si necesitas reprogramar, respóndenos a este correo.');
    }
}
