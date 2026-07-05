<?php

namespace App\Filament\Resources;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
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
            Infolists\Components\TextEntry::make('name')->label('Nombre'),
            Infolists\Components\TextEntry::make('email')->label('Correo')->copyable(),
            Infolists\Components\TextEntry::make('nivel')->label('Rol')
                ->formatStateUsing(fn (RolUsuario $state) => $state->label()),
            Infolists\Components\TextEntry::make('estado')->label('Estado')
                ->formatStateUsing(fn (EstadoUsuario $state) => $state->label()),
            Infolists\Components\TextEntry::make('created_at')->label('Registro')->dateTime('d/m/Y H:i'),
        ])->columns(2);
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
                    ->action(fn (User $u) => $u->forceFill(['estado' => EstadoUsuario::Suspendido])->save()),
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
