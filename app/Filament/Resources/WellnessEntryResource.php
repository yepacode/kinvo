<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WellnessEntryResource\Pages;
use App\Models\User;
use App\Models\WellnessEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WellnessEntryResource extends Resource
{
    protected static ?string $model = WellnessEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';
    protected static ?string $navigationGroup = 'Fase 2 · Producto';
    protected static ?string $navigationLabel = 'Expediente de cuidado';
    protected static ?string $modelLabel = 'entrada de expediente';
    protected static ?string $pluralModelLabel = 'Expediente';
    protected static ?int $navigationSort = 22;

    private static function tipos(): array
    {
        return [
            WellnessEntry::TYPE_TELEMEDICINE => 'Telemedicina',
            WellnessEntry::TYPE_PHYSIO       => 'Fisioterapia',
            WellnessEntry::TYPE_TALK         => 'Charla / capacitación',
            WellnessEntry::TYPE_INSURANCE    => 'Póliza / seguro',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Beneficiario')->schema([
                Forms\Components\Select::make('professional_user_id')
                    ->label('Coach / profesional')
                    ->options(fn () => User::where('rol', 'profesional')
                        ->orderBy('name')->pluck('name', 'id'))
                    ->searchable()->required(),
                Forms\Components\Select::make('created_by_admin_id')
                    ->label('Registrado por (admin)')
                    ->options(fn () => User::where('rol', 'admin')
                        ->orderBy('name')->pluck('name', 'id'))
                    ->searchable(),
            ])->columns(2),

            Forms\Components\Section::make('Datos del beneficio')->schema([
                Forms\Components\Select::make('type')->label('Tipo')
                    ->options(self::tipos())->required(),
                Forms\Components\DatePicker::make('occurred_on')->label('Fecha')->required(),
                Forms\Components\TextInput::make('provider')->label('Proveedor')
                    ->placeholder('Ej: Dr. Salinas, Fisio Roldán, Kinvoo Sessions')
                    ->maxLength(180),
                Forms\Components\DatePicker::make('valid_until')
                    ->label('Vigencia hasta (solo pólizas)')
                    ->helperText('Rellenar solo si es una póliza o seguro.'),
                Forms\Components\Textarea::make('notes')->label('Notas')
                    ->rows(3)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('occurred_on')->label('Fecha')
                    ->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('professional.name')->label('Coach')
                    ->description(fn ($record) => $record?->professional?->email)
                    ->searchable(['users.name', 'users.email'])->sortable(),
                Tables\Columns\TextColumn::make('type')->label('Tipo')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        WellnessEntry::TYPE_TELEMEDICINE => 'info',
                        WellnessEntry::TYPE_PHYSIO => 'success',
                        WellnessEntry::TYPE_TALK => 'warning',
                        WellnessEntry::TYPE_INSURANCE => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => self::tipos()[$state] ?? ($state ?? '—')),
                Tables\Columns\TextColumn::make('provider')->label('Proveedor')
                    ->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('valid_until')->label('Vigencia')
                    ->date('d/m/Y')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('createdByAdmin.name')->label('Registró')
                    ->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('occurred_on', 'desc')
            ->filters([
                SelectFilter::make('type')->label('Tipo')->options(self::tipos()),
                SelectFilter::make('professional_user_id')->label('Coach')
                    ->options(fn () => User::where('rol', 'profesional')
                        ->orderBy('name')->limit(200)->pluck('name', 'id'))
                    ->searchable(),
                Filter::make('occurred')
                    ->form([
                        Forms\Components\DatePicker::make('desde')->label('Ocurrió desde'),
                        Forms\Components\DatePicker::make('hasta')->label('Ocurrió hasta'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['desde'] ?? null, fn ($q, $d) => $q->whereDate('occurred_on', '>=', $d))
                        ->when($data['hasta'] ?? null, fn ($q, $d) => $q->whereDate('occurred_on', '<=', $d))),
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
            'index' => Pages\ListWellnessEntries::route('/'),
            'create' => Pages\CreateWellnessEntry::route('/create'),
            'edit' => Pages\EditWellnessEntry::route('/{record}/edit'),
        ];
    }
}
