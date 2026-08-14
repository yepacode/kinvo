<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ landing('beneficios_header_titulo') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
        <x-back-link :href="route('dashboard')" :value="__('← Volver al panel')" />

        <x-guia-inline :titulo="landing('beneficios_guia_titulo')" tono="beige">
            <p>{{ landing('beneficios_guia_body') }}</p>
        </x-guia-inline>

        {{-- Contadores rápidos --}}
        <div class="mt-6 grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-line bg-white p-5 text-center">
                <div class="text-2xl">🩺</div>
                <div class="mt-2 text-2xl font-semibold text-ink">{{ $usos['telemed_usadas'] }}</div>
                <div class="text-xs text-warmgray">{{ __('Consultas de telemedicina') }}</div>
            </div>
            <div class="rounded-2xl border border-line bg-white p-5 text-center">
                <div class="text-2xl">💪</div>
                <div class="mt-2 text-2xl font-semibold text-ink">{{ $usos['fisio_usadas'] }}</div>
                <div class="text-xs text-warmgray">{{ __('Sesiones de fisio') }}</div>
            </div>
            <div class="rounded-2xl border border-line bg-white p-5 text-center">
                <div class="text-2xl">📚</div>
                <div class="mt-2 text-2xl font-semibold text-ink">{{ $usos['contenido_visto'] }}</div>
                <div class="text-xs text-warmgray">{{ __('Contenidos vistos') }}</div>
            </div>
        </div>

        {{-- Beneficios --}}
        <h3 class="mt-10 font-serif text-lg font-medium text-ink">{{ landing('beneficios_activos_titulo') }}</h3>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            @foreach ($beneficios as $b)
                <div class="rounded-2xl border p-5 {{ $b['activo'] ? 'border-sage bg-sage/5' : 'border-line bg-cream/40 opacity-70' }}">
                    <div class="flex items-start gap-3">
                        <div class="text-2xl">{{ $b['icono'] }}</div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-medium text-ink">{{ $b['titulo'] }}</p>
                                @if ($b['activo'])
                                    <span class="rounded-full bg-sage/20 px-2 py-0.5 text-xs font-medium text-sage">{{ __('Activo') }}</span>
                                @else
                                    <span class="rounded-full bg-cream px-2 py-0.5 text-xs font-medium text-warmgray">{{ __('No incluido en tu plan') }}</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-warmgray">{{ $b['detalle'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8 rounded-2xl border border-line bg-cream/40 p-5 text-sm text-warmgray">
            {{ landing('beneficios_upgrade_texto') }}
            <a href="{{ route('membresias.index') }}" class="ml-1 font-medium text-sage underline">{{ landing('beneficios_upgrade_cta') }}</a>
        </div>
    </div>
</x-app-layout>
