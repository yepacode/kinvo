<?php

namespace App\Providers;

use App\Services\Billing\FakeGateway;
use App\Services\Billing\MercadoPagoGateway;
use App\Services\Billing\StripeGateway;
use App\Services\Billing\SubscriptionGateway;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Fase 2 · Bind del gateway de pagos según config('billing.gateway').
        // Por default se usa FakeGateway (sin cobros reales). En producción
        // Marian eligió MercadoPago (México); Stripe queda como alternativa.
        $this->app->singleton(SubscriptionGateway::class, function () {
            return match (config('billing.gateway', 'fake')) {
                'mercadopago' => new MercadoPagoGateway(),
                'stripe'      => new StripeGateway(),
                default       => new FakeGateway(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Contraseñas fuertes en registro y restablecimiento: mínimo 8 con
        // mayúsculas, minúsculas, números y símbolos. En producción además
        // se rechazan las contraseñas filtradas (HaveIBeenPwned); en local/tests
        // se omite esa comprobación para no depender de la red.
        Password::defaults(function () {
            $rule = Password::min(8)->mixedCase()->numbers()->symbols();

            return app()->isProduction() ? $rule->uncompromised() : $rule;
        });

        // M8 · Auditoría legal: registra login/logout/register/failed en AuditLog.
        Event::subscribe(\App\Listeners\AuditAuthEvents::class);
    }
}
