<?php

namespace App\Filament\Resources;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $modelLabel = 'usuario';

    protected static ?string $pluralModelLabel = 'Usuarios';

    protected static ?int $navigationSort = 1;

    /** El owner gestiona miembros (profesionales y contratantes), no otros admins. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('nivel', '!=', RolUsuario::Admin->value);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->where('estado', EstadoUsuario::Pendiente->value)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Cuenta')->schema([
                Infolists\Components\TextEntry::make('name')->label('Nombre'),
                Infolists\Components\TextEntry::make('email')->label('Correo')->copyable(),
                Infolists\Components\TextEntry::make('nivel')->label('Rol')
                    ->formatStateUsing(fn (RolUsuario $state) => $state->label()),
                Infolists\Components\TextEntry::make('estado')->label('Estado')
                    ->formatStateUsing(fn (EstadoUsuario $state) => $state->label()),
                Infolists\Components\TextEntry::make('created_at')->label('Registro')->dateTime('d/m/Y H:i'),
            ])->columns(2),

            Infolists\Components\Section::make('Perfil profesional')
                ->visible(fn (User $record) => $record->esProfesional() && $record->professionalProfile)
                ->schema([
                    Infolists\Components\ImageEntry::make('professionalProfile.photo_path')->label('Foto')
                        ->disk('public')->circular()->placeholder('—'),
                    Infolists\Components\TextEntry::make('professionalProfile.full_name')->label('Nombre completo')->placeholder('—'),
                    Infolists\Components\TextEntry::make('professionalProfile.headline')->label('Titular')->placeholder('—'),
                    Infolists\Components\TextEntry::make('professionalProfile.birthdate')->label('Nacimiento')
                        ->date('d/m/Y')->placeholder('—'),
                    Infolists\Components\TextEntry::make('professionalProfile.years_experience')->label('Experiencia')
                        ->suffix(' años')->placeholder('—'),
                    Infolists\Components\TextEntry::make('professionalProfile.modalidad')->label('Modalidad')
                        ->formatStateUsing(fn ($state) => $state?->label())->placeholder('—'),
                    Infolists\Components\TextEntry::make('professionalProfile.location.ciudad')->label('Ubicación')->placeholder('—'),
                    Infolists\Components\TextEntry::make('disciplinas_lista')->label('Disciplinas')
                        ->state(fn (User $record) => $record->professionalProfile->disciplines->pluck('nombre')->implode(', ') ?: '—'),
                    Infolists\Components\TextEntry::make('professionalProfile.phone')->label('Teléfono (privado)')
                        ->copyable()->placeholder('—'),
                    Infolists\Components\TextEntry::make('professionalProfile.bio')->label('Bio')
                        ->columnSpanFull()->placeholder('—'),
                    Infolists\Components\TextEntry::make('idiomas')->label('Idiomas')
                        ->state(fn (User $record) => implode(', ', $record->professionalProfile->idiomasLegibles()) ?: '—'),
                    Infolists\Components\TextEntry::make('disponibilidad')->label('Disponibilidad')
                        ->state(fn (User $record) => collect($record->professionalProfile->disponibilidadPorDia())
                            ->map(fn ($f, $d) => "$d: $f")->implode(' · ') ?: '—'),
                    Infolists\Components\TextEntry::make('professionalProfile.certifications_text')->label('Certificaciones')
                        ->columnSpanFull()->placeholder('—'),
                    Infolists\Components\TextEntry::make('professionalProfile.media_url')->label('Multimedia')
                        ->url(fn (User $record) => $record->professionalProfile->media_url)
                        ->openUrlInNewTab()->placeholder('—'),
                    Infolists\Components\TextEntry::make('certificacion_adjunta')->label('Adjunto de certificación')
                        ->state(fn (User $record) => $record->professionalProfile->certification_file_path ? '📎 Descargar archivo' : 'Sin adjunto')
                        ->url(fn (User $record) => $record->professionalProfile->certification_file_path
                            ? route('admin.certificacion', $record->professionalProfile)
                            : null)
                        ->openUrlInNewTab()
                        ->color(fn (User $record) => $record->professionalProfile->certification_file_path ? 'primary' : 'gray'),
                ])->columns(2),

            Infolists\Components\Section::make('Perfil del estudio')
                ->visible(fn (User $record) => $record->esContratante() && $record->companyProfile)
                ->schema([
                    Infolists\Components\TextEntry::make('companyProfile.company_name')->label('Estudio / gym')->placeholder('—'),
                    Infolists\Components\TextEntry::make('companyProfile.disciplines_text')->label('Disciplina')->placeholder('—'),
                    Infolists\Components\TextEntry::make('companyProfile.estado')->label('Entidad (ubicación)')->placeholder('—'),
                    Infolists\Components\TextEntry::make('companyProfile.postal_code')->label('CP')->placeholder('—'),
                    Infolists\Components\TextEntry::make('companyProfile.address')->label('Dirección')->columnSpanFull()->placeholder('—'),
                    Infolists\Components\TextEntry::make('companyProfile.contact_name')->label('Contacto (privado)')->placeholder('—'),
                    Infolists\Components\TextEntry::make('companyProfile.contact_phone')->label('Teléfono (privado)')->copyable()->placeholder('—'),
                    Infolists\Components\TextEntry::make('companyProfile.contact_email')->label('Email (privado)')->copyable()->placeholder('—'),
                    Infolists\Components\TextEntry::make('companyProfile.media_url')->label('Multimedia')
                        ->url(fn (User $record) => $record->companyProfile->media_url)->openUrlInNewTab()->placeholder('—'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->description(fn (User $u) => $u->email)
                    ->searchable(),
                Tables\Columns\TextColumn::make('nivel')
                    ->label('Rol')
                    ->badge()
                    ->formatStateUsing(fn (RolUsuario $state) => $state->label())
                    ->color(fn (RolUsuario $state) => $state === RolUsuario::Professional ? 'success' : 'info'),
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (EstadoUsuario $state) => $state->label())
                    ->color(fn (EstadoUsuario $state) => match ($state) {
                        EstadoUsuario::Pendiente => 'warning',
                        EstadoUsuario::Activo => 'success',
                        EstadoUsuario::Suspendido => 'danger',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registro')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('nivel')
                    ->label('Rol')
                    ->options([
                        RolUsuario::Professional->value => 'Profesional',
                        RolUsuario::Contractor->value => 'Contratante',
                    ]),
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        EstadoUsuario::Pendiente->value => 'Pendiente',
                        EstadoUsuario::Activo->value => 'Activo',
                        EstadoUsuario::Suspendido->value => 'Suspendido',
                    ]),
                Tables\Filters\Filter::make('rango')
                    ->label('Fecha de registro')
                    ->form([
                        DatePicker::make('desde')->label('Desde'),
                        DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['desde'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['hasta'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '<=', $d)))
                    ->indicateUsing(function (array $data): array {
                        $ind = [];
                        if ($data['desde'] ?? null) {
                            $ind[] = 'Desde '.$data['desde'];
                        }
                        if ($data['hasta'] ?? null) {
                            $ind[] = 'Hasta '.$data['hasta'];
                        }

                        return $ind;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Ver'),
                Tables\Actions\Action::make('aprobar')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (User $u) => $u->estado === EstadoUsuario::Pendiente)
                    ->action(function (User $u) {
                        $u->forceFill(['estado' => EstadoUsuario::Activo])->save();
                        $u->notify(new \App\Notifications\CuentaAprobadaNotification());
                    }),
                Tables\Actions\Action::make('suspender')
                    ->label('Suspender')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $u) => $u->estado === EstadoUsuario::Activo)
                    ->action(function (User $u) {
                        $u->forceFill(['estado' => EstadoUsuario::Suspendido])->save();
                        // Al suspender: ocultar el perfil (despublicar) y revocar la verificación.
                        $u->professionalProfile?->update([
                            'is_published' => false,
                            'is_verified' => false,
                            'verified_at' => null,
                        ]);
                    }),
                Tables\Actions\Action::make('reactivar')
                    ->label('Reactivar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn (User $u) => $u->estado === EstadoUsuario::Suspendido)
                    ->action(fn (User $u) => $u->forceFill(['estado' => EstadoUsuario::Activo])->save()),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
        ];
    }
}
