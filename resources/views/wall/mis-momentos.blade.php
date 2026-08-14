<x-app-layout>
    <x-slot name="header">
        {{-- Título editable desde /admin/configuracion-sitio (tab Comparte un momento). --}}
        <h2 class="font-serif text-2xl font-medium text-ink">{{ landing('momento_pagina_titulo') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        <x-back-link :href="route('dashboard')" :value="__('← Volver al panel')" />

        {{-- Copy editable — Marian ajusta desde el admin sin código. --}}
        <x-guia-inline :titulo="landing('momento_bloque_titulo')" tono="beige">
            <p class="font-medium text-ink">{{ landing('momento_subtitulo') }}</p>
            <p class="mt-2">{{ landing('momento_bloque_body') }}</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-warmgray">
                <li>{{ landing('momento_bullet_1') }}</li>
                <li>{{ landing('momento_bullet_2') }}</li>
                <li>{{ landing('momento_bullet_3') }}</li>
            </ul>
        </x-guia-inline>

        @if (session('status') === 'momento-enviado')
            <div class="mt-6 rounded-xl border border-sage/40 bg-sage/10 px-5 py-3 text-sm text-ink">
                <strong>{{ landing('wall_mis_momentos_flash_enviado_titulo') }}</strong>
                {{ landing('wall_mis_momentos_flash_enviado_body') }}
            </div>
        @elseif (session('status') === 'momento-archivado')
            <div class="mt-6 rounded-xl border border-line bg-white px-5 py-3 text-sm text-warmgray">
                {{ __('Momento archivado. Ya no aparecerá en Comunidad.') }}
            </div>
        @endif

        {{-- Form del wall: layout limpio que pidió la clienta (docx PRUEBA KINVOO):
             "Foto o video" con drop-zone · "Cuéntanos qué pasó" · Nombre del estudio auto. --}}
        @php
            $nombreEstudio = auth()->user()->companyProfile?->company_name
                ?? auth()->user()->name;
        @endphp
        <form method="POST" action="{{ route('wall.guardar') }}" enctype="multipart/form-data"
              class="mt-6 space-y-5 rounded-2xl border border-line bg-white p-6 sm:p-8"
              x-data="{ fileName: '' }">
            @csrf

            {{-- Foto o video · drop-zone estilo social. --}}
            <div>
                <label for="media_file" class="block text-sm font-medium text-ink">{{ __('Foto o video') }}</label>
                <label for="media_file"
                       class="mt-2 flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-line bg-cream/40 px-6 py-10 text-center transition hover:border-sage hover:bg-sage/5"
                       @dragover.prevent="$el.classList.add('border-sage','bg-sage/5')"
                       @dragleave.prevent="$el.classList.remove('border-sage','bg-sage/5')"
                       @drop.prevent="
                           const f = $event.dataTransfer.files[0];
                           if (f) { document.getElementById('media_file').files = $event.dataTransfer.files; fileName = f.name; }
                           $el.classList.remove('border-sage','bg-sage/5');
                       ">
                    <svg class="h-8 w-8 text-warmgray" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3v13m0 0l-4-4m4 4l4-4M4 21h16"/>
                    </svg>
                    <span class="text-sm text-warmgray" x-show="!fileName">{{ __('Arrastra o selecciona un archivo') }}</span>
                    <span class="text-sm font-medium text-sage" x-show="fileName" x-text="fileName"></span>
                </label>
                <input id="media_file" name="media_file" type="file" required
                       accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime"
                       class="sr-only"
                       @change="fileName = $event.target.files[0]?.name || ''">
                <p class="mt-1 text-xs text-warmgray">{{ __('JPG · PNG · WEBP · MP4 · WEBM. Máximo 25 MB.') }}</p>
                @error('media_file')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Cuéntanos qué pasó (antes "Tu frase"). --}}
            <div>
                <label for="caption" class="block text-sm font-medium text-ink">{{ __('Cuéntanos qué pasó') }}</label>
                <input id="caption" name="caption" type="text" required maxlength="280"
                       value="{{ old('caption') }}"
                       placeholder="{{ __('Hoy celebramos el cumpleaños de una de nuestras coaches — pastel y sorpresa incluidos.') }}"
                       class="mt-1 w-full min-h-[44px] rounded-xl border border-line px-3 py-2 text-sm">
                @error('caption')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Nombre del estudio — auto-poblado desde el perfil, informativo. --}}
            <div>
                <label class="block text-sm font-medium text-ink">{{ __('Nombre del estudio') }}</label>
                <input type="text" value="{{ $nombreEstudio }}" readonly
                       class="mt-1 w-full min-h-[44px] rounded-xl border border-line bg-cream/40 px-3 py-2 text-sm text-warmgray cursor-not-allowed"
                       aria-describedby="nombre-help">
                <p id="nombre-help" class="mt-1 text-xs text-warmgray">{{ __('Aparece automático con tu perfil.') }}</p>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="min-h-[44px] rounded-full bg-sage px-6 py-2 text-sm font-semibold text-cream hover:bg-ink">
                    {{ landing('wall_mis_momentos_cta_publicar') }}
                </button>
            </div>
        </form>

        {{-- Listado de mis momentos con estado de moderación. --}}
        <h3 class="mt-10 font-serif text-lg font-medium text-ink">{{ __('Mis momentos') }}</h3>
        @forelse ($posts as $post)
            @php
                // Usar asset() en vez de Storage::url() para respetar el host actual
                // (localhost vs 127.0.0.1) — evita mixed-origin en el browser.
                $url = asset('storage/'.$post->media_path);
                $estados = [
                    \App\Models\WallPost::STATUS_PENDING  => ['label' => __('En revisión'),  'clase' => 'bg-beige text-ink'],
                    \App\Models\WallPost::STATUS_APPROVED => ['label' => __('Publicado'),    'clase' => 'bg-sage/20 text-sage'],
                    \App\Models\WallPost::STATUS_REJECTED => ['label' => __('No aprobado'),  'clase' => 'bg-red-100 text-red-700'],
                    \App\Models\WallPost::STATUS_ARCHIVED => ['label' => __('Archivado'),    'clase' => 'bg-cream text-warmgray'],
                ];
                $badge = $estados[$post->status] ?? $estados['pending'];
            @endphp
            <article class="mt-4 overflow-hidden rounded-2xl border border-line bg-white">
                @if ($post->media_type === 'video')
                    <video src="{{ $url }}" controls preload="metadata"
                           class="max-h-96 w-full bg-ink object-contain"></video>
                @else
                    <img src="{{ $url }}" alt="{{ $post->caption }}"
                         class="max-h-96 w-full bg-cream object-contain">
                @endif
                <div class="p-4">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <p class="text-sm text-ink">{{ $post->caption }}</p>
                        <span class="rounded-full px-3 py-1 text-xs font-medium {{ $badge['clase'] }}">{{ $badge['label'] }}</span>
                    </div>
                    <p class="mt-1 text-xs text-warmgray">
                        {{ $post->created_at->translatedFormat('d M Y') }}
                        @if ($post->status === \App\Models\WallPost::STATUS_REJECTED && $post->moderation_reason)
                            · {{ __('Motivo:') }} {{ $post->moderation_reason }}
                        @endif
                    </p>
                    @if (in_array($post->status, [\App\Models\WallPost::STATUS_PENDING, \App\Models\WallPost::STATUS_APPROVED], true))
                        <form method="POST" action="{{ route('wall.archivar', $post) }}" class="mt-3"
                              onsubmit="return confirm('{{ __('¿Archivar este momento? Ya no aparecerá en Comunidad.') }}');">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="inline-flex min-h-[36px] items-center rounded-full border border-line px-3 py-1.5 text-xs font-medium text-warmgray hover:border-red-300 hover:text-red-600">
                                {{ __('Archivar') }}
                            </button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <p class="mt-4 rounded-2xl border border-line bg-white px-6 py-8 text-center text-warmgray">
                {{ landing('wall_mis_momentos_empty_state') }}
            </p>
        @endforelse

        <div class="mt-6">{{ $posts->links() }}</div>
    </div>
</x-app-layout>
