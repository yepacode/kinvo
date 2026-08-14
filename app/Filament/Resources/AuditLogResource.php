<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * M8 · Bitácora legal (petición cliente: "por cuestiones legales esto debe
 * quedar en el historial hasta lo más mínimo"). Admin ve TODO lo que pasa
 * en la plataforma, filtrable por usuario, tipo de acción, fecha y IP.
 * Solo lectura: la bitácora es append-only.
 */
class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Fase 2 · Producto';
    protected static ?string $modelLabel = 'evento';
    protected static ?string $pluralModelLabel = 'Bitácora legal';
    protected static ?int $navigationSort = 90;

    public static function canCreate(): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn () => AuditLog::query()->with('actor'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')->dateTime('d M Y · H:i:s')->sortable(),
                Tables\Columns\TextColumn::make('actor.name')
                    ->label('Actor')
                    ->formatStateUsing(fn ($state, $record) => $state ?: '(sistema)')
                    ->description(fn ($record) => $record->actor?->email)
                    ->searchable(query: fn (Builder $q, string $s) =>
                        $q->orWhereHas('actor', fn ($a) => $a->where('name','like',"%$s%")->orWhere('email','like',"%$s%"))),
                Tables\Columns\TextColumn::make('action')
                    ->label('Acción')
                    ->badge()
                    ->color(fn (string $state) => match (true) {
                        str_starts_with($state, 'auth_')       => 'gray',
                        str_contains($state, 'created')        => 'success',
                        str_contains($state, 'updated')        => 'info',
                        str_contains($state, 'deleted')        => 'danger',
                        str_contains($state, 'failed')         => 'danger',
                        str_contains($state, 'rejected')       => 'warning',
                        default                                => 'primary',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Sobre')
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('subject_id')->label('ID')->toggleable(),
                Tables\Columns\TextColumn::make('ip')->label('IP')->toggleable(),
                Tables\Columns\TextColumn::make('user_agent')
                    ->label('Navegador')->limit(30)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label('Tipo de acción')
                    ->options(fn () => AuditLog::query()
                        ->select('action')->distinct()->orderBy('action')
                        ->pluck('action', 'action')->toArray()),
                Tables\Filters\Filter::make('rango_fechas')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('desde')->label('Desde'),
                        \Filament\Forms\Components\DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(fn (Builder $q, array $data) => $q
                        ->when($data['desde'] ?? null, fn ($qq, $v) => $qq->whereDate('created_at', '>=', $v))
                        ->when($data['hasta'] ?? null, fn ($qq, $v) => $qq->whereDate('created_at', '<=', $v))),
                Tables\Filters\Filter::make('sin_actor')
                    ->label('Sistema (sin actor)')
                    ->query(fn (Builder $q) => $q->whereNull('actor_user_id')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('exportar')
                    ->label('Exportar CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () {
                        $filename = 'bitacora-kinvoo-'.now()->format('Ymd-His').'.csv';
                        return response()->streamDownload(function () {
                            $out = fopen('php://output', 'w');
                            fputcsv($out, ['fecha_iso','actor_email','actor_id','action','subject_type','subject_id','ip','user_agent','old_json','new_json']);
                            AuditLog::query()->with('actor')->orderBy('created_at')
                                ->chunk(500, function ($rows) use ($out) {
                                    foreach ($rows as $r) {
                                        fputcsv($out, [
                                            $r->created_at?->toIso8601String(),
                                            $r->actor?->email,
                                            $r->actor_user_id,
                                            $r->action,
                                            class_basename($r->subject_type),
                                            $r->subject_id,
                                            $r->ip,
                                            $r->user_agent,
                                            json_encode($r->old, JSON_UNESCAPED_UNICODE),
                                            json_encode($r->new, JSON_UNESCAPED_UNICODE),
                                        ]);
                                    }
                                });
                            fclose($out);
                        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view'  => Pages\ViewAuditLog::route('/{record}'),
        ];
    }
}
