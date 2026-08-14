<?php

namespace App\Notifications;

use App\Models\WallPost;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * HIGH-33 · Cuando el admin rechaza un momento del Wall, se avisa al
 * estudio autor (campana + correo) con el motivo. Sin este aviso el
 * estudio no entendía por qué su post desapareció del feed.
 */
class MomentoRechazadoNotification extends Notification
{
    public function __construct(public WallPost $post) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'momento_rechazado',
            'icono' => '🚫',
            'wall_post_id' => $this->post->id,
            'titulo' => 'Tu momento no se publicó',
            'mensaje' => $this->post->moderation_reason
                ? Str::limit($this->post->moderation_reason, 140)
                : 'El equipo de Kinvoo revisó el momento y no pudo aprobarlo.',
            'url' => route('wall.mis-momentos', absolute: false),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('wall.mis-momentos', absolute: false));
        $motivo = $this->post->moderation_reason ?: 'No se especificó un motivo — escríbenos a hola@gokinvoo.com si quieres conocer los detalles.';

        $t = \App\Models\EmailTemplate::render('momento_rechazado',
            ['motivo' => $motivo],
            [
                'subject'      => 'Kinvoo · Tu momento no se publicó',
                'greeting'     => 'Hola,',
                'body'         => 'El equipo de Kinvoo revisó tu momento en el Wall y no pudo aprobarlo.'."\n\n".'**Motivo:** '.$motivo,
                'action_label' => 'Ver mis momentos',
                'action_url'   => $url,
                'outro'        => 'Puedes subir uno nuevo cuando quieras. Estamos aquí para lo que necesites.',
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
