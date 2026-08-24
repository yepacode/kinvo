<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifica al admin cuando un contratista con estado PerfilPendiente envía
 * su perfil de empresa para 2ª revisión. Auditoría ago-2026: agregado canal
 * 'mail' para que el admin no dependa de entrar al panel.
 */
class PerfilEmpresaEnviadoNotification extends Notification
{
    public function __construct(public User $contratista) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $nombre = $this->contratista->companyProfile?->company_name ?: $this->contratista->name;
        $url = url('/admin/users?tableFilters[estado][value]=profile_pending');

        $t = EmailTemplate::render('perfil_empresa_enviado_admin',
            ['nombre' => $nombre],
            [
                'subject' => 'Kinvoo · Perfil de empresa pendiente de revisar — '.$nombre,
                'greeting' => 'Hola equipo Kinvoo,',
                'body' => '**'.$nombre.'** acaba de enviar su perfil de empresa para revisión (2º paso).'."\n\n".'Revísalo y apruébalo cuanto antes — le dijimos al estudio que la publicación tarda máximo 24 h.',
                'action_label' => 'Ver en el panel',
                'action_url' => $url,
                'outro' => 'Un vistazo rápido evita que el estudio se enfríe esperando.',
            ]
        );
        $t['action_url'] = $url;

        return EmailTemplate::toMailMessage($t);
    }

    public function toArray(object $notifiable): array
    {
        $nombre = $this->contratista->companyProfile?->company_name ?: $this->contratista->name;

        return [
            'tipo' => 'admin',
            'icono' => '📋',
            'titulo_key' => 'Perfil de empresa para revisar',
            'titulo_params' => [],
            'mensaje_key' => ':nombre envió su perfil de empresa. Revísalo y aprueba para publicarlo.',
            'mensaje_params' => ['nombre' => $nombre],
            'titulo' => 'Perfil de empresa para revisar',
            'mensaje' => $nombre.' envió su perfil de empresa. Revísalo y aprueba para publicarlo.',
            'url' => '/admin/users?tableFilters[estado][value]=profile_pending',
        ];
    }
}
