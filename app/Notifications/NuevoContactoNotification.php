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
        return [
            'tipo' => 'contacto',
            'icono' => '✉️',
            'titulo' => 'Nuevo contacto de '.$this->contact->contact_name,
            'mensaje' => Str::limit($this->contact->message, 90),
            'url' => route('professional.contactos', absolute: false),
        ];
    }
}
