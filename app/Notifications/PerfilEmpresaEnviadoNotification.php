<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * Notifica al admin cuando un contratista con estado PerfilPendiente envía
 * su perfil de empresa para 2ª revisión.
 */
class PerfilEmpresaEnviadoNotification extends Notification
{
    public function __construct(public User $contratista) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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
