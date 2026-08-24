<?php

namespace App\Notifications;

use App\Enums\RolUsuario;
use App\Models\EmailTemplate;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso al usuario cuando el owner cambia su tipo de cuenta desde el panel
 * (de talento a estudio o viceversa). Va por campana Y correo (auditoría
 * ago-2026): es un cambio importante y sin mail el usuario puede tardar días
 * en volver al panel para entender por qué cambió su dashboard.
 */
class TipoDeCuentaCambiadoNotification extends Notification
{
    public function __construct(public RolUsuario $nuevoTipo) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $esCoach = $this->nuevoTipo === RolUsuario::Professional;
        $nombre = $notifiable->name ?: 'Hola';
        $url = url($esCoach
            ? route('professional.bienvenida', absolute: false)
            : route('company.bienvenida', absolute: false));
        $tipoNuevo = $esCoach ? 'talento (coach)' : 'estudio / marca';

        $t = EmailTemplate::render('tipo_cuenta_cambiado',
            ['nombre' => $nombre, 'tipo' => $tipoNuevo],
            [
                'subject' => 'Kinvoo · Cambiamos tu tipo de cuenta a '.$tipoNuevo,
                'greeting' => 'Hola '.$nombre.',',
                'body' => 'El equipo de Kinvoo actualizó tu tipo de cuenta a **'.$tipoNuevo.'**.'."\n\n".'Al entrar verás un panel distinto y un nuevo perfil por completar. Tu historial anterior queda guardado.',
                'action_label' => 'Completar mi nuevo perfil',
                'action_url' => $url,
                'outro' => '¿Fue un error? Escríbenos y lo revertimos.',
            ]
        );
        $t['action_url'] = $url;

        return EmailTemplate::toMailMessage($t);
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
