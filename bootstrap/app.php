<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'cuenta.activa' => \App\Http\Middleware\EnsureCuentaActiva::class,
            'nocache' => \App\Http\Middleware\NoCacheAutenticado::class,
            'membresia' => \App\Http\Middleware\EnsureContractorMembership::class,
            'acceso.directorio' => \App\Http\Middleware\AccesoDirectorioTalento::class,
        ]);

        // Cabeceras de seguridad en todas las respuestas web (anti-clickjacking, nosniff).
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Si un usuario autenticado NO admin recibe 403 en el panel /admin,
        // en vez del error se le redirige a su propia área (mejor UX que el 403 pelón).
        $exceptions->render(function (HttpExceptionInterface $e, $request) {
            if ($e->getStatusCode() === 403
                && $request->is('admin', 'admin/*')
                && ($user = $request->user())
                && ! $user->esAdmin()) {
                return redirect($user->homeRoute());
            }
        });
    })->create();
