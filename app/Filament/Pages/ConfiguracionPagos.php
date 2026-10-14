<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Models\PaymentCredential;
use App\Models\Plan;
use App\Support\FalloDePasarela;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Feedback Karla 06-10-2026: antes las llaves de MercadoPago sólo se podían
 * poner editando el .env por SSH. Esta página permite capturarlas desde el
 * panel, cifradas en BD, con un botón para probar que responden.
 *
 * Seguridad:
 *  - Sólo rol Admin (canAccess).
 *  - Los secretos se guardan con cast `encrypted` (APP_KEY).
 *  - Nunca se muestran en claro: al cargar el formulario se enmascaran y
 *    sólo se sobrescriben si el admin escribe un valor nuevo.
 *  - Cada cambio queda en la Bitácora Legal (sin los valores).
 */
class ConfiguracionPagos extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Fase 2 · Cobros';
    protected static ?string $navigationLabel = 'Configuración de pagos';
    protected static ?int $navigationSort = 1;
    protected static ?string $slug = 'configuracion-pagos';
    protected static string $view = 'filament.pages.configuracion-pagos';

    /** Marca que el admin dejó el campo intacto (no sobrescribir el guardado). */
    private const SIN_CAMBIO = '__sin_cambio__';

    public ?array $data = [];

    public function getTitle(): string
    {
        return __('Configuración de pagos');
    }

    public function mount(): void
    {
        $cred = PaymentCredential::activa() ?? PaymentCredential::query()->where('gateway', 'mercadopago')->first();

        $this->form->fill([
            'gateway' => $cred?->gateway ?? config('billing.gateway', 'fake'),
            'modo' => $cred?->modo ?? 'produccion',
            // Si ya hay un secreto guardado mandamos el placeholder; así el
            // admin ve que existe sin que viaje el valor real al navegador.
            'access_token' => $cred?->secreto('access_token') ? self::SIN_CAMBIO : null,
            'webhook_secret' => $cred?->secreto('webhook_secret') ? self::SIN_CAMBIO : null,
            'public_key' => $cred?->secreto('public_key') ? self::SIN_CAMBIO : null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Si la pasarela rechazó el último intento de cobro, el motivo
                // se enseña aquí arriba. Antes ese dato sólo existía en el log
                // del servidor, así que desde el panel "no deja pagar" no tenía
                // ninguna explicación visible.
                Forms\Components\Section::make(__('Último rechazo de la pasarela'))
                    ->icon('heroicon-o-exclamation-triangle')
                    ->visible(fn () => \App\Support\FalloDePasarela::ultimo() !== null)
                    ->schema([
                        Forms\Components\Placeholder::make('ultimo_fallo')
                            ->hiddenLabel()
                            ->content(function () {
                                $f = \App\Support\FalloDePasarela::ultimo();
                                if (! $f) {
                                    return '';
                                }
                                $ctx = $f['contexto'] ?? [];

                                return new \Illuminate\Support\HtmlString(
                                    '<p class="text-sm text-danger-600 font-medium">'.e($f['motivo']).'</p>'
                                    .'<p class="mt-2 text-xs text-gray-500">'
                                    .e(__('Cuando: :cuando', ['cuando' => $f['cuando'] ?? '—'])).' · '
                                    .e(__('Plan: :plan', ['plan' => $ctx['plan'] ?? '—'])).' · '
                                    .e(__('Persona: :quien', ['quien' => $ctx['usuario'] ?? '—']))
                                    .'</p>'
                                );
                            }),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('limpiar_fallo')
                                ->label(__('Ya está resuelto, ocultar'))
                                ->color('gray')
                                ->action(function () {
                                    \App\Support\FalloDePasarela::limpiar();
                                    $this->form->fill($this->form->getState(afterValidate: false));
                                    Notification::make()->title(__('Aviso ocultado'))->success()->send();
                                }),
                        ]),
                    ]),

                // La pregunta que no se podía contestar desde ninguna parte:
                // ¿MercadoPago nos ha llamado alguna vez? Un webhook mal
                // configurado en su panel y una integración sana que aún no ha
                // vendido nada se veían exactamente igual — silencio.
                Forms\Components\Section::make(__('Avisos de MercadoPago (webhook)'))
                    ->icon('heroicon-o-signal')
                    ->description(__('MercadoPago avisa a Kinvoo cada vez que alguien paga. Sin esos avisos el cobro se hace pero la membresía nunca se activa.'))
                    ->visible(fn (Forms\Get $get) => $get('gateway') === 'mercadopago')
                    ->schema([
                        Forms\Components\Placeholder::make('pulso_webhook')
                            ->hiddenLabel()
                            ->content(function () {
                                $ok = \App\Support\PulsoWebhook::ultimoAceptado();
                                $ko = \App\Support\PulsoWebhook::ultimoRechazado();

                                if (! $ok && ! $ko) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<p class="text-sm font-medium text-danger-600">'
                                        .e(__('MercadoPago todavía no ha llamado a Kinvoo ni una sola vez.'))
                                        .'</p><p class="mt-1 text-xs text-gray-500">'
                                        .e(__('Casi siempre significa que la dirección del webhook no está dada de alta en el panel de MercadoPago, en Notificaciones › Webhooks. Es la dirección que aparece aquí abajo.'))
                                        .'</p>'
                                    );
                                }

                                if ($ko && (! $ok || $ko['cuando'] > $ok['cuando'])) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<p class="text-sm font-medium text-warning-600">'
                                        .e(__('MercadoPago sí está llamando, pero Kinvoo rechazó el último aviso.'))
                                        .'</p><p class="mt-1 text-xs text-gray-500">'
                                        .e(__('Suele ser que el Secret de firma guardado aquí no es el que generó MercadoPago. Cópialo de nuevo desde Notificaciones › Webhooks.'))
                                        .'</p><p class="mt-1 text-xs text-gray-400">'
                                        .e(__('Último rechazo: :cuando · :motivo', ['cuando' => $ko['cuando'], 'motivo' => $ko['motivo'] ?? '—']))
                                        .'</p>'
                                    );
                                }

                                return new \Illuminate\Support\HtmlString(
                                    '<p class="text-sm font-medium text-success-600">'
                                    .e(__('Los avisos llegan y se aceptan correctamente.'))
                                    .'</p><p class="mt-1 text-xs text-gray-500">'
                                    .e(__('Último aviso: :cuando · :tipo', ['cuando' => $ok['cuando'], 'tipo' => $ok['tipo'] ?? '—']))
                                    .'</p>'
                                );
                            }),
                    ]),

                Forms\Components\Section::make(__('Pasarela activa'))
                    ->description(__('Elige con qué procesador se cobran las membresías. "Simulada" no mueve dinero real: sirve para probar el flujo completo sin cobrar.'))
                    ->schema([
                        Forms\Components\Radio::make('gateway')
                            ->label(__('Procesador'))
                            ->options([
                                'fake' => __('Simulada (pruebas · no cobra dinero real)'),
                                'mercadopago' => __('MercadoPago (cobros reales)'),
                            ])
                            ->descriptions([
                                'fake' => __('El usuario ve una pantalla de confirmación y el sistema actúa como si hubiera pagado. Útil para demos.'),
                                'mercadopago' => __('Redirige a MercadoPago. Requiere Access Token y Secret de firma.'),
                            ])
                            ->required()
                            ->live()
                            ->default('fake'),

                        Forms\Components\Select::make('modo')
                            ->label(__('Modo declarado'))
                            ->options([
                                'produccion' => __('Producción (dinero real)'),
                                'pruebas' => __('Pruebas (sandbox)'),
                            ])
                            ->default('produccion')
                            ->helperText(__('Sólo informativo. Lo que realmente manda es el token: los de producción empiezan con APP_USR y los de prueba con TEST.'))
                            ->visible(fn (Forms\Get $get) => $get('gateway') === 'mercadopago'),
                    ]),

                Forms\Components\Section::make(__('Credenciales de MercadoPago'))
                    ->description(__('Se guardan cifradas. Si dejas un campo vacío se conserva el valor que ya estaba guardado.'))
                    ->visible(fn (Forms\Get $get) => $get('gateway') === 'mercadopago')
                    ->schema([
                        Forms\Components\TextInput::make('access_token')
                            ->label(__('Access Token'))
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->maxLength(500)
                            ->helperText(__('MercadoPago → Tu aplicación → Credenciales de producción → Access Token. Empieza con APP_USR-')),

                        Forms\Components\TextInput::make('webhook_secret')
                            ->label(__('Secret de firma del webhook'))
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->maxLength(500)
                            ->helperText(__('MercadoPago → Tu aplicación → Webhooks → Secret de firma. Sin esto los pagos entran pero no se confirman solos.')),

                        Forms\Components\TextInput::make('public_key')
                            ->label(__('Public Key (opcional)'))
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->maxLength(500)
                            ->helperText(__('Hoy no se usa: el checkout va por redirección. Guárdala si más adelante se activa el formulario de tarjeta dentro de Kinvoo.')),

                        Forms\Components\Placeholder::make('webhook_url')
                            ->label(__('URL que debes registrar en MercadoPago'))
                            ->content(fn () => url('/webhooks/billing'))
                            ->helperText(__('Cópiala en MercadoPago → Tu aplicación → Webhooks, y marca los eventos de Pagos y Suscripciones.')),
                    ]),
            ])
            ->statePath('data');
    }

    public function guardar(): void
    {
        abort_unless(auth()->user()?->esAdmin(), 403);

        $datos = $this->form->getState();
        $gateway = $datos['gateway'];

        DB::transaction(function () use ($datos, $gateway) {
            $cred = PaymentCredential::firstOrNew(['gateway' => $gateway]);

            $cred->modo = $datos['modo'] ?? 'produccion';
            $cred->updated_by_admin_id = auth()->id();

            // Sólo sobrescribimos un secreto si el admin escribió algo nuevo.
            // El placeholder significa "déjalo como está".
            foreach (['access_token', 'webhook_secret', 'public_key'] as $campo) {
                $valor = $datos[$campo] ?? null;
                if (filled($valor) && $valor !== self::SIN_CAMBIO) {
                    $cred->{$campo} = trim($valor);
                }
            }

            $cred->save();
            $cred->activarExclusivo();

            AuditLog::record(
                auth()->user(),
                $cred,
                'payment_gateway_updated',
                old: [],
                // Nunca registramos los valores, sólo qué campos se tocaron.
                new: [
                    'gateway' => $gateway,
                    'modo' => $cred->modo,
                    'campos_actualizados' => collect(['access_token', 'webhook_secret', 'public_key'])
                        ->filter(fn ($c) => filled($datos[$c] ?? null) && $datos[$c] !== self::SIN_CAMBIO)
                        ->values()->all(),
                ]
            );
        });

        PaymentCredential::olvidarCache();

        Notification::make()
            ->title(__('Configuración guardada'))
            ->body($gateway === 'mercadopago'
                ? __('MercadoPago quedó activo. Usa "Probar conexión" para confirmar que las llaves responden.')
                : __('Quedó activa la pasarela simulada. No se cobrará dinero real.'))
            ->success()
            ->send();

        $this->mount();
    }

    /**
     * Llama a la API de MercadoPago con el token guardado. Es una consulta de
     * sólo lectura (/users/me): no crea nada ni mueve dinero.
     */
    public function probarConexion(): void
    {
        abort_unless(auth()->user()?->esAdmin(), 403);

        $cred = PaymentCredential::query()->where('gateway', 'mercadopago')->first();
        $token = $cred?->secreto('access_token');

        if (! $token) {
            Notification::make()
                ->title(__('Falta el Access Token'))
                ->body(__('Captúralo y guarda antes de probar la conexión.'))
                ->warning()->send();
            return;
        }

        try {
            $res = Http::withToken($token)->timeout(15)->get('https://api.mercadopago.com/users/me');
        } catch (\Throwable $e) {
            report($e);
            $this->registrarPrueba($cred, false, __('No se pudo conectar con MercadoPago. Revisa la conexión del servidor.'));
            return;
        }

        if ($res->successful()) {
            $cuenta = $res->json();
            $esProduccion = str_starts_with($token, 'APP_USR');
            $detalle = __('Cuenta :nick (:email) · :modo', [
                'nick' => $cuenta['nickname'] ?? '—',
                'email' => $cuenta['email'] ?? '—',
                'modo' => $esProduccion ? __('token de producción') : __('token de pruebas'),
            ]);
            $this->registrarPrueba($cred, true, $detalle);
            return;
        }

        $motivo = $res->status() === 401
            ? __('El token fue rechazado por MercadoPago (401). Verifica que lo copiaste completo y que no esté revocado.')
            : __('MercadoPago respondió :codigo.', ['codigo' => $res->status()]);

        $this->registrarPrueba($cred, false, $motivo);
    }

    /**
     * Prueba el cobro de verdad: crea en MercadoPago una suscripción de
     * prueba con el MISMO payload que usa el checkout y enseña la respuesta
     * literal.
     *
     * "Probar conexión" sólo preguntaba quién es el dueño del token
     * (/users/me), que responde bien aunque la cuenta no pueda crear
     * suscripciones. Por eso podía decir "conexión correcta" mientras el
     * botón de comprar devolvía un error 500. Esta prueba sí recorre el
     * camino real.
     *
     * No cobra nada: la suscripción queda en `pending`, sin tarjeta asociada,
     * y se cancela acto seguido.
     */
    public function probarCobro(): void
    {
        abort_unless(auth()->user()?->esAdmin(), 403);

        $gateway = app(\App\Services\Billing\SubscriptionGateway::class);

        if (! $gateway instanceof \App\Services\Billing\MercadoPagoGateway) {
            Notification::make()
                ->title(__('La pasarela activa no es MercadoPago'))
                ->body(__('Elige MercadoPago y guarda antes de probar el cobro.'))
                ->warning()->send();

            return;
        }

        $planes = Plan::query()
            ->where('activo', true)
            ->whereNotNull('precio')
            ->where('precio', '>', 0)
            ->orderBy('orden')->orderBy('id')
            ->get();

        if ($planes->isEmpty()) {
            Notification::make()
                ->title(__('No hay ningún plan con precio'))
                ->body(__('Captura el precio de al menos un plan antes de probar el cobro.'))
                ->warning()->send();

            return;
        }

        // Se prueban TODOS: basta con que un plan tenga un dato que
        // MercadoPago no admite para que sólo ese dé error al pagarlo, y
        // probando uno solo nunca se habría visto.
        $fallos = [];
        foreach ($planes as $plan) {
            try {
                $gateway->probarPlan($plan);
            } catch (\Throwable $e) {
                report($e);
                $fallos[] = $plan->nombre.' ('.$plan->precio.' '.$plan->moneda.'): '.FalloDePasarela::motivo($e);
            }
        }

        if (! $fallos) {
            FalloDePasarela::limpiar();

            Notification::make()
                ->title(__('MercadoPago acepta cobrar los :n planes', ['n' => $planes->count()]))
                ->body(__('El circuito de cobro funciona. Si aun así alguien no puede pagar, el problema está en su cuenta, no en la pasarela.'))
                ->success()->persistent()->send();

            return;
        }

        $resumen = implode(' — ', $fallos);
        FalloDePasarela::registrar($resumen, [
            'origen' => 'prueba de planes desde el panel',
            'planes_revisados' => $planes->count(),
            'planes_con_fallo' => count($fallos),
        ]);

        Notification::make()
            ->title(__('MercadoPago rechaza :n de :total planes', ['n' => count($fallos), 'total' => $planes->count()]))
            ->body($resumen)
            ->danger()->persistent()->send();
    }

    private function registrarPrueba(PaymentCredential $cred, bool $ok, string $detalle): void
    {
        $cred->forceFill([
            'verificado_at' => now(),
            'verificado_resultado' => ($ok ? 'OK · ' : 'ERROR · ').$detalle,
        ])->save();

        Notification::make()
            ->title($ok ? __('Conexión correcta') : __('No se pudo verificar'))
            ->body($detalle)
            ->{$ok ? 'success' : 'danger'}()
            ->persistent()
            ->send();
    }

    /** Estado que pinta la vista (banner superior). */
    public function getEstadoActual(): array
    {
        $cred = PaymentCredential::activa();
        $gateway = config('billing.gateway', 'fake');
        $tieneToken = filled(config('billing.mercadopago.access_token'));
        $tieneWebhook = filled(config('billing.mercadopago.webhook_secret'));

        return [
            'gateway' => $gateway,
            'origen' => $cred ? __('panel de administración') : __('variables del servidor (.env)'),
            'cobra_real' => $gateway === 'mercadopago' && $tieneToken,
            'tiene_token' => $tieneToken,
            'tiene_webhook' => $tieneWebhook,
            'verificado_at' => $cred?->verificado_at,
            'verificado_resultado' => $cred?->verificado_resultado,
            'webhook_url' => url('/webhooks/billing'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->esAdmin() ?? false;
    }
}
