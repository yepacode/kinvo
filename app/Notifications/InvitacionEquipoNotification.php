<?php

namespace App\Notifications;

use App\Models\TeamMember;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Fase 2 · Al profesional cuando un estudio lo invita a su equipo.
 * Petición cliente (docx PRUEBA KINVOO): "preferible que le llegue correo".
 * Envío síncrono (sin ShouldQueue) para Hostinger compartido.
 */
class InvitacionEquipoNotification extends Notification
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
            'tipo' => 'invitacion_equipo',
            'icono' => '🤝',
            'team_member_id' => $this->teamMember->id,
            'titulo_key' => 'Te invitaron a un equipo',
            'titulo_params' => [],
            'mensaje_key' => ':estudio quiere sumarte a su equipo. Acéptalo o recházalo desde tus notificaciones.',
            'mensaje_params' => ['estudio' => $estudio],
            'titulo' => 'Te invitaron a un equipo',
            'mensaje' => "$estudio quiere sumarte a su equipo.",
            'url' => route('notifications.index', absolute: false),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $estudio = $this->estudio();
        $coach = $notifiable->name ?: 'Coach';
        $url = url(route('notifications.index', absolute: false));

        $t = \App\Models\EmailTemplate::render('invitacion_equipo',
            ['coach' => $coach, 'estudio' => $estudio],
            [
                'subject' => 'Kinvoo · '.$estudio.' te invita a su equipo',
                'greeting' => 'Hola '.$coach.',',
                'body' => '**'.$estudio.'** quiere sumarte a su equipo en Kinvoo.'."\n\n".
                    'Al aceptar, su cuidado empieza a sumar en tu perfil (telemedicina, fisio, contenido, etc. según el plan del estudio).',
                'action_label' => 'Ver invitación',
                'action_url' => $url,
                'outro' => 'Si no fue una invitación esperada, simplemente recházala desde la campanita.',
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
