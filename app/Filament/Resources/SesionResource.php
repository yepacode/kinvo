<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SesionResource\Pages;
use App\Filament\Resources\SesionResource\RelationManagers\InvitadosRelationManager;
use App\Mail\InvitacionSesion;
use App\Models\Sesion;
use App\Models\SesionInvitado;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class SesionResource extends Resource
{
    protected static ?string $model = Sesion::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    protected static ?string $navigationGroup = 'Fase 2 · Producto';
    protected static ?string $navigationLabel = 'Sesiones en vivo';
    protected static ?string $modelLabel = 'sesión';
    protected static ?string $pluralModelLabel = 'Sesiones';
    protected static ?string $slug = 'sesiones';
    protected static ?int $navigationSort = 23;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Sesión')->schema([
                Forms\Components\TextInput::make('title')->label('Título')->required()->maxLength(200),
                Forms\Components\Select::make('tipo')->label('Tipo')->required()
                    ->options([
                        Sesion::TIPO_WEBINAR  => 'Webinar',
                        Sesion::TIPO_TALLER   => 'Taller',
                        Sesion::TIPO_CONSULTA => 'Consulta',
                        Sesion::TIPO_OTRO     => 'Otro',
                    ])->default(Sesion::TIPO_WEBINAR),
                Forms\Components\DateTimePicker::make('scheduled_at')->label('Fecha y hora')
                    ->required()->seconds(false),
                Forms\Components\TextInput::make('duration_min')->label('Duración (min)')
                    ->numeric()->default(60)->minValue(5)->maxValue(600),
                Forms\Components\TextInput::make('link')->label('Link Zoom / Meet')
                    ->url()->maxLength(500)->columnSpanFull(),
                Forms\Components\Textarea::make('description')->label('Descripción interna')
                    ->rows(3)->columnSpanFull()
                    ->helperText('Notas para ti. NO se muestra al invitado.'),
            ])->columns(2),

            Forms\Components\Section::make('Audiencia')->schema([
                Forms\Components\Select::make('audience')->label('Rol destinatario')
                    ->options([
                        Sesion::AUDIENCE_ALL          => 'Todos (coaches + estudios)',
                        Sesion::AUDIENCE_PROFESSIONAL => 'Solo coaches (profesionales)',
                        Sesion::AUDIENCE_CONTRACTOR   => 'Solo estudios (contratantes)',
                    ])->default(Sesion::AUDIENCE_ALL)->required()
                    ->helperText('El botón "Invitar a toda la audiencia" usa este filtro. Además puedes agregar/quitar personas puntuales en la pestaña "Invitados".'),
            ])->columns(1),

            Forms\Components\Section::make('Correo (opcional — deja vacío para usar el default)')->schema([
                Forms\Components\TextInput::make('subject_override')->label('Asunto personalizado')
                    ->maxLength(255)
                    ->placeholder('Invitación: <título> · Kinvoo'),
                Forms\Components\Textarea::make('body_override')->label('Cuerpo personalizado')
                    ->rows(6)->columnSpanFull()
                    ->placeholder('Si lo dejas vacío, el correo usará el mensaje por default de Kinvoo con el título y la fecha.'),
            ])->columns(2)->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('scheduled_at')->label('Cuándo')
                    ->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Título')
                    ->description(fn ($record) => $record?->tipo)
                    ->searchable()->wrap()->limit(60),
                Tables\Columns\TextColumn::make('audience')->label('Audiencia')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        Sesion::AUDIENCE_ALL          => 'Todos',
                        Sesion::AUDIENCE_PROFESSIONAL => 'Coaches',
                        Sesion::AUDIENCE_CONTRACTOR   => 'Estudios',
                        default => $state ?? '—',
                    }),
                Tables\Columns\TextColumn::make('invitados_count')
                    ->label('Invitados')
                    ->counts('invitados')->alignEnd(),
                Tables\Columns\TextColumn::make('rsvp_stats')
                    ->label('RSVP (✓ / ✗ / …)')
                    ->state(function ($record) {
                        $c = $record->invitados()->selectRaw("
                            sum(case when rsvp='accepted' then 1 else 0 end) as ok,
                            sum(case when rsvp='declined' then 1 else 0 end) as no,
                            sum(case when rsvp='pending'  then 1 else 0 end) as p
                        ")->first();
                        return sprintf('%d / %d / %d', (int) $c->ok, (int) $c->no, (int) $c->p);
                    }),
                Tables\Columns\TextColumn::make('notified_at')->label('Último envío')
                    ->dateTime('d/m/Y H:i')->placeholder('—')->toggleable(),
            ])
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                SelectFilter::make('tipo')->options([
                    Sesion::TIPO_WEBINAR  => 'Webinar',
                    Sesion::TIPO_TALLER   => 'Taller',
                    Sesion::TIPO_CONSULTA => 'Consulta',
                    Sesion::TIPO_OTRO     => 'Otro',
                ]),
                SelectFilter::make('audience')->label('Audiencia')->options([
                    Sesion::AUDIENCE_ALL          => 'Todos',
                    Sesion::AUDIENCE_PROFESSIONAL => 'Coaches',
                    Sesion::AUDIENCE_CONTRACTOR   => 'Estudios',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('invitar_audiencia')
                    ->label('Invitar a toda la audiencia')
                    ->icon('heroicon-o-user-plus')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Invitar a toda la audiencia')
                    ->modalDescription('Agrega como invitados a TODOS los usuarios que cumplen el rol de esta sesión. No envía correos — solo los registra.')
                    ->action(function ($record) {
                        $count = static::invitarAudiencia($record);
                        Notification::make()->success()
                            ->title("Se agregaron {$count} invitados")
                            ->send();
                    }),
                Tables\Actions\Action::make('enviar_correos')
                    ->label('Enviar correos a pendientes')
                    ->icon('heroicon-o-envelope')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Enviar invitaciones por correo')
                    ->modalDescription('Enviará la invitación a los invitados que aún no la hayan recibido (envío síncrono, puede tardar unos segundos).')
                    ->action(function ($record) {
                        $enviados = static::enviarCorreosPendientes($record);
                        Notification::make()->success()
                            ->title("Se enviaron {$enviados} correos")
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** Agrega como invitados a todos los users que cumplen la audiencia. */
    public static function invitarAudiencia(Sesion $sesion): int
    {
        $agregados = 0;
        $sesion->usuariosDeAudiencia()
            ->select('id')
            ->chunkById(200, function ($users) use ($sesion, &$agregados) {
                foreach ($users as $u) {
                    $inv = SesionInvitado::firstOrCreate(
                        ['sesion_id' => $sesion->id, 'user_id' => $u->id],
                        ['rsvp' => SesionInvitado::RSVP_PENDING]
                    );
                    if ($inv->wasRecentlyCreated) {
                        $agregados++;
                    }
                }
            });
        return $agregados;
    }

    /** Envía el correo a los invitados con notified_at NULL. Retorna la cuenta. */
    public static function enviarCorreosPendientes(Sesion $sesion): int
    {
        $enviados = 0;
        $sesion->invitados()->whereNull('notified_at')->with('user')
            ->chunkById(100, function ($invitados) use ($sesion, &$enviados) {
                foreach ($invitados as $inv) {
                    if (! $inv->user) continue;
                    try {
                        Mail::to($inv->user)->send(new InvitacionSesion($inv->user, $sesion, $inv));
                        $inv->update(['notified_at' => now()]);
                        $enviados++;
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            });
        if ($enviados > 0) {
            $sesion->update(['notified_at' => now()]);
        }
        return $enviados;
    }

    public static function getRelations(): array
    {
        return [InvitadosRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSesions::route('/'),
            'create' => Pages\CreateSesion::route('/create'),
            'edit'   => Pages\EditSesion::route('/{record}/edit'),
        ];
    }
}
