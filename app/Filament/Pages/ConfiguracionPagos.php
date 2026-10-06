<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Models\PaymentCredential;
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
