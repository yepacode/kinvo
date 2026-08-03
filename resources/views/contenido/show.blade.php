<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ $item->title }}</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        <div class="rounded-2xl border border-line bg-white p-6 sm:p-8">
            @if ($item->type === 'video' && $item->url)
                <div class="mb-6 aspect-video overflow-hidden rounded-xl bg-black">
                    <video src="{{ $item->url }}" controls class="h-full w-full"></video>
                </div>
            @elseif ($item->type === 'audio' && $item->url)
                <audio src="{{ $item->url }}" controls class="mb-6 w-full"></audio>
            @elseif ($item->type === 'document' && ($item->file_path || $item->url))
                <a href="{{ $item->url ?? \Illuminate\Support\Facades\Storage::url($item->file_path) }}"
                   target="_blank"
                   class="mb-6 inline-flex items-center gap-2 rounded-full bg-sage px-5 py-2 text-sm font-semibold text-cream">
                    📄 {{ __('Descargar documento') }}
                </a>
            @elseif ($item->url)
                <a href="{{ $item->url }}" target="_blank"
                   class="mb-6 inline-flex items-center gap-2 text-sm text-sage underline">
                    {{ __('Abrir recurso externo') }} →
                </a>
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
