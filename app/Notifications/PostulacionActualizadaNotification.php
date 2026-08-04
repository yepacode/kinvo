<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Notifications\Notification;

/**
 * Fase 2 · Al profesional cuando el estudio cambia el estado de su
 * postulación (seen/in_contact/accepted/rejected).
 */
class PostulacionActualizadaNotification extends Notification
{
    public function __construct(public Application $application) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $labels = [
            Application::STATUS_SEEN       => 'El estudio vio tu postulación',
            Application::STATUS_IN_CONTACT => 'El estudio quiere contactarte',
            Application::STATUS_ACCEPTED   => 'Postulación aceptada',
            Application::STATUS_REJECTED   => 'Postulación rechazada',
        ];
        $titulo = $labels[$this->application->status] ?? 'Postulación actualizada';

        $this->application->loadMissing('offer');
        $oferta = $this->application->offer?->title ?: 'tu postulación';

        return [
            'tipo' => 'postulacion_actualizada',
            'icono' => match ($this->application->status) {
                Application::STATUS_ACCEPTED => '🎉',
                Application::STATUS_REJECTED => '📭',
                default => '👀',
            },
            'titulo_key' => $titulo,
            'titulo_params' => [],
            'mensaje_key' => 'Oferta: :oferta',
            'mensaje_params' => ['oferta' => $oferta],
            'titulo' => $titulo,
            'mensaje' => "Oferta: $oferta",
            'url' => route('ofertas.mis-postulaciones', absolute: false),
        ];
    }
}
