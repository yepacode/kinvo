<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ landing('pulso_coach_header_titulo') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6">
        <x-back-link :href="route('dashboard')" :value="__('← Volver al panel')" />

        <x-guia-inline :titulo="landing('pulso_coach_guia_titulo')" tono="beige">
            <p>{{ landing('pulso_coach_guia_body') }}</p>
        </x-guia-inline>

        @if (session('status') === 'pulso-guardado')
            <div class="mt-6 rounded-xl border border-sage/40 bg-sage/10 px-5 py-3 text-sm text-ink">
                {{ __('¡Gracias! Guardamos tu respuesta.') }}
            </div>
        @endif

        <form method="POST" action="{{ route('pulso.guardar') }}"
              class="mt-6 space-y-5 rounded-2xl border border-line bg-white p-6 sm:p-8">
            @csrf
            <div>
                <label class="block text-sm font-medium text-ink">{{ __('En general, ¿cómo calificas a tu estudio esta semana?') }} *</label>
                <div class="mt-2 flex items-center gap-3" role="radiogroup"
                     aria-label="{{ __('Calificación de tu estudio') }}">
                    @for ($i = 1; $i <= 5; $i++)
                        <label class="cursor-pointer text-2xl leading-none">
                            <input type="radio" name="rating" value="{{ $i }}" required class="peer sr-only" aria-label="{{ $i }} {{ __('estrellas') }}">
                            <span class="text-line peer-checked:text-yellow-500 hover:text-yellow-500 peer-focus-visible:ring-2 peer-focus-visible:ring-sage peer-focus-visible:ring-offset-2 rounded">★</span>
                        </label>
                    @endfor
                    <span class="text-xs text-warmgray">{{ __('1 = muy mal · 5 = excelente') }}</span>
                </div>
            </div>

            <div>
                <label for="answer_energy" class="block text-sm font-medium text-ink">{{ __('¿Qué está haciendo bien tu estudio?') }}</label>
                <input id="answer_energy" name="answer_energy" type="text" maxlength="500"
                       placeholder="{{ __('Ej: buen ambiente, apoyo del equipo...') }}"
                       class="mt-1 w-full min-h-[44px] rounded-xl border border-line px-3 py-2 text-sm">
            </div>
            <div>
                <label for="answer_growth" class="block text-sm font-medium text-ink">{{ __('¿En qué podría mejorar tu estudio?') }}</label>
                <input id="answer_growth" name="answer_growth" type="text" maxlength="500"
                       placeholder="{{ __('Ej: comunicación, horarios, espacios...') }}"
                       class="mt-1 w-full min-h-[44px] rounded-xl border border-line px-3 py-2 text-sm">
            </div>
            <div>
                <label for="answer_support" class="block text-sm font-medium text-ink">{{ __('¿Qué apoyo necesitas de tu estudio?') }}</label>
                <input id="answer_support" name="answer_support" type="text" maxlength="500"
                       placeholder="{{ __('Ej: más material, capacitación, acompañamiento...') }}"
                       class="mt-1 w-full min-h-[44px] rounded-xl border border-line px-3 py-2 text-sm">
            </div>

            <div class="flex justify-end">
                <button type="submit" class="min-h-[44px] rounded-full bg-sage px-6 py-2 text-sm font-semibold text-cream hover:bg-ink">
                    {{ landing('pulso_coach_cta_enviar') }}
                </button>
            </div>
        </form>

        <h3 class="mt-10 font-serif text-lg font-medium text-ink">{{ landing('pulso_coach_historial_titulo') }}</h3>
        @forelse ($historial as $r)
            <div class="mt-3 rounded-2xl border border-line bg-white px-5 py-4">
                <div class="flex items-center justify-between">
                    <span class="text-yellow-500">
                        @for ($j = 1; $j <= 5; $j++)@if ($j <= $r->rating)★@else<span class="text-line">★</span>@endif @endfor
                    </span>
                    <span class="text-xs text-warmgray">{{ $r->created_at->translatedFormat('d M Y') }}</span>
                </div>
                @if ($r->answer_energy || $r->answer_growth || $r->answer_support)
                    <ul class="mt-2 space-y-1 text-sm text-ink/90">
                        @if ($r->answer_energy)<li>⚡ {{ $r->answer_energy }}</li>@endif
                        @if ($r->answer_growth)<li>🌱 {{ $r->answer_growth }}</li>@endif
                        @if ($r->answer_support)<li>🤝 {{ $r->answer_support }}</li>@endif
                    </ul>
                @endif
            </div>
        @empty
            <p class="mt-3 rounded-2xl border border-line bg-white px-6 py-8 text-center text-warmgray">
                {{ landing('pulso_coach_empty_state') }}
            </p>
        @endforelse
        <div class="mt-6">{{ $historial->links() }}</div>
    </div>
</x-app-layout>
