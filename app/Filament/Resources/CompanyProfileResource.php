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
    protected static ?string $pluralModelLabel = 'Estudios';
    protected static ?int $navigationSort = 25;

    public static function canCreate(): bool
    {
        return false; // Se crean via registro público.
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            // Petición Karla 27-ago: el admin necesita poder EDITAR el perfil
            // del estudio (antes casi todo estaba solo lectura). Reestructurado
            // en 4 secciones editables + 1 solo lectura para la nota que el
            // estudio deja de su propio equipo (esa sigue siendo del estudio,
            // el admin la lee pero no la escribe).
            Forms\Components\Section::make('Identidad del estudio')
                ->description('Datos básicos que aparecen en el perfil público.')
                ->schema([
                    Forms\Components\TextInput::make('company_name')
                        ->label('Nombre del estudio')
                        ->required()
                        ->maxLength(180),
                    Forms\Components\Placeholder::make('user_email')
                        ->label('Correo del dueño (no editable)')
                        ->content(fn (?CompanyProfile $record) => $record?->user?->email ?? '—'),
                    Forms\Components\TextInput::make('sector')
                        ->label('Sector')
                        ->placeholder('Ej: Estudio boutique, Gimnasio, Marca')
                        ->maxLength(120),
                    Forms\Components\TextInput::make('website')
                        ->label('Sitio web')
                        ->url()
                        ->prefix('https://')
                        ->maxLength(200),
                    Forms\Components\Textarea::make('disciplines_text')
                        ->label('Disciplinas que ofrece')
                        ->placeholder('Ej: Yoga, Pilates, HIIT, Barré')
                        ->rows(2)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('description')
                        ->label('Descripción / bio del estudio')
                        ->rows(4)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Ubicación (México)')
                ->schema([
                    Forms\Components\Select::make('estado')
                        ->label('Estado')
                        ->options(array_combine(CompanyProfile::ESTADOS_MX, CompanyProfile::ESTADOS_MX))
                        ->searchable(),
                    Forms\Components\TextInput::make('colonia')
                        ->label('Colonia')
                        ->maxLength(120),
                    Forms\Components\TextInput::make('address')
                        ->label('Dirección')
                        ->maxLength(220)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('postal_code')
                        ->label('C.P.')
                        ->maxLength(10),
                    Forms\Components\Toggle::make('show_address')
                        ->label('Mostrar dirección exacta en el perfil público'),
                ])->columns(2)->collapsible(),

            Forms\Components\Section::make('Contacto')
                ->schema([
                    Forms\Components\TextInput::make('contact_name')
                        ->label('Persona de contacto')
                        ->maxLength(120),
                    Forms\Components\TextInput::make('contact_phone')
                        ->label('Teléfono de contacto')
                        ->tel()
                        ->maxLength(30),
                    Forms\Components\TextInput::make('contact_email')
                        ->label('Correo de contacto')
                        ->email()
                        ->maxLength(180)
                        ->columnSpanFull(),
                ])->columns(2)->collapsible()->collapsed(),

            Forms\Components\Section::make('Membresía y notas internas')
                ->schema([
                    Forms\Components\TextInput::make('max_coach_slots')
                        ->label('Cupos de coaches asignados')
                        ->helperText(fn (?CompanyProfile $record) => 'Máximo de coaches activos que puede tener este estudio. Deja vacío = sin límite.'
                            .($record ? ' Coaches activos actualmente: '.\App\Models\TeamMember::where('contractor_user_id', $record->user_id)
                                ->where('status', \App\Models\TeamMember::STATUS_ACTIVE)->count() : ''))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(9999)
                        // MED-H7 · Advertir si el nuevo tope queda por debajo de
                        // coaches activos. Bloquea el guardado; el admin debe pedirle
                        // al estudio que remueva coaches antes de bajar el cupo.
                        ->rules([
                            function () {
                                return function (string $attr, $value, \Closure $fail) {
                                    if ($value === null || $value === '') return;
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
                        ->helperText('Solo visible para el admin. El estudio NO ve este campo.')
                        ->maxLength(2000)
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\Placeholder::make('wellness_notes_ro')
                        ->label('Nota del estudio sobre su equipo (solo lectura)')
                        ->content(fn (?CompanyProfile $record) => $record?->wellness_notes ?: '—')
                        ->extraAttributes(['class' => 'text-sm text-warmgray'])
                        ->columnSpanFull(),
                ])->columns(2),
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
