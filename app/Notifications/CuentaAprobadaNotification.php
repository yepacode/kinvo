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
        return [
            'tipo' => 'cuenta',
            'icono' => '✅',
            'titulo' => '¡Tu cuenta fue aprobada!',
            'mensaje' => 'Ya puedes completar y publicar tu perfil en Kinvoo.',
            'url' => $notifiable->homeRoute(),
        ];
    }
}
