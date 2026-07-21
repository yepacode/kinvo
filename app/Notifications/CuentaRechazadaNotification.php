<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Se dispara cuando el owner rechaza una solicitud de registro. Se guarda
 * en la campanita del usuario para que sepa por qué no puede entrar a
 * la plataforma. Sin canal `mail` a propósito: el rechazo es una acción
 * delicada que el owner suele comunicar por su canal habitual.
 */
class CuentaRechazadaNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'cuenta_rechazada',
            'icono' => '🚫',
            'titulo' => 'Tu solicitud no fue aprobada',
            'mensaje' => 'Escríbenos a hola@gokinvoo.com si crees que fue un error o para conocer los siguientes pasos.',
            'url' => null,
        ];
    }
}
