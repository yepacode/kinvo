<?php

namespace App\Filament\Widgets;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Widget para el dashboard del admin: cuántas cuentas están esperando
 * aprobación y con link directo al filtro de UserResource.
 *
 * Ayuda a Marian a saber a golpe de vista si tiene cuentas pendientes
 * sin tener que entrar al recurso a buscarlas.
 */
class CuentasPendientesWidget extends BaseWidget
{
    // sort más negativo = renderiza antes. ResumenStats está en -3, así que
    // -4 pone las cuentas pendientes arriba de todo (es lo más accionable para Marian).
    protected static ?int $sort = -4;

    protected function getStats(): array
    {
        $pendCuenta = User::where('estado', EstadoUsuario::Pendiente->value)
            ->where('nivel', '!=', RolUsuario::Admin->value)
            ->count();
        $pendPerfil = User::where('estado', EstadoUsuario::PerfilPendiente->value)
            ->where('nivel', '!=', RolUsuario::Admin->value)
            ->count();
        $total = $pendCuenta + $pendPerfil;

        $urlCuenta  = UserResource::getUrl('index', ['tableFilters' => ['estado' => ['value' => 'pending']]]);
        $urlPerfil  = UserResource::getUrl('index', ['tableFilters' => ['estado' => ['value' => 'profile_pending']]]);

        return [
            Stat::make('Cuentas por aprobar', $pendCuenta)
                ->description($pendCuenta > 0 ? 'Necesitan tu aprobación inicial' : 'Al día')
                ->descriptionIcon($pendCuenta > 0 ? 'heroicon-o-exclamation-circle' : 'heroicon-o-check-circle')
                ->color($pendCuenta > 0 ? 'warning' : 'success')
                ->url($urlCuenta),

            Stat::make('Perfiles por revisar', $pendPerfil)
                ->description($pendPerfil > 0 ? 'Contratistas listos para publicar' : 'Al día')
                ->descriptionIcon($pendPerfil > 0 ? 'heroicon-o-eye' : 'heroicon-o-check-circle')
                ->color($pendPerfil > 0 ? 'warning' : 'success')
                ->url($urlPerfil),

            Stat::make('Total pendiente', $total)
                ->description($total > 0 ? "$total usuarios esperando" : 'Nada por hacer, buen trabajo')
                ->descriptionIcon($total > 0 ? 'heroicon-o-user-group' : 'heroicon-o-hand-thumb-up')
                ->color($total > 0 ? 'primary' : 'success')
                ->url(UserResource::getUrl('index')),
        ];
    }
}
