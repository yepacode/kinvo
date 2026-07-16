<?php

namespace App\Notifications;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Se dispara cuando un profesional pulsa "Me interesa, conéctame con el estudio"
 * en su bandeja. Va al owner de Kinvoo por dos canales:
 *  - database  → campanita en el panel + resource "Contactos"
 *  - mail      → aviso a hola@kinvoo.com para gestionar la conexión
 * El correo va en cola (ShouldQueue) para no bloquear el request; si el mailer
 * está caído la notificación database sigue quedando registrada.
 */
class ProfesionalInteresadoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Contact $contact) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        $this->contact->loadMissing(['professionalProfile.user']);

        return [
            'tipo' => 'conexion_pendiente',
            'icono' => '🤝',
            'titulo' => 'Conexión pendiente: '.($this->contact->professionalProfile->user->name ?? 'Profesional')
                .' quiere que lo conectes con '.$this->contact->contact_name,
            'mensaje' => Str::limit($this->contact->message, 90),
            'url' => route('filament.admin.resources.contacts.index', absolute: false),
            'contact_id' => $this->contact->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->contact->loadMissing(['professionalProfile.user']);

        $profesional = $this->contact->professionalProfile->user->name ?? 'Un profesional';
        $estudio = $this->contact->contact_name;

        return (new MailMessage)
            ->subject('Conexión pendiente en Kinvoo · '.$profesional.' quiere conectar con '.$estudio)
            ->greeting('Hola equipo Kinvoo,')
            ->line($profesional.' marcó "Me interesa, conéctame" en la bandeja de contactos.')
            ->line('Quiere que hagamos el puente con **'.$estudio.'**.')
            ->line('Mensaje original del estudio:')
            ->line('> '.Str::limit($this->contact->message, 300))
            ->action('Ver en el panel', url(route('filament.admin.resources.contacts.index', absolute: false)))
            ->line('Recuerda cerrar la conexión y avisar a ambas partes por su canal habitual.');
    }
}
