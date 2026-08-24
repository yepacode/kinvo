<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ $item->title }}</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        <div class="rounded-2xl border border-line bg-white p-6 sm:p-8">
            @php $recurso = $item->archivoUrl(); @endphp

            @if ($item->type === 'video' && $recurso)
                <div class="mb-6 aspect-video overflow-hidden rounded-xl bg-black">
                    <video src="{{ $recurso }}" controls playsinline preload="metadata" class="h-full w-full"></video>
                </div>
            @elseif ($item->type === 'image' && $recurso)
                <img src="{{ $recurso }}" alt="{{ $item->title }}" class="mb-6 w-full rounded-xl">
            @elseif ($item->type === 'audio' && $recurso)
                <audio src="{{ $recurso }}" controls class="mb-6 w-full"></audio>
            @elseif ($item->type === 'document' && $recurso)
                <a href="{{ $recurso }}" target="_blank" rel="noopener"
                   class="mb-6 inline-flex items-center gap-2 rounded-full bg-sage px-5 py-2 text-sm font-semibold text-cream">
                    📄 {{ __('Descargar documento') }}
                </a>
            @elseif ($item->type !== 'blog' && $item->url)
                <a href="{{ $item->url }}" target="_blank" rel="noopener"
                   class="mb-6 inline-flex items-center gap-2 text-sm text-sage underline">
                    {{ __('Abrir recurso externo') }} →
                </a>
            @endif

            @if ($item->esBlog() && $item->body)
                {{-- Cuerpo del artículo. HTML de confianza: sólo lo escribe el admin. --}}
                <div class="prose prose-sm mb-6 max-w-none text-ink/90">
                    {!! $item->body !!}
                </div>
            @endif

            @if ($item->description)
                <p class="whitespace-pre-line text-ink/90">{{ $item->description }}</p>
            @endif

            <p class="mt-4 text-xs text-warmgray">
                {{ $item->category }} · {{ __('Publicado el :fecha', ['fecha' => $item->published_at?->translatedFormat('d M Y')]) }}
            </p>
        </div>

        <a href="{{ route('contenido.index') }}" class="mt-6 inline-block text-sm text-sage hover:underline">← {{ __('Volver a contenidos') }}</a>
    </div>
</x-app-layout>
