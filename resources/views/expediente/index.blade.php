<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ landing('expediente_header_titulo') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
        <x-back-link :href="route('dashboard')" :value="__('← Volver al panel')" />

        {{-- Copy exacto del cliente (docx PRUEBA KINVOO, ago-2026). --}}
        <p class="mb-6 text-sm text-warmgray">
            {{ landing('expediente_intro_descripcion') }}
        </p>

        {{-- 4 tarjetas de beneficios (Telemedicina / Fisio / Seguro / Desarrollo). --}}
        <div class="grid gap-4 sm:grid-cols-2">
            @php
                $iconos = [
                    'telemedicina' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3v6a4 4 0 0 0 8 0V3M6 21v-3a6 6 0 0 1 12 0v3"/><circle cx="12" cy="14" r="1.5"/></svg>',
                    'fisioterapia' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="5" r="2"/><path d="M12 7v4M5 12h4l3-1 3 1h4M8 22l4-8 4 8"/></svg>',
                    'seguro'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>',
                    'desarrollo'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6M10 22h4M12 2a6 6 0 0 0-4 10.5c1 1 1.5 2 1.5 3.5h5c0-1.5.5-2.5 1.5-3.5A6 6 0 0 0 12 2z"/></svg>',
                ];
                $colorIcono = ['telemedicina'=>'text-info-500 sm:text-blue-500', 'fisioterapia'=>'text-amber-600', 'seguro'=>'text-sage', 'desarrollo'=>'text-amber-500'];
                $badgeStyle = [
                    'success' => 'bg-sage/15 text-sage',
                    'info'    => 'bg-blue-50 text-blue-600',
                    'gray'    => 'bg-cream text-warmgray',
                ];
            @endphp
            @foreach ($beneficios as $key => $b)
                <div class="flex items-start justify-between gap-3 rounded-2xl border border-line bg-white p-5 {{ $b['activo'] ? '' : 'opacity-90' }}">
                    <div class="flex min-w-0 items-start gap-3">
                        <div class="h-8 w-8 shrink-0 {{ $colorIcono[$key] ?? 'text-sage' }}">
                            {!! $iconos[$key] ?? '' !!}
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-ink">{{ $b['titulo'] }}</p>
                            <p class="mt-1 text-xs text-warmgray">{{ $b['subtitulo'] }}</p>
                        </div>
                    </div>
                    <span class="whitespace-nowrap rounded-full px-3 py-1 text-xs font-medium {{ $badgeStyle[$b['badgeColor']] }}">
                        {{ $b['badge'] }}
                    </span>
                </div>
            @endforeach
        </div>

        {{-- Charlas y capacitaciones a las que ha asistido. --}}
        <h3 class="mt-10 font-serif text-lg font-medium text-ink">
            {{ landing('expediente_charlas_titulo') }}
        </h3>
        @if ($charlas->isEmpty())
            <p class="mt-3 rounded-2xl border border-line bg-white px-6 py-8 text-center text-warmgray">
                {{ landing('expediente_charlas_empty_state') }}
            </p>
        @else
            <ul class="mt-3 divide-y divide-line/60 rounded-2xl border border-line bg-white">
                @foreach ($charlas as $c)
                    <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                        <span class="min-w-0 flex-1 truncate text-ink">{{ $c->titulo }}</span>
                        <time class="whitespace-nowrap text-xs text-warmgray">
                            {{ \Illuminate\Support\Carbon::parse($c->fecha)->translatedFormat('d M') }}
                        </time>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-app-layout>
