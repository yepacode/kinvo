<?php

namespace App\Filament\Pages;

use App\Enums\RolUsuario;
use App\Models\Offer;
use App\Models\TeamMember;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * H5 · Reporte por estudio (petición cliente, docx PRUEBA KINVOO):
 *  - Coaches activos actuales
 *  - Altas y bajas del mes en curso
 *  - Vacantes publicadas vs. cerradas
 *  - Última actividad del estudio
 *  - Cupos asignados por el admin (max_coach_slots)
 */
class ReporteEstudios extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $navigationLabel = 'Actividad por estudio';
    protected static ?int $navigationSort = 21;
    protected static string $view = 'filament.pages.reporte-tabla';

    public function getTitle(): string
    {
        return landing('admin_reporte_estudios_titulo');
    }

    public function table(Table $table): Table
    {
        $inicioMes = now()->startOfMonth();

        return $table
            ->query(
                User::query()
                    ->where('nivel', RolUsuario::Contractor)
                    ->with('companyProfile')
                    ->withCount([
                        'offers as vacantes_activas_count' => fn (Builder $q) =>
                            $q->where('status', Offer::STATUS_PUBLISHED),
                        'offers as vacantes_cerradas_count' => fn (Builder $q) =>
                            $q->where('status', Offer::STATUS_CLOSED),
                    ])
            )
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Estudio')
                    ->state(fn (User $r) => $r->companyProfile?->company_name ?? $r->name)
                    ->weight('medium')
                    ->searchable(query: fn (Builder $q, string $search) =>
                        $q->where('name', 'like', "%$search%")
                          ->orWhereHas('companyProfile', fn ($c) => $c->where('company_name', 'like', "%$search%"))),
                Tables\Columns\TextColumn::make('coaches_activos')
                    ->label('Coaches activos')
                    ->state(fn (User $r) => TeamMember::where('contractor_user_id', $r->id)
                        ->where('status', TeamMember::STATUS_ACTIVE)->count())
                    ->badge()->color('success'),
                Tables\Columns\TextColumn::make('cupos')
                    ->label('Cupos')
                    ->state(fn (User $r) => $r->companyProfile?->max_coach_slots
                        ? $r->companyProfile->max_coach_slots
                        : '—')
                    ->tooltip('Cupos asignados por el admin (max_coach_slots)'),
                Tables\Columns\TextColumn::make('altas_mes')
                    ->label('Altas mes')
                    ->state(fn (User $r) => TeamMember::where('contractor_user_id', $r->id)
                        ->where('joined_at', '>=', now()->startOfMonth())->count())
                    ->color('success'),
                Tables\Columns\TextColumn::make('bajas_mes')
                    ->label('Bajas mes')
                    ->state(fn (User $r) => TeamMember::where('contractor_user_id', $r->id)
                        ->where('left_at', '>=', now()->startOfMonth())->count())
                    ->color('danger'),
                Tables\Columns\TextColumn::make('vacantes_activas_count')
                    ->label('Vacantes activas')
                    ->badge()->color('info')->sortable(),
                Tables\Columns\TextColumn::make('vacantes_cerradas_count')
                    ->label('Cerradas')
                    ->badge()->color('gray')->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última actividad')
                    ->since()->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('con_vacantes')
                    ->label('Con vacantes activas')
                    ->query(fn (Builder $q) => $q->has('offers')),
                Tables\Filters\Filter::make('sin_cupos_asignados')
                    ->label('Sin cupos asignados')
                    ->query(fn (Builder $q) => $q->whereHas('companyProfile', fn ($c) => $c->whereNull('max_coach_slots'))),
            ])
            ->actions([
                // Ver detalle: modal con los coaches activos y las vacantes del estudio.
                Tables\Actions\Action::make('detalle')
                    ->label('Ver detalle')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (User $record) => 'Detalle · '.($record->companyProfile?->company_name ?? $record->name))
                    ->modalContent(fn (User $record) => view('filament.reportes.estudio-detalle', [
                        'coaches' => TeamMember::with('professional')
                            ->where('contractor_user_id', $record->id)
                            ->where('status', TeamMember::STATUS_ACTIVE)
                            ->orderByDesc('joined_at')->get(),
                        'ofertas' => Offer::where('contractor_user_id', $record->id)
                            ->latest()->get(),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
            ])
            ->emptyStateHeading(landing('admin_reporte_estudios_empty'));
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->esAdmin() ?? false;
    }
}
