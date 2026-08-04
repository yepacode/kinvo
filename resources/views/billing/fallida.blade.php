<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ __('El pago no se completó') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6">
        <div class="rounded-2xl border border-line bg-white p-8 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-beige">
                <span class="text-2xl" aria-hidden="true">⚠️</span>
            </div>
            <h3 class="mt-4 font-serif text-2xl font-medium text-ink">{{ __('El pago fue cancelado') }}</h3>
            <p class="mt-2 text-sm text-warmgray">
                {{ __('No se realizó ningún cobro. Puedes intentar de nuevo cuando quieras.') }}
            </p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ route('membresias.index') }}" class="rounded-full bg-sage px-6 py-2.5 text-sm font-semibold text-cream transition hover:bg-ink">
                    {{ __('Ver los planes') }}
                </a>
                <a href="{{ route('dashboard') }}" class="rounded-full border border-line px-6 py-2.5 text-sm font-medium text-ink transition hover:border-sage hover:text-sage">
                    {{ __('Volver al panel') }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
