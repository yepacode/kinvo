<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ landing('ofertas_index_titulo') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 space-y-6">
        <x-back-link :href="route('dashboard')" :value="__('← Volver al panel')" />

        <x-guia-inline :titulo="__('¿Cómo postulo a una oferta?')" tono="beige">
            {{-- H2 · texto del cliente (docx PRUEBA KINVOO, jul-2026). --}}
            <p>{{ landing('ofertas_index_guia_texto1') }}</p>
            <p>{{ __('El estado de tus postulaciones lo ves en Mis postulaciones.') }}</p>
        </x-guia-inline>

        <form method="GET" class="mb-6 flex flex-wrap gap-3" role="search">
            <label for="ofertas-q" class="sr-only">{{ __('Buscar oferta por título o descripción') }}</label>
            <input id="ofertas-q" type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('Buscar por título o descripción...') }}"
                   class="min-h-[44px] basis-full sm:basis-64 sm:flex-1 rounded-full border border-line px-4 py-2 text-sm">
            <label for="ofertas-modalidad" class="sr-only">{{ __('Modalidad') }}</label>
            <select id="ofertas-modalidad" name="modalidad" aria-label="{{ __('Modalidad') }}"
                    class="min-h-[44px] basis-full sm:basis-auto rounded-full border border-line px-4 py-2 text-sm">
                <option value="">{{ __('Todas las modalidades') }}</option>
                <option value="presencial" @selected(request('modalidad')==='presencial')>{{ __('Presencial') }}</option>
                <option value="online" @selected(request('modalidad')==='online')>{{ __('Online') }}</option>
                <option value="hibrido" @selected(request('modalidad')==='hibrido')>{{ __('Híbrido') }}</option>
            </select>
            <button type="submit" class="min-h-[44px] basis-full sm:basis-auto rounded-full bg-sage px-5 py-2 text-sm font-semibold text-cream">{{ __('Filtrar') }}</button>
        </form>

        @forelse ($ofertas as $o)
            @php
                // H3 · petición cliente: mostrar logo del estudio en el listado
                // para que el coach lo identifique de un vistazo.
                $logoRel = $o->contractor?->companyProfile?->logo_path;
                // asset() respeta el host actual (localhost/producción); Storage::url()
                // usaba APP_URL y provocaba mixed-origin en dev — ver fix del wall.
                $logoUrl = $logoRel ? asset('storage/'.$logoRel) : null;
                $companyName = $o->contractor?->companyProfile?->company_name ?? $o->contractor?->name;
            @endphp
            <a href="{{ route('ofertas.show', $o->slug) }}"
               class="mb-4 block rounded-2xl border border-line bg-white p-5 transition hover:border-sage sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex min-w-0 items-start gap-3">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $companyName }}"
                                 loading="lazy"
                                 class="h-12 w-12 shrink-0 rounded-lg border border-line bg-cream object-cover">
                        @else
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-line bg-cream text-sm font-medium text-warmgray"
                                 aria-hidden="true">
                                {{ mb_strtoupper(mb_substr($companyName ?? '?', 0, 1)) }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <h3 class="font-serif text-lg font-medium text-ink">{{ $o->title }}</h3>
                            <p class="text-sm text-warmgray">
                                {{ $companyName }}
                                @if ($o->location) · {{ $o->location->ciudad }} @endif
                                · {{ enum_label('modality', $o->modality) }}
                            </p>
                        </div>
                    </div>
                    <span class="rounded-full bg-beige px-3 py-1 text-xs font-medium text-ink">
                        {{ trans_choice(':n postulación|:n postulaciones', $o->applications_count, ['n' => $o->applications_count]) }}
                    </span>
                </div>
                <p class="mt-3 line-clamp-2 text-sm text-ink/80">{{ $o->description }}</p>
                @if ($o->salary_min_cents || $o->salary_max_cents)
                    <p class="mt-2 text-sm font-medium text-sage">
                        ${{ number_format(($o->salary_min_cents ?? 0) / 100, 0) }} – ${{ number_format(($o->salary_max_cents ?? 0) / 100, 0) }} {{ $o->salary_currency }} / {{ enum_label('salary_period', $o->salary_period) }}
                    </p>
                @endif
            </a>
        @empty
            <div class="rounded-2xl border border-line bg-white px-6 py-12 text-center">
                <p class="text-warmgray">{{ landing('ofertas_index_empty') }}</p>
            </div>
        @endforelse

        <div class="mt-6">{{ $ofertas->withQueryString()->links() }}</div>
    </div>
</x-app-layout>
