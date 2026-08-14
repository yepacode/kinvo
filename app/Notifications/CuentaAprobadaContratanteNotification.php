<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * HIGH-6 · 1ª aprobación del contratista: canal database + mail.
 * La cuenta queda activa para login pero el perfil de empresa aún no se
 * publica hasta la 2ª aprobación.
 */
class CuentaAprobadaContratanteNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
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

    public function toMail(object $notifiable): MailMessage
    {
        $nombre = $notifiable->name ?: 'Equipo del estudio';
        $url = url(route('company.profile.edit', absolute: false));

        $t = \App\Models\EmailTemplate::render('cuenta_aprobada_contratante',
            ['estudio' => $nombre],
            [
                'subject'      => 'Kinvoo · ¡Tu cuenta de estudio fue aprobada!',
                'greeting'     => 'Hola '.$nombre.',',
                'body'         => 'Tu cuenta ya está activa en Kinvoo. El siguiente paso es completar el perfil de tu estudio (nombre, ubicación, disciplinas, contacto). Cuando lo envíes, el equipo de Kinvoo lo revisará por segunda vez y quedará publicado en un máximo de 24 horas.',
                'action_label' => 'Completar perfil de empresa',
                'action_url'   => $url,
                'outro'        => 'Este segundo paso es rápido y nos asegura que los coaches confíen en tu marca desde el primer día.',
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
