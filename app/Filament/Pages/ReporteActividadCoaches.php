<?php

namespace App\Filament\Pages;

use App\Enums\RolUsuario;
use App\Models\ContentView;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

/**
 * H5 · Actividad de coaches en contenido de desarrollo
 * (petición cliente, docx PRUEBA KINVOO):
 * "no solo cuántos vio, sino cuáles y cuándo, para saber si alguien avanzó
 * del Nivel 1 al Nivel 2 y que se vea en su expediente".
 */
class ReporteActividadCoaches extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $navigationLabel = 'Actividad de coaches';
    protected static ?int $navigationSort = 23;
    protected static string $view = 'filament.pages.reporte-tabla';

    public function getTitle(): string
    {
        return landing('admin_reporte_coaches_titulo');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(User::query()->where('nivel', RolUsuario::Professional))
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Coach')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('Correo')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('vistas_totales')
                    ->label('Vistas totales')
                    ->state(fn (User $r) => ContentView::where('user_id', $r->id)->count())
                    ->badge()->color('info'),
                Tables\Columns\TextColumn::make('ultimo_contenido')
                    ->label('Última pieza vista')
                    ->state(function (User $r) {
                        $ultima = ContentView::where('user_id', $r->id)
                            ->with('contentItem')
                            ->latest('viewed_at')
                            ->first();
                        return $ultima?->contentItem?->title ?? '—';
                    })
                    ->wrap(),
                Tables\Columns\TextColumn::make('ultima_vista')
                    ->label('Cuándo')
                    ->state(fn (User $r) => optional(
                        ContentView::where('user_id', $r->id)->latest('viewed_at')->first()
                    )?->viewed_at?->diffForHumans() ?? '—'),
                Tables\Columns\TextColumn::make('categorias_vistas')
                    ->label('Categorías vistas')
                    ->state(fn (User $r) => ContentView::where('user_id', $r->id)
                        ->join('content_items', 'content_items.id', '=', 'content_views.content_item_id')
                        ->whereNotNull('content_items.category')
                        ->distinct()->pluck('content_items.category')->join(', ') ?: '—'),
            ])
            ->actions([
                Tables\Actions\Action::make('ver_historial')
                    ->label('Historial completo')
                    ->icon('heroicon-o-list-bullet')
                    ->modalHeading(fn (User $r) => str_replace(':name', $r->name, landing('admin_reporte_coaches_modal_titulo')))
                    ->modalContent(fn (User $r) => view('filament.pages.partials.historial-vistas', [
                        'vistas' => ContentView::where('user_id', $r->id)
                            ->with('contentItem')
                            ->latest('viewed_at')
                            ->take(100)->get(),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
            ])
            ->emptyStateHeading(landing('admin_reporte_coaches_empty'));
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->esAdmin() ?? false;
    }
}
