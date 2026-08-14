<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Membresía';

    protected static ?string $modelLabel = 'plan';

    protected static ?string $pluralModelLabel = 'Planes';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos del plan')->schema([
                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(120)
                        ->placeholder('Esencial, Pro, Plus...'),
                    Forms\Components\Select::make('audiencia')
                        ->label('Dirigido a')
                        ->options(Plan::AUDIENCIAS)
                        ->default('individual')
                        ->required(),
                    Forms\Components\TextInput::make('precio')
                        ->label('Precio')
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0)
                        ->helperText('Déjalo vacío si el precio es "a consultar".'),
                    Forms\Components\Select::make('periodo')
                        ->label('Periodo')
                        ->options(Plan::PERIODOS)
                        ->default('mensual')
                        ->required(),
                    Forms\Components\TextInput::make('moneda')
                        ->label('Moneda')
                        ->default('MXN')
                        ->maxLength(3),
                ])->columns(2),

                Forms\Components\Section::make('Contenido')->schema([
                    Forms\Components\Textarea::make('descripcion')
                        ->label('Descripción')
                        ->rows(2)
                        ->maxLength(500),
                    Forms\Components\TagsInput::make('beneficios')
                        ->label('Beneficios / incluye')
                        ->helperText('Escribe cada beneficio y presiona Enter.')
                        ->placeholder('Nuevo beneficio'),
                    Forms\Components\Textarea::make('cobertura')
                        ->label('Cobertura')
                        ->rows(2)
                        ->maxLength(500),
                ]),

                Forms\Components\Section::make('Pasarela de pago')
                    ->description('Kinvoo soporta MercadoPago (default en México) y Stripe (alternativa). MercadoPago usa el "precio" de arriba directamente — no necesita más config. Stripe sí requiere el Price ID que creas en su dashboard.')
                    ->schema([
                        Forms\Components\Toggle::make('is_recurring')
                            ->label('Es recurrente')
                            ->helperText('Suscripción periódica (recomendado). Desactiva sólo si es un pago único.')
                            ->default(true),
                        Forms\Components\Select::make('interval')
                            ->label('Periodicidad del cobro')
                            ->options(['month' => 'Mensual', 'year' => 'Anual'])
                            ->default('month')
                            ->helperText('MercadoPago lo interpreta como frequency (1 o 12 meses). En Stripe debe coincidir con el interval del Price.'),
                        Forms\Components\TextInput::make('provider_price_id')
                            ->label('Stripe Price ID (opcional)')
                            ->placeholder('price_1AbCd...')
                            ->helperText('SOLO si vas a usar Stripe: se obtiene en Stripe → Products → tu producto → Prices → copiar (formato price_...). Si usas MercadoPago, déjalo vacío.')
                            ->maxLength(120),
                    ])->columns(3)->collapsible(),

                Forms\Components\Section::make('Visibilidad')->schema([
                    Forms\Components\Toggle::make('destacado')
                        ->label('Destacado')
                        ->helperText('Resáltalo como plan recomendado.'),
                    Forms\Components\Toggle::make('activo')
                        ->label('Activo')
                        ->default(true),
                    Forms\Components\TextInput::make('orden')
                        ->label('Orden')
                        ->numeric()
                        ->default(0)
                        ->helperText('Menor número aparece primero.'),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('orden')
            ->defaultSort('orden')
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->description(fn (Plan $p) => $p->audienciaLabel())
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('audiencia')
                    ->label('Dirigido a')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Plan::AUDIENCIAS[$state] ?? $state)
                    ->color(fn (string $state) => $state === 'estudio' ? 'info' : 'success'),
                Tables\Columns\TextColumn::make('precio')
                    ->label('Precio')
                    ->money(fn (Plan $p) => $p->moneda ?: 'MXN')
                    ->placeholder('A consultar')
                    ->sortable(),
                Tables\Columns\TextColumn::make('periodo')
                    ->label('Periodo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Plan::PERIODOS[$state] ?? $state),
                Tables\Columns\IconColumn::make('destacado')
                    ->label('Destacado')
                    ->boolean(),
                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('audiencia')
                    ->label('Dirigido a')
                    ->options(Plan::AUDIENCIAS),
                Tables\Filters\TernaryFilter::make('activo')->label('Activo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Plan $record, Tables\Actions\DeleteAction $action) {
                        // FK users.membership_plan_id es nullOnDelete: sin este guard,
                        // borrar un plan dejaría a los contratantes con plan=null pero
                        // `membership_expires_at` seguirá activa (membresía "fantasma").
                        $activos = \App\Models\User::where('membership_plan_id', $record->id)->count();
                        if ($activos > 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('No se puede eliminar')
                                ->body('"'.$record->nombre.'" tiene '.$activos.' usuario(s) con membresía activa. Marca el plan como "Inactivo" (queda oculto en la página pública, sin romper las membresías existentes).')
                                ->danger()->persistent()->send();
                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (\Illuminate\Support\Collection $records, Tables\Actions\DeleteBulkAction $action) {
                            $ids = $records->pluck('id')->all();
                            $activos = \App\Models\User::whereIn('membership_plan_id', $ids)->count();
                            if ($activos > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Hay planes con membresías activas')
                                    ->body('Hay '.$activos.' usuario(s) con membresía en alguno de los planes seleccionados. Marca los planes como "Inactivos" en vez de eliminarlos.')
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
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
