<x-public-layout :title="$title.' · Kinvoo'" :description="'Kinvoo · '.$title">
    <article class="mx-auto max-w-3xl px-6 py-16 sm:py-20">
        <header class="mb-10 border-b border-line/70 pb-8">
            <h1 class="font-serif text-4xl font-medium tracking-tight text-ink sm:text-5xl">{{ $title }}</h1>
            @if ($updated)
                <p class="mt-3 text-sm text-warmgray">{{ $updated }}</p>
            @endif
        </header>

        <div class="space-y-5 leading-relaxed text-ink/90">
            @foreach (preg_split('/\R{2,}/', trim($body)) as $bloque)
                @php $bloque = trim($bloque); @endphp
                @continue($bloque === '')
                @if (preg_match('/^\d+\.\s+\S/u', $bloque))
                    @php [$titulo, $resto] = array_pad(preg_split('/\R/u', $bloque, 2), 2, ''); @endphp
                    <h2 class="pt-4 font-serif text-xl font-medium text-ink">{{ trim($titulo) }}</h2>
                    @if (trim($resto) !== '')
                        <p class="text-warmgray">{{ trim($resto) }}</p>
                    @endif
                @else
                    <p class="text-warmgray">{{ $bloque }}</p>
                @endif
            @endforeach
        </div>

        <div class="mt-14 border-t border-line/70 pt-6 text-sm text-warmgray">
            <a href="{{ route('legal.privacidad') }}" class="hover:text-sage">Aviso de Privacidad</a>
            <span class="px-2 text-line">·</span>
            <a href="{{ route('legal.terminos') }}" class="hover:text-sage">Términos y Condiciones</a>
        </div>
    </article>
</x-public-layout>
