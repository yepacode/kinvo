<?php

namespace App\Filament\Resources\SesionResource\RelationManagers;

use App\Mail\InvitacionSesion;
use App\Models\SesionInvitado;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

/**
 * Fase 2 · Lista de invitados por sesión. Permite agregar/quitar personas
 * puntuales encima del filtro por audiencia + reenviar el correo por fila.
 */
class InvitadosRelationManager extends RelationManager
{
    protected static string $relationship = 'invitados';
    protected static ?string $title = 'Invitados';
    protected static ?string $recordTitleAttribute = 'user.name';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('Usuario')
                ->options(fn () => User::query()
                    ->whereIn('nivel', [
                        \App\Enums\RolUsuario::Professional->value,
                        \App\Enums\RolUsuario::Contractor->value,
                    ])
                    ->orderBy('name')->pluck('name', 'id'))
                ->searchable()->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Nombre')
                    ->description(fn ($record) => $record?->user?->email)
                    ->searchable(['users.name', 'users.email']),
                Tables\Columns\TextColumn::make('user.nivel')->label('Rol')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ((int) (is_object($state) ? $state->value : $state)) {
                        \App\Enums\RolUsuario::Professional->value => 'Coach',
                        \App\Enums\RolUsuario::Contractor->value   => 'Estudio',
                        \App\Enums\RolUsuario::Admin->value        => 'Admin',
                        default => '—',
                    }),
                Tables\Columns\TextColumn::make('rsvp')->label('RSVP')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        SesionInvitado::RSVP_ACCEPTED => 'success',
                        SesionInvitado::RSVP_DECLINED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        SesionInvitado::RSVP_ACCEPTED => 'Va',
                        SesionInvitado::RSVP_DECLINED => 'No puede',
                        SesionInvitado::RSVP_PENDING  => 'Pendiente',
                        default => $state ?? '—',
                    }),
                Tables\Columns\TextColumn::make('notified_at')->label('Correo enviado')
                    ->dateTime('d/m/Y H:i')->placeholder('—'),
                Tables\Columns\TextColumn::make('rsvp_at')->label('Respondió')
                    ->dateTime('d/m/Y H:i')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('invited_at')->label('Invitado el')
                    ->dateTime('d/m/Y')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('rsvp')->options([
                    SesionInvitado::RSVP_ACCEPTED => 'Va',
                    SesionInvitado::RSVP_DECLINED => 'No puede',
                    SesionInvitado::RSVP_PENDING  => 'Pendiente',
                ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Agregar persona'),
            ])
            ->actions([
                Tables\Actions\Action::make('reenviar')
                    ->label('Reenviar correo')
                    ->icon('heroicon-o-envelope')
                    ->action(function (SesionInvitado $record) {
                        $u = $record->user;
                        if (! $u) {
                            Notification::make()->warning()
                                ->title('El invitado ya no tiene usuario asociado')->send();
                            return;
                        }
                        try {
                            Mail::to($u)->send(new InvitacionSesion($u, $record->sesion, $record));
                            $record->update(['notified_at' => now()]);
                            Notification::make()->success()
                                ->title("Correo reenviado a {$u->email}")->send();
                        } catch (\Throwable $e) {
                            report($e);
                            Notification::make()->danger()
                                ->title('Falló el reenvío')->body($e->getMessage())->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make()->label('Quitar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
