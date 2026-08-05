@props([
    'titulo' => __('¿Cómo funciona?'),
    'tono' => 'sage', // sage | beige | yellow
])

@php
    $bg = match ($tono) {
        'beige' => 'border-line bg-cream/60',
        'yellow' => 'border-yellow-200 bg-yellow-50',
        default => 'border-sage/40 bg-sage/10',
    };
@endphp

<details class="rounded-2xl border {{ $bg }} px-5 py-4 text-sm text-ink" open>
    <summary class="cursor-pointer font-medium">
        <span aria-hidden="true" class="mr-1">💡</span>{{ $titulo }}
    </summary>
    <div class="mt-3 space-y-2 text-warmgray">
        {{ $slot }}
    </div>
</details>
