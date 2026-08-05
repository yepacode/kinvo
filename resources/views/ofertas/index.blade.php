<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ __('Ofertas de trabajo') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 space-y-6">
        <x-guia-inline :titulo="__('¿Cómo postulo a una oferta?')" tono="beige">
            <p>{{ __('Abre la oferta que te interesa, escribe una carta breve (opcional) y presiona “Enviar postulación”. El estudio recibe tu candidatura y te contactará si le interesa.') }}</p>
            <p>{{ __('El estado de tus postulaciones lo ves en Mis postulaciones.') }}</p>
        </x-guia-inline>

        <form method="GET" class="mb-6 flex flex-wrap gap-3">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('Buscar por título o descripción...') }}"
                   class="min-h-[44px] basis-full sm:basis-64 sm:flex-1 rounded-full border border-line px-4 py-2 text-sm">
            <select name="modalidad" class="min-h-[44px] basis-full sm:basis-auto rounded-full border border-line px-4 py-2 text-sm">
                <option value="">{{ __('Todas las modalidades') }}</option>
                <option value="presencial" @selected(request('modalidad')==='presencial')>{{ __('Presencial') }}</option>
                <option value="online" @selected(request('modalidad')==='online')>{{ __('Online') }}</option>
                <option value="hibrido" @selected(request('modalidad')==='hibrido')>{{ __('Híbrido') }}</option>
            </select>
            <button type="submit" class="min-h-[44px] basis-full sm:basis-auto rounded-full bg-sage px-5 py-2 text-sm font-semibold text-cream">{{ __('Filtrar') }}</button>
        </form>

        @forelse ($ofertas as $o)
            <a href="{{ route('ofertas.show', $o->slug) }}"
               class="mb-4 block rounded-2xl border border-line bg-white p-5 transition hover:border-sage sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0">
                        <h3 class="font-serif text-lg font-medium text-ink">{{ $o->title }}</h3>
                        <p class="text-sm text-warmgray">
                            {{ $o->contractor?->companyProfile?->company_name ?? $o->contractor?->name }}
                            @if ($o->location) · {{ $o->location->ciudad }} @endif
                            · {{ enum_label('modality', $o->modality) }}
                        </p>
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
                <p class="text-warmgray">{{ __('No hay ofertas publicadas por ahora.') }}</p>
            </div>
        @endforelse

        <div class="mt-6">{{ $ofertas->withQueryString()->links() }}</div>
    </div>
</x-app-layout>
