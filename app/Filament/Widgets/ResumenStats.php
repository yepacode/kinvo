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

    /** Excluye emails de demostración (Fase 2) para no ensuciar reportes contables. */
    private const DEMO_PREFIX = 'demo.f2.';

    protected function getStats(): array
    {
        $inicioMes = now()->startOfMonth();
        $inicioSemana = now()->startOfWeek();

        // Base: excluye admins Y cuentas demo (que no cuentan como negocio real).
        $noDemos = fn ($q) => $q->where('email', 'not like', self::DEMO_PREFIX.'%');
        $miembros = User::where('nivel', '!=', RolUsuario::Admin->value)->tap($noDemos);

        // Totales por rol (excluyendo demos)
        $profQ  = fn () => User::where('nivel', RolUsuario::Professional->value)->tap($noDemos);
        $contrQ = fn () => User::where('nivel', RolUsuario::Contractor->value)->tap($noDemos);

        $profTotal   = $profQ()->count();
        $profMes     = $profQ()->where('created_at', '>=', $inicioMes)->count();
        $profSemana  = $profQ()->where('created_at', '>=', $inicioSemana)->count();

        $contrTotal  = $contrQ()->count();
        $contrMes    = $contrQ()->where('created_at', '>=', $inicioMes)->count();
        $contrSemana = $contrQ()->where('created_at', '>=', $inicioSemana)->count();

        // Perfiles publicados (excluyendo dueños demo)
        $publicados = ProfessionalProfile::where('is_published', true)
            ->whereHas('user', $noDemos)
            ->count();

        // Aprobaciones pendientes: los dueños demo también quedan fuera aquí.
        $pendientes = (clone $miembros)->where('estado', EstadoUsuario::Pendiente->value)->count();
        $perfilesEnRevision = $contrQ()
            ->where('estado', EstadoUsuario::PerfilPendiente->value)->count();

        // Contactos (excluir los enviados desde cuentas demo)
        $contactBase = fn () => Contact::whereHas('contractor', $noDemos);
        $contactosTotal   = $contactBase()->count();
        $contactosMes     = $contactBase()->where('created_at', '>=', $inicioMes)->count();
        $contactosSemana  = $contactBase()->where('created_at', '>=', $inicioSemana)->count();
        $contactosAceptadosTotal = $contactBase()->whereNotNull('professional_interesado_at')->count();
        $contactosAceptadosMes   = $contactBase()->whereNotNull('professional_interesado_at')
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
