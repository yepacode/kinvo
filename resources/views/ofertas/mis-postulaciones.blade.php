<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ landing('mis_postulaciones_titulo') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 space-y-6">
        <x-back-link :href="route('dashboard')" :value="__('← Volver al panel')" />

        <x-guia-inline :titulo="__('¿Cómo funciona una postulación?')">
            <p>{{ __('Cuando postulas a una oferta, el estudio la ve y decide. Aquí ves el estado actual de cada una.') }}</p>
            <ul class="list-disc space-y-1 pl-5">
                <li><strong>{{ __('Enviada') }}</strong>: {{ __('llegó al estudio, aún no la revisa.') }}</li>
                <li><strong>{{ __('Vista') }}</strong>: {{ __('el estudio ya la vio.') }}</li>
                <li><strong>{{ __('En contacto') }}</strong>: {{ __('el estudio te va a escribir.') }}</li>
                <li><strong>{{ __('Aceptada') }}</strong>: {{ __('¡vas para adelante!') }}</li>
                <li><strong>{{ __('Rechazada') }}</strong>: {{ __('no fue esta vez — sigue postulando.') }}</li>
            </ul>
        </x-guia-inline>

        @forelse ($postulaciones as $p)
            <div class="mb-4 rounded-2xl border border-line bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <a href="{{ route('ofertas.show', $p->offer->slug) }}" class="min-w-0">
                        <h3 class="font-serif text-lg font-medium text-ink hover:text-sage">{{ $p->offer->title }}</h3>
                        <p class="text-sm text-warmgray">
                            {{ $p->offer->contractor?->name }}
                            @if ($p->offer->location) · {{ $p->offer->location->ciudad }} @endif
                        </p>
                    </a>
                    <span class="rounded-full px-3 py-1 text-xs font-medium
                                 {{ match($p->status) {
                                    'accepted' => 'bg-sage/20 text-sage',
                                    'rejected' => 'bg-red-100 text-red-700',
                                    'in_contact' => 'bg-lime/20 text-ink',
                                    'seen' => 'bg-beige text-ink',
                                    default => 'bg-cream text-warmgray',
                                 } }}">
                        {{ enum_label('application_status', $p->status) }}
                    </span>
                </div>
                <p class="mt-2 text-xs text-warmgray">
                    {{ __('Enviada:') }} {{ $p->created_at->translatedFormat('d M Y H:i') }}
                    @if ($p->status_changed_at && $p->status !== 'submitted')
                        · {{ __('Actualizada:') }} {{ $p->status_changed_at->translatedFormat('d M Y') }}
                    @endif
                </p>
            </div>
        @empty
            <div class="rounded-2xl border border-line bg-white px-6 py-12 text-center">
                <p class="text-warmgray">{{ landing('mis_postulaciones_empty') }}</p>
                <a href="{{ route('ofertas.index') }}" class="mt-4 inline-block rounded-full bg-sage px-5 py-2 text-sm font-semibold text-cream">{{ __('Ver ofertas') }}</a>
            </div>
        @endforelse

        <div class="mt-6">{{ $postulaciones->links() }}</div>
    </div>
</x-app-layout>
