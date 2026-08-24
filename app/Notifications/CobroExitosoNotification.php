<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Notifications\Notification;

/**
 * Fase 2 · Al user cuando su cobro se procesa exitosamente. Complementa
 * al correo AvisoCobroExitoso con una entrada in-app en la campana.
 */
class CobroExitosoNotification extends Notification
{
    public function __construct(public Payment $payment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'cobro_exitoso',
            'icono' => '✅',
            'titulo_key' => 'Recibimos tu pago',
            'titulo_params' => [],
            'mensaje_key' => 'Se cobraron :monto correctamente. Tu suscripción quedó activa.',
            'mensaje_params' => ['monto' => $this->payment->montoFormateado()],
            'titulo' => 'Recibimos tu pago',
            'mensaje' => 'Se cobraron '.$this->payment->montoFormateado().' correctamente.',
            // Coherente con el correo AvisoCobroExitoso (que lleva a /membresias).
            'url' => route('membresias.index', absolute: false),
        ];
    }
}
