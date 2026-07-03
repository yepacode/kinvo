<?php

namespace App\Filament\Resources;

use App\Enums\EstadoContacto;
use App\Filament\Resources\ContactResource\Pages;
use App\Models\Contact;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $modelLabel = 'contacto';

    protected static ?string $pluralModelLabel = 'Contactos';

    protected static ?int $navigationSort = 0;

    /** Los contactos se crean desde el formulario público, no en el panel. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('estado', EstadoContacto::NoLeido->value)->count() ?: null;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\TextEntry::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i'),
            Infolists\Components\TextEntry::make('contact_name')->label('Contratante'),
            Infolists\Components\TextEntry::make('contact_email')->label('Correo')->copyable(),
            Infolists\Components\TextEntry::make('contact_phone')->label('Teléfono')->placeholder('—'),
            Infolists\Components\TextEntry::make('professionalProfile.user.name')->label('Profesional contactado'),
            Infolists\Components\TextEntry::make('message')->label('Mensaje')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('contact_name')
                    ->label('Contratante')
                    ->description(fn (Contact $r) => $r->contact_email)
                    ->searchable(),
                Tables\Columns\TextColumn::make('professionalProfile.user.name')
                    ->label('Profesional contactado')
                    ->searchable(),
                Tables\Columns\TextColumn::make('message')
                    ->label('Mensaje')
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (EstadoContacto $state) => $state->label())
                    ->color(fn (EstadoContacto $state) => $state === EstadoContacto::NoLeido ? 'warning' : 'gray'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        EstadoContacto::NoLeido->value => 'No leído',
                        EstadoContacto::Leido->value => 'Leído',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver')
                    ->after(fn (Contact $r) => $r->estado === EstadoContacto::NoLeido
                        ? $r->update(['estado' => EstadoContacto::Leido])
                        : null),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContacts::route('/'),
        ];
    }
}
