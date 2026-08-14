@props([
    'path',                 // storage-relative path, ej. 'perfiles/xxx.png'
    'type' => 'image',      // 'image' | 'video'
    'alt' => '',
    'label' => null,        // texto opcional en el overlay hover (ej. "Ver más grande")
])
@php
    // asset() respeta el host del request — evita mixed-origin cuando APP_URL
    // no coincide con el host del browser (ver fix del wall).
    $url = asset('storage/'.$path);
    $isVideo = $type === 'video';
@endphp

{{-- H3 · petición cliente: "multimedia en miniatura o carrusel — no tan grande".
     Miniatura fija (h-40 w-56 = 160×224px). Click abre lightbox a pantalla completa. --}}
<div x-data="{ open: false }" {{ $attributes->merge(['class' => 'inline-block']) }}>
    <button type="button" @click="open = true"
            class="group relative block h-40 w-56 overflow-hidden rounded-xl border border-line bg-cream focus:outline-none focus-visible:ring-2 focus-visible:ring-sage"
            aria-label="{{ __('Ampliar multimedia') }}">
        @if ($isVideo)
            <video src="{{ $url }}" class="h-full w-full object-cover"
                   muted playsinline preload="metadata"></video>
            {{-- Botón play visual, no funcional (el click abre el modal). --}}
            <span class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/30 transition group-hover:bg-black/50">
                <svg class="h-12 w-12 text-white drop-shadow" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M8 5v14l11-7z"/>
                </svg>
            </span>
        @else
            <img src="{{ $url }}" alt="{{ $alt }}" loading="lazy"
                 class="h-full w-full object-cover transition group-hover:scale-105">
        @endif
        <span class="pointer-events-none absolute bottom-1.5 right-1.5 rounded-full bg-black/60 px-2.5 py-0.5 text-[10px] font-medium text-white opacity-0 transition group-hover:opacity-100">
            {{ $label ?: __('Ver más grande') }}
        </span>
    </button>

    {{-- Lightbox teleportado al body para escapar de contenedores con overflow/z-index. --}}
    <template x-teleport="body">
        <div x-show="open" x-transition.opacity
             class="fixed inset-0 z-[100] flex items-center justify-center bg-black/85 p-4"
             @click.self="open = false"
             @keyup.escape.window="open = false"
             role="dialog" aria-modal="true" style="display: none;">
            <button type="button" @click="open = false"
                    class="absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-ink hover:bg-white"
                    aria-label="{{ __('Cerrar') }}">
                ✕
            </button>
            @if ($isVideo)
                <video src="{{ $url }}" controls autoplay playsinline
                       class="max-h-[90vh] max-w-[95vw] rounded-md shadow-2xl"></video>
            @else
                <img src="{{ $url }}" alt="{{ $alt }}"
                     class="max-h-[90vh] max-w-[95vw] rounded-md object-contain shadow-2xl">
            @endif
        </div>
    </template>
</div>
