@php
    $nombre = $profile->user->name;
    $titulo = trim(($profile->headline ? $nombre.' — '.$profile->headline : $nombre).' · Kinvoo');
    $desc = $profile->bio
        ? \Illuminate\Support\Str::limit(strip_tags($profile->bio), 155)
        : trim(($profile->headline ?: 'Profesional del fitness').($profile->location ? ' en '.$profile->location->etiqueta() : ''));
@endphp

<x-public-layout :title="$titulo" :description="$desc">
    <x-slot name="head">
        <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $nombre,
            'jobTitle' => $profile->headline,
            'description' => $profile->bio ? strip_tags($profile->bio) : null,
            'image' => $profile->photo_path ? Storage::url($profile->photo_path) : null,
            'knowsAbout' => $profile->disciplines->pluck('nombre')->all() ?: null,
            'address' => $profile->location ? [
                '@type' => 'PostalAddress',
                'addressLocality' => $profile->location->ciudad,
                'addressRegion' => $profile->location->region,
                'addressCountry' => $profile->location->pais,
            ] : null,
        ]), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
        </script>
    </x-slot>

    <div class="mx-auto max-w-3xl px-6 py-10">
        @if (session('status') === 'contacto-enviado')
            <div class="mb-6 rounded-xl border border-sage/30 bg-sage/10 px-4 py-3 text-sm text-sage">
                ✓ Tu mensaje fue enviado a {{ $profile->user->name }}. Te contactará al correo que dejaste.
            </div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-line bg-white">
            {{-- Encabezado --}}
            <div class="flex flex-col items-center gap-4 border-b border-line bg-beige/50 px-6 py-8 text-center sm:flex-row sm:text-left">
                <div class="h-28 w-28 shrink-0 overflow-hidden rounded-full border border-line bg-cream">
                    @if ($profile->photo_path)
                        <img src="{{ Storage::url($profile->photo_path) }}" alt="{{ $nombre }}" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-4xl text-warmgray">🏋️</div>
                    @endif
                </div>
                <div class="min-w-0">
                    <h1 class="font-serif text-3xl font-500 text-ink">{{ $nombre }} <x-verified-badge :profile="$profile" /></h1>
                    @if ($profile->headline)
                        <p class="mt-1 text-lg text-sage">{{ $profile->headline }}</p>
                    @endif
                    <div class="mt-3 flex flex-wrap items-center justify-center gap-x-4 gap-y-1 text-sm text-warmgray sm:justify-start">
                        @if ($profile->location)
                            <span>📍 {{ $profile->location->etiqueta() }}</span>
                        @endif
                        @if ($profile->modalidad)
                            <span>· {{ $profile->modalidad->label() }}</span>
                        @endif
                        @if (! is_null($profile->years_experience))
                            <span>· {{ $profile->years_experience }} años de experiencia</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="space-y-8 px-6 py-8">
                {{-- Acciones: contactar (solo contratantes activos) + guardar (cualquier usuario) --}}
                @auth
                    <div class="flex flex-col gap-3 sm:flex-row">
                        @if (auth()->user()->esContratante() && auth()->user()->estaActivo())
                            <a href="{{ route('contacto.create', $profile->slug) }}"
                               class="flex flex-1 items-center justify-center rounded-full bg-sage px-7 py-3 text-sm font-600 text-cream transition hover:bg-ink">
                                Contactar a {{ \Illuminate\Support\Str::before($profile->user->name, ' ') }}
                            </a>
                        @endif
                        <x-save-button :profile="$profile" />
                    </div>
                @else
                    <a href="{{ route('login') }}"
                       class="flex items-center justify-center rounded-full border border-line px-7 py-3 text-sm font-500 text-warmgray transition hover:border-sage hover:text-sage">
                        Inicia sesión como contratante para contactar
                    </a>
                @endauth

                {{-- Bio --}}
                @if ($profile->bio)
                    <div>
                        <h2 class="font-serif text-xl font-500 text-ink">Sobre {{ \Illuminate\Support\Str::before($nombre, ' ') }}</h2>
                        <p class="mt-2 whitespace-pre-line text-warmgray">{{ $profile->bio }}</p>
                    </div>
                @endif

                {{-- Disciplinas --}}
                @if ($profile->disciplines->isNotEmpty())
                    <div>
                        <h2 class="font-serif text-xl font-500 text-ink">Disciplinas</h2>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($profile->disciplines as $d)
                                <span class="rounded-full bg-sage/10 px-3 py-1 text-sm text-sage">{{ $d->nombre }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Certificaciones --}}
                @if ($profile->certifications->isNotEmpty())
                    <div>
                        <h2 class="font-serif text-xl font-500 text-ink">Certificaciones</h2>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($profile->certifications as $c)
                                <span class="rounded-full border border-line px-3 py-1 text-sm text-ink">{{ $c->nombre }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Contacto / redes (el botón "Contactar" para contratantes llega en F5) --}}
                @php $s = $profile->socials ?? []; @endphp
                @if (! empty($s['web']) || ! empty($s['instagram']) || ! empty($s['tiktok']))
                    <div class="flex flex-wrap gap-4 border-t border-line pt-6 text-sm">
                        @if (! empty($s['web']))
                            <a href="{{ $s['web'] }}" target="_blank" rel="noopener" class="text-sage underline hover:text-ink">Sitio web ↗</a>
                        @endif
                        @if (! empty($s['instagram']))
                            <span class="text-warmgray">Instagram: {{ $s['instagram'] }}</span>
                        @endif
                        @if (! empty($s['tiktok']))
                            <span class="text-warmgray">TikTok: {{ $s['tiktok'] }}</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-public-layout>
