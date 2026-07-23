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
                    Infolists\Components\TextEntry::make('professionalProfile.colonia')->label('Colonia')->placeholder('—'),
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
                    Infolists\Components\TextEntry::make('professionalProfile.media_url')->label('Multimedia (enlace)')
                        ->url(fn (User $record) => $record->professionalProfile->media_url)
                        ->openUrlInNewTab()->placeholder('—'),
                    Infolists\Components\TextEntry::make('multimedia_subida_profesional')->label('Multimedia (archivo subido)')
                        ->state(fn (User $record) => $record->professionalProfile->media_path
                            ? '📁 '.($record->professionalProfile->media_type === 'video' ? 'Video' : 'Imagen').' subida'
                            : 'Sin archivo')
                        ->url(fn (User $record) => $record->professionalProfile->media_path
                            ? \Illuminate\Support\Facades\Storage::url($record->professionalProfile->media_path)
                            : null)
                        ->openUrlInNewTab()
                        ->color(fn (User $record) => $record->professionalProfile->media_path ? 'primary' : 'gray'),
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
                    Infolists\Components\TextEntry::make('companyProfile.colonia')->label('Colonia')->placeholder('—'),
                    Infolists\Components\TextEntry::make('companyProfile.address')->label('Dirección')->columnSpanFull()->placeholder('—'),
                    Infolists\Components\TextEntry::make('companyProfile.contact_name')->label('Contacto (privado)')->placeholder('—'),
                    Infolists\Components\TextEntry::make('companyProfile.contact_phone')->label('Teléfono (privado)')->copyable()->placeholder('—'),
                    Infolists\Components\TextEntry::make('companyProfile.contact_email')->label('Email (privado)')->copyable()->placeholder('—'),
                    Infolists\Components\TextEntry::make('companyProfile.media_url')->label('Multimedia (enlace)')
                        ->url(fn (User $record) => $record->companyProfile->media_url)->openUrlInNewTab()->placeholder('—'),
                    Infolists\Components\TextEntry::make('multimedia_subida_estudio')->label('Multimedia (archivo subido)')
                        ->state(fn (User $record) => $record->companyProfile->media_path
                            ? '📁 '.($record->companyProfile->media_type === 'video' ? 'Video' : 'Imagen').' subida'
                            : 'Sin archivo')
                        ->url(fn (User $record) => $record->companyProfile->media_path
                            ? \Illuminate\Support\Facades\Storage::url($record->companyProfile->media_path)
                            : null)
                        ->openUrlInNewTab()
                        ->color(fn (User $record) => $record->companyProfile->media_path ? 'primary' : 'gray'),
                    Infolists\Components\TextEntry::make('membershipPlan.nombre')->label('Plan de membresía')->placeholder('Sin plan'),
                    Infolists\Components\TextEntry::make('membership_expires_at')->label('Membresía vence')
                        ->date('d/m/Y')
                        ->badge()
                        ->color(fn (User $record) => $record->tieneMembresiaActiva() ? 'success' : 'danger')
                        ->placeholder('Sin membresía'),
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
                        EstadoUsuario::PerfilPendiente => 'warning',
                        EstadoUsuario::Activo => 'success',
                        EstadoUsuario::Suspendido => 'danger',
                    }),
                Tables\Columns\TextColumn::make('membresia')
                    ->label('Membresía')
                    ->state(fn (User $u) => $u->esContratante()
                        ? ($u->tieneMembresiaActiva() ? 'Activa' : 'Sin membresía')
                        : '—')
                    ->badge()
                    ->color(fn (string $state) => $state === 'Activa' ? 'success' : 'gray'),
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
                        EstadoUsuario::PerfilPendiente->value => 'Perfil en revisión',
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
                // Primera aprobación — solo aparece sobre cuentas recién registradas.
                // Profesional: activa la cuenta y publica su perfil (única aprobación).
                // Contratista: activa la cuenta pero deja el perfil de empresa
                // en revisión pendiente (2ª aprobación necesaria; ver acción de abajo).
                Tables\Actions\Action::make('aprobar')
                    ->label(fn (User $u) => $u->esContratante() ? 'Aprobar cuenta' : 'Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $u) => $u->esContratante() ? 'Aprobar cuenta del contratista' : 'Aprobar cuenta')
                    ->modalDescription(fn (User $u) => $u->esContratante()
                        ? 'Revisa que su membresía esté vigente. El contratista podrá iniciar sesión y llenar su perfil de empresa. Cuando lo termine y lo envíe, deberás aprobar el perfil por segunda vez para publicarlo.'
                        : 'El usuario podrá iniciar sesión y su perfil se publicará para que aparezca en el buscador. Esta acción envía una notificación de bienvenida.')
                    ->modalSubmitActionLabel('Aprobar')
                    ->visible(fn (User $u) => $u->estado === EstadoUsuario::Pendiente)
                    ->action(function (User $u) {
                        if ($u->esContratante()) {
                            $u->forceFill(['estado' => EstadoUsuario::PerfilPendiente])->save();
                            $u->notify(new \App\Notifications\CuentaAprobadaContratanteNotification());
                            return;
                        }

                        // Profesional: aprobación única — activa y publica perfil.
                        $u->forceFill(['estado' => EstadoUsuario::Activo])->save();
                        $u->professionalProfile?->update(['is_published' => true]);
                        $u->notify(new \App\Notifications\CuentaAprobadaNotification());
                    }),
                // Segunda aprobación — solo aparece sobre contratistas con estado
                // PerfilPendiente: ya llenaron su perfil de empresa y esperan
                // la revisión final para que sea visible en /estudio/{slug}.
                Tables\Actions\Action::make('aprobar_perfil_empresa')
                    ->label('Aprobar perfil de empresa')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Aprobar perfil de empresa')
                    ->modalDescription('Revisa que los datos del perfil (nombre del estudio, contacto, dirección, multimedia) estén correctos. Al aprobar, el perfil quedará visible en el directorio y el contratista podrá usar el buscador de talento.')
                    ->modalSubmitActionLabel('Aprobar perfil')
                    ->visible(fn (User $u) => $u->esContratante() && $u->estado === EstadoUsuario::PerfilPendiente)
                    ->action(function (User $u) {
                        $u->forceFill(['estado' => EstadoUsuario::Activo])->save();
                        $u->notify(new \App\Notifications\PerfilEmpresaAprobadoNotification());
                    }),
                Tables\Actions\Action::make('rechazar')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Rechazar solicitud')
                    ->modalDescription('El usuario queda como suspendido y no podrá acceder a la plataforma. Si más adelante lo reactivas, tendrás que aprobarlo de nuevo para publicar su perfil.')
                    ->modalSubmitActionLabel('Rechazar')
                    ->visible(fn (User $u) => $u->estado === EstadoUsuario::Pendiente)
                    ->action(function (User $u) {
                        $u->suspenderYOcultarPerfil();
                        $u->notify(new \App\Notifications\CuentaRechazadaNotification());
                    }),
                Tables\Actions\Action::make('suspender')
                    ->label('Suspender')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Suspender cuenta')
                    ->modalDescription('El usuario no podrá iniciar sesión y su perfil se ocultará del buscador. Podrás reactivarla más adelante.')
                    ->modalSubmitActionLabel('Suspender')
                    ->visible(fn (User $u) => $u->estado === EstadoUsuario::Activo)
                    ->action(fn (User $u) => $u->suspenderYOcultarPerfil()),
                Tables\Actions\Action::make('reactivar')
                    ->label('Reactivar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Reactivar cuenta')
                    ->modalDescription('El usuario podrá volver a iniciar sesión y su perfil se publicará de nuevo en el buscador.')
                    ->modalSubmitActionLabel('Reactivar')
                    ->visible(fn (User $u) => $u->estado === EstadoUsuario::Suspendido)
                    ->action(function (User $u) {
                        // Reactivación restaura el estado Activo Y republica el perfil,
                        // porque suspender/rechazar lo despublica. Sin esto, un flujo
                        // Rechazar → Reactivar dejaba al usuario Activo con perfil
                        // is_published=false y sin la acción "Aprobar" disponible
                        // (visible solo si estado=Pendiente) → invisible sin fix.
                        $u->forceFill(['estado' => EstadoUsuario::Activo])->save();
                        $u->professionalProfile?->update(['is_published' => true]);
                    }),
                Tables\Actions\Action::make('membresia')
                    ->label('Membresía')
                    ->icon('heroicon-o-credit-card')
                    ->color('warning')
                    ->visible(fn (User $u) => $u->esContratante())
                    ->fillForm(fn (User $u) => [
                        'membership_plan_id' => $u->membership_plan_id,
                        'membership_expires_at' => $u->membership_expires_at,
                    ])
                    ->form([
                        \Filament\Forms\Components\Select::make('membership_plan_id')
                            ->label('Plan')
                            ->options(\App\Models\Plan::orderBy('orden')->pluck('nombre', 'id'))
                            ->searchable(),
                        \Filament\Forms\Components\DatePicker::make('membership_expires_at')
                            ->label('Vence el')
                            ->helperText('Deja vacío para quitar la membresía (queda inactiva).'),
                    ])
                    ->action(fn (User $u, array $data) => $u->forceFill([
                        'membership_plan_id' => $data['membership_plan_id'] ?? null,
                        'membership_expires_at' => $data['membership_expires_at'] ?? null,
                    ])->save()),
                Tables\Actions\Action::make('cambiar_tipo')
                    ->label('Cambiar tipo de cuenta')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Cambiar tipo de cuenta')
                    ->modalDescription('Úsalo cuando alguien se registró con el tipo incorrecto (talento cuando quería ser estudio o viceversa). Se borrará el perfil actual, sus contactos y la membresía si la tenía, y se creará uno vacío del nuevo tipo la próxima vez que el usuario abra su perfil.')
                    ->modalSubmitActionLabel('Cambiar')
                    ->visible(fn (User $u) => ! $u->esAdmin())
                    ->fillForm(fn (User $u) => ['tipo' => (string) $u->nivel->value])
                    ->form([
                        \Filament\Forms\Components\Radio::make('tipo')
                            ->label('Nuevo tipo')
                            ->options([
                                (string) RolUsuario::Professional->value => 'Talento (coach, instructor, staff)',
                                (string) RolUsuario::Contractor->value => 'Estudio / marca (busca talento)',
                            ])
                            ->in([(string) RolUsuario::Professional->value, (string) RolUsuario::Contractor->value])
                            ->required(),
                    ])
                    ->action(function (User $u, array $data) {
                        // Whitelist server-side: Filament no aplica `in:` sobre `options()`,
                        // así que un submit manipulado con tipo=0 (Admin) pasaría RolUsuario::from()
                        // y escalaría privilegios. Aquí lo bloqueamos explícitamente.
                        $valorSolicitado = (int) $data['tipo'];
                        if (! in_array($valorSolicitado, [
                            RolUsuario::Professional->value,
                            RolUsuario::Contractor->value,
                        ], true)) {
                            abort(422, 'Tipo de cuenta inválido.');
                        }
                        $nuevo = RolUsuario::from($valorSolicitado);

                        if ($u->nivel === $nuevo) {
                            \Filament\Notifications\Notification::make()
                                ->title('Sin cambios')
                                ->body('La cuenta ya estaba en ese tipo.')
                                ->info()->send();
                            return;
                        }

                        // Capturamos rutas de archivos ANTES de la transacción para
                        // borrarlos del disco DESPUÉS (Storage::delete no revierte si
                        // la BD hace rollback, así que primero commit BD y luego disco).
                        $archivosPublic = [];
                        $archivosLocal = [];
                        if ($pp = $u->professionalProfile) {
                            if ($pp->photo_path) $archivosPublic[] = $pp->photo_path;
                            if ($pp->media_path) $archivosPublic[] = $pp->media_path;
                            if ($pp->certification_file_path) $archivosLocal[] = $pp->certification_file_path;
                        }
                        if ($cp = $u->companyProfile) {
                            if ($cp->logo_path) $archivosPublic[] = $cp->logo_path;
                        }

                        \Illuminate\Support\Facades\DB::transaction(function () use ($u, $nuevo) {
                            // Los perfiles se borran por FK cascade sobre user_id. Pero los
                            // Save de OTROS usuarios que guardaron este perfil son polimórficos
                            // (sin FK): quedarían dangling en /guardados. Los limpiamos primero.
                            if ($pp = $u->professionalProfile) {
                                \App\Models\Save::where('saveable_type', \App\Models\ProfessionalProfile::class)
                                    ->where('saveable_id', $pp->id)
                                    ->delete();
                            }
                            if ($cp = $u->companyProfile) {
                                \App\Models\Save::where('saveable_type', \App\Models\CompanyProfile::class)
                                    ->where('saveable_id', $cp->id)
                                    ->delete();
                            }

                            $u->professionalProfile()?->delete();
                            $u->companyProfile()?->delete();

                            // Contactos ENVIADOS por el user cuando era contratante: no tienen
                            // sentido si ahora es talento (el "estudio" que envió ya no lo es).
                            \App\Models\Contact::where('contractor_user_id', $u->id)->delete();

                            // Membresía: el plan es específico del rol de contratante. Si cambia
                            // de tipo, la pierde. El owner puede reasignarla si corresponde.
                            $u->forceFill([
                                'nivel' => $nuevo,
                                'membership_plan_id' => null,
                                'membership_expires_at' => null,
                            ])->save();
                        });

                        // Fuera de la transacción: limpiamos archivos huérfanos del disco.
                        // Si el archivo ya no existe, delete() lo ignora silenciosamente.
                        if ($archivosPublic) {
                            \Illuminate\Support\Facades\Storage::disk('public')->delete($archivosPublic);
                        }
                        if ($archivosLocal) {
                            \Illuminate\Support\Facades\Storage::disk('local')->delete($archivosLocal);
                        }

                        $u->notify(new \App\Notifications\TipoDeCuentaCambiadoNotification($nuevo));

                        \Filament\Notifications\Notification::make()
                            ->title('Tipo de cuenta actualizado')
                            ->body('El usuario recibió aviso en su campanita y podrá completar su nuevo perfil al iniciar sesión.')
                            ->success()->send();
                    }),
                Tables\Actions\Action::make('eliminar')
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar cuenta permanentemente')
                    ->modalDescription('Se borrarán la cuenta, el perfil, contactos, guardados, vistas y todos los archivos subidos. Esta acción es irreversible. Úsala solo si el usuario infringió las normas.')
                    ->modalSubmitActionLabel('Eliminar definitivamente')
                    ->visible(fn (User $u) => ! $u->esAdmin() && $u->id !== auth()->id())
                    ->action(function (User $u) {
                        $u->deleteConLimpieza();
                        \Filament\Notifications\Notification::make()
                            ->title('Cuenta eliminada')
                            ->body('Se borraron todos los datos y archivos del usuario.')
                            ->success()
                            ->send();
                    }),
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
