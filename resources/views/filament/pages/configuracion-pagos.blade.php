@php $estado = $this->getEstadoActual(); @endphp

<x-filament-panels::page>

    {{-- Banner de estado: lo primero que debe ver el admin es si hoy se
         está cobrando dinero real o si todo es simulado. --}}
    @if ($estado['cobra_real'])
        <div class="rounded-xl border border-success-300 bg-success-50 p-4 dark:border-success-700 dark:bg-success-950">
            <p class="font-semibold text-success-700 dark:text-success-300">
                {{ __('Cobros reales activos · MercadoPago') }}
            </p>
            <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                {{ __('Las membresías se cobran de verdad. Credenciales tomadas del :origen.', ['origen' => $estado['origen']]) }}
            </p>
            @unless ($estado['tiene_webhook'])
                <p class="mt-2 text-sm font-medium text-warning-700 dark:text-warning-400">
                    {{ __('Falta el Secret de firma del webhook: los pagos entrarán pero no se confirmarán solos.') }}
                </p>
            @endunless
        </div>
    @else
        <div class="rounded-xl border border-warning-300 bg-warning-50 p-4 dark:border-warning-700 dark:bg-warning-950">
            <p class="font-semibold text-warning-700 dark:text-warning-300">
                {{ __('Pasarela simulada · no se cobra dinero real') }}
            </p>
            <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                {{ __('Hoy el sistema actúa como si los pagos se completaran, pero no mueve dinero. Para cobrar de verdad, elige MercadoPago abajo y captura las credenciales.') }}
            </p>
        </div>
    @endif

    @if ($estado['verificado_resultado'])
        <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm dark:border-gray-700 dark:bg-gray-900">
            <p class="font-medium text-gray-900 dark:text-gray-100">{{ __('Última verificación') }}</p>
            <p class="mt-1 text-gray-600 dark:text-gray-400">
                {{ $estado['verificado_resultado'] }}
                @if ($estado['verificado_at'])
                    <span class="text-gray-400">· {{ $estado['verificado_at']->diffForHumans() }}</span>
                @endif
            </p>
        </div>
    @endif

    <form wire:submit="guardar">
        {{ $this->form }}

        <div class="mt-6 flex flex-wrap items-center gap-3">
            <x-filament::button type="submit">
                {{ __('Guardar configuración') }}
            </x-filament::button>

            <x-filament::button type="button" color="gray" wire:click="probarConexion">
                {{ __('Probar conexión') }}
            </x-filament::button>

            {{-- "Probar conexión" sólo pregunta de quién es el token, y eso
                 responde bien aunque la cuenta no pueda crear suscripciones.
                 Esta otra recorre el camino real del cobro. --}}
            <x-filament::button type="button" color="warning" wire:click="probarCobro"
                                wire:loading.attr="disabled" wire:target="probarCobro">
                <span wire:loading.remove wire:target="probarCobro">{{ __('Probar un cobro de verdad') }}</span>
                <span wire:loading wire:target="probarCobro">{{ __('Preguntando a MercadoPago…') }}</span>
            </x-filament::button>
        </div>

        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            {{ __('"Probar un cobro de verdad" le pide a MercadoPago una suscripción de prueba por CADA plan activo con precio, y las cancela enseguida. No cobra nada ni pide tarjeta: sirve para que MercadoPago diga, con sus palabras, qué plan acepta y cuál no. Basta con que un plan tenga un dato que no admita para que sólo ese dé error al pagarlo.') }}
        </p>
    </form>

    {{-- Guía corta para que el admin no tenga que buscar en documentación --}}
    <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 text-sm dark:border-gray-700 dark:bg-gray-900">
        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ __('Cómo conectar MercadoPago') }}</p>
        <ol class="mt-3 list-decimal space-y-2 pl-5 text-gray-700 dark:text-gray-300">
            <li>{{ __('Entra a tu cuenta de MercadoPago → Tu negocio → Configuración → Gestión y administración → Tus integraciones.') }}</li>
            <li>{{ __('Abre tu aplicación (o crea una si no existe) y ve a "Credenciales de producción". Copia el Access Token.') }}</li>
            <li>{{ __('En el menú izquierdo de la aplicación, baja hasta NOTIFICACIONES y entra en "Webhooks" — ojo: NO en el campo de "URL de redireccionamiento (OAuth)" de Configuración avanzada, que no envía ningún aviso. Elige la pestaña "Modo productivo" y registra esta dirección:') }}
                <code class="mt-1 block rounded bg-gray-200 px-2 py-1 font-mono text-xs dark:bg-gray-800">{{ $estado['webhook_url'] }}</code>
            </li>
            <li>{{ __('Marca estos tres eventos: "Pagos", "Planes y suscripciones" y "Facturas de suscripción" (este último es el de los cobros de cada mes). Guarda, y copia el "Secret de firma" que te genera: antes de guardar ahí, esa clave todavía no existe.') }}</li>
            <li>{{ __('Pega ambos valores aquí arriba, elige MercadoPago como procesador y guarda.') }}</li>
            <li>{{ __('Pulsa "Probar conexión" para confirmar que las llaves responden antes de recibir el primer cobro.') }}</li>
        </ol>
        <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
            {{ __('Las credenciales se guardan cifradas y nunca se muestran completas. Si cambias de llaves, basta con capturarlas de nuevo: no hace falta tocar el servidor.') }}
        </p>
    </div>

</x-filament-panels::page>
