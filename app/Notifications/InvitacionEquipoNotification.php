<?php

namespace App\Notifications;

use App\Models\TeamMember;
use Illuminate\Notifications\Notification;

/**
 * Fase 2 · Al profesional cuando un estudio lo invita a su equipo.
 */
class InvitacionEquipoNotification extends Notification
{
    public function __construct(public TeamMember $teamMember) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->teamMember->loadMissing('contractor.companyProfile');
        $estudio = $this->teamMember->contractor?->companyProfile?->company_name
            ?? $this->teamMember->contractor?->name
            ?? 'Un estudio';

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
}
