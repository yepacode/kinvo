<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'Fase 2 · Cobros';
    protected static ?string $navigationLabel = 'Suscripciones';
    protected static ?string $modelLabel = 'suscripción';
    protected static ?string $pluralModelLabel = 'Suscripciones';
    protected static ?int $navigationSort = 11;

    /** Las suscripciones nacen y viven por la pasarela — solo lectura desde el panel. */
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Alta')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Cliente')
                    ->description(fn ($record) => $record?->user?->email)
                    ->searchable(['users.name', 'users.email'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('plan.nombre')
                    ->label('Plan')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('provider')
                    ->label('Pasarela')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'stripe' => 'info', 'mercadopago' => 'warning',
                        'fake', 'demo' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        Subscription::STATUS_ACTIVE => 'success',
                        Subscription::STATUS_TRIALING => 'info',
                        Subscription::STATUS_PAST_DUE => 'warning',
                        Subscription::STATUS_INCOMPLETE => 'gray',
                        Subscription::STATUS_CANCELED, Subscription::STATUS_UNPAID => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        Subscription::STATUS_ACTIVE => 'Activa',
                        Subscription::STATUS_TRIALING => 'En prueba',
                        Subscription::STATUS_PAST_DUE => 'Morosa',
                        Subscription::STATUS_INCOMPLETE => 'Incompleta',
                        Subscription::STATUS_CANCELED => 'Cancelada',
                        Subscription::STATUS_UNPAID => 'Sin pagar',
                        default => $state ?? '—',
                    }),
                Tables\Columns\TextColumn::make('current_period_end')
                    ->label('Vigente hasta')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('canceled_at')
                    ->label('Cancelada el')
                    ->dateTime('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('provider_subscription_id')
                    ->label('Ref. pasarela')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('Estado')->options([
                    Subscription::STATUS_ACTIVE => 'Activa',
                    Subscription::STATUS_TRIALING => 'En prueba',
                    Subscription::STATUS_PAST_DUE => 'Morosa',
                    Subscription::STATUS_INCOMPLETE => 'Incompleta',
                    Subscription::STATUS_CANCELED => 'Cancelada',
                    Subscription::STATUS_UNPAID => 'Sin pagar',
                ]),
                SelectFilter::make('provider')->label('Pasarela')
                    ->options(['stripe' => 'Stripe', 'mercadopago' => 'MercadoPago', 'fake' => 'Fake (dev)', 'demo' => 'Demo']),
                Filter::make('vence')
                    ->form([
                        DatePicker::make('desde')->label('Vence desde'),
                        DatePicker::make('hasta')->label('Vence hasta'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['desde'] ?? null, fn ($q, $d) => $q->whereDate('current_period_end', '>=', $d))
                        ->when($data['hasta'] ?? null, fn ($q, $d) => $q->whereDate('current_period_end', '<=', $d))),
            ])
            ->actions([
                // Feedback Karla 08-10-2026: el panel era 100% read-only, así
                // que una suscripción atascada (p. ej. un checkout que nunca
                // se confirmó) no se podía desbloquear desde ningún lado —
                // ni el usuario ni el admin. Esta acción permite liberarla.
                // No llama a la pasarela: solo marca el registro local, porque
                // una INCOMPLETE nunca llegó a cobrar nada.
                Tables\Actions\Action::make('cancelar')
                    ->authorize(fn () => auth()->user()?->esAdmin() ?? false)
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Subscription $r) => in_array($r->status, [
                        Subscription::STATUS_INCOMPLETE,
                        Subscription::STATUS_ACTIVE,
                        Subscription::STATUS_TRIALING,
                    ], true))
                    ->requiresConfirmation()
                    ->modalHeading(fn (Subscription $r) => 'Cancelar suscripción de '.($r->user?->name ?? '—'))
                    ->modalDescription(fn (Subscription $r) => $r->status === Subscription::STATUS_INCOMPLETE
                        ? 'Este intento de pago nunca se completó. Al cancelarlo, la persona podrá volver a suscribirse de inmediato.'
                        : 'La suscripción quedará cancelada en Kinvoo. Si el cobro recurrente sigue activo en la pasarela, cancélalo también ahí.')
                    ->modalSubmitActionLabel('Cancelar suscripción')
                    // Cancelar y cortar el acceso son dos cosas distintas: quien
                    // pagó el mes lo conserva, pero una prueba o un plan sin costo
                    // no tienen nada que conservar. Antes no había forma de
                    // retirar el acceso desde el panel.
                    ->form(fn (Subscription $r) => [
                        \Filament\Forms\Components\Toggle::make('cortar_acceso')
                            ->label('Cortar el acceso de inmediato')
                            ->helperText('Sin marcar, conserva el acceso hasta el final del periodo ya pagado.')
                            ->default($r->status === Subscription::STATUS_INCOMPLETE
                                || ($r->plan?->esGratuito() ?? false)),
                    ])
                    ->action(function (Subscription $r, array $data) {
                        $antes = $r->status;
                        $cortar = (bool) ($data['cortar_acceso'] ?? false);

                        $cambios = [
                            'status' => Subscription::STATUS_CANCELED,
                            'canceled_at' => now(),
                            'ends_at' => $cortar ? now() : $r->current_period_end,
                        ];
                        // Es `current_period_end` lo que decide si sigue vigente,
                        // así que para cortar hay que cerrarlo aquí.
                        if ($cortar) {
                            $cambios['current_period_end'] = now();
                        }

                        // El save dispara SubscriptionObserver, que recalcula la
                        // membresía del usuario. Por eso aquí no se toca
                        // users.membership_*: tener dos sitios escribiéndolo es
                        // justo lo que dejaba "MEMBRESÍA ACTIVA" tras cancelar.
                        $r->forceFill($cambios)->save();

                        \App\Models\AuditLog::record(
                            auth()->user(), $r, 'subscription_canceled_by_admin',
                            old: ['status' => $antes],
                            new: ['status' => Subscription::STATUS_CANCELED, 'acceso_cortado' => $cortar],
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Suscripción cancelada')
                            ->body($cortar
                                ? 'Acceso retirado. La persona ya puede volver a suscribirse.'
                                : 'Conserva el acceso hasta el final del periodo y ya puede volver a suscribirse.')
                            ->success()->send();
                    }),
            ])
            ->bulkActions([]);    // read-only: sin DeleteBulkAction
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSubscriptions::route('/')];
    }
}
