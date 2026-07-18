<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CertificationResource\Pages;
use App\Models\Certification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CertificationResource extends Resource
{
    protected static ?string $model = Certification::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Taxonomías';

    protected static ?string $modelLabel = 'certificación';

    protected static ?string $pluralModelLabel = 'Certificaciones';

    protected static ?int $navigationSort = 2;

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
                // Certification es catálogo de referencia (los perfiles capturan sus
                // certificaciones como texto libre); no está enlazado a perfiles,
                // por lo que el borrado no arrastra datos.
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertifications::route('/'),
            'create' => Pages\CreateCertification::route('/create'),
            'edit' => Pages\EditCertification::route('/{record}/edit'),
        ];
    }
}
