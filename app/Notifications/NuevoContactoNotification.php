<?php

namespace App\Notifications;

use App\Models\Contact;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NuevoContactoNotification extends Notification
{
    public function __construct(public Contact $contact) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        // Guardamos CLAVES + params; el nombre de contacto y el mensaje son
        // datos capturados (permanecen en el idioma en que se escribieron).
        $nombre = trim(preg_replace("/[\r\n]+/", ' ', (string) $this->contact->contact_name));
        $nombre = mb_strimwidth($nombre, 0, 80, '…', 'UTF-8');
        $extracto = Str::limit($this->contact->message, 90);

        return [
            'tipo' => 'contacto',
            'icono' => '✉️',
            'titulo_key' => 'Nuevo contacto de :nombre',
            'titulo_params' => ['nombre' => $nombre],
            'mensaje_key' => ':extracto',
            'mensaje_params' => ['extracto' => $extracto],
            // Fallbacks para notificaciones creadas antes de este refactor:
            'titulo' => 'Nuevo contacto de '.$nombre,
            'mensaje' => $extracto,
            'url' => route('professional.contactos', absolute: false),
        ];
    }
}
