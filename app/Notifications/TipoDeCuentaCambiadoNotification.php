<?php

namespace App\Notifications;

use App\Enums\RolUsuario;
use Illuminate\Notifications\Notification;

/**
 * Aviso al usuario cuando el owner cambia su tipo de cuenta desde el panel
 * (de talento a estudio o viceversa). Se guarda en la campanita para que al
 * volver a entrar entienda por qué su dashboard cambió.
 */
class TipoDeCuentaCambiadoNotification extends Notification
{
    public function __construct(public RolUsuario $nuevoTipo) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $titulo = $this->nuevoTipo === RolUsuario::Professional
            ? 'Ahora tu cuenta es de talento'
            : 'Ahora tu cuenta es de estudio / marca';

        $ruta = $this->nuevoTipo === RolUsuario::Professional
            ? route('professional.bienvenida', absolute: false)
            : route('company.bienvenida', absolute: false);

        return [
            'tipo' => 'tipo_cuenta_cambiado',
            'icono' => '🔄',
            'titulo' => $titulo,
            'mensaje' => 'Llena tu nuevo perfil para que empecemos a conectarte con la comunidad Kinvoo.',
            'url' => $ruta,
        ];
    }
}
