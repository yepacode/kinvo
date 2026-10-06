<?php

namespace App\Providers;

use App\Models\PaymentCredential;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

/**
 * Feedback Karla 06-10-2026: hidrata config('billing.*') desde la tabla
 * payment_credentials para que las llaves se puedan cambiar desde
 * /admin/configuracion-pagos sin tocar el .env del servidor.
 *
 * PRECEDENCIA:
 *   1. Fila activa en payment_credentials (lo que capture el admin)
 *   2. Variables del .env (fallback — si nadie ha usado el panel todavía,
 *      el comportamiento es idéntico al de antes de este cambio)
 *
 * Corre en boot() y no en register() porque necesita la conexión a BD ya
 * resuelta. Es tolerante a fallos: si la tabla no existe (deploy a medias)
 * o la BD no responde, no lanza — simplemente deja la config del .env.
 */
class BillingConfigServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // En consola sólo aplica para comandos que tocan cobros; durante
        // migrate/install la tabla puede no existir todavía.
        if ($this->app->runningInConsole() && ! $this->app->runningUnitTests()) {
            if ($this->esComandoDeInstalacion()) {
                return;
            }
        }

        $cred = PaymentCredential::activa();
        if (! $cred) {
            return; // sin credenciales en BD → se queda lo del .env
        }

        $gateway = $cred->gateway;
        $token = $cred->secreto('access_token');
        $webhook = $cred->secreto('webhook_secret');
        $publica = $cred->secreto('public_key');

        // Sólo conmutamos la pasarela si realmente hay token: una fila
        // marcada activa pero vacía dejaría el checkout roto, así que en
        // ese caso respetamos lo que diga el .env.
        if ($gateway === 'fake') {
            Config::set('billing.gateway', 'fake');
            return;
        }

        if (! $token) {
            return;
        }

        Config::set('billing.gateway', $gateway);

        if ($gateway === 'mercadopago') {
            Config::set('billing.mercadopago.access_token', $token);
            if ($webhook) {
                Config::set('billing.mercadopago.webhook_secret', $webhook);
            }
            if ($publica) {
                Config::set('billing.mercadopago.public_key', $publica);
            }
        } elseif ($gateway === 'stripe') {
            Config::set('billing.stripe.secret', $token);
            if ($webhook) {
                Config::set('billing.stripe.webhook_secret', $webhook);
            }
            if ($publica) {
                Config::set('billing.stripe.publishable_key', $publica);
            }
        }
    }

    /** Comandos donde la tabla puede no existir todavía. */
    private function esComandoDeInstalacion(): bool
    {
        $comando = $_SERVER['argv'][1] ?? '';

        foreach (['migrate', 'db:', 'package:discover', 'key:generate', 'config:', 'optimize'] as $prefijo) {
            if (str_starts_with($comando, $prefijo)) {
                return true;
            }
        }

        return false;
    }
}
