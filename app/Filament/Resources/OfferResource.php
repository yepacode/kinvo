<?php

namespace App\Filament\Resources;

use App\Enums\RolUsuario;
use App\Filament\Resources\OfferResource\Pages;
use App\Models\Offer;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OfferResource extends Resource
{
    protected static ?string $model = Offer::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Fase 2 · Cobros';
    protected static ?string $navigationLabel = 'Ofertas de trabajo';
    protected static ?string $modelLabel = 'oferta';
    protected static ?string $pluralModelLabel = 'Ofertas';
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Publica quién')->schema([
                Forms\Components\Select::make('contractor_user_id')
                    ->label('Estudio contratante')
                    ->options(fn () => User::whereIn('nivel', [RolUsuario::Contractor->value, RolUsuario::Admin->value])
                        ->orderBy('name')->pluck('name', 'id'))
                    ->searchable()->required(),
                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options([
                        Offer::STATUS_DRAFT => 'Borrador',
                        Offer::STATUS_PUBLISHED => 'Publicada',
                        Offer::STATUS_CLOSED => 'Cerrada',
                        Offer::STATUS_EXPIRED => 'Vencida',
                    ])
                    ->default(Offer::STATUS_DRAFT)->required(),
            ])->columns(2),

            Forms\Components\Section::make('Contenido')->schema([
                Forms\Components\TextInput::make('title')->label('Título')->required()->maxLength(180),
                Forms\Components\TextInput::make('slug')->label('Slug (opcional, se autogenera)')
                    ->helperText('Déjalo vacío para generarlo del título.')->maxLength(220),
                Forms\Components\Select::make('discipline_id')->label('Disciplina')
                    ->relationship('discipline', 'nombre')->searchable(),
                Forms\Components\Select::make('location_id')->label('Ubicación')
                    ->relationship('location', 'nombre')->searchable(),
                Forms\Components\Textarea::make('description')->label('Descripción')
                    ->required()->rows(4)->columnSpanFull(),
                Forms\Components\Textarea::make('requirements')->label('Requisitos')
                    ->rows(3)->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('Compensación y modalidad')->schema([
                Forms\Components\TextInput::make('salary_min_cents')->label('Salario mínimo (centavos)')->numeric(),
                Forms\Components\TextInput::make('salary_max_cents')->label('Salario máximo (centavos)')->numeric(),
                Forms\Components\TextInput::make('salary_currency')->label('Moneda')
                    ->default('MXN')->maxLength(3)->required(),
                Forms\Components\Select::make('salary_period')->label('Periodo')
                    ->options(['hour' => 'Hora', 'month' => 'Mes', 'year' => 'Año', 'project' => 'Proyecto'])
                    ->default('month')->required(),
                Forms\Components\Select::make('modality')->label('Modalidad')
                    ->options(['presencial' => 'Presencial', 'remoto' => 'Remoto', 'hibrido' => 'Híbrido'])
                    ->default('presencial')->required(),
                Forms\Components\Select::make('contract_type')->label('Tipo de contrato')
                    ->options(['full_time' => 'Tiempo completo', 'part_time' => 'Medio tiempo', 'freelance' => 'Freelance']),
            ])->columns(3),

            Forms\Components\Section::make('Vigencia')->schema([
                Forms\Components\DateTimePicker::make('published_at')->label('Publicada el'),
                Forms\Components\DatePicker::make('expires_on')->label('Vence el'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Título')
                    ->description(fn ($record) => $record->slug)
                    ->searchable()->sortable()->wrap()->limit(60),
                Tables\Columns\TextColumn::make('contractor.name')->label('Estudio')
                    ->searchable(['users.name'])->sortable(),
                Tables\Columns\TextColumn::make('discipline.nombre')->label('Disciplina')->placeholder('—'),
                Tables\Columns\TextColumn::make('location.nombre')->label('Ubicación')->placeholder('—'),
                Tables\Columns\TextColumn::make('modality')->label('Modalidad')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'presencial' => 'Presencial', 'remoto' => 'Remoto', 'hibrido' => 'Híbrido',
                        default => $state ?? '—',
                    }),
                Tables\Columns\TextColumn::make('status')->label('Estado')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        Offer::STATUS_PUBLISHED => 'success',
                        Offer::STATUS_DRAFT => 'gray',
                        Offer::STATUS_CLOSED => 'warning',
                        Offer::STATUS_EXPIRED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        Offer::STATUS_DRAFT => 'Borrador',
                        Offer::STATUS_PUBLISHED => 'Publicada',
                        Offer::STATUS_CLOSED => 'Cerrada',
                        Offer::STATUS_EXPIRED => 'Vencida',
                        default => $state ?? '—',
                    }),
                Tables\Columns\TextColumn::make('applications_count')->label('Postulaciones')
                    ->numeric()->sortable()->alignEnd(),
                Tables\Columns\TextColumn::make('published_at')->label('Publicada')
                    ->dateTime('d/m/Y')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('expires_on')->label('Vence')
                    ->date('d/m/Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('Estado')->options([
                    Offer::STATUS_DRAFT => 'Borrador',
                    Offer::STATUS_PUBLISHED => 'Publicada',
                    Offer::STATUS_CLOSED => 'Cerrada',
                    Offer::STATUS_EXPIRED => 'Vencida',
                ]),
                SelectFilter::make('modality')->label('Modalidad')->options([
                    'presencial' => 'Presencial', 'remoto' => 'Remoto', 'hibrido' => 'Híbrido',
                ]),
                SelectFilter::make('discipline_id')->label('Disciplina')
                    ->relationship('discipline', 'nombre'),
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
            'index' => Pages\ListOffers::route('/'),
            'create' => Pages\CreateOffer::route('/create'),
            'edit' => Pages\EditOffer::route('/{record}/edit'),
        ];
    }
}
