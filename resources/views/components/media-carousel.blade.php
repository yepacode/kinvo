@props([
    'items' => [],
    'legacyPath' => null,
    'legacyType' => 'image',
])
@php
    $data = collect($items)->map(fn ($m) => [
        'url' => asset('storage/'.$m->path),
        'type' => $m->type ?? 'image',
        'caption' => $m->caption ?? '',
    ])->values();
    if ($data->isEmpty() && $legacyPath) {
        $data = collect([[
            'url' => asset('storage/'.$legacyPath),
            'type' => $legacyType,
            'caption' => '',
        ]]);
    }
    $count = $data->count();
    // Duración total proporcional al número de items (5s por item).
    $seconds = max(15, $count * 5);
@endphp
@if ($count === 0)
    {{-- nada que mostrar --}}
@else
<div x-data="mediaCarousel({{ Illuminate\Support\Js::from($data) }})"
     {{ $attributes->merge(['class' => 'relative']) }}>
    {{-- Cinta animada (marquee) — se desliza sola, pausa al hover. --}}
    <div class="group overflow-hidden">
        <div class="kinvoo-marquee flex w-max items-stretch gap-3 py-1"
             style="--marquee-duration: {{ $seconds }}s;"
             role="listbox" aria-label="{{ __('Multimedia') }}">
            {{-- Duplicamos el set 2× para que el loop no tenga salto visible. --}}
            @foreach ($data as $i => $m)
                <button type="button" @click="open({{ $i }})"
                        class="group/item relative h-40 w-56 shrink-0 overflow-hidden rounded-xl border border-line bg-cream focus:outline-none focus-visible:ring-2 focus-visible:ring-sage"
                        aria-label="{{ __('Abrir multimedia :n', ['n' => $i + 1]) }}">
                    @if ($m['type'] === 'video')
                        <video src="{{ $m['url'] }}" muted playsinline preload="metadata"
                               class="h-full w-full object-cover"></video>
                        <span class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/30 transition group-hover/item:bg-black/50">
                            <svg class="h-10 w-10 text-white drop-shadow" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                        </span>
                    @else
                        <img src="{{ $m['url'] }}" alt="{{ $m['caption'] }}" loading="lazy"
                             class="h-full w-full object-cover">
                    @endif
                </button>
            @endforeach
            {{-- Duplicado para loop infinito sin salto. Aria-hidden para no duplicar en screen readers. --}}
            @foreach ($data as $i => $m)
                <button type="button" @click="open({{ $i }})" aria-hidden="true" tabindex="-1"
                        class="group/item relative h-40 w-56 shrink-0 overflow-hidden rounded-xl border border-line bg-cream">
                    @if ($m['type'] === 'video')
                        <video src="{{ $m['url'] }}" muted playsinline preload="metadata" class="h-full w-full object-cover"></video>
                        <span class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/30">
                            <svg class="h-10 w-10 text-white drop-shadow" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                        </span>
                    @else
                        <img src="{{ $m['url'] }}" alt="" loading="lazy" class="h-full w-full object-cover">
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    {{-- Lightbox con navegación prev/next. --}}
    <template x-teleport="body">
        <div x-show="isOpen" x-transition.opacity
             class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 p-4"
             @click.self="close()"
             @keyup.escape.window="close()"
             @keyup.left.window="prev()"
             @keyup.right.window="next()"
             role="dialog" aria-modal="true" style="display: none;">
            <button type="button" @click="close()"
                    class="absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-ink hover:bg-white z-10"
                    aria-label="{{ __('Cerrar') }}">✕</button>
            @if ($count > 1)
                <button type="button" @click="prev()"
                        class="absolute left-4 top-1/2 -translate-y-1/2 flex h-11 w-11 items-center justify-center rounded-full bg-white/80 text-ink hover:bg-white z-10"
                        aria-label="{{ __('Anterior') }}">‹</button>
                <button type="button" @click="next()"
                        class="absolute right-4 top-1/2 -translate-y-1/2 flex h-11 w-11 items-center justify-center rounded-full bg-white/80 text-ink hover:bg-white z-10"
                        aria-label="{{ __('Siguiente') }}">›</button>
            @endif
            <div class="flex max-h-[92vh] w-full max-w-6xl flex-col items-center gap-3">
                <template x-if="current.type === 'video'">
                    <video :src="current.url" controls autoplay playsinline
                           class="max-h-[80vh] max-w-full rounded-md shadow-2xl"></video>
                </template>
                <template x-if="current.type !== 'video'">
                    <img :src="current.url" :alt="current.caption"
                         class="max-h-[80vh] max-w-full rounded-md object-contain shadow-2xl">
                </template>
                <p x-show="current.caption" x-text="current.caption" class="text-center text-sm text-white/90"></p>
                @if ($count > 1)
                    <p class="text-xs text-white/70"><span x-text="index + 1"></span> / {{ $count }}</p>
                @endif
            </div>
        </div>
    </template>
</div>

@once
    {{-- Estilos y script del carrusel — se registran UNA vez por página. --}}
    <style>
        @keyframes kinvooMarquee {
            0%   { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .kinvoo-marquee {
            animation: kinvooMarquee var(--marquee-duration, 30s) linear infinite;
        }
        .group:hover .kinvoo-marquee,
        .kinvoo-marquee:hover,
        .kinvoo-marquee:focus-within {
            animation-play-state: paused;
        }
        @media (prefers-reduced-motion: reduce) {
            .kinvoo-marquee { animation: none; }
        }
    </style>
    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('mediaCarousel', (items) => ({
            items,
            index: 0,
            isOpen: false,
            get current() { return this.items[this.index] || {url:'',type:'image',caption:''}; },
            open(i) { this.index = i % this.items.length; this.isOpen = true; },
            close() { this.isOpen = false; },
            prev() { this.index = (this.index - 1 + this.items.length) % this.items.length; },
            next() { this.index = (this.index + 1) % this.items.length; },
        }));
    });
    </script>
@endonce
@endif
