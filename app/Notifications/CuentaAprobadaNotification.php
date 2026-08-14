<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * HIGH-6 · Aprobación de cuenta (profesional): canal database + mail.
 * Antes solo campana → si el user no volvía a loguear, nunca sabía que
 * fue aprobado. Ahora recibe correo con el link para completar el perfil.
 */
class CuentaAprobadaNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
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
            'titulo' => '¡Tu cuenta fue aprobada!',
            'mensaje' => 'Ya puedes completar y publicar tu perfil en Kinvoo.',
            'url' => $notifiable->homeRoute(absolute: false),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $nombre = $notifiable->name ?: 'Coach';
        $url = url($notifiable->homeRoute(absolute: false));

        $t = \App\Models\EmailTemplate::render('cuenta_aprobada',
            ['coach' => $nombre],
            [
                'subject'      => 'Kinvoo · ¡Tu cuenta fue aprobada!',
                'greeting'     => 'Hola '.$nombre.',',
                'body'         => 'Tu cuenta ya está activa en Kinvoo. Ya puedes completar tu perfil, subir tu foto y publicarlo para que los estudios te encuentren.',
                'action_label' => 'Ir a mi panel',
                'action_url'   => $url,
                'outro'        => '¡Bienvenido a la red profesional del fitness!',
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
