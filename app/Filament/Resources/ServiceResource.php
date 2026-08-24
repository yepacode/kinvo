<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Fase 2 · Producto';
    protected static ?string $navigationLabel = 'Servicios';
    protected static ?string $modelLabel = 'servicio';
    protected static ?string $pluralModelLabel = 'Servicios';
    protected static ?int $navigationSort = 22;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Servicio')->schema([
                Forms\Components\TextInput::make('nombre')->label('Nombre')
                    ->required()->maxLength(120)
                    ->placeholder('Ej: Nutrición, Psicología, Salud, Fisioterapia…'),
                Forms\Components\TextInput::make('icono')->label('Ícono (emoji)')
                    ->maxLength(40)->placeholder('🥗')
                    ->helperText('Un emoji para mostrarlo en la tarjeta (opcional).'),
                Forms\Components\Textarea::make('descripcion')->label('Descripción')
                    ->rows(3)->columnSpanFull()
                    ->placeholder('Qué incluye el servicio y cómo se agenda.'),
                Forms\Components\TextInput::make('orden')->label('Orden')
                    ->numeric()->default(0)->helperText('Menor = aparece primero.'),
                Forms\Components\Toggle::make('activo')->label('Activo')->default(true),
            ])->columns(2),

            Forms\Components\Section::make('¿Qué planes lo incluyen?')->schema([
                Forms\Components\Select::make('plans')->label('Planes / membresías')
                    ->relationship('plans', 'nombre')
                    ->multiple()->preload()
                    ->helperText('Los miembros de estos planes verán y podrán solicitar este servicio.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->label('Servicio')
                    ->formatStateUsing(fn ($state, $record) => trim(($record->icono ? $record->icono.' ' : '').$state))
                    ->description(fn ($record) => $record?->descripcion ? \Illuminate\Support\Str::limit($record->descripcion, 60) : null)
                    ->searchable()->sortable(),
                Tables\Columns\TextColumn::make('plans_count')->label('Planes')
                    ->counts('plans')->badge()->alignCenter(),
                Tables\Columns\IconColumn::make('activo')->label('Activo')->boolean(),
                Tables\Columns\TextColumn::make('orden')->label('Orden')->numeric()->sortable()->alignEnd(),
            ])
            ->defaultSort('orden')
            ->filters([
                TernaryFilter::make('activo')->label('Estado')
                    ->placeholder('Todos')->trueLabel('Activos')->falseLabel('Inactivos'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
