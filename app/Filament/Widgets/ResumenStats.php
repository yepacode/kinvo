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

        // Totales por rol
        $profTotal = User::where('nivel', RolUsuario::Professional->value)->count();
        $profMes = User::where('nivel', RolUsuario::Professional->value)
            ->where('created_at', '>=', $inicioMes)->count();
        $profSemana = User::where('nivel', RolUsuario::Professional->value)
            ->where('created_at', '>=', $inicioSemana)->count();

        $contrTotal = User::where('nivel', RolUsuario::Contractor->value)->count();
        $contrMes = User::where('nivel', RolUsuario::Contractor->value)
            ->where('created_at', '>=', $inicioMes)->count();
        $contrSemana = User::where('nivel', RolUsuario::Contractor->value)
            ->where('created_at', '>=', $inicioSemana)->count();

        $publicados = ProfessionalProfile::where('is_published', true)->count();

        // Aprobaciones pendientes: 1ª (Pendiente) y 2ª (PerfilPendiente).
        $pendientes = (clone $miembros)->where('estado', EstadoUsuario::Pendiente->value)->count();
        $perfilesEnRevision = User::where('nivel', RolUsuario::Contractor->value)
            ->where('estado', EstadoUsuario::PerfilPendiente->value)->count();

        // Contactos
        $contactosTotal = Contact::count();
        $contactosMes = Contact::where('created_at', '>=', $inicioMes)->count();
        $contactosSemana = Contact::where('created_at', '>=', $inicioSemana)->count();
        // Aceptados: talento marcó "Me interesa, conéctame".
        $contactosAceptadosTotal = Contact::whereNotNull('professional_interesado_at')->count();
        $contactosAceptadosMes = Contact::whereNotNull('professional_interesado_at')
            ->where('professional_interesado_at', '>=', $inicioMes)->count();

        return [
            Stat::make('Profesionales', $profTotal)
                ->description("+{$profMes} este mes · +{$profSemana} esta semana · {$publicados} publicados")
                ->descriptionIcon('heroicon-m-user')
                ->color('success')
                ->url($this->urlUsuarios(['nivel' => RolUsuario::Professional->value])),

            Stat::make('Contratantes (estudios)', $contrTotal)
                ->description("+{$contrMes} este mes · +{$contrSemana} esta semana")
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('info')
                ->url($this->urlUsuarios(['nivel' => RolUsuario::Contractor->value])),

            Stat::make('Aprobaciones pendientes (1ª)', $pendientes)
                ->description($pendientes > 0 ? 'Cuentas recién registradas — clic para revisar' : 'Todo al día')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendientes > 0 ? 'warning' : 'gray')
                ->url($this->urlUsuarios(['estado' => EstadoUsuario::Pendiente->value])),

            Stat::make('Perfiles en revisión (2ª)', $perfilesEnRevision)
                ->description($perfilesEnRevision > 0
                    ? 'Contratistas que ya llenaron su perfil — revísalos y aprueba'
                    : 'Todo al día')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color($perfilesEnRevision > 0 ? 'warning' : 'gray')
                ->url($this->urlUsuarios(['estado' => EstadoUsuario::PerfilPendiente->value])),

            Stat::make('Contactos enviados', $contactosMes)
                ->description("Este mes (clic) · esta semana: {$contactosSemana} · total: {$contactosTotal}")
                ->descriptionIcon('heroicon-m-envelope')
                ->color('success')
                ->url(ContactResource::getUrl('index', ['tableFilters' => ['rango' => ['desde' => $inicioMes->toDateString()]]])),

            Stat::make('Contactos aceptados', $contactosAceptadosMes)
                ->description("Este mes · total: {$contactosAceptadosTotal} (talentos que dijeron 'Me interesa')")
                ->descriptionIcon('heroicon-m-hand-thumb-up')
                ->color('success')
                ->url(ContactResource::getUrl('index')),
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
