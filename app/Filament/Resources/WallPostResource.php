<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WallPostResource\Pages;
use App\Models\WallPost;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * H4 · Moderación del Wall "Comparte un momento" (petición cliente).
 *
 * El admin ve los posts pendientes primero y los aprueba/rechaza.
 * No se pueden crear desde aquí — se crean desde el frontend por el estudio.
 */
class WallPostResource extends Resource
{
    protected static ?string $model = WallPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-camera';

    protected static ?string $modelLabel = 'momento';

    protected static ?string $pluralModelLabel = 'Momentos (Wall)';

    protected static ?int $navigationSort = 5;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $pendientes = static::getModel()::where('status', WallPost::STATUS_PENDING)->count();
        return $pendientes ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn () => WallPost::query()->with('author.companyProfile')->latest())
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('media_path')
                    ->label('Media')
                    ->disk('public')
                    ->square()
                    ->size(72),
                Tables\Columns\TextColumn::make('author.name')
                    ->label('Estudio')
                    ->formatStateUsing(fn ($record) => $record->author?->companyProfile?->company_name ?? $record->author?->name)
                    ->searchable(),
                Tables\Columns\TextColumn::make('caption')
                    ->label('Frase')
                    ->wrap()
                    ->limit(80)
                    ->searchable(),
                Tables\Columns\TextColumn::make('media_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn ($state) => $state === 'video' ? 'info' : 'gray'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        WallPost::STATUS_PENDING  => 'warning',
                        WallPost::STATUS_APPROVED => 'success',
                        WallPost::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Publicado')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        WallPost::STATUS_PENDING  => 'En revisión',
                        WallPost::STATUS_APPROVED => 'Aprobado',
                        WallPost::STATUS_REJECTED => 'Rechazado',
                        WallPost::STATUS_ARCHIVED => 'Archivado',
                    ])
                    ->default(WallPost::STATUS_PENDING),
            ])
            ->actions([
                Tables\Actions\Action::make('aprobar')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (WallPost $r) => $r->status === WallPost::STATUS_PENDING)
                    ->requiresConfirmation()
                    ->action(function (WallPost $r) {
                        $r->update([
                            'status'       => WallPost::STATUS_APPROVED,
                            'moderator_id' => auth()->id(),
                            'moderated_at' => now(),
                        ]);
                        Notification::make()->title('Momento aprobado')->success()->send();
                    }),
                Tables\Actions\Action::make('rechazar')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (WallPost $r) => $r->status === WallPost::STATUS_PENDING)
                    ->form([
                        \Filament\Forms\Components\Textarea::make('moderation_reason')
                            ->label('Motivo (se muestra al estudio)')
                            ->maxLength(500)
                            ->required(),
                    ])
                    ->action(function (WallPost $r, array $data) {
                        // MED-G7 · Cuando se rechaza, el archivo NO debe seguir
                        // accesible en /storage. Guardamos el path original en
                        // el AuditLog y borramos el archivo del disco público.
                        $mediaOriginal = $r->media_path;
                        \App\Models\AuditLog::record(auth()->user(), $r, 'wall_post_rejected', old: [
                            'media_path' => $mediaOriginal,
                        ], new: ['reason' => $data['moderation_reason']]);
                        $r->update([
                            'status'            => WallPost::STATUS_REJECTED,
                            'moderator_id'      => auth()->id(),
                            'moderated_at'      => now(),
                            'moderation_reason' => $data['moderation_reason'],
                            'media_path'        => null,
                        ]);
                        if ($mediaOriginal) {
                            \Illuminate\Support\Facades\Storage::disk('public')->delete($mediaOriginal);
                        }
                        // HIGH-33 · avisar al estudio autor (campana + correo).
                        try {
                            $r->author?->notify(new \App\Notifications\MomentoRechazadoNotification($r));
                        } catch (\Throwable $e) { report($e); }
                        Notification::make()->title('Momento rechazado, archivo eliminado y aviso enviado al estudio')->danger()->send();
                    }),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWallPosts::route('/'),
            'view'  => Pages\ViewWallPost::route('/{record}'),
        ];
    }
}
