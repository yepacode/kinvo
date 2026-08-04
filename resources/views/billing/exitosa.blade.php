<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ __('¡Suscripción exitosa!') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6">
        <div class="rounded-2xl border border-sage/30 bg-sage/5 p-8 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-sage/20">
                <span class="text-2xl" aria-hidden="true">✅</span>
            </div>
            <h3 class="mt-4 font-serif text-2xl font-medium text-ink">{{ __('¡Gracias por suscribirte!') }}</h3>
            <p class="mt-2 text-sm text-warmgray">
                {{ __('Tu suscripción se está activando. Te enviaremos un correo con la confirmación en cuanto la pasarela verifique el pago.') }}
            </p>
            <a href="{{ route('dashboard') }}" class="mt-6 inline-block rounded-full bg-sage px-6 py-2.5 text-sm font-semibold text-cream transition hover:bg-ink">
                {{ __('Ir a mi panel') }}
            </a>
        </div>
    </div>
</x-app-layout>
