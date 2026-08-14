<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BenefitRequestResource\Pages;
use App\Models\BenefitRequest;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * M6 · Admin gestiona solicitudes de Respaldo (telemedicina / fisio).
 * Al agendar, se notifica al coach automáticamente.
 */
class BenefitRequestResource extends Resource
{
    protected static ?string $model = BenefitRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';
    protected static ?string $navigationGroup = 'Beneficios coaches';
    protected static ?string $modelLabel = 'respaldo';
    protected static ?string $pluralModelLabel = 'Respaldos';
    protected static ?int $navigationSort = 30;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $n = static::getModel()::where('status', BenefitRequest::STATUS_PENDING)->count();
        return $n ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn () => BenefitRequest::query()->with('user'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Coach')->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')->badge()
                    ->color(fn ($state) => $state === 'physio' ? 'success' : 'info')
                    ->formatStateUsing(fn ($state) => $state === 'physio' ? '💪 Fisioterapia' : '🩺 Telemedicina'),
                Tables\Columns\TextColumn::make('preferred_slot')
                    ->label('Prefiere')->limit(40)->placeholder('—'),
                Tables\Columns\TextColumn::make('scheduled_for')
                    ->label('Agendada')->dateTime('d M · H:i')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')->badge()
                    ->color(fn ($state) => match ($state) {
                        BenefitRequest::STATUS_PENDING   => 'warning',
                        BenefitRequest::STATUS_SCHEDULED => 'info',
                        BenefitRequest::STATUS_DONE      => 'success',
                        BenefitRequest::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Recibida')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pendiente',
                        'scheduled' => 'Agendada',
                        'done' => 'Realizada',
                        'cancelled' => 'Cancelada',
                    ])->default('pending'),
                Tables\Filters\SelectFilter::make('type')
                    ->options(['telemedicine' => 'Telemedicina', 'physio' => 'Fisioterapia']),
            ])
            ->actions([
                Tables\Actions\Action::make('agendar')
                    ->label('Agendar')
                    ->icon('heroicon-o-calendar-days')
                    ->color('info')
                    ->visible(fn (BenefitRequest $r) => in_array($r->status, ['pending', 'scheduled']))
                    ->form([
                        DateTimePicker::make('scheduled_for')
                            ->label('Fecha y hora')
                            ->native(false)
                            ->minDate(now())
                            ->required(),
                        Textarea::make('admin_note')
                            ->label('Nota para el coach (opcional)')
                            ->maxLength(500),
                    ])
                    ->fillForm(fn (BenefitRequest $r) => [
                        'scheduled_for' => $r->scheduled_for,
                        'admin_note'    => $r->admin_note,
                    ])
                    ->action(function (BenefitRequest $r, array $data) {
                        // HIGH-38 · Antes de agendar, revalidar que el coach
                        // sigue con el beneficio activo. Si la membresía caducó
                        // entre la solicitud y esta acción, no debemos comprometer
                        // recursos (sesión con proveedor externo) para alguien
                        // que ya no tiene el derecho contractual.
                        $benefitKey = $r->type === BenefitRequest::TYPE_PHYSIO
                            ? 'respaldo_fisio' : 'respaldo_telemed';
                        if (! $r->user?->hasBenefit($benefitKey)) {
                            Notification::make()
                                ->title('No se puede agendar')
                                ->body('El coach ya no tiene el beneficio activo (membresía expirada o plan sin cobertura). Revisa su membresía o cancela la solicitud.')
                                ->danger()->persistent()->send();
                            return;
                        }
                        $r->update([
                            'status'        => BenefitRequest::STATUS_SCHEDULED,
                            'scheduled_for' => $data['scheduled_for'],
                            'admin_note'    => $data['admin_note'] ?? null,
                            'handled_by'    => auth()->id(),
                        ]);
                        \App\Models\AuditLog::record(auth()->user(), $r, 'benefit_scheduled',
                            new: ['scheduled_for' => (string) $data['scheduled_for']]);
                        // HIGH-36 · Feedback honesto sobre el correo: si falla
                        // el envío, decirlo. Antes el try/catch tragaba el
                        // error y siempre mostraba "Se avisó al coach".
                        $aviso = 'Sesión agendada.';
                        try {
                            $r->user?->notify(new \App\Notifications\RespaldoAgendadoNotification($r));
                            $aviso .= ' Se avisó al coach.';
                        } catch (\Throwable $e) {
                            report($e);
                            $aviso .= ' El envío del aviso al coach falló — revísalo en Log o contacta al coach directo.';
                        }
                        Notification::make()->title($aviso)->success()->send();
                    }),
                Tables\Actions\Action::make('realizada')
                    ->label('Marcar realizada')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (BenefitRequest $r) => $r->status === 'scheduled')
                    ->requiresConfirmation()
                    ->action(function (BenefitRequest $r) {
                        // MED-J8 · No permitir "realizada" ANTES de la hora
                        // agendada — antes el admin podía marcarla cerrada
                        // por adelantado, dejando la WellnessEntry con fecha
                        // incorrecta (today) y el coach sin la sesión.
                        if ($r->scheduled_for && $r->scheduled_for->isFuture()) {
                            Notification::make()
                                ->title('Aún no ocurre')
                                ->body('La sesión está agendada para '.$r->scheduled_for->translatedFormat('d M Y H:i').'. Espera a que ocurra para marcarla realizada.')
                                ->danger()->persistent()->send();
                            return;
                        }
                        $r->update(['status' => BenefitRequest::STATUS_DONE]);
                        // Registrar en el expediente de cuidado del coach.
                        $tipo = $r->type === BenefitRequest::TYPE_PHYSIO
                            ? \App\Models\WellnessEntry::TYPE_PHYSIO
                            : \App\Models\WellnessEntry::TYPE_TELEMEDICINE;
                        \App\Models\WellnessEntry::create([
                            'professional_user_id' => $r->user_id,
                            'created_by_admin_id'  => auth()->id(),
                            'type'                 => $tipo,
                            'occurred_on'          => now()->toDateString(),
                            'provider'             => 'Kinvoo',
                            'notes'                => $r->admin_note,
                        ]);
                        Notification::make()->title('Marcada como realizada y registrada en el expediente.')->success()->send();
                    }),
                Tables\Actions\Action::make('cancelar')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (BenefitRequest $r) => in_array($r->status, ['pending', 'scheduled']))
                    ->requiresConfirmation()
                    ->action(function (BenefitRequest $r) {
                        $estadoAnterior = $r->status;
                        $r->update(['status' => BenefitRequest::STATUS_CANCELLED]);
                        \App\Models\AuditLog::record(auth()->user(), $r, 'benefit_cancelled',
                            old: ['status' => $estadoAnterior],
                            new: ['status' => BenefitRequest::STATUS_CANCELLED]);
                        // HIGH-37 · avisar al coach que la sesión ya no está en
                        // pie — antes se cancelaba en silencio y el coach se
                        // presentaba a una hora inexistente.
                        $aviso = 'Solicitud cancelada.';
                        try {
                            $r->user?->notify(new \App\Notifications\RespaldoCanceladoNotification($r));
                            $aviso .= ' Se avisó al coach.';
                        } catch (\Throwable $e) {
                            report($e);
                            $aviso .= ' El aviso al coach falló — contáctalo directamente.';
                        }
                        Notification::make()->title($aviso)->danger()->send();
                    }),
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBenefitRequests::route('/'),
            'view'  => Pages\ViewBenefitRequest::route('/{record}'),
        ];
    }
}
