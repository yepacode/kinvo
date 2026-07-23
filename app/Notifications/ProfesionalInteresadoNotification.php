<?php

namespace App\Notifications;

use App\Models\Contact;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Se dispara cuando un profesional pulsa "Me interesa, conéctame con el estudio"
 * en su bandeja. Va al owner de Kinvoo por dos canales:
 *  - database  → campanita en el panel + resource "Contactos"
 *  - mail      → aviso a hola@gokinvoo.com para gestionar la conexión
 * Envío SÍNCRONO (sin ShouldQueue). En Hostinger compartido la cola no
 * es confiable; enviamos directo en el request. El sender ya envuelve
 * la llamada en try/catch, así que un fallo SMTP no rompe el flujo.
 */
class ProfesionalInteresadoNotification extends Notification
{
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

        // Null-safe en toda la cadena: si el profesional o su perfil fueron
        // eliminados entre la creación del contacto y este render, el correo
        // aún sale — sólo se pierde el nombre.
        $profesional = $this->contact->professionalProfile?->user?->name ?? 'Un profesional';
        $estudio = $this->contact->contact_name ?: 'Un estudio';

        return (new MailMessage)
            ->subject('Conexión pendiente en Kinvoo · '.$profesional.' quiere conectar con '.$estudio)
            ->greeting('Hola equipo Kinvoo,')
            ->line($profesional.' marcó "Me interesa, conéctame" en la bandeja de contactos.')
            ->line('Quiere que hagamos el puente con **'.$estudio.'**.')
            ->line('Mensaje original del estudio:')
            ->line('> '.Str::limit((string) $this->contact->message, 300))
            ->action('Ver en el panel', url(route('filament.admin.resources.contacts.index', absolute: false)))
            ->line('Recuerda cerrar la conexión y avisar a ambas partes por su canal habitual.');
    }
}
