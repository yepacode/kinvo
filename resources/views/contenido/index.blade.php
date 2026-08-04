<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ __('Contenido y capacitaciones') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
        @if ($categorias->isNotEmpty())
            <div class="mb-6 flex flex-wrap gap-2">
                <a href="{{ route('contenido.index') }}"
                   class="rounded-full border border-line bg-white px-4 py-1.5 text-sm text-ink {{ ! request('categoria') ? 'border-sage text-sage' : '' }}">
                    {{ __('Todas') }}
                </a>
                @foreach ($categorias as $cat)
                    <a href="{{ route('contenido.index', ['categoria' => $cat]) }}"
                       class="rounded-full border border-line bg-white px-4 py-1.5 text-sm text-ink {{ request('categoria') === $cat ? 'border-sage text-sage' : '' }}">
                        {{ __($cat) }}
                    </a>
                @endforeach
            </div>
        @endif

        @if ($items->isEmpty())
            <div class="rounded-2xl border border-line bg-white px-6 py-12 text-center">
                <p class="text-warmgray">{{ __('No hay contenido disponible para tu cuenta en esta categoría.') }}</p>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($items as $item)
                    <a href="{{ route('contenido.show', $item->slug) }}"
                       class="block rounded-2xl border border-line bg-white p-5 transition hover:border-sage">
                        <span class="rounded-full bg-beige px-3 py-1 text-xs font-medium text-ink">
                            {{ enum_label('content_type', $item->type) }}
                        </span>
                        @if ($item->category)
                            <span class="ml-1 text-xs text-warmgray">· {{ __($item->category) }}</span>
                        @endif
                        <h3 class="mt-3 font-serif text-lg font-medium text-ink">{{ $item->title }}</h3>
                        @if ($item->description)
                            <p class="mt-2 line-clamp-3 text-sm text-warmgray">{{ $item->description }}</p>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif

        <div class="mt-6">{{ $items->withQueryString()->links() }}</div>
    </div>
</x-app-layout>
