<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * HIGH-6 · Rechazo de solicitud de registro: canal database + mail.
 * Antes solo campana con la política "el owner comunica por su canal
 * habitual". Ese paso manual se olvidaba (cliente lo reportó). Ahora el
 * user recibe correo con el motivo genérico y la vía de contacto directo.
 */
class CuentaRechazadaNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
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

    public function toMail(object $notifiable): MailMessage
    {
        $nombre = $notifiable->name ?: '';

        $t = \App\Models\EmailTemplate::render('cuenta_rechazada',
            ['nombre' => $nombre],
            [
                'subject'      => 'Kinvoo · Sobre tu solicitud de registro',
                'greeting'     => $nombre ? ('Hola '.$nombre.',') : 'Hola,',
                'body'         => 'Después de revisar tu solicitud, no pudimos aprobarla en este momento.'."\n\n".'Si crees que fue un error o quieres conocer los pasos para reintentarlo, respóndenos a este correo o escríbenos a hola@gokinvoo.com. Con gusto te acompañamos.',
                'action_label' => null,
                'action_url'   => null,
                'outro'        => 'Gracias por tu interés en Kinvoo.',
            ]
        );

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
