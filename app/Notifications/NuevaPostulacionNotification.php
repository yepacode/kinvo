<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Fase 2 · Al estudio cuando un profesional postula a su oferta.
 * Solo canal database (campana). El correo lo maneja el flujo aparte.
 */
class NuevaPostulacionNotification extends Notification
{
    public function __construct(public Application $application) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->application->loadMissing(['professional', 'offer']);
        $nombre = $this->application->professional?->name ?: 'Un profesional';
        $oferta = $this->application->offer?->title ?: 'tu oferta';

        return [
            'tipo' => 'nueva_postulacion',
            'icono' => '📥',
            'titulo_key' => 'Nueva postulación recibida',
            'titulo_params' => [],
            'mensaje_key' => ':nombre postuló a ":oferta"',
            'mensaje_params' => ['nombre' => $nombre, 'oferta' => Str::limit($oferta, 40)],
            'titulo' => 'Nueva postulación recibida',
            'mensaje' => "$nombre postuló a \"".Str::limit($oferta, 40).'"',
            'url' => route('ofertas.mis-ofertas', absolute: false),
        ];
    }
}
