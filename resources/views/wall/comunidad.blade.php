<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ landing('wall_comunidad_header_titulo') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
        <x-back-link :href="route('dashboard')" :value="__('← Volver al panel')" />

        <x-guia-inline :titulo="landing('wall_comunidad_guia_titulo')" tono="beige">
            <p>{{ landing('wall_comunidad_guia_body') }}</p>
        </x-guia-inline>

        @auth
            @if (auth()->user()->esContratante())
                <div class="mt-4 flex justify-end">
                    <a href="{{ route('wall.mis-momentos') }}"
                       class="inline-flex min-h-[44px] items-center rounded-full bg-sage px-5 py-2 text-sm font-semibold text-cream hover:bg-ink">
                        {{ landing('wall_comunidad_cta_publicar') }}
                    </a>
                </div>
            @endif
        @endauth

        @if ($posts->isEmpty())
            <div class="mt-6 rounded-2xl border border-line bg-white px-6 py-12 text-center">
                <p class="text-warmgray">{{ landing('wall_comunidad_empty_state') }}</p>
            </div>
        @else
            <div class="mt-6 grid gap-6 sm:grid-cols-2">
                @foreach ($posts as $post)
                    @php
                        // asset() respeta el host actual (localhost vs 127.0.0.1)
                        // — evita el ícono roto cuando el browser está en un host
                        // distinto al APP_URL. Ver H4/H5 issue del wall.
                        $url = asset('storage/'.$post->media_path);
                        $logoRel = $post->author?->companyProfile?->logo_path;
                        $logoUrl = $logoRel ? asset('storage/'.$logoRel) : null;
                        $company = $post->author?->companyProfile?->company_name ?? $post->author?->name ?? __('Estudio Kinvoo');
                    @endphp
                    <article class="overflow-hidden rounded-2xl border border-line bg-white">
                        @if ($post->media_type === 'video')
                            <video src="{{ $url }}" controls preload="metadata"
                                   class="aspect-video w-full bg-ink object-cover"></video>
                        @else
                            <img src="{{ $url }}" alt="{{ $post->caption }}" loading="lazy"
                                 class="aspect-video w-full bg-cream object-cover">
                        @endif
                        <div class="p-4">
                            <div class="flex items-center gap-2">
                                @if ($logoUrl)
                                    <img src="{{ $logoUrl }}" alt="{{ $company }}"
                                         class="h-8 w-8 rounded-full border border-line bg-cream object-cover">
                                @else
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full border border-line bg-cream text-xs font-medium text-warmgray" aria-hidden="true">
                                        {{ mb_strtoupper(mb_substr($company, 0, 1)) }}
                                    </div>
                                @endif
                                <span class="text-sm font-medium text-ink">{{ $company }}</span>
                                <span class="ml-auto text-xs text-warmgray">{{ $post->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="mt-3 text-sm text-ink/90">{{ $post->caption }}</p>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-8">{{ $posts->links() }}</div>
        @endif
    </div>
</x-app-layout>
