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
        // Cuenta las dos revisiones pendientes: cuentas recién registradas
        // (Pendiente) y perfiles de contratista listos para 2ª revisión
        // (PerfilPendiente). Ver EstadoUsuario para el flujo completo.
        return static::getEloquentQuery()
            ->whereIn('estado', [
                EstadoUsuario::Pendiente->value,
                EstadoUsuario::PerfilPendiente->value,
            ])
            ->count() ?: null;
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
                        $estadoAnterior = $u->estado?->value;
                        if ($u->esContratante()) {
                            $u->forceFill(['estado' => EstadoUsuario::PerfilPendiente])->save();
                            // HIGH-3 · bitácora legal de la acción admin.
                            \App\Models\AuditLog::record(auth()->user(), $u, 'user_approved_step1',
                                old: ['estado' => $estadoAnterior],
                                new: ['estado' => EstadoUsuario::PerfilPendiente->value]);
                            $u->notify(new \App\Notifications\CuentaAprobadaContratanteNotification());
                            return;
                        }

                        // Profesional: aprobación única — activa y publica perfil.
                        $u->forceFill(['estado' => EstadoUsuario::Activo])->save();
                        $u->professionalProfile?->forceFill(['is_published' => true])->save();
                        \App\Models\AuditLog::record(auth()->user(), $u, 'user_approved',
                            old: ['estado' => $estadoAnterior, 'is_published' => false],
                            new: ['estado' => EstadoUsuario::Activo->value, 'is_published' => true]);
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
                        // HIGH-34 · validar que el perfil de empresa tenga los
                        // datos mínimos ANTES de aprobarlo — evita publicar
                        // "estudios fantasma" con nombre vacío o sin contacto.
                        $cp = $u->companyProfile;
                        $faltantes = [];
                        if (! $cp) {
                            $faltantes[] = 'no existe el perfil de empresa';
                        } else {
                            if (blank($cp->company_name)) $faltantes[] = 'nombre del estudio';
                            if (blank($cp->estado)) $faltantes[] = 'estado (ubicación)';
                            if (blank($cp->description) && blank($cp->disciplines_text)) {
                                $faltantes[] = 'descripción o disciplinas';
                            }
                        }
                        if ($faltantes) {
                            \Filament\Notifications\Notification::make()
                                ->title('Perfil incompleto — no se puede aprobar')
                                ->body('Falta(n): '.implode(', ', $faltantes).'. Pídele al estudio que lo complete antes de aprobar.')
                                ->danger()->persistent()->send();
                            return;
                        }
                        $estadoAnterior = $u->estado?->value;
                        $u->forceFill(['estado' => EstadoUsuario::Activo])->save();
                        \App\Models\AuditLog::record(auth()->user(), $u, 'company_profile_approved',
                            old: ['estado' => $estadoAnterior],
                            new: ['estado' => EstadoUsuario::Activo->value]);
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
                        $estadoAnterior = $u->estado?->value;
                        $u->suspenderYOcultarPerfil();
                        \App\Models\AuditLog::record(auth()->user(), $u, 'user_rejected',
                            old: ['estado' => $estadoAnterior],
                            new: ['estado' => EstadoUsuario::Suspendido->value]);
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
                    ->action(function (User $u) {
                        $estadoAnterior = $u->estado?->value;
                        $u->suspenderYOcultarPerfil();
                        \App\Models\AuditLog::record(auth()->user(), $u, 'user_suspended',
                            old: ['estado' => $estadoAnterior],
                            new: ['estado' => EstadoUsuario::Suspendido->value]);
                    }),
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
                        $estadoAnterior = $u->estado?->value;
                        // HIGH-4 · Reactivación por rol:
                        //  - Profesional: vuelve directo a Activo (una sola aprobación).
                        //  - Contratante: vuelve a PerfilPendiente para forzar la 2ª
                        //    revisión del perfil de empresa (mismo flujo del alta).
                        // Sin este split, reactivar un estudio rechazado lo dejaba
                        // Activo sin haber sido revisado en su 2ª etapa → visible en
                        // el directorio con datos sin validar.
                        if ($u->esContratante()) {
                            $u->forceFill(['estado' => EstadoUsuario::PerfilPendiente])->save();
                            \App\Models\AuditLog::record(auth()->user(), $u, 'user_reactivated_to_perfil_pendiente',
                                old: ['estado' => $estadoAnterior],
                                new: ['estado' => EstadoUsuario::PerfilPendiente->value]);
                            \Filament\Notifications\Notification::make()
                                ->title('Reactivado en estado "Perfil pendiente"')
                                ->body('El estudio deberá completar/actualizar su perfil y tú lo apruebas de nuevo con "Aprobar perfil de empresa".')
                                ->success()->send();
                            return;
                        }
                        // Profesional: Activo + republicar perfil (suspender lo despublica).
                        $u->forceFill(['estado' => EstadoUsuario::Activo])->save();
                        $u->professionalProfile?->forceFill(['is_published' => true])->save();
                        \App\Models\AuditLog::record(auth()->user(), $u, 'user_reactivated',
                            old: ['estado' => $estadoAnterior, 'is_published' => false],
                            new: ['estado' => EstadoUsuario::Activo->value, 'is_published' => true]);
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
                            ->helperText('Deja vacío para quitar la membresía (queda inactiva).')
                            // MED-J7 · No aceptar fechas ya vencidas — un admin
                            // que ponga una fecha pasada por accidente estaría
                            // asignando membresía "muerta". Si quiere quitarla,
                            // el helperText dice cómo (dejar vacío).
                            ->minDate(now()->toDateString()),
                    ])
                    ->action(function (User $u, array $data) {
                        $old = [
                            'membership_plan_id' => $u->membership_plan_id,
                            'membership_expires_at' => (string) $u->membership_expires_at,
                        ];
                        $new = [
                            'membership_plan_id' => $data['membership_plan_id'] ?? null,
                            'membership_expires_at' => $data['membership_expires_at'] ?? null,
                        ];
                        $u->forceFill($new)->save();
                        \App\Models\AuditLog::record(auth()->user(), $u, 'membership_updated_by_admin',
                            old: $old, new: $new);
                    }),
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

                        $tipoAnterior = $u->nivel?->value;
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

                            // HIGH-35 · Cleanup completo de datos ligados al ROL ANTERIOR
                            // — sin esto quedaban filas huérfanas apuntando a un user
                            // cuyo tipo dejó de tener sentido (offers de un ex-contratante
                            // ahora talento, postulaciones de un ex-talento ahora estudio, etc.).
                            // Todo en la misma transacción para atomicidad.
                            \App\Models\Application::where('professional_user_id', $u->id)->delete();
                            \App\Models\Offer::where('contractor_user_id', $u->id)->delete();
                            \App\Models\TeamMember::where('contractor_user_id', $u->id)
                                ->orWhere('professional_user_id', $u->id)
                                ->delete();
                            \App\Models\BenefitRequest::where('user_id', $u->id)->delete();
                            \App\Models\PulseResponse::where('professional_user_id', $u->id)
                                ->orWhere('contractor_user_id', $u->id)
                                ->delete();
                            \App\Models\WellnessEntry::where('professional_user_id', $u->id)->delete();
                            \App\Models\WallPost::where('user_id', $u->id)->delete();
                            // Suscripciones activas: cancelar en la pasarela es la
                            // responsabilidad del admin (fuera de este flujo). Aquí
                            // sólo marcamos las locales como canceladas para no dejar
                            // membresías inconsistentes con el nuevo rol.
                            \App\Models\Subscription::where('user_id', $u->id)
                                ->whereIn('status', [
                                    \App\Models\Subscription::STATUS_ACTIVE,
                                    \App\Models\Subscription::STATUS_TRIALING,
                                    \App\Models\Subscription::STATUS_PAST_DUE,
                                    \App\Models\Subscription::STATUS_INCOMPLETE,
                                ])
                                ->update([
                                    'status' => \App\Models\Subscription::STATUS_CANCELED,
                                    'canceled_at' => now(),
                                    'ends_at' => now(),
                                ]);

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

                        \App\Models\AuditLog::record(auth()->user(), $u, 'user_type_changed',
                            old: ['nivel' => $tipoAnterior],
                            new: ['nivel' => $nuevo->value]);

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
                        // HIGH-3 · Registrar en bitácora ANTES del delete: después,
                        // el subject queda apuntando a un user inexistente pero el
                        // registro conserva actor + old values.
                        \App\Models\AuditLog::record(auth()->user(), $u, 'user_deleted_by_admin', old: [
                            'email'  => $u->email,
                            'nivel'  => $u->nivel?->value,
                            'estado' => $u->estado?->value,
                            'name'   => $u->name,
                        ]);
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
