<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ landing('equipo_pagina_titulo') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 space-y-8">
        <x-back-link :href="route('dashboard')" :value="__('← Volver al panel')" />

        {{-- H2 · texto proporcionado por la clienta (docx PRUEBA KINVOO, jul-2026). --}}
        <x-guia-inline :titulo="landing('equipo_guia_titulo')">
            <p>{{ landing('equipo_guia_intro') }}</p>
            <ol class="list-decimal space-y-1 pl-5">
                <li>{{ __('Escribes su correo en “Agregar a alguien al equipo”.') }}</li>
                <li>{{ __('El coach ve la invitación en sus notificaciones y le llega también un correo.') }}</li>
                <li>{{ __('Al aceptar queda listado como Activo y su cuidado suma al panel.') }}</li>
                <li>{{ __('Puedes “Quitar” a alguien cuando ya no colabora contigo, y agregar a nuevos miembros en su lugar.') }}</li>
            </ol>
        </x-guia-inline>

        {{-- Pulso Kinvoo (rediseño petición cliente, ago-2026):
             header PULSO KINVOO + score 8.2/10 + delta vs mes pasado +
             4 categorías nuevas (Salud/Cuerpo/Desarrollo/Respaldo) +
             comentario anónimo destacado del equipo. --}}
        <section class="rounded-2xl border border-line bg-white p-6">
            <p class="text-xs font-medium uppercase tracking-[0.2em] text-warmgray">{{ __('Pulso Kinvoo') }}</p>
            <div class="mt-2 flex flex-wrap items-baseline gap-3">
                <span class="font-serif text-5xl font-medium text-ink">
                    {{ $pulso['score'] !== null ? number_format($pulso['score'], 1) : '—' }}
                </span>
                <span class="text-sm text-warmgray">{{ __('/ 10') }}</span>
                @if ($pulso['delta'] !== null)
                    @php
                        $up = $pulso['delta'] > 0;
                        $flat = abs($pulso['delta']) < 0.05;
                        $badgeClase = $flat ? 'bg-cream text-warmgray'
                            : ($up ? 'bg-sage/15 text-sage' : 'bg-red-50 text-red-600');
                        $flecha = $flat ? '·' : ($up ? '↑' : '↓');
                    @endphp
                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ $badgeClase }}">
                        {{ $flecha }} {{ number_format(abs($pulso['delta']), 1) }} {{ __('vs. mes pasado') }}
                    </span>
                @endif
            </div>
            <p class="mt-2 text-sm text-warmgray">
                {{ __('Cómo se siente tu equipo esta semana, en sus propias palabras.') }}
            </p>

            {{-- 4 categorías nuevas (Salud / Cuerpo / Desarrollo / Respaldo). --}}
            <div class="mt-5 grid gap-3 sm:grid-cols-2 md:grid-cols-4">
                <div class="rounded-xl border border-line/60 bg-white p-4 text-center">
                    <svg class="mx-auto h-6 w-6 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    <div class="mt-2 text-2xl font-semibold text-ink">{{ $impacto['telemedicine'] }}</div>
                    <div class="text-sm font-medium text-ink">{{ __('Salud') }}</div>
                    <div class="text-xs text-warmgray">{{ __('Consultas médicas') }}</div>
                </div>
                <div class="rounded-xl border border-line/60 bg-white p-4 text-center">
                    <svg class="mx-auto h-6 w-6 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="5" r="2"/><path d="M12 7v4M5 12h4l3-1 3 1h4M8 22l4-8 4 8"/></svg>
                    <div class="mt-2 text-2xl font-semibold text-ink">{{ $impacto['physio'] }}</div>
                    <div class="text-sm font-medium text-ink">{{ __('Cuerpo') }}</div>
                    <div class="text-xs text-warmgray">{{ __('Sesiones de fisio') }}</div>
                </div>
                <div class="rounded-xl border border-line/60 bg-white p-4 text-center">
                    <svg class="mx-auto h-6 w-6 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6M10 22h4M12 2a6 6 0 0 0-4 10.5c1 1 1.5 2 1.5 3.5h5c0-1.5.5-2.5 1.5-3.5A6 6 0 0 0 12 2z"/></svg>
                    <div class="mt-2 text-2xl font-semibold text-ink">{{ $impacto['talk'] }}</div>
                    <div class="text-sm font-medium text-ink">{{ __('Desarrollo') }}</div>
                    <div class="text-xs text-warmgray">{{ __('Charlas y capacitaciones') }}</div>
                </div>
                <div class="rounded-xl border border-line/60 bg-white p-4 text-center">
                    <svg class="mx-auto h-6 w-6 text-sage" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                    <div class="mt-2 text-2xl font-semibold text-ink">{{ $impacto['insurance'] }}</div>
                    <div class="text-sm font-medium text-ink">{{ __('Respaldo') }}</div>
                    <div class="text-xs text-warmgray">{{ __('Pólizas vigentes') }}</div>
                </div>
            </div>

            {{-- Comentario anónimo destacado del equipo (si hay). --}}
            @if (! empty($pulso['comentario']))
                <div class="mt-6 rounded-xl border border-line/60 bg-cream/40 px-5 py-4">
                    <p class="text-sm italic text-ink/90">"{{ $pulso['comentario'] }}"</p>
                    <p class="mt-2 text-xs text-warmgray">— {{ __('Comentario anónimo de tu equipo') }}</p>
                </div>
            @endif

            {{-- H3 · petición cliente: calificación (1-5) + campo de texto libre. --}}
            @php $cp = auth()->user()->companyProfile; @endphp
            <form method="POST" action="{{ route('equipo.bienestar.nota') }}" class="mt-6 border-t border-line/60 pt-5">
                @csrf
                @if (session('status') === 'bienestar-guardado')
                    <p class="mb-3 rounded-lg border border-sage/40 bg-sage/10 px-3 py-2 text-sm text-ink">{{ __('Guardado. Podrás actualizar tu evaluación cuando quieras.') }}</p>
                @endif
                <label class="block text-sm font-medium text-ink">{{ landing('equipo_eval_pregunta') }}</label>
                <div class="mt-2 flex items-center gap-3" role="radiogroup" aria-label="{{ __('Calificación de bienestar') }}">
                    @for ($i = 1; $i <= 5; $i++)
                        <label class="cursor-pointer text-2xl leading-none">
                            <input type="radio" name="wellness_rating" value="{{ $i }}"
                                   @checked((int) old('wellness_rating', $cp?->wellness_rating) === $i)
                                   class="peer sr-only">
                            <span class="text-line peer-checked:text-yellow-500 hover:text-yellow-500">★</span>
                        </label>
                    @endfor
                    <span class="text-xs text-warmgray">{{ __('1 = por mejorar · 5 = excelente') }}</span>
                </div>
                <div class="mt-4">
                    <label for="wellness_notes" class="block text-sm font-medium text-ink">{{ __('Notas u observaciones (opcional)') }}</label>
                    <textarea id="wellness_notes" name="wellness_notes" rows="3" maxlength="2000"
                              placeholder="{{ __('Ej: renovar pólizas en agosto, agendar sesión de fisio grupal, etc.') }}"
                              class="mt-1 w-full rounded-xl border border-line px-3 py-2 text-sm">{{ old('wellness_notes', $cp?->wellness_notes) }}</textarea>
                </div>
                <div class="mt-3 flex justify-end">
                    <button type="submit" class="min-h-[44px] rounded-full bg-sage px-5 py-2 text-sm font-semibold text-cream">{{ __('Guardar evaluación') }}</button>
                </div>
            </form>
        </section>

        {{-- Invitar --}}
        <section class="rounded-2xl border border-line bg-white p-6">
            <h3 class="font-serif text-lg font-medium text-ink">{{ landing('equipo_invitar_titulo') }}</h3>
            @if (session('status') === 'invitacion-enviada')
                <div class="mt-2 rounded-lg border border-sage/40 bg-sage/10 px-3 py-2 text-sm text-ink">{{ __('Invitación enviada. El profesional la verá en su panel.') }}</div>
            @elseif (session('status') === 'profesional-no-invitable' || session('status') === 'profesional-no-existe')
                <div class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ __('No encontramos un profesional con ese correo en Kinvoo.') }}</div>
            @elseif (session('status') === 'cupos-alcanzados')
                <div class="mt-2 rounded-lg border border-yellow-200 bg-yellow-50 px-3 py-2 text-sm text-ink">
                    {{ __('Ya alcanzaste el máximo de coaches para tu plan. Escríbenos para ampliar cupos.') }}
                </div>
            @endif
            {{-- H5 · muestra uso de cupos si el admin fijó un límite. --}}
            @php
                $cp = auth()->user()->companyProfile;
                $activos = \App\Models\TeamMember::where('contractor_user_id', auth()->id())
                    ->whereIn('status', [\App\Models\TeamMember::STATUS_ACTIVE, \App\Models\TeamMember::STATUS_INVITED])->count();
            @endphp
            @if ($cp?->max_coach_slots !== null)
                <p class="mt-2 text-xs text-warmgray">
                    {{ __('Cupos usados:') }}
                    <strong class="text-ink">{{ $activos }} / {{ $cp->max_coach_slots }}</strong>
                    @if ($activos >= $cp->max_coach_slots)
                        · <span class="text-red-600">{{ __('Sin cupos libres') }}</span>
                    @endif
                </p>
            @endif
            <form method="POST" action="{{ route('equipo.invitar') }}" class="mt-3 flex flex-wrap gap-2">
                @csrf
                <input type="email" name="email" required placeholder="{{ __('correo@ejemplo.com') }}"
                       class="min-h-[44px] basis-full sm:basis-64 sm:flex-1 rounded-full border border-line px-4 py-2 text-sm">
                <button type="submit" class="min-h-[44px] basis-full sm:basis-auto rounded-full bg-sage px-5 py-2 text-sm font-semibold text-cream">{{ __('Invitar') }}</button>
            </form>
        </section>

        {{-- Listado --}}
        <section>
            <h3 class="font-serif text-lg font-medium text-ink">{{ landing('equipo_listado_titulo') }}</h3>
            @if ($miembros->isEmpty())
                <p class="mt-3 rounded-2xl border border-line bg-white px-6 py-8 text-center text-warmgray">
                    {{ landing('equipo_empty_state') }}
                </p>
            @else
                <ul class="mt-3 space-y-2">
                    @foreach ($miembros as $tm)
                        <li class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line bg-white px-4 py-3">
                            <div>
                                <p class="font-medium text-ink">{{ $tm->professional?->name }}</p>
                                <p class="text-xs text-warmgray">{{ $tm->professional?->email }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="rounded-full px-3 py-1 text-xs font-medium
                                             {{ match($tm->status) {
                                                'active' => 'bg-sage/20 text-sage',
                                                'invited' => 'bg-lime/20 text-ink',
                                                'declined' => 'bg-red-100 text-red-700',
                                                default => 'bg-cream text-warmgray',
                                             } }}">
                                    {{ enum_label('team_status', $tm->status) }}
                                </span>
                                @if ($tm->status !== 'removed')
                                    <form method="POST" action="{{ route('equipo.remover', $tm) }}"
                                          onsubmit="return confirm('{{ __('¿Seguro que quieres quitar a este miembro?') }}');">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex min-h-[44px] items-center rounded-full border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">
                                            {{ __('Quitar') }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-app-layout>
