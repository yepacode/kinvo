<?php

namespace App\Filament\Widgets;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Models\Contact;
use App\Models\ProfessionalProfile;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResumenStats extends BaseWidget
{
    protected static ?int $sort = -3;

    protected function getStats(): array
    {
        $inicioMes = now()->startOfMonth();

        $profesionales = User::where('nivel', RolUsuario::Professional->value)->count();
        $contratantes = User::where('nivel', RolUsuario::Contractor->value)->count();
        $publicados = ProfessionalProfile::where('is_published', true)->count();
        $pendientes = User::where('nivel', '!=', RolUsuario::Admin->value)
            ->where('estado', EstadoUsuario::Pendiente->value)->count();
        $contactosMes = Contact::where('created_at', '>=', $inicioMes)->count();
        $registrosMes = User::where('nivel', '!=', RolUsuario::Admin->value)
            ->where('created_at', '>=', $inicioMes)->count();

        return [
            Stat::make('Profesionales', $profesionales)
                ->description($publicados.' con perfil publicado')
                ->color('success'),
            Stat::make('Contratantes', $contratantes)
                ->color('info'),
            Stat::make('Pendientes de aprobación', $pendientes)
                ->description($pendientes > 0 ? 'Requieren revisión' : 'Todo al día')
                ->color($pendientes > 0 ? 'warning' : 'gray'),
            Stat::make('Contactos del mes', $contactosMes)
                ->color('success'),
            Stat::make('Registros nuevos del mes', $registrosMes)
                ->color('info'),
        ];
    }
}
