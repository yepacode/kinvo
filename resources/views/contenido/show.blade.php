<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ $item->title }}</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        <div class="rounded-2xl border border-line bg-white p-6 sm:p-8">
            @php $recurso = $item->archivoUrl(); @endphp

            @if ($item->type === 'video' && $recurso)
                {{-- Feedback Karla 06-10-2026 ("no reproduce contenido"): player
                     con <source> explícito (más tolerante que src directo) +
                     fallback de descarga si el navegador no puede decodificar +
                     mensaje de error on-screen si falla el load.
                     IMPORTANTE: usar x-on:error (no @error) porque @error es
                     directive de Blade para $errors->has(). --}}
                <div class="mb-6 aspect-video overflow-hidden rounded-xl bg-black" x-data="{ err: false }">
                    <video controls playsinline preload="metadata" class="h-full w-full" x-show="!err"
                           x-on:error="err = true">
                        <source src="{{ $recurso }}" type="video/mp4">
                        <source src="{{ $recurso }}" type="video/webm">
                        {{ __('Tu navegador no soporta la reproducción directa.') }}
                    </video>
                    <div x-show="err" x-cloak class="flex h-full w-full flex-col items-center justify-center gap-3 p-6 text-center text-white">
                        <p>{{ __('No pudimos reproducir el video acá. Puedes descargarlo y abrirlo en tu dispositivo:') }}</p>
                        <a href="{{ $recurso }}" download
                           class="inline-flex items-center gap-2 rounded-full bg-sage px-5 py-2 text-sm font-semibold text-cream">
                            ⬇ {{ __('Descargar video') }}
                        </a>
                    </div>
                </div>
            @elseif ($item->type === 'image' && $recurso)
                <img src="{{ $recurso }}" alt="{{ $item->title }}" class="mb-6 w-full rounded-xl">
            @elseif ($item->type === 'audio' && $recurso)
                <div class="mb-6" x-data="{ err: false }">
                    <audio controls class="w-full" x-show="!err" x-on:error="err = true">
                        <source src="{{ $recurso }}" type="audio/mpeg">
                        <source src="{{ $recurso }}" type="audio/mp4">
                        <source src="{{ $recurso }}" type="audio/ogg">
                        {{ __('Tu navegador no soporta la reproducción.') }}
                    </audio>
                    <div x-show="err" x-cloak class="rounded-xl border border-line bg-cream/40 p-4 text-center text-sm text-warmgray">
                        <a href="{{ $recurso }}" download class="font-semibold text-sage underline">⬇ {{ __('Descargar audio') }}</a>
                    </div>
                </div>
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
                {{-- Cuerpo del artículo. HTML de confianza: sólo lo escribe el admin.
                     Estilos inline: el proyecto NO compila @tailwindcss/typography, así que
                     `prose` no aplica; usamos [&_h2]:.., [&_strong]:.. de Tailwind arbitrary
                     variants para que h2/h3/strong/em/ul/ol/a se vean con formato. --}}
                <div class="mb-6 max-w-none text-ink/90 leading-relaxed
                            [&_h2]:mt-6 [&_h2]:mb-3 [&_h2]:text-2xl [&_h2]:font-serif [&_h2]:font-medium [&_h2]:text-ink
                            [&_h3]:mt-5 [&_h3]:mb-2 [&_h3]:text-xl [&_h3]:font-serif [&_h3]:font-medium [&_h3]:text-ink
                            [&_p]:mb-3
                            [&_strong]:font-semibold [&_strong]:text-ink
                            [&_em]:italic [&_em]:text-ink/85
                            [&_ul]:my-3 [&_ul]:pl-6 [&_ul]:list-disc
                            [&_ol]:my-3 [&_ol]:pl-6 [&_ol]:list-decimal
                            [&_li]:mb-1
                            [&_a]:text-sage [&_a]:underline
                            [&_blockquote]:border-l-4 [&_blockquote]:border-line [&_blockquote]:pl-4 [&_blockquote]:italic [&_blockquote]:text-warmgray">
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
