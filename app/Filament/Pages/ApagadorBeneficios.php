<?php

namespace App\Filament\Pages;

use App\Enums\BenefitKey;
use App\Enums\RolUsuario;
use App\Models\AuditLog;
use App\Models\MemberBenefitState;
use App\Models\User;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

/**
 * Feedback Karla 01-10-2026: aunque la acción "Beneficios (apagador)" ya
 * existía en UserResource, Karla la buscaba en Expediente de cuidado y no
 * la encontraba. Esta página dedicada en el menú lateral deja el apagador
 * a un click, con los 4 estados visibles por fila y un botón directo para
 * prender/apagar sin tener que navegar a Usuarios.
 */
class ApagadorBeneficios extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    protected static ?string $navigationGroup = 'Beneficios coaches';
    protected static ?string $navigationLabel = 'Apagador de beneficios';
    protected static ?int $navigationSort = 10;
    protected static ?string $slug = 'apagador';
    protected static string $view = 'filament.pages.reporte-tabla';

    public function getTitle(): string
    {
        return __('Apagador de beneficios');
    }

    public function getHeading(): string
    {
        return __('Apagador de beneficios');
    }

    public function getSubheading(): ?string
    {
        return __('Interruptor on/off por coach × subservicio. Kinvoo no agenda ni ejecuta: solo refleja el estado del trámite con el proveedor.');
    }

    public function table(Table $table): Table
    {
        $cols = [
            Tables\Columns\TextColumn::make('name')
                ->label('Coach')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('email')
                ->label('Correo')
                ->searchable()
                ->toggleable(),
        ];

        foreach (BenefitKey::todos() as $k) {
            $cols[] = Tables\Columns\TextColumn::make('ben_'.$k->value)
                ->label($k->icono().' '.$k->label())
                ->state(function (User $u) use ($k) {
                    $s = $u->benefitStates()->where('benefit_key', $k->value)->first();
                    return $s && $s->activo ? 'Activo' : 'Pendiente';
                })
                ->badge()
                ->color(fn (string $state): string => $state === 'Activo' ? 'success' : 'gray')
                ->tooltip(fn (User $u) => __('Pilar :pilar · :prov', [
                    'pilar' => $k->pilar(),
                    'prov' => $k->proveedor() ?? 'Kinvoo',
                ]));
        }

        $cols[] = Tables\Columns\TextColumn::make('membership_expires_at')
            ->label('Vigencia')
            ->date('d/m/Y')
            ->placeholder('—')
            ->toggleable();

        return $table
            ->query(
                User::query()
                    ->where('nivel', RolUsuario::Professional)
                    ->whereNotNull('membership_plan_id')
            )
            ->columns($cols)
            ->filters([
                Tables\Filters\Filter::make('membresia_vigente')
                    ->label('Solo con membresía vigente')
                    ->query(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('membership_expires_at', '>=', now()))
                    ->default(),
                Tables\Filters\Filter::make('con_pendiente')
                    ->label('Al menos un beneficio pendiente')
                    // Audit 02-10: la lógica anterior (OR de doesntHave activo=true
                    // + has activo=false) dejaba fuera al coach con 1 beneficio
                    // activo y los otros 3 sin fila (caso frecuente porque los
                    // rows se crean on-demand con firstOrNew). Reemplazado por
                    // whereHas con count < total: cuenta cuántos beneficios tiene
                    // prendidos y filtra los que tienen menos del total, lo que
                    // cubre todos los casos (0 activos, 1 activo, 2 activos, etc.).
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query) {
                        $totalKeys = count(BenefitKey::todos());
                        $query->whereHas('benefitStates', fn ($q) => $q->where('activo', true), '<', $totalKeys);
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('toggle_beneficios')
                    // Audit 02-10: defensa en profundidad explícita, igual que
                    // UserResource.php:312. Aunque la Page completa está detrás
                    // de canAccess()+canAccessPanel(), si en el futuro se expone
                    // un segundo panel Filament a otro rol, la acción queda
                    // protegida en origen.
                    ->authorize(fn () => auth()->user()?->esAdmin() ?? false)
                    ->label('Prender/Apagar')
                    ->icon('heroicon-o-bolt')
                    ->color('warning')
                    ->modalHeading(fn (User $u) => __('Beneficios de :nombre', ['nombre' => $u->name]))
                    ->modalDescription(__('Prende un beneficio sólo cuando el trámite con el proveedor esté resuelto. El miembro ve "Activo" cuando está prendido y "Pendiente" cuando está apagado.'))
                    ->modalWidth('lg')
                    ->form(function (User $u) {
                        $estados = $u->benefitStates()->get()->keyBy(fn ($s) => $s->benefit_key->value);
                        $campos = [];
                        foreach (BenefitKey::todos() as $k) {
                            $estado = $estados->get($k->value);
                            $prov = $k->proveedor() ? ' — proveedor: '.$k->proveedor() : ' — Kinvoo';
                            $campos[] = Forms\Components\Toggle::make('activo_'.$k->value)
                                ->label($k->icono().' '.$k->label())
                                ->helperText('Pilar '.$k->pilar().$prov)
                                ->default($estado?->activo ?? false)
                                ->inline(false);
                        }
                        $campos[] = Forms\Components\Textarea::make('admin_notes')
                            ->label(__('Nota interna del admin (opcional)'))
                            ->helperText(__('Sólo el admin la ve.'))
                            ->rows(2)
                            ->maxLength(1000);
                        return $campos;
                    })
                    ->action(function (User $u, array $data) {
                        $cambios = DB::transaction(function () use ($u, $data) {
                            $ahora = now();
                            $out = [];
                            foreach (BenefitKey::todos() as $k) {
                                $nuevo = (bool) ($data['activo_'.$k->value] ?? false);
                                $estado = MemberBenefitState::firstOrNew([
                                    'user_id' => $u->id,
                                    'benefit_key' => $k->value,
                                ]);
                                $antes = (bool) $estado->activo;
                                // Audit 02-10: antes el early-continue también
                                // dejaba pasar cuando el admin solo escribía
                                // admin_notes sin tocar toggles → se guardaban
                                // 4 filas con la misma nota y la notificación
                                // decía "4 beneficios modificados". Ahora la
                                // nota solo se adhiere a los beneficios que SÍ
                                // cambian; si no cambia ningún toggle, la nota
                                // se descarta y la UI dice "Sin cambios".
                                if ($antes === $nuevo) {
                                    continue;
                                }
                                $estado->activo = $nuevo;
                                if ($nuevo && ! $antes) $estado->activated_at = $ahora;
                                elseif (! $nuevo && $antes) $estado->deactivated_at = $ahora;
                                if (filled($data['admin_notes'] ?? null)) {
                                    $estado->admin_notes = $data['admin_notes'];
                                }
                                $estado->changed_by_admin_id = auth()->id();
                                $estado->save();
                                $out[$k->value] = ['antes' => $antes, 'despues' => $nuevo];
                            }
                            if ($out) {
                                AuditLog::record(auth()->user(), $u,
                                    'member_benefits_updated',
                                    old: [], new: $out);
                            }
                            return $out;
                        });

                        Notification::make()
                            ->title($cambios ? __('Beneficios actualizados') : __('Sin cambios'))
                            ->body($cambios
                                ? __(':n beneficio(s) modificado(s).', ['n' => count($cambios)])
                                : __('No detectamos cambios.'))
                            ->success()->send();
                    }),
            ])
            ->defaultSort('name')
            ->emptyStateHeading(__('Sin coaches con membresía todavía'))
            ->emptyStateDescription(__('Cuando un coach contrate el Plan Esencial aparecerá aquí.'));
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->esAdmin() ?? false;
    }
}
