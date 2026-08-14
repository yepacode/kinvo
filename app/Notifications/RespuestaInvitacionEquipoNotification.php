<?php

namespace App\Notifications;

use App\Models\TeamMember;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Al estudio cuando el coach ACEPTA o RECHAZA una invitación al equipo.
 * Petición cliente (docx PRUEBA KINVOO): "(correo de aceptación/rechazo)".
 * Envío síncrono — mismo patrón que las demás notifs de Fase 2.
 */
class RespuestaInvitacionEquipoNotification extends Notification
{
    public const ACEPTADA  = 'aceptada';
    public const RECHAZADA = 'rechazada';

    public function __construct(public TeamMember $teamMember, public string $accion) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    private function coach(): string
    {
        $this->teamMember->loadMissing('professional');
        return $this->teamMember->professional?->name ?? 'Un coach';
    }

    public function toArray(object $notifiable): array
    {
        $esAceptada = $this->accion === self::ACEPTADA;
        return [
            'tipo'    => 'respuesta_invitacion_equipo',
            'icono'   => $esAceptada ? '✅' : '📭',
            'titulo'  => $esAceptada
                ? $this->coach().' aceptó tu invitación'
                : $this->coach().' rechazó tu invitación',
            'mensaje' => $esAceptada
                ? 'Ya forma parte de tu equipo y su cuidado empieza a sumar.'
                : 'Puedes invitar a otro profesional cuando quieras.',
            'url'     => route('equipo.index', absolute: false),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $esAceptada = $this->accion === self::ACEPTADA;
        $coach = $this->coach();
        $estudio = $notifiable->name ?: 'Equipo';
        $url = url(route('equipo.index', absolute: false));
        $key = $esAceptada ? 'respuesta_equipo_aceptada' : 'respuesta_equipo_rechazada';

        // Fallback hard-coded (mismo texto de antes) por si la plantilla se desactiva.
        $fallback = $esAceptada ? [
            'subject' => 'Kinvoo · '.$coach.' aceptó tu invitación',
            'greeting' => 'Hola '.$estudio.',',
            'body' => '**'.$coach.'** aceptó formar parte de tu equipo en Kinvoo.'."\n\n".
                'A partir de ahora, su cuidado (consultas, fisio, charlas, etc.) suma en tu Panel de bienestar.',
            'action_label' => 'Ver mi equipo',
            'action_url' => $url,
            'outro' => 'Gracias por seguir cuidando a tu equipo con Kinvoo.',
        ] : [
            'subject' => 'Kinvoo · '.$coach.' declinó tu invitación',
            'greeting' => 'Hola '.$estudio.',',
            'body' => '**'.$coach.'** declinó por ahora tu invitación.'."\n\n".
                'Es normal — puedes invitar a otros profesionales cuando quieras.',
            'action_label' => 'Ver mi equipo',
            'action_url' => $url,
            'outro' => 'Gracias por seguir cuidando a tu equipo con Kinvoo.',
        ];

        $t = \App\Models\EmailTemplate::render($key, ['coach' => $coach, 'estudio' => $estudio], $fallback);
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
