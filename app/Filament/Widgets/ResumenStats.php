<?php

namespace App\Filament\Widgets;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Filament\Resources\ContactResource;
use App\Filament\Resources\UserResource;
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
        $inicioSemana = now()->startOfWeek();

        // Miembros (excluye admins). Son totales de toda la plataforma.
        $miembros = User::where('nivel', '!=', RolUsuario::Admin->value);

        $profesionales = User::where('nivel', RolUsuario::Professional->value)->count();
        $contratantes = User::where('nivel', RolUsuario::Contractor->value)->count();
        $publicados = ProfessionalProfile::where('is_published', true)->count();
        $pendientes = (clone $miembros)->where('estado', EstadoUsuario::Pendiente->value)->count();

        $contactosTotal = Contact::count();
        $contactosMes = Contact::where('created_at', '>=', $inicioMes)->count();
        $contactosSemana = Contact::where('created_at', '>=', $inicioSemana)->count();

        $registrosTotal = (clone $miembros)->count();
        $registrosMes = (clone $miembros)->where('created_at', '>=', $inicioMes)->count();
        $registrosSemana = (clone $miembros)->where('created_at', '>=', $inicioSemana)->count();

        return [
            Stat::make('Profesionales', $profesionales)
                ->description($publicados.' con perfil publicado · total en la plataforma')
                ->descriptionIcon('heroicon-m-user')
                ->color('success')
                ->url($this->urlUsuarios(['nivel' => RolUsuario::Professional->value])),

            Stat::make('Contratantes', $contratantes)
                ->description('Estudios y marcas · total en la plataforma')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('info')
                ->url($this->urlUsuarios(['nivel' => RolUsuario::Contractor->value])),

            Stat::make('Pendientes de aprobación', $pendientes)
                ->description($pendientes > 0 ? 'Requieren revisión — clic para verlos' : 'Todo al día')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendientes > 0 ? 'warning' : 'gray')
                ->url($this->urlUsuarios(['estado' => EstadoUsuario::Pendiente->value])),

            Stat::make('Contactos', $contactosMes)
                ->description("Este mes (clic) · esta semana: {$contactosSemana} · total: {$contactosTotal}")
                ->descriptionIcon('heroicon-m-envelope')
                ->color('success')
                ->url(ContactResource::getUrl('index', ['tableFilters' => ['rango' => ['desde' => $inicioMes->toDateString()]]])),

            Stat::make('Registros nuevos', $registrosMes)
                ->description("Este mes (clic) · esta semana: {$registrosSemana} · total: {$registrosTotal}")
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('info')
                ->url(UserResource::getUrl('index', ['tableFilters' => ['rango' => ['desde' => $inicioMes->toDateString()]]])),
        ];
    }

    /** URL al listado de usuarios con filtros aplicados (tarjetas clicables). */
    private function urlUsuarios(array $filtros = []): string
    {
        $tableFilters = [];
        foreach ($filtros as $campo => $valor) {
            $tableFilters[$campo] = ['value' => $valor];
        }

        return UserResource::getUrl('index', $tableFilters ? ['tableFilters' => $tableFilters] : []);
    }
}
