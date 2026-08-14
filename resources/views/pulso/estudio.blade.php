<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ landing('pulso_estudio_titulo') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
        <x-back-link :href="route('equipo.index')" :value="__('← Mi equipo')" />

        <x-guia-inline :titulo="__('🌡️ Cómo va tu equipo')" tono="beige">
            <p>{{ __('Este pulso agregado te ayuda a leer el momento de tu equipo. Kinvoo no muestra respuestas individuales — sólo tendencias.') }}</p>
        </x-guia-inline>

        <div class="mt-6 grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-line bg-white p-5 text-center">
                <div class="text-xs uppercase tracking-wider text-warmgray">{{ __('Promedio') }}</div>
                <div class="mt-1 text-3xl font-semibold text-ink">{{ number_format($promedio, 2) }}</div>
                <div class="mt-1 text-yellow-500">
                    @for ($i = 1; $i <= 5; $i++)@if ($i <= round($promedio))★@else<span class="text-line">★</span>@endif @endfor
                </div>
            </div>
            <div class="rounded-2xl border border-line bg-white p-5 text-center">
                <div class="text-xs uppercase tracking-wider text-warmgray">{{ __('Respuestas totales') }}</div>
                <div class="mt-1 text-3xl font-semibold text-ink">{{ $total }}</div>
            </div>
            <div class="rounded-2xl border border-line bg-white p-5 text-center">
                <div class="text-xs uppercase tracking-wider text-warmgray">{{ __('Última semana') }}</div>
                <div class="mt-1 text-3xl font-semibold text-ink">
                    {{ $ultimas->where('created_at', '>=', now()->subDays(7))->count() }}
                </div>
            </div>
        </div>

        <h3 class="mt-10 font-serif text-lg font-medium text-ink">{{ __('Distribución de rating') }}</h3>
        <div class="mt-3 space-y-2 rounded-2xl border border-line bg-white p-5">
            @for ($r = 5; $r >= 1; $r--)
                @php $n = $porRating[$r] ?? 0; $pct = $total ? round(($n / $total) * 100) : 0; @endphp
                <div class="flex items-center gap-3 text-sm">
                    <span class="w-10 text-warmgray">{{ $r }} ★</span>
                    <div class="flex-1 h-3 rounded-full bg-cream">
                        <div class="h-3 rounded-full bg-sage" style="width: {{ $pct }}%"></div>
                    </div>
                    <span class="w-16 text-right text-warmgray">{{ $n }} ({{ $pct }}%)</span>
                </div>
            @endfor
        </div>

        <h3 class="mt-10 font-serif text-lg font-medium text-ink">{{ __('Voces recientes') }}</h3>
        <p class="text-xs text-warmgray">{{ __('Respuestas breves, presentadas de forma anónima.') }}</p>
        @forelse ($ultimas as $r)
            @if ($r->answer_energy || $r->answer_growth || $r->answer_support)
                <div class="mt-3 rounded-2xl border border-line bg-white px-5 py-4">
                    <div class="flex items-center justify-between text-xs text-warmgray">
                        <span class="text-yellow-500">
                            @for ($j = 1; $j <= 5; $j++)@if ($j <= $r->rating)★@else<span class="text-line">★</span>@endif @endfor
                        </span>
                        <span>{{ $r->created_at->diffForHumans() }}</span>
                    </div>
                    <ul class="mt-2 space-y-1 text-sm text-ink/90">
                        @if ($r->answer_energy)<li>⚡ {{ $r->answer_energy }}</li>@endif
                        @if ($r->answer_growth)<li>🌱 {{ $r->answer_growth }}</li>@endif
                        @if ($r->answer_support)<li>🤝 {{ $r->answer_support }}</li>@endif
                    </ul>
                </div>
            @endif
        @empty
            <p class="mt-3 rounded-2xl border border-line bg-white px-6 py-8 text-center text-warmgray">
                {{ __('Todavía nadie ha respondido. Anima a tu equipo a contestar su Pulso.') }}
            </p>
        @endforelse
    </div>
</x-app-layout>
