@php
    $nombre = $profile->company_name;
    $ubicacion = collect([$profile->estado])->filter()->implode(', ');
    $titulo = trim($nombre.($profile->estado ? ' — '.$profile->estado : '').' · Kinvoo');
    $desc = $profile->description
        ? \Illuminate\Support\Str::limit(strip_tags($profile->description), 155)
        : trim('Estudio de fitness'.($profile->disciplines_text ? ': '.$profile->disciplines_text : '').($profile->estado ? ' en '.$profile->estado : ''));
    $disciplinas = collect(explode(',', (string) $profile->disciplines_text))->map(fn ($d) => trim($d))->filter();
@endphp

<x-public-layout :title="$titulo" :description="$desc">
    <x-slot name="head">
        <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@'.'context' => 'https://schema.org',
            '@type' => 'SportsActivityLocation',
            'name' => $nombre,
            'description' => $profile->description ? strip_tags($profile->description) : null,
            'image' => $profile->logo_path ? Storage::url($profile->logo_path) : null,
            'url' => $profile->website ?: null,
            'address' => ($profile->estado || ($profile->show_address && $profile->address)) ? array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $profile->show_address ? $profile->address : null,
                'addressLocality' => $profile->show_address ? $profile->colonia : null,
                'postalCode' => $profile->show_address ? $profile->postal_code : null,
                'addressRegion' => $profile->estado,
                'addressCountry' => 'MX',
            ]) : null,
        ]), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
        </script>
    </x-slot>

    <div class="mx-auto max-w-3xl px-6 py-10">
        <div class="overflow-hidden rounded-2xl border border-line bg-white">
            {{-- Encabezado --}}
            <div class="flex flex-col items-center gap-4 border-b border-line bg-beige/50 px-6 py-8 text-center sm:flex-row sm:text-left">
                <div class="h-28 w-28 shrink-0 overflow-hidden rounded-2xl border border-line bg-cream">
                    @if ($profile->logo_path)
                        <img src="{{ Storage::url($profile->logo_path) }}" alt="{{ $nombre }}" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-4xl text-warmgray" aria-hidden="true">🏢</div>
                    @endif
                </div>
                <div class="min-w-0">
                    <h1 class="font-serif text-3xl font-medium text-ink">{{ $nombre }}</h1>
                    @if ($ubicacion)
                        <p class="mt-2 text-sm text-warmgray"><span aria-hidden="true">📍</span> {{ $ubicacion }}</p>
                    @endif
                </div>
            </div>

            <div class="space-y-8 px-6 py-8">
                {{-- Disciplinas --}}
                @if ($disciplinas->isNotEmpty())
                    <div>
                        <h2 class="font-serif text-xl font-medium text-ink">Disciplinas</h2>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($disciplinas as $d)
                                <span class="rounded-full bg-sage/10 px-3 py-1 text-sm text-sage">{{ $d }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Sobre el estudio --}}
                @if ($profile->description)
                    <div>
                        <h2 class="font-serif text-xl font-medium text-ink">Sobre {{ $nombre }}</h2>
                        <p class="mt-2 whitespace-pre-line text-warmgray">{{ $profile->description }}</p>
                    </div>
                @endif

                {{-- Ubicación: dirección exacta solo si el estudio la habilitó; si no, solo el estado. --}}
                @php
                    $ubicacionPartes = $profile->show_address
                        ? collect([$profile->address, $profile->colonia, $profile->postal_code, $profile->estado])->filter()
                        : collect([$profile->estado])->filter();
                @endphp
                @if ($ubicacionPartes->isNotEmpty())
                    <div>
                        <h2 class="font-serif text-xl font-medium text-ink">Ubicación</h2>
                        <p class="mt-2 text-warmgray">{{ $ubicacionPartes->implode(', ') }}</p>
                    </div>
                @endif

                {{-- Sitio web + multimedia --}}
                @if ($profile->website || $profile->media_url || $profile->media_path)
                    <div class="border-t border-line pt-6">
                        @if ($profile->media_path)
                            <div class="mb-4">
                                @if ($profile->media_type === 'video')
                                    <video class="w-full max-w-2xl rounded-md border border-line" controls playsinline preload="metadata">
                                        <source src="{{ Storage::url($profile->media_path) }}">
                                        Tu navegador no soporta este video.
                                    </video>
                                @else
                                    <img class="w-full max-w-2xl rounded-md border border-line" src="{{ Storage::url($profile->media_path) }}" alt="Multimedia de {{ $profile->company_name }}">
                                @endif
                            </div>
                        @endif
                        <div class="flex flex-wrap gap-4 text-sm">
                            @if ($profile->website)
                                <a href="{{ $profile->website }}" target="_blank" rel="noopener noreferrer" class="text-sage underline hover:text-ink">Sitio web ↗</a>
                            @endif
                            @if ($profile->media_url)
                                <a href="{{ $profile->media_url }}" target="_blank" rel="noopener noreferrer" class="text-sage underline hover:text-ink">Contenido multimedia ↗</a>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Los datos de contacto del estudio son privados: la conexión pasa por Kinvoo. --}}
            </div>
        </div>
    </div>
</x-public-layout>
