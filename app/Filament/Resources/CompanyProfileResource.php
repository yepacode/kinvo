<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyProfileResource\Pages;
use App\Models\CompanyProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Admin gestiona los estudios. Especialmente:
 *  - fija `max_coach_slots` (petición cliente: "el admin determina cuántos
 *    cupos tiene cada estudio conforme a lo pagado").
 *  - `wellness_notes` como comentario interno del admin sobre el estudio.
 */
class CompanyProfileResource extends Resource
{
    protected static ?string $model = CompanyProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Fase 2 · Producto';
    protected static ?string $modelLabel = 'estudio';
    protected static ?string $pluralModelLabel = 'Estudios (cupos, notas)';
    protected static ?int $navigationSort = 25;

    public static function canCreate(): bool
    {
        return false; // Se crean via registro público.
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('company_name')
                ->label('Nombre del estudio')
                ->disabled()
                ->dehydrated(false),
            Forms\Components\Placeholder::make('user_email')
                ->label('Correo del dueño')
                ->content(fn (?CompanyProfile $record) => $record?->user?->email ?? '—'),
            Forms\Components\TextInput::make('max_coach_slots')
                ->label('Cupos de coaches asignados')
                ->helperText(fn (?CompanyProfile $record) => 'Máximo de coaches activos que puede tener este estudio. Deja vacío = sin límite.'
                    .($record ? ' Coaches activos actualmente: '.\App\Models\TeamMember::where('contractor_user_id', $record->user_id)
                        ->where('status', \App\Models\TeamMember::STATUS_ACTIVE)->count() : ''))
                ->numeric()
                ->minValue(0)
                ->maxValue(9999)
                // MED-H7 · Advertir si el nuevo tope queda por debajo de
                // coaches activos. No los saca automáticamente (evita
                // decisiones destructivas silenciosas): sólo bloquea el guardado
                // hasta que el admin ajuste (o convenza al estudio de remover
                // manualmente algunos coaches antes de bajar el cupo).
                ->rules([
                    function (\Filament\Forms\Get $get) {
                        return function (string $attr, $value, \Closure $fail) use ($get) {
                            if ($value === null || $value === '') return;
                            // Filament pasa el record en el livewire state; obtenemos el user_id
                            // desde la ruta actual (edit) para contar activos reales.
                            $userId = request()->route('record');
                            if (! $userId) return;
                            $profile = \App\Models\CompanyProfile::where('slug', $userId)->first();
                            if (! $profile) return;
                            $activos = \App\Models\TeamMember::where('contractor_user_id', $profile->user_id)
                                ->where('status', \App\Models\TeamMember::STATUS_ACTIVE)
                                ->count();
                            if ((int) $value < $activos) {
                                $fail("Este estudio tiene {$activos} coaches activos — no puedes bajar el cupo a {$value}. Pide al estudio que remueva coaches primero.");
                            }
                        };
                    },
                ]),
            Forms\Components\Textarea::make('admin_notes')
                ->label('Nota interna del admin')
                ->helperText('Solo visible para el admin. Sirve para llevar contexto del estudio. El estudio NO ve este campo.')
                ->maxLength(2000)
                ->rows(3),
            Forms\Components\Placeholder::make('wellness_notes_ro')
                ->label('Nota del estudio sobre su equipo (solo lectura)')
                ->content(fn (?CompanyProfile $record) => $record?->wellness_notes ?: '—')
                ->extraAttributes(['class' => 'text-sm text-warmgray']),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn () => CompanyProfile::query()->with('user'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('company_name')->label('Estudio')->searchable()->weight('medium'),
                Tables\Columns\TextColumn::make('user.email')->label('Correo')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('coaches_activos')
                    ->label('Coaches activos')
                    ->state(fn (CompanyProfile $r) => \App\Models\TeamMember::where('contractor_user_id', $r->user_id)
                        ->where('status', \App\Models\TeamMember::STATUS_ACTIVE)->count())
                    ->badge()->color('success'),
                Tables\Columns\TextColumn::make('max_coach_slots')
                    ->label('Cupos')
                    ->formatStateUsing(fn ($state) => $state === null ? '∞' : $state)
                    ->badge()
                    ->color(fn ($state) => $state === null ? 'gray' : 'info'),
                Tables\Columns\TextColumn::make('estado')->label('Estado (MX)')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('Alta')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('sin_cupos_asignados')
                    ->label('Sin cupos asignados')
                    ->query(fn (Builder $q) => $q->whereNull('max_coach_slots')),
                Tables\Filters\Filter::make('con_cupos_asignados')
                    ->label('Con cupos asignados')
                    ->query(fn (Builder $q) => $q->whereNotNull('max_coach_slots')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Asignar cupos / nota'),
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanyProfiles::route('/'),
            'edit'  => Pages\EditCompanyProfile::route('/{record}/edit'),
            'view'  => Pages\ViewCompanyProfile::route('/{record}'),
        ];
    }
}
