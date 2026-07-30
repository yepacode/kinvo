<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WellnessEntryResource\Pages;
use App\Filament\Resources\WellnessEntryResource\RelationManagers;
use App\Models\WellnessEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WellnessEntryResource extends Resource
{
    protected static ?string $model = WellnessEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';
    protected static ?string $navigationGroup = 'Fase 2 · Producto';
    protected static ?string $navigationLabel = 'Expediente de cuidado';
    protected static ?string $modelLabel = 'entrada de expediente';
    protected static ?string $pluralModelLabel = 'Expediente';
    protected static ?int $navigationSort = 22;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('professional_user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('created_by_admin_id')
                    ->numeric(),
                Forms\Components\TextInput::make('type')
                    ->required(),
                Forms\Components\DatePicker::make('occurred_on')
                    ->required(),
                Forms\Components\TextInput::make('provider'),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('valid_until'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('professional_user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_by_admin_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('occurred_on')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('provider')
                    ->searchable(),
                Tables\Columns\TextColumn::make('valid_until')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWellnessEntries::route('/'),
            'create' => Pages\CreateWellnessEntry::route('/create'),
            'edit' => Pages\EditWellnessEntry::route('/{record}/edit'),
        ];
    }
}
