<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('Kinvoo · Bolsa de Talento')
            ->sidebarCollapsibleOnDesktop()  // en tablet/desktop el sidebar puede plegarse
            ->login()
            ->colors([
                'primary' => Color::hex('#5C7A5F'),  // verde salvia Kinvoo
                'success' => Color::hex('#5C7A5F'),
                'warning' => Color::hex('#C8C040'),  // lima/oliva (acento)
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                // Feedback Karla 17-sep: Filament NO pasa por el grupo `web`
                // de Laravel — sus rutas se cargan con su propio middleware
                // stack (ver vendor/filament/filament/routes/web.php). Sin
                // este orden, ClearBrowserStateOnLogout registrado global en
                // bootstrap/app.php cubría solo /logout del frontend y NO
                // /admin/logout. Se declara acá primero (envoltura externa)
                // para que la cabecera Clear-Site-Data se conserve en la
                // respuesta final que devuelve RedirectAdminLogoutToFrontend.
                \App\Http\Middleware\ClearBrowserStateOnLogout::class,
                // Feedback Karla 17-sep: tras logout admin manda a /login
                // (frontend) en vez de dejar en /admin/login, donde el coach
                // y el estudio no pueden entrar.
                \App\Http\Middleware\RedirectAdminLogoutToFrontend::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            // Feedback Karla 16-sep: el keepalive CSRF vive en head-assets del
            // layout público (@auth), pero el panel Filament tiene su propio
            // layout — sin este renderHook Karla seguiría viendo 419 al enviar
            // formularios largos del panel. Se inyecta como <script defer> en
            // el <head>, con la misma lógica del keepalive del frontend.
            ->renderHook(
                'panels::head.end',
                fn (): string => view('partials.csrf-keepalive-filament')->render(),
            );
    }
}
