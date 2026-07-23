<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * 1ª aprobación del contratista: la cuenta queda activa para login pero
 * el perfil de empresa aún no se publica hasta la 2ª aprobación.
 * Se envía por la campana; el correo lo puede complementar Kinvoo manualmente.
 */
class CuentaAprobadaContratanteNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        // Guardamos CLAVES + params para que la campana traduzca al idioma
        // activo del receptor en cada render.
        return [
            'tipo' => 'cuenta',
            'icono' => '✅',
            'titulo_key' => '¡Tu cuenta fue aprobada!',
            'titulo_params' => [],
            'mensaje_key' => 'Ahora llena tu perfil de empresa. Cuando lo envíes, nuestro equipo lo revisará por segunda vez y quedará publicado en un máximo de 24 horas.',
            'mensaje_params' => [],
            'titulo' => '¡Tu cuenta fue aprobada!',
            'mensaje' => 'Ahora llena tu perfil de empresa. Cuando lo envíes, nuestro equipo lo revisará por segunda vez y quedará publicado en un máximo de 24 horas.',
            'url' => route('company.profile.edit', absolute: false),
        ];
    }
}
