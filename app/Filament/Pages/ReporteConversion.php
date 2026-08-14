<?php

namespace App\Filament\Pages;

use App\Enums\RolUsuario;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * H5 · Reporte de conversión (petición cliente, docx PRUEBA KINVOO):
 * "Fecha de registro vs. fecha de conversión a pago"
 * "Origen del registro (evento / Zoom / bolsa de trabajo / referido)"
 */
class ReporteConversion extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $navigationLabel = 'Conversión de usuarios';
    protected static ?int $navigationSort = 22;
    protected static string $view = 'filament.pages.reporte-tabla';

    public function getTitle(): string
    {
        return landing('admin_reporte_conversion_titulo');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(User::query()->whereIn('nivel', [RolUsuario::Professional, RolUsuario::Contractor]))
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('Correo')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('nivel')->label('Rol')->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registro')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('converted_to_paid_at')
                    ->label('Conversión')
                    ->date('d M Y')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('dias_para_convertir')
                    ->label('Días hasta pagar')
                    ->state(function (User $r) {
                        if (! $r->converted_to_paid_at) return '—';
                        $dias = (int) floor($r->created_at->diffInDays($r->converted_to_paid_at, absolute: true));
                        return $dias === 0 ? __('mismo día') : $dias.' '.__('días');
                    })
                    ->color(fn (User $r) => $r->converted_to_paid_at ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('registration_source')
                    ->label('Origen')
                    ->badge()
                    ->color(fn (?string $s) => match ($s) {
                        'evento'   => 'warning',
                        'zoom'     => 'info',
                        'bolsa'    => 'success',
                        'referido' => 'primary',
                        default    => 'gray',
                    })
                    ->placeholder('directo'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('convertidos')
                    ->label('Solo convertidos')
                    ->query(fn (Builder $q) => $q->whereNotNull('converted_to_paid_at')),
                Tables\Filters\Filter::make('no_convertidos')
                    ->label('Aún sin pagar')
                    ->query(fn (Builder $q) => $q->whereNull('converted_to_paid_at')),
                Tables\Filters\SelectFilter::make('registration_source')
                    ->label('Origen')
                    ->options([
                        'evento'   => 'Evento',
                        'zoom'     => 'Zoom',
                        'bolsa'    => 'Bolsa de trabajo',
                        'referido' => 'Referido',
                        'directo'  => 'Directo',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('fijar_origen')
                    ->label('Fijar origen')
                    ->icon('heroicon-o-tag')
                    ->form([
                        \Filament\Forms\Components\Select::make('registration_source')
                            ->label('Origen del registro')
                            ->options([
                                'evento'   => 'Evento',
                                'zoom'     => 'Zoom',
                                'bolsa'    => 'Bolsa de trabajo',
                                'referido' => 'Referido',
                                'directo'  => 'Directo',
                            ])
                            ->required(),
                    ])
                    ->action(fn (User $r, array $data) => $r->update([
                        'registration_source' => $data['registration_source'],
                    ])),
            ])
            ->emptyStateHeading(landing('admin_reporte_conversion_empty'));
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->esAdmin() ?? false;
    }
}
