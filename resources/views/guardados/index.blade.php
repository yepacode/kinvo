<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">Mis guardados</h2>
    </x-slot>

    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
        @if ($profiles->isEmpty())
            <div class="rounded-2xl border border-dashed border-line bg-white/60 px-6 py-16 text-center">
                <p class="text-3xl">⭐</p>
                <p class="mt-3 font-serif text-xl font-medium text-ink">Sin guardados todavía</p>
                <p class="mt-1 text-sm text-warmgray">Guarda perfiles desde el buscador para tenerlos a mano.</p>
                <a href="{{ route('talento.index') }}" class="mt-5 inline-block rounded-full bg-sage px-6 py-2.5 text-sm font-semibold text-cream transition hover:bg-ink">Buscar talento</a>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($profiles as $profile)
                    <x-talento-card :profile="$profile" />
                @endforeach
            </div>
            @if ($profiles->hasPages())
                <div class="mt-8">{{ $profiles->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
