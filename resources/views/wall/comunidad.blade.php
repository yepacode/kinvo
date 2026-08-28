<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ landing('wall_comunidad_header_titulo') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
        <x-back-link :href="route('dashboard')" :value="__('← Volver al panel')" />

        <x-guia-inline :titulo="landing('wall_comunidad_guia_titulo')" tono="beige">
            <p>{{ landing('wall_comunidad_guia_body') }}</p>
        </x-guia-inline>

        {{-- Flashes (venían de la vista mis-momentos que ya no existe como tal). --}}
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

        {{-- Feedback Karla 27-ago: "Mis momentos" y "Comunidad" se unificaron
             en esta página. Si el estudio tiene benefit para publicar, mostramos
             el form arriba; abajo va el feed global + una sección con sus propios
             posts (moderación pending / aprobado / archivado). --}}
        @if ($puedePublicar ?? false)
            @php
                $nombreEstudio = auth()->user()->companyProfile?->company_name
                    ?? auth()->user()->name;
            @endphp
            <details class="mt-6 rounded-2xl border border-line bg-white p-6 sm:p-8" open>
                <summary class="cursor-pointer font-serif text-lg font-medium text-ink">
                    {{ landing('momento_bloque_titulo') }}
                </summary>
                <form method="POST" action="{{ route('wall.guardar') }}" enctype="multipart/form-data"
                      class="mt-4 space-y-5" x-data="{ fileName: '' }">
                    @csrf
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
                        <p class="mt-1 text-xs text-warmgray">{{ __('JPG · PNG · WEBP · MP4 · WEBM. Máximo 100 MB.') }}</p>
                        @error('media_file')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="caption" class="block text-sm font-medium text-ink">{{ __('Cuéntanos qué pasó') }}</label>
                        <input id="caption" name="caption" type="text" required maxlength="280"
                               value="{{ old('caption') }}"
                               placeholder="{{ __('Hoy celebramos el cumpleaños de una de nuestras coaches — pastel y sorpresa incluidos.') }}"
                               class="mt-1 w-full min-h-[44px] rounded-xl border border-line px-3 py-2 text-sm">
                        @error('caption')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ink">{{ __('Nombre del estudio') }}</label>
                        <input type="text" value="{{ $nombreEstudio }}" readonly
                               class="mt-1 w-full min-h-[44px] rounded-xl border border-line bg-cream/40 px-3 py-2 text-sm text-warmgray cursor-not-allowed">
                        <p class="mt-1 text-xs text-warmgray">{{ __('Aparece automático con tu perfil.') }}</p>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="min-h-[44px] rounded-full bg-sage px-6 py-2 text-sm font-semibold text-cream hover:bg-ink">
                            {{ landing('wall_mis_momentos_cta_publicar') }}
                        </button>
                    </div>
                </form>
            </details>

            {{-- Mis momentos (con estado de moderación) --}}
            @if (($misPosts ?? collect())->isNotEmpty())
                <h3 class="mt-10 font-serif text-lg font-medium text-ink">{{ __('Mis momentos') }}</h3>
                <div class="mt-3 space-y-4">
                    @foreach ($misPosts as $post)
                        @php
                            $url = asset('storage/'.$post->media_path);
                            $estados = [
                                \App\Models\WallPost::STATUS_PENDING  => ['label' => __('En revisión'),  'clase' => 'bg-beige text-ink'],
                                \App\Models\WallPost::STATUS_APPROVED => ['label' => __('Publicado'),    'clase' => 'bg-sage/20 text-sage'],
                                \App\Models\WallPost::STATUS_REJECTED => ['label' => __('No aprobado'),  'clase' => 'bg-red-100 text-red-700'],
                                \App\Models\WallPost::STATUS_ARCHIVED => ['label' => __('Archivado'),    'clase' => 'bg-cream text-warmgray'],
                            ];
                            $badge = $estados[$post->status] ?? $estados['pending'];
                        @endphp
                        <article class="overflow-hidden rounded-2xl border border-line bg-white">
                            @if ($post->media_type === 'video')
                                <video src="{{ $url }}" controls preload="metadata"
                                       class="max-h-72 w-full bg-ink object-contain"></video>
                            @else
                                <img src="{{ $url }}" alt="{{ $post->caption }}"
                                     class="max-h-72 w-full bg-cream object-contain">
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
                    @endforeach
                </div>
            @endif
        @endif

        {{-- Feed global (posts aprobados de la comunidad). --}}
        <h3 class="mt-10 font-serif text-lg font-medium text-ink">{{ __('Comunidad Kinvoo') }}</h3>

        @if ($posts->isEmpty())
            <div class="mt-3 rounded-2xl border border-line bg-white px-6 py-12 text-center">
                <p class="text-warmgray">{{ landing('wall_comunidad_empty_state') }}</p>
            </div>
        @else
            <div class="mt-3 grid gap-6 sm:grid-cols-2">
                @foreach ($posts as $post)
                    @php
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
