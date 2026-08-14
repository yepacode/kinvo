<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ landing('mis_contenidos_titulo') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
        <x-back-link :href="route('dashboard')" :value="__('← Volver al panel')" />

        @foreach (['contenido-creado' => __('Contenido publicado. Ya lo ven los usuarios de Kinvoo.'),
                   'contenido-actualizado' => __('Cambios guardados.'),
                   'contenido-eliminado' => __('Contenido eliminado.')] as $flag => $mensaje)
            @if (session('status') === $flag)
                <div class="mb-6 rounded-xl border border-sage/40 bg-sage/10 px-5 py-3 text-sm text-ink">{{ $mensaje }}</div>
            @endif
        @endforeach

        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-warmgray">
                {{ landing('mis_contenidos_intro') }}
            </p>
            <a href="{{ route('contenido.crear') }}"
               class="inline-flex min-h-[44px] items-center rounded-full bg-sage px-5 py-2.5 text-sm font-semibold text-cream hover:bg-ink">
                + {{ __('Nuevo contenido') }}
            </a>
        </div>

        @forelse ($items as $item)
            <div class="mb-4 rounded-2xl border border-line bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0">
                        <h3 class="font-serif text-lg font-medium text-ink">{{ $item->title }}</h3>
                        <p class="mt-1 text-xs text-warmgray">
                            {{ enum_label('content_type', $item->type) }}
                            @if ($item->category) · {{ __($item->category) }} @endif
                            · {{ $item->published_at?->translatedFormat('d M Y') ?? '—' }}
                            · {{ $item->views_count }} {{ __('vistas') }}
                        </p>
                    </div>
                </div>
                @if ($item->description)
                    <p class="mt-2 text-sm text-warmgray">{{ Str::limit($item->description, 160) }}</p>
                @endif

                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('contenido.show', $item->slug) }}"
                       class="inline-flex min-h-[36px] items-center rounded-full border border-line px-3 py-1.5 text-xs font-medium text-ink hover:border-sage hover:text-sage">
                        {{ __('Ver') }}
                    </a>
                    <a href="{{ route('contenido.editar', $item) }}"
                       class="inline-flex min-h-[36px] items-center rounded-full border border-line px-3 py-1.5 text-xs font-medium text-ink hover:border-sage hover:text-sage">
                        {{ __('Editar') }}
                    </a>
                    <form method="POST" action="{{ route('contenido.eliminar', $item) }}"
                          onsubmit="return confirm('{{ __('¿Eliminar este contenido? No se puede deshacer.') }}');">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="inline-flex min-h-[36px] items-center rounded-full border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50">
                            {{ __('Eliminar') }}
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-line bg-white px-6 py-12 text-center">
                <p class="text-warmgray">{{ landing('mis_contenidos_empty_state') }}</p>
            </div>
        @endforelse

        <div class="mt-6">{{ $items->links() }}</div>
    </div>
</x-app-layout>
