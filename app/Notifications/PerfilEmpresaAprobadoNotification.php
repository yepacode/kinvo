<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * 2ª aprobación del contratista: el perfil de empresa ya fue revisado
 * y es visible en /estudio/{slug}. Puede usar el buscador de talento.
 */
class PerfilEmpresaAprobadoNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'cuenta',
            'icono' => '🎉',
            'titulo_key' => '¡Tu perfil de empresa fue aprobado!',
            'titulo_params' => [],
            'mensaje_key' => 'Ya está publicado en el directorio. Puedes empezar a buscar talento en Kinvoo.',
            'mensaje_params' => [],
            'titulo' => '¡Tu perfil de empresa fue aprobado!',
            'mensaje' => 'Ya está publicado en el directorio. Puedes empezar a buscar talento en Kinvoo.',
            'url' => route('talento.index', absolute: false),
        ];
    }
}
