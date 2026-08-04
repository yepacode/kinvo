<?php

namespace App\Providers;

use App\Services\Billing\FakeGateway;
use App\Services\Billing\StripeGateway;
use App\Services\Billing\SubscriptionGateway;
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
        // Por default se usa FakeGateway (sin cobros reales) — se cambia a
        // 'stripe' vía .env cuando la cliente comparta las claves del kick-off.
        $this->app->singleton(SubscriptionGateway::class, function () {
            return match (config('billing.gateway', 'fake')) {
                'stripe' => new StripeGateway(),
                default  => new FakeGateway(),
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
    }
}
