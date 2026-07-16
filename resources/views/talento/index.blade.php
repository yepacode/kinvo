<x-public-layout title="Buscar talento fitness · Kinvoo"
                 description="Encuentra coaches, instructores y profesionales del fitness por disciplina, ubicación y modalidad.">
    <div class="mx-auto max-w-5xl px-6 py-10">
        <div class="text-center">
            <h1 class="font-serif text-4xl font-medium text-ink">Encuentra talento fitness</h1>
            <p class="mt-2 text-warmgray">Filtra por disciplina, ubicación o modalidad.</p>
        </div>

        {{-- Filtros --}}
        <form method="GET" action="{{ route('talento.index') }}"
              class="mt-8 rounded-2xl border border-line bg-white p-5">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <input type="text" name="q" value="{{ $filtros['q'] ?? '' }}" placeholder="Buscar por nombre o palabra clave"
                       class="rounded-md border-line shadow-sm focus:border-sage focus:ring-sage sm:col-span-2 lg:col-span-3">

                <select name="discipline_id" class="rounded-md border-line shadow-sm focus:border-sage focus:ring-sage">
                    <option value="">Disciplina</option>
                    @foreach ($disciplines as $d)
                        <option value="{{ $d->id }}" @selected(($filtros['discipline_id'] ?? null) == $d->id)>{{ $d->nombre }}</option>
                    @endforeach
                </select>

                <select name="location_id" class="rounded-md border-line shadow-sm focus:border-sage focus:ring-sage">
                    <option value="">Ubicación</option>
                    @foreach ($locations as $loc)
                        <option value="{{ $loc->id }}" @selected(($filtros['location_id'] ?? null) == $loc->id)>{{ $loc->etiqueta() }}</option>
                    @endforeach
                </select>

                <select name="modalidad" class="rounded-md border-line shadow-sm focus:border-sage focus:ring-sage">
                    <option value="">Modalidad</option>
                    @foreach ($modalidades as $val => $label)
                        <option value="{{ $val }}" @selected(($filtros['modalidad'] ?? null) === $val)>{{ $label }}</option>
                    @endforeach
                </select>

                <div class="flex gap-2">
                    <button type="submit"
                            class="flex-1 rounded-full bg-sage px-5 py-2 text-sm font-semibold text-cream transition hover:bg-ink">
                        Buscar
                    </button>
                    <a href="{{ route('talento.index') }}"
                       class="rounded-full border border-line px-4 py-2 text-sm text-warmgray transition hover:border-sage hover:text-sage">
                        Limpiar
                    </a>
                </div>
            </div>
        </form>

        {{-- Resultados --}}
        <p class="mt-6 text-sm text-warmgray">{{ $profiles->total() }} {{ $profiles->total() === 1 ? 'profesional' : 'profesionales' }}</p>

        @if ($profiles->isEmpty())
            <div class="mt-4 rounded-2xl border border-dashed border-line bg-white/60 px-6 py-16 text-center">
                <p class="text-3xl" aria-hidden="true">🔍</p>
                <p class="mt-3 font-serif text-xl font-medium text-ink">Sin resultados</p>
                <p class="mt-1 text-sm text-warmgray">Prueba con otros filtros o limpia la búsqueda.</p>
            </div>
        @else
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
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
