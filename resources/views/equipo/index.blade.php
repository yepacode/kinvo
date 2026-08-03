<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ __('Mi equipo') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 space-y-8">
        {{-- Panel de impacto (2.13) --}}
        <section class="rounded-2xl border border-line bg-white p-6">
            <h3 class="font-serif text-lg font-medium text-ink">{{ __('Panel de impacto') }}</h3>
            <p class="mt-1 text-sm text-warmgray">{{ __('Cuidado facilitado a los miembros activos de tu equipo.') }}</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-line/60 bg-cream/50 p-4 text-center">
                    <div class="text-2xl">🩺</div>
                    <div class="mt-2 text-2xl font-semibold text-ink">{{ $impacto['telemedicine'] }}</div>
                    <div class="text-xs text-warmgray">{{ __('Consultas médicas') }}</div>
                </div>
                <div class="rounded-xl border border-line/60 bg-cream/50 p-4 text-center">
                    <div class="text-2xl">💪</div>
                    <div class="mt-2 text-2xl font-semibold text-ink">{{ $impacto['physio'] }}</div>
                    <div class="text-xs text-warmgray">{{ __('Sesiones de fisio') }}</div>
                </div>
                <div class="rounded-xl border border-line/60 bg-cream/50 p-4 text-center">
                    <div class="text-2xl">🎤</div>
                    <div class="mt-2 text-2xl font-semibold text-ink">{{ $impacto['talk'] }}</div>
                    <div class="text-xs text-warmgray">{{ __('Charlas') }}</div>
                </div>
                <div class="rounded-xl border border-line/60 bg-cream/50 p-4 text-center">
                    <div class="text-2xl">🛡️</div>
                    <div class="mt-2 text-2xl font-semibold text-ink">{{ $impacto['insurance'] }}</div>
                    <div class="text-xs text-warmgray">{{ __('Pólizas vigentes') }}</div>
                </div>
            </div>
        </section>

        {{-- Invitar --}}
        <section class="rounded-2xl border border-line bg-white p-6">
            <h3 class="font-serif text-lg font-medium text-ink">{{ __('Agregar a alguien al equipo') }}</h3>
            @if (session('status') === 'invitacion-enviada')
                <div class="mt-2 rounded-lg border border-sage/40 bg-sage/10 px-3 py-2 text-sm text-ink">{{ __('Invitación enviada. El profesional la verá en su panel.') }}</div>
            @elseif (session('status') === 'profesional-no-existe')
                <div class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ __('No encontramos un profesional con ese correo en Kinvoo.') }}</div>
            @endif
            <form method="POST" action="{{ route('equipo.invitar') }}" class="mt-3 flex flex-wrap gap-2">
                @csrf
                <input type="email" name="email" required placeholder="{{ __('correo@ejemplo.com') }}"
                       class="flex-1 min-w-64 rounded-full border border-line px-4 py-2 text-sm">
                <button type="submit" class="rounded-full bg-sage px-5 py-2 text-sm font-semibold text-cream">{{ __('Invitar') }}</button>
            </form>
        </section>

        {{-- Listado --}}
        <section>
            <h3 class="font-serif text-lg font-medium text-ink">{{ __('Miembros del equipo') }}</h3>
            @if ($miembros->isEmpty())
                <p class="mt-3 rounded-2xl border border-line bg-white px-6 py-8 text-center text-warmgray">
                    {{ __('Aún no tienes miembros en tu equipo. Empieza invitando a un profesional por correo.') }}
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
                                    {{ __(ucfirst($tm->status)) }}
                                </span>
                                @if ($tm->status !== 'removed')
                                    <form method="POST" action="{{ route('equipo.remover', $tm) }}">
                                        @csrf
                                        <button type="submit" class="text-xs text-red-600 hover:underline">{{ __('Quitar') }}</button>
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
