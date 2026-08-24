<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ __('Mis servicios') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
        @if (session('status') === 'servicio-solicitado')
            <div class="mb-6 rounded-xl border border-sage/30 bg-sage/10 px-4 py-3 text-sm font-medium text-ink">
                {{ __('¡Listo! Enviamos tu solicitud. El equipo de Kinvoo la revisará y te confirmará.') }}
            </div>
        @elseif (session('status') === 'servicio-ya-solicitado')
            <div class="mb-6 rounded-xl border border-lime/40 bg-lime/10 px-4 py-3 text-sm font-medium text-ink">
                {{ __('Ya tienes una solicitud abierta para ese servicio. Espera la confirmación del equipo.') }}
            </div>
        @endif

        <p class="mb-6 text-sm text-warmgray">
            {{ __('Estos son los servicios que incluye tu membresía. Solicita el que necesites y el equipo de Kinvoo te lo confirma.') }}
        </p>

        @php
            $estadoBadge = [
                'pending'   => ['label' => __('Pendiente'), 'clase' => 'bg-beige text-ink'],
                'scheduled' => ['label' => __('Agendada'),  'clase' => 'bg-sage/20 text-sage'],
                'done'      => ['label' => __('Realizada'), 'clase' => 'bg-sage/20 text-sage'],
                'cancelled' => ['label' => __('Cancelada'), 'clase' => 'bg-red-100 text-red-700'],
            ];
        @endphp

        @if ($servicios->isEmpty())
            <div class="rounded-2xl border border-line bg-white px-6 py-12 text-center">
                <p class="text-warmgray">{{ __('Tu membresía actual no incluye servicios, o no tienes una membresía activa.') }}</p>
                <a href="{{ route('membresias.index') }}" class="mt-3 inline-block text-sm font-medium text-sage underline">
                    {{ __('Ver planes y sus servicios') }} →
                </a>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($servicios as $servicio)
                    @php
                        $solicitud = $solicitudes[$servicio->id] ?? null;
                        $abierta = $solicitud && in_array($solicitud->status, ['pending', 'scheduled']);
                    @endphp
                    <div class="flex flex-col rounded-2xl border border-line bg-white p-5">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="font-serif text-lg font-medium text-ink">
                                @if ($servicio->icono)<span aria-hidden="true">{{ $servicio->icono }}</span> @endif{{ $servicio->nombre }}
                            </h3>
                            @if ($solicitud)
                                @php $b = $estadoBadge[$solicitud->status] ?? $estadoBadge['pending']; @endphp
                                <span class="shrink-0 rounded-full px-3 py-1 text-xs font-medium {{ $b['clase'] }}">{{ $b['label'] }}</span>
                            @endif
                        </div>

                        @if ($servicio->descripcion)
                            <p class="mt-2 text-sm text-ink/80">{{ $servicio->descripcion }}</p>
                        @endif

                        @if ($abierta)
                            <p class="mt-4 border-t border-line/60 pt-4 text-xs text-warmgray">
                                {{ __('Solicitud en curso — el equipo te confirmará.') }}
                                @if ($solicitud->scheduled_for) · {{ __('Agendada:') }} {{ $solicitud->scheduled_for->translatedFormat('d M H:i') }}@endif
                            </p>
                        @else
                            <form method="POST" action="{{ route('servicios.solicitar', $servicio) }}"
                                  class="mt-4 space-y-3 border-t border-line/60 pt-4">
                                @csrf
                                <div>
                                    <label class="block text-xs font-medium text-ink" for="slot-{{ $servicio->id }}">{{ __('¿Cuándo te queda mejor? (opcional)') }}</label>
                                    <input id="slot-{{ $servicio->id }}" name="preferred_slot" type="text" maxlength="200"
                                           class="mt-1 w-full rounded-xl border border-line px-3 py-2 text-sm"
                                           placeholder="{{ __('Ej: martes por la tarde') }}">
                                    <x-input-error :messages="$errors->get('preferred_slot')" class="mt-1" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-ink" for="note-{{ $servicio->id }}">{{ __('Nota (opcional)') }}</label>
                                    <textarea id="note-{{ $servicio->id }}" name="note" rows="2" maxlength="1000"
                                              class="mt-1 w-full rounded-xl border border-line px-3 py-2 text-sm"
                                              placeholder="{{ __('Cuéntanos qué necesitas.') }}"></textarea>
                                    <x-input-error :messages="$errors->get('note')" class="mt-1" />
                                </div>
                                <button type="submit"
                                        class="min-h-[44px] w-full rounded-full bg-sage px-4 py-2 text-sm font-semibold text-cream hover:bg-ink">
                                    {{ __('Solicitar este servicio') }}
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <a href="{{ route('dashboard') }}" class="mt-6 inline-block text-sm text-sage hover:underline">← {{ __('Volver al panel') }}</a>
    </div>
</x-app-layout>
