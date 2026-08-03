<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ __('Checkout de prueba') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-lg px-4 py-12 sm:px-6">
        <div class="rounded-2xl border border-line bg-white p-8">
            <div class="mb-4 rounded-lg border border-yellow-300 bg-yellow-50 p-3 text-xs text-yellow-800">
                <strong>{{ __('Modo demostración') }}</strong> —
                {{ __('esta pantalla reemplaza a la pasarela real (Stripe/MercadoPago). Aquí no se cobra dinero. Al confirmar, la suscripción queda activa como si el pago hubiera pasado.') }}
            </div>

            <h3 class="font-serif text-xl font-medium text-ink">{{ __('Confirmar suscripción') }}</h3>
            <div class="mt-4 space-y-1 text-sm">
                <p><strong>{{ __('Plan:') }}</strong> {{ $subscription->plan?->nombre ?? '—' }}</p>
                <p><strong>{{ __('Precio:') }}</strong>
                    {{ number_format((float) ($subscription->plan?->precio ?? 199), 2) }}
                    {{ $subscription->plan?->moneda ?? 'MXN' }}
                    {{ __('/ mensual') }}
                </p>
                <p><strong>{{ __('Estado actual:') }}</strong> <span class="text-warmgray">{{ $subscription->status }}</span></p>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <form method="POST" action="{{ url('/billing/fake-checkout/'.substr($subscription->provider_subscription_id, 9).'/confirmar') }}" class="flex-1">
                    @csrf
                    <input type="hidden" name="success" value="{{ $successUrl }}">
                    <button type="submit" class="w-full rounded-full bg-sage px-6 py-2.5 text-sm font-semibold text-cream shadow-sm transition hover:bg-ink">
                        {{ __('Simular pago exitoso ✓') }}
                    </button>
                </form>
                <a href="{{ $cancelUrl }}" class="flex-1 rounded-full border border-line px-6 py-2.5 text-center text-sm font-medium text-ink transition hover:border-sage hover:text-sage">
                    {{ __('Cancelar') }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
