<?php

namespace App\Observers;

use App\Models\Subscription;

/**
 * Mantiene la membresía del usuario al día con sus suscripciones.
 *
 * El motivo de existir: `users.membership_plan_id` / `membership_expires_at`
 * son campos denormalizados que lee media aplicación (menú, candados,
 * página de membresías, gates de contenido, servicios del plan). Cada flujo
 * que tocaba una suscripción se acordaba —o no— de escribirlos. Los que
 * cancelaban no lo hacían, así que se podía cancelar una suscripción y la
 * persona seguía viendo "MEMBRESÍA ACTIVA" sin poder contratar nada.
 *
 * Con el observer da igual quién mueva la suscripción (el botón del panel,
 * el webhook de la pasarela, el reintento de cobro, un comando): la
 * membresía se recalcula después. Nadie puede olvidarse.
 *
 * Nota: los `update()` masivos de query builder no disparan eventos de
 * modelo. Están todos sobre estados que no conceden acceso (incomplete), o
 * acompañados de su propia limpieza, pero si alguien añade uno nuevo sobre
 * suscripciones vigentes tiene que llamar a `sincronizarMembresia()`.
 */
class SubscriptionObserver
{
    public function saved(Subscription $subscription): void
    {
        $this->sincronizar($subscription);
    }

    public function deleted(Subscription $subscription): void
    {
        $this->sincronizar($subscription);
    }

    private function sincronizar(Subscription $subscription): void
    {
        // Sin usuario no hay nada que recalcular (suscripciones de prueba).
        $user = $subscription->user;
        if (! $user) {
            return;
        }

        // Nunca dejamos que un fallo aquí tumbe el guardado de la suscripción:
        // el cobro es lo importante, el campo denormalizado se puede recalcular.
        try {
            $user->sincronizarMembresia();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
