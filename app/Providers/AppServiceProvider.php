<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
