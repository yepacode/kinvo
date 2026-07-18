<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DisciplineResource\Pages;
use App\Models\Discipline;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DisciplineResource extends Resource
{
    protected static ?string $model = Discipline::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Taxonomías';

    protected static ?string $modelLabel = 'disciplina';

    protected static ?string $pluralModelLabel = 'Disciplinas';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->label('Nombre (ES)')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('nombre_en')
                    ->label('Nombre (EN)')
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->helperText('Se genera automáticamente si lo dejas vacío.')
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
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre (ES)')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nombre_en')
                    ->label('Nombre (EN)')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('professionals_count')
                    ->label('Profesionales')
                    ->counts('professionals')
                    ->badge(),
                Tables\Columns\IconColumn::make('activo')
                    ->label('Activa')
                    ->boolean(),
            ])
            ->defaultSort('nombre')
            ->filters([
                Tables\Filters\TernaryFilter::make('activo')->label('Activa'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Discipline $record, Tables\Actions\DeleteAction $action) {
                        // Guard adicional al hook `deleting` del modelo: notificamos
                        // al admin en vez de fallar silenciosamente si está en uso.
                        $usos = $record->professionals()->count();
                        if ($usos > 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('No se puede eliminar')
                                ->body('"'.$record->nombre.'" tiene '.$usos.' profesional(es) asociados. Marca la disciplina como "Inactiva" o desasocia primero a los perfiles.')
                                ->danger()->persistent()->send();
                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (\Illuminate\Support\Collection $records, Tables\Actions\DeleteBulkAction $action) {
                            $enUso = $records->filter(fn (Discipline $d) => $d->professionals()->exists());
                            if ($enUso->isNotEmpty()) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Hay disciplinas en uso')
                                    ->body('No se puede eliminar en masa: '.$enUso->pluck('nombre')->join(', ').'. Marca como "Inactivas" o desasocia primero.')
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
            'index' => Pages\ListDisciplines::route('/'),
            'create' => Pages\CreateDiscipline::route('/create'),
            'edit' => Pages\EditDiscipline::route('/{record}/edit'),
        ];
    }
}
