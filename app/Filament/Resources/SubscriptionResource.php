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
            ->actions([])         // read-only: sin EditAction
            ->bulkActions([]);    // read-only: sin DeleteBulkAction
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSubscriptions::route('/')];
    }
}
