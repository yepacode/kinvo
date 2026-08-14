<x-public-layout :title="__('Buscar talento · Kinvoo')"
                 :description="__('Encuentra coaches, instructores y profesionales del fitness por disciplina, ubicación y modalidad.')">
    <div class="mx-auto max-w-5xl px-6 py-10">
        @auth
            <x-back-link :href="auth()->user()->homeRoute()" :value="__('← Volver al panel')" />
        @endauth
        <div class="text-center">
            <h1 class="font-serif text-4xl font-medium text-ink">{{ landing('talento_index_titulo') }}</h1>
            <p class="mt-2 text-warmgray">{{ landing('talento_index_subtitulo') }}</p>
        </div>

        {{-- Filtros --}}
        <form method="GET" action="{{ route('talento.index') }}"
              class="mt-8 rounded-2xl border border-line bg-white p-5">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <input type="text" name="q" value="{{ $filtros['q'] ?? '' }}" placeholder="{{ __('Buscar por nombre o palabra clave') }}"
                       class="rounded-md border-line shadow-sm focus:border-sage focus:ring-sage sm:col-span-2 lg:col-span-3">

                <select name="discipline_id" class="rounded-md border-line shadow-sm focus:border-sage focus:ring-sage">
                    <option value="">{{ __('Disciplina') }}</option>
                    @foreach ($disciplines as $d)
                        <option value="{{ $d->id }}" @selected(($filtros['discipline_id'] ?? null) == $d->id)>{{ $d->nombreLocalizado() }}</option>
                    @endforeach
                </select>

                <select name="location_id" class="rounded-md border-line shadow-sm focus:border-sage focus:ring-sage">
                    <option value="">{{ __('Ubicación') }}</option>
                    @foreach ($locations as $loc)
                        <option value="{{ $loc->id }}" @selected(($filtros['location_id'] ?? null) == $loc->id)>{{ $loc->etiqueta() }}</option>
                    @endforeach
                </select>

                <select name="modalidad" class="rounded-md border-line shadow-sm focus:border-sage focus:ring-sage">
                    <option value="">{{ __('Modalidad') }}</option>
                    @foreach ($modalidades as $val => $label)
                        <option value="{{ $val }}" @selected(($filtros['modalidad'] ?? null) === $val)>{{ __($label) }}</option>
                    @endforeach
                </select>

                <div class="flex gap-2">
                    <button type="submit"
                            class="flex-1 rounded-full bg-sage px-5 py-2 text-sm font-semibold text-cream transition hover:bg-ink">
                        {{ __('Buscar') }}
                    </button>
                    <a href="{{ route('talento.index') }}"
                       class="rounded-full border border-line px-4 py-2 text-sm text-warmgray transition hover:border-sage hover:text-sage">
                        {{ __('Limpiar') }}
                    </a>
                </div>
            </div>
        </form>

        {{-- Resultados --}}
        <p class="mt-6 text-sm text-warmgray">
            {{ trans_choice(':count profesional|:count profesionales', $profiles->total(), ['count' => $profiles->total()]) }}
        </p>

        @if ($profiles->isEmpty())
            <div class="mt-4 rounded-2xl border border-dashed border-line bg-white/60 px-6 py-16 text-center">
                <p class="text-3xl" aria-hidden="true">🔍</p>
                <p class="mt-3 font-serif text-xl font-medium text-ink">{{ landing('talento_index_empty_titulo') }}</p>
                <p class="mt-1 text-sm text-warmgray">{{ __('Prueba con otros filtros o limpia la búsqueda.') }}</p>
            </div>
        @else
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($profiles as $profile)
                    <x-talento-card :profile="$profile" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $profiles->links() }}
            </div>
        @endif
    </div>
</x-public-layout>
