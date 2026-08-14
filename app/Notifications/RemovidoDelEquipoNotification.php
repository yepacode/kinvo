<?php

namespace App\Notifications;

use App\Models\TeamMember;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * CRITICAL-7: Cuando el estudio remueve a un coach de su equipo, el coach
 * NO recibía ningún aviso — el requisito explícito del cliente ("preferible
 * que le llegue correo") sí estaba cubierto para invitación/aceptación/
 * rechazo pero faltaba para remoción, dejando al coach en el limbo.
 * Canal database + mail, síncrono (Hostinger compartido, sin queue).
 */
class RemovidoDelEquipoNotification extends Notification
{
    public function __construct(public TeamMember $teamMember) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    private function estudio(): string
    {
        $this->teamMember->loadMissing('contractor.companyProfile');
        return $this->teamMember->contractor?->companyProfile?->company_name
            ?? $this->teamMember->contractor?->name
            ?? 'Un estudio';
    }

    public function toArray(object $notifiable): array
    {
        $estudio = $this->estudio();
        return [
            'tipo' => 'removido_equipo',
            'icono' => '👋',
            'team_member_id' => $this->teamMember->id,
            'titulo_key' => 'Ya no formas parte de un equipo',
            'titulo_params' => [],
            'mensaje_key' => ':estudio te retiró de su equipo. Tu perfil sigue activo y puedes seguir aplicando a otras oportunidades.',
            'mensaje_params' => ['estudio' => $estudio],
            'titulo' => 'Ya no formas parte de un equipo',
            'mensaje' => "$estudio te retiró de su equipo.",
            'url' => route('notifications.index', absolute: false),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $estudio = $this->estudio();
        $coach = $notifiable->name ?: 'Coach';
        $url = url(route('notifications.index', absolute: false));

        $t = \App\Models\EmailTemplate::render('removido_equipo',
            ['coach' => $coach, 'estudio' => $estudio],
            [
                'subject' => 'Kinvoo · Ya no formas parte del equipo de '.$estudio,
                'greeting' => 'Hola '.$coach.',',
                'body' => '**'.$estudio.'** te retiró de su equipo en Kinvoo.'."\n\n".
                    'Tu perfil sigue activo y puedes seguir aplicando a otras oportunidades. Los beneficios del estudio (telemedicina, fisio, contenido) dejan de acumularse desde hoy, pero conservas todo lo que ya recibiste.',
                'action_label' => 'Ver mis notificaciones',
                'action_url' => $url,
                'outro' => 'Si crees que fue un error, ponte en contacto con el estudio directamente.',
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
