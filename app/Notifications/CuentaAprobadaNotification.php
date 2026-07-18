<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class CuentaAprobadaNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        // Guardamos CLAVES en vez de strings, para que la campanita y /notificaciones
        // los traduzcan al idioma activo del receptor cada vez que se rendericen
        // (evita que EN vea "¡Tu cuenta fue aprobada!" y ES vea inglés).
        return [
            'tipo' => 'cuenta',
            'icono' => '✅',
            'titulo_key' => '¡Tu cuenta fue aprobada!',
            'titulo_params' => [],
            'mensaje_key' => 'Ya puedes completar y publicar tu perfil en Kinvoo.',
            'mensaje_params' => [],
            // Fallbacks para notificaciones creadas antes de este refactor:
            'titulo' => '¡Tu cuenta fue aprobada!',
            'mensaje' => 'Ya puedes completar y publicar tu perfil en Kinvoo.',
            'url' => $notifiable->homeRoute(absolute: false),
        ];
    }
}
