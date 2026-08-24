<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Notifications\Notification;

/**
 * Complementa el correo AvisoCobroFallido con una entrada in-app (campana).
 * Auditoría ago-2026: sin esta notif, si el correo se iba a spam el usuario
 * descubría la baja de su membresía sin ninguna explicación.
 */
class CobroFallidoNotification extends Notification
{
    public function __construct(public ?Subscription $subscription = null) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $plan = $this->subscription?->plan?->nombre ?? 'tu membresía';

        return [
            'tipo' => 'cobro_fallido',
            'icono' => '⚠️',
            'titulo_key' => 'No pudimos procesar tu pago',
            'titulo_params' => [],
            'mensaje_key' => 'El cobro de :plan no se pudo completar. Actualiza tu método de pago para seguir con Kinvoo.',
            'mensaje_params' => ['plan' => $plan],
            'titulo' => 'No pudimos procesar tu pago',
            'mensaje' => 'El cobro de '.$plan.' no se pudo completar.',
            'url' => route('membresias.index', absolute: false),
        ];
    }
}
