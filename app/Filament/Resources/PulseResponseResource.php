<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PulseResponseResource\Pages;
use App\Models\PulseResponse;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * M6 · Admin ve todas las respuestas del Pulso Kinvoo (analítica global).
 * Solo lectura; el coach responde desde /encuesta-pulso.
 */
class PulseResponseResource extends Resource
{
    protected static ?string $model = PulseResponse::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';
    protected static ?string $navigationGroup = 'Beneficios coaches';
    protected static ?string $modelLabel = 'respuesta de pulso';
    protected static ?string $pluralModelLabel = 'Pulso Kinvoo';
    protected static ?int $navigationSort = 31;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn () => PulseResponse::query()->with(['user', 'contractor.companyProfile']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Coach')->searchable(),
                Tables\Columns\TextColumn::make('contractor.name')
                    ->label('Estudio')
                    ->state(fn (PulseResponse $r) => $r->contractor?->companyProfile?->company_name
                        ?? $r->contractor?->name ?? '—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('rating')
                    ->label('★')->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 4 => 'success',
                        $state === 3 => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('answer_energy')
                    ->label('Energía')->wrap()->limit(60)->toggleable(),
                Tables\Columns\TextColumn::make('answer_growth')
                    ->label('Creció')->wrap()->limit(60)->toggleable(),
                Tables\Columns\TextColumn::make('answer_support')
                    ->label('Respaldo pedido')->wrap()->limit(60)->toggleable(),
                Tables\Columns\TextColumn::make('period_start')
                    ->label('Semana')->date('d M')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Recibida')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('rating')
                    ->options([1=>'1★', 2=>'2★', 3=>'3★', 4=>'4★', 5=>'5★']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPulseResponses::route('/'),
            'view'  => Pages\ViewPulseResponse::route('/{record}'),
        ];
    }
}
