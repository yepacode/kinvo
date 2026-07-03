<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProfessionalProfileResource\Pages;
use App\Models\ProfessionalProfile;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProfessionalProfileResource extends Resource
{
    protected static ?string $model = ProfessionalProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $modelLabel = 'perfil';

    protected static ?string $pluralModelLabel = 'Perfiles profesionales';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Profesional')
                    ->description(fn (ProfessionalProfile $r) => $r->headline)
                    ->searchable(),
                Tables\Columns\TextColumn::make('location.ciudad')
                    ->label('Ubicación')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_published')
                    ->label('Publicado')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Verificado')
                    ->boolean()
                    ->trueColor('success'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_verified')->label('Verificado'),
                Tables\Filters\TernaryFilter::make('is_published')->label('Publicado'),
            ])
            ->actions([
                Tables\Actions\Action::make('verPublico')
                    ->label('Ver')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (ProfessionalProfile $r) => route('talento.show', $r->slug), shouldOpenInNewTab: true)
                    ->visible(fn (ProfessionalProfile $r) => $r->is_published),
                Tables\Actions\Action::make('verificar')
                    ->label('Verificar')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ProfessionalProfile $r) => ! $r->is_verified)
                    ->action(fn (ProfessionalProfile $r) => $r->update(['is_verified' => true, 'verified_at' => now()])),
                Tables\Actions\Action::make('quitarVerificacion')
                    ->label('Quitar verificación')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ProfessionalProfile $r) => $r->is_verified)
                    ->action(fn (ProfessionalProfile $r) => $r->update(['is_verified' => false, 'verified_at' => null])),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProfessionalProfiles::route('/'),
        ];
    }
}
