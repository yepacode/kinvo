<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * HIGH-6 · 2ª aprobación del contratista: canal database + mail.
 * El perfil de empresa ya fue revisado y es visible en /estudio/{slug}.
 * Puede usar el buscador de talento.
 */
class PerfilEmpresaAprobadoNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
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

    public function toMail(object $notifiable): MailMessage
    {
        $nombre = $notifiable->companyProfile?->company_name ?: $notifiable->name ?: 'Equipo del estudio';
        $url = url(route('talento.index', absolute: false));

        $t = \App\Models\EmailTemplate::render('perfil_empresa_aprobado',
            ['estudio' => $nombre],
            [
                'subject'      => 'Kinvoo · Tu perfil de '.$nombre.' ya está publicado',
                'greeting'     => 'Hola '.$nombre.',',
                'body'         => 'Tu perfil de empresa acaba de aprobarse y ya está publicado en el directorio de Kinvoo. Los coaches pueden encontrarte y ya puedes empezar a buscar talento y contactarlos.',
                'action_label' => 'Buscar talento ahora',
                'action_url'   => $url,
                'outro'        => 'Cualquier duda, aquí estamos: hola@gokinvoo.com',
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
