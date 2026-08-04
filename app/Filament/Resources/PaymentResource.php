<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Fase 2 · Cobros';
    protected static ?string $navigationLabel = 'Cobros';
    protected static ?string $modelLabel = 'cobro';
    protected static ?string $pluralModelLabel = 'Cobros';
    protected static ?int $navigationSort = 10;

    /** Los cobros los crea la pasarela vía webhook — no se editan a mano. */
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema([]); // sin form (read-only)
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Cliente')
                    ->description(fn (Payment $p) => $p->user?->email)
                    ->searchable(['users.name', 'users.email'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('provider')
                    ->label('Pasarela')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'stripe' => 'info', 'mercadopago' => 'warning', 'fake', 'demo' => 'gray', default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->state(fn (Payment $p) => $p->montoFormateado())
                    ->alignRight()
                    ->sortable('amount_cents'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        Payment::STATUS_SUCCEEDED => 'success',
                        Payment::STATUS_PENDING => 'warning',
                        Payment::STATUS_FAILED => 'danger',
                        Payment::STATUS_REFUNDED, Payment::STATUS_PARTIAL_REFUND => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        Payment::STATUS_SUCCEEDED => 'Exitoso',
                        Payment::STATUS_PENDING => 'Pendiente',
                        Payment::STATUS_FAILED => 'Fallido',
                        Payment::STATUS_REFUNDED => 'Reembolsado',
                        Payment::STATUS_PARTIAL_REFUND => 'Reemb. parcial',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('failure_message')
                    ->label('Motivo de rechazo')
                    ->limit(40)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('provider_payment_id')
                    ->label('Ref. pasarela')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        Payment::STATUS_SUCCEEDED => 'Exitoso',
                        Payment::STATUS_PENDING => 'Pendiente',
                        Payment::STATUS_FAILED => 'Fallido',
                        Payment::STATUS_REFUNDED => 'Reembolsado',
                    ]),
                SelectFilter::make('provider')->label('Pasarela')
                    ->options(['stripe' => 'Stripe', 'mercadopago' => 'MercadoPago', 'fake' => 'Fake (dev)', 'demo' => 'Demo']),
                Filter::make('rango')
                    ->form([
                        DatePicker::make('desde')->label('Desde'),
                        DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(fn (Builder $q, array $data) => $q
                        ->when($data['desde'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['hasta'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))),
                Filter::make('rol')
                    ->form([
                        \Filament\Forms\Components\Select::make('rol')
                            ->options(['professional' => 'Profesional', 'contractor' => 'Estudio'])
                            ->placeholder('Todos'),
                    ])
                    ->query(function (Builder $q, array $data) {
                        if (! ($data['rol'] ?? null)) return;
                        $q->whereHas('user', fn ($u) => $u->where('nivel', $data['rol'] === 'professional' ? 1 : 2));
                    }),
            ])
            ->actions([])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export_csv')
                    ->label('Exportar CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn ($records) => static::exportarCsv($records)),
            ]);
    }

    /** Genera un CSV descargable con los cobros seleccionados. */
    public static function exportarCsv($records): StreamedResponse
    {
        $filename = 'cobros-'.now()->format('Ymd-His').'.csv';
        return response()->streamDownload(function () use ($records) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Fecha', 'Cliente', 'Email', 'Pasarela', 'Monto', 'Moneda', 'Estado', 'Ref pasarela', 'Motivo rechazo']);
            foreach ($records as $r) {
                /** @var Payment $r */
                fputcsv($out, [
                    $r->created_at?->format('d/m/Y H:i'),
                    $r->user?->name,
                    $r->user?->email,
                    $r->provider,
                    number_format($r->amount_cents / 100, 2, '.', ''),
                    $r->currency,
                    $r->status,
                    $r->provider_payment_id,
                    $r->failure_message,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListPayments::route('/')];
    }
}
