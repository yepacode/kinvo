<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfferResource\Pages;
use App\Filament\Resources\OfferResource\RelationManagers;
use App\Models\Offer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OfferResource extends Resource
{
    protected static ?string $model = Offer::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Fase 2 · Producto';
    protected static ?string $navigationLabel = 'Ofertas de trabajo';
    protected static ?string $modelLabel = 'oferta';
    protected static ?string $pluralModelLabel = 'Ofertas';
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('contractor_user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('discipline_id')
                    ->relationship('discipline', 'id'),
                Forms\Components\Select::make('location_id')
                    ->relationship('location', 'id'),
                Forms\Components\TextInput::make('slug')
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('requirements')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('salary_min_cents')
                    ->numeric(),
                Forms\Components\TextInput::make('salary_max_cents')
                    ->numeric(),
                Forms\Components\TextInput::make('salary_currency')
                    ->required(),
                Forms\Components\TextInput::make('salary_period')
                    ->required(),
                Forms\Components\TextInput::make('modality')
                    ->required(),
                Forms\Components\TextInput::make('contract_type'),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\DateTimePicker::make('published_at'),
                Forms\Components\DatePicker::make('expires_on'),
                Forms\Components\TextInput::make('applications_count')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('contractor_user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('discipline.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('location.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('salary_min_cents')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('salary_max_cents')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('salary_currency')
                    ->searchable(),
                Tables\Columns\TextColumn::make('salary_period')
                    ->searchable(),
                Tables\Columns\TextColumn::make('modality')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contract_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expires_on')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('applications_count')
                    ->numeric()
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
            'index' => Pages\ListOffers::route('/'),
            'create' => Pages\CreateOffer::route('/create'),
            'edit' => Pages\EditOffer::route('/{record}/edit'),
        ];
    }
}
