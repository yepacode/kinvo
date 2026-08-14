<?php

namespace App\Notifications;

use App\Models\PulseResponse;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * HIGH-12 · Al estudio cuando alguien de su equipo contesta el Pulso.
 * SIN detalle del respondedor — la encuesta es anónima. Solo notificamos
 * "hay una nueva respuesta esta semana" para que el estudio vuelva al
 * panel de Pulso y revise el agregado. Canal database + mail (opt-out
 * futuro si el estudio recibe muchas).
 */
class PulseRespondidoNotification extends Notification
{
    public function __construct(public PulseResponse $response) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'pulso_respondido',
            'icono' => '💚',
            'titulo' => 'Nueva respuesta al Pulso Kinvoo',
            'mensaje' => 'Alguien de tu equipo contestó la encuesta esta semana. Revisa el agregado.',
            'url' => route('pulso.estudio', absolute: false),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('pulso.estudio', absolute: false));

        $t = \App\Models\EmailTemplate::render('pulso_respondido',
            [],
            [
                'subject'      => 'Kinvoo · Nueva respuesta al Pulso de tu equipo',
                'greeting'     => 'Hola,',
                'body'         => 'Alguien de tu equipo contestó la encuesta de Pulso Kinvoo esta semana.'."\n\n".'Las respuestas son anónimas — no vas a saber quién contestó, pero sí puedes ver el score general y el comentario destacado. Es la mejor forma de tomar el pulso al bienestar de tu equipo.',
                'action_label' => 'Ver Pulso Kinvoo',
                'action_url'   => $url,
                'outro'        => 'Tip: revisa el Pulso una vez a la semana para captar tendencias.',
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
