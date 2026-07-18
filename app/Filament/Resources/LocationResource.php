<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Models\Location;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Taxonomías';

    protected static ?string $modelLabel = 'ubicación';

    protected static ?string $pluralModelLabel = 'Ubicaciones';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('ciudad')
                    ->label('Ciudad')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('region')
                    ->label('Región / Estado')
                    ->maxLength(255),
                Forms\Components\TextInput::make('pais')
                    ->label('País')
                    ->default('México')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('activo')
                    ->label('Activa')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ciudad')
                    ->label('Ciudad')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('region')
                    ->label('Región / Estado')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pais')
                    ->label('País')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('activo')
                    ->label('Activa')
                    ->boolean(),
            ])
            ->defaultSort('ciudad')
            ->filters([
                Tables\Filters\TernaryFilter::make('activo')->label('Activa'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Location $record, Tables\Actions\DeleteAction $action) {
                        // FK con nullOnDelete: sin este guard, borrar una ubicación
                        // dejaría los perfiles con `location_id=null` en silencio.
                        $prof = $record->professionalProfiles()->count();
                        $emp = $record->companyProfiles()->count();
                        $total = $prof + $emp;
                        if ($total > 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('No se puede eliminar')
                                ->body('"'.$record->ciudad.'" tiene '.$total.' perfil(es) usándola. Marca la ubicación como "Inactiva" o reasigna los perfiles primero.')
                                ->danger()->persistent()->send();
                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (\Illuminate\Support\Collection $records, Tables\Actions\DeleteBulkAction $action) {
                            $enUso = $records->filter(fn (Location $l) => $l->professionalProfiles()->exists() || $l->companyProfiles()->exists());
                            if ($enUso->isNotEmpty()) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Hay ubicaciones en uso')
                                    ->body('No se puede eliminar en masa: '.$enUso->pluck('ciudad')->join(', ').'. Marca como "Inactivas" o reasigna primero.')
                                    ->danger()->persistent()->send();
                                $action->cancel();
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
        ];
    }
}
