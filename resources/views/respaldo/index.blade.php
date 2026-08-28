<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ landing('respaldo_header_titulo') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        {{-- Tras el rediseño 27-ago el coach ya no llega a Respaldo desde
             "Mis beneficios" (unificado en Expediente); volvemos ahí. --}}
        <x-back-link :href="route('expediente.index')" :value="__('← Expediente')" />

        <x-guia-inline :titulo="landing('respaldo_guia_titulo')" tono="beige">
            <p>{{ landing('respaldo_guia_body') }}</p>
        </x-guia-inline>

        @if (session('status') === 'respaldo-enviado')
            <div class="mt-6 rounded-xl border border-sage/40 bg-sage/10 px-5 py-3 text-sm text-ink">
                <strong>{{ landing('respaldo_flash_enviado_titulo') }}</strong> {{ __('El equipo Kinvoo se pondrá en contacto para agendar.') }}
            </div>
        @endif

        <form method="POST" action="{{ route('respaldo.solicitar') }}"
              class="mt-6 space-y-4 rounded-2xl border border-line bg-white p-6 sm:p-8">
            @csrf
            <div>
                <label for="type" class="block text-sm font-medium text-ink">{{ __('Tipo de sesión') }} *</label>
                <select id="type" name="type" required
                        class="mt-1 w-full min-h-[44px] rounded-xl border border-line px-3 py-2 text-sm">
                    <option value="telemedicine">{{ __('🩺 Telemedicina — consulta médica a distancia') }}</option>
                    @if ($puedeFisio)
                        <option value="physio">{{ __('💪 Fisioterapia — solo Plan Plus') }}</option>
                    @endif
                </select>
                @unless ($puedeFisio)
                    <p class="mt-1 text-xs text-warmgray">{{ __('La fisioterapia se desbloquea con el Plan Plus.') }}</p>
                @endunless
            </div>

            <div>
                <label for="preferred_slot" class="block text-sm font-medium text-ink">{{ __('¿Cuándo te queda mejor? (opcional)') }}</label>
                <input id="preferred_slot" name="preferred_slot" type="text" maxlength="200"
                       placeholder="{{ __('Ej: lunes o miércoles por la tarde') }}"
                       class="mt-1 w-full min-h-[44px] rounded-xl border border-line px-3 py-2 text-sm">
            </div>

            <div>
                <label for="note" class="block text-sm font-medium text-ink">{{ __('Cuéntanos brevemente (opcional)') }}</label>
                <textarea id="note" name="note" rows="3" maxlength="1000"
                          placeholder="{{ __('Un motivo, un contexto — lo que ayude a Kinvoo a atenderte mejor.') }}"
                          class="mt-1 w-full rounded-xl border border-line px-3 py-2 text-sm"></textarea>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="min-h-[44px] rounded-full bg-sage px-6 py-2 text-sm font-semibold text-cream hover:bg-ink">
                    {{ landing('respaldo_cta_enviar') }}
                </button>
            </div>
        </form>

        <h3 class="mt-10 font-serif text-lg font-medium text-ink">{{ landing('respaldo_solicitudes_titulo') }}</h3>
        @php
            $estadoBadge = [
                'pending'   => ['label' => __('Pendiente'),  'clase' => 'bg-beige text-ink'],
                'scheduled' => ['label' => __('Agendada'),   'clase' => 'bg-sage/20 text-sage'],
                'done'      => ['label' => __('Realizada'),  'clase' => 'bg-cream text-warmgray'],
                'cancelled' => ['label' => __('Cancelada'),  'clase' => 'bg-red-100 text-red-700'],
            ];
        @endphp
        @forelse ($solicitudes as $s)
            @php $b = $estadoBadge[$s->status] ?? $estadoBadge['pending']; @endphp
            <div class="mt-3 flex flex-wrap items-start justify-between gap-2 rounded-2xl border border-line bg-white px-5 py-4">
                <div>
                    <p class="font-medium text-ink">
                        @if ($s->type === 'telemedicine') 🩺 {{ __('Telemedicina') }}
                        @else 💪 {{ __('Fisioterapia') }}
                        @endif
                    </p>
                    <p class="mt-1 text-xs text-warmgray">
                        {{ $s->created_at->translatedFormat('d M Y') }}
                        @if ($s->scheduled_for) · {{ __('Agendada:') }} {{ $s->scheduled_for->translatedFormat('d M H:i') }} @endif
                    </p>
                    @if ($s->admin_note)
                        <p class="mt-2 text-sm text-ink/80">{{ __('Nota Kinvoo:') }} {{ $s->admin_note }}</p>
                    @endif
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-medium {{ $b['clase'] }}">{{ $b['label'] }}</span>
            </div>
        @empty
            <p class="mt-3 rounded-2xl border border-line bg-white px-6 py-8 text-center text-warmgray">
                {{ landing('respaldo_empty_state') }}
            </p>
        @endforelse
        <div class="mt-6">{{ $solicitudes->links() }}</div>
    </div>
</x-app-layout>
