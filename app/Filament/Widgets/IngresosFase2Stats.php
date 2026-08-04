<?php

namespace App\Filament\Widgets;

use App\Enums\RolUsuario;
use App\Filament\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Fase 2 · Widgets de ingresos por semana/mes, coaches vs estudios y
 * suscripciones activas. Excluye los pagos de proveedores fake/demo.
 */
class IngresosFase2Stats extends BaseWidget
{
    protected static ?int $sort = -2;

    /** Solo consideramos cobros reales. */
    private const REAL_PROVIDERS = ['stripe', 'mercadopago'];

    protected function getStats(): array
    {
        $inicioMes = now()->startOfMonth();
        $inicioSemana = now()->startOfWeek();

        $exitosos = fn () => Payment::where('status', Payment::STATUS_SUCCEEDED)
            ->whereIn('provider', self::REAL_PROVIDERS);

        $ingresoMes = (int) $exitosos()->where('paid_at', '>=', $inicioMes)->sum('amount_cents');
        $ingresoSemana = (int) $exitosos()->where('paid_at', '>=', $inicioSemana)->sum('amount_cents');
        $ingresoTotal = (int) $exitosos()->sum('amount_cents');

        $porRol = fn (int $nivel) => (int) Payment::where('status', Payment::STATUS_SUCCEEDED)
            ->whereIn('provider', self::REAL_PROVIDERS)
            ->whereHas('user', fn ($q) => $q->where('nivel', $nivel))
            ->sum('amount_cents');
        $ingresoCoaches = $porRol(RolUsuario::Professional->value);
        $ingresoEstudios = $porRol(RolUsuario::Contractor->value);

        $activas = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->whereIn('provider', self::REAL_PROVIDERS)
            ->count();
        $morosas = Subscription::where('status', Subscription::STATUS_PAST_DUE)
            ->whereIn('provider', self::REAL_PROVIDERS)
            ->count();

        return [
            Stat::make('Ingreso este mes', self::formato($ingresoMes))
                ->description('Esta semana: '.self::formato($ingresoSemana).' · total histórico: '.self::formato($ingresoTotal))
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->url(PaymentResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => Payment::STATUS_SUCCEEDED]],
                ])),

            Stat::make('Coaches vs Estudios', self::formato($ingresoCoaches + $ingresoEstudios))
                ->description('Coaches: '.self::formato($ingresoCoaches).' · Estudios: '.self::formato($ingresoEstudios))
                ->descriptionIcon('heroicon-m-scale')
                ->color('info'),

            Stat::make('Suscripciones activas', $activas)
                ->description($morosas > 0 ? "Morosas: {$morosas} (past_due — clic para revisar)" : 'Sin morosidad — todo al día')
                ->descriptionIcon($morosas > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-badge')
                ->color($morosas > 0 ? 'warning' : 'success'),
        ];
    }

    private static function formato(int $cents): string
    {
        return '$'.number_format($cents / 100, 2, '.', ',').' MXN';
    }
}
