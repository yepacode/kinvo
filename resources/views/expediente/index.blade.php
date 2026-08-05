<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ __('Mi expediente de cuidado') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        <x-guia-inline :titulo="__('¿Qué es este expediente?')" tono="beige">
            <p>{{ __('Es tu bitácora de bienestar dentro de Kinvoo. Aquí quedan registradas las consultas, sesiones de fisio, charlas y pólizas que Kinvoo facilita. Los registros los agrega el equipo de Kinvoo por ti.') }}</p>
        </x-guia-inline>

        <p class="mb-6 mt-6 text-sm text-warmgray">
            {{ __('Todo tu cuidado en un mismo lugar: consultas médicas, sesiones de fisio, charlas y tu póliza de seguro. Kinvoo lo mantiene actualizado por ti.') }}
        </p>

        <div class="mb-6 flex flex-wrap gap-2">
            <a href="{{ route('expediente.index') }}"
               class="rounded-full border border-line bg-white px-4 py-1.5 text-sm text-ink {{ ! request('tipo') ? 'border-sage text-sage' : '' }}">
                {{ __('Todos') }}
            </a>
            @foreach ($tipos as $slug => $label)
                <a href="{{ route('expediente.index', ['tipo' => $slug]) }}"
                   class="rounded-full border border-line bg-white px-4 py-1.5 text-sm text-ink {{ request('tipo') === $slug ? 'border-sage text-sage' : '' }}">
                    {{ __($label) }}
                </a>
            @endforeach
        </div>

        @if ($entradas->isEmpty())
            <div class="rounded-2xl border border-line bg-white px-6 py-12 text-center">
                <p class="text-warmgray">{{ __('Tu expediente aún no tiene entradas. Kinvoo lo irá alimentando conforme uses los servicios.') }}</p>
            </div>
        @else
            <ol class="space-y-4">
                @foreach ($entradas as $e)
                    @php
                        $icono = ['telemedicine' => '🩺', 'physio' => '💪', 'talk' => '🎤', 'insurance' => '🛡️', 'other' => '📝'][$e->type] ?? '•';
                        $color = ['telemedicine' => 'sage', 'physio' => 'lime', 'talk' => 'beige', 'insurance' => 'cream', 'other' => 'cream'][$e->type] ?? 'cream';
                    @endphp
                    <li class="flex gap-4 rounded-2xl border border-line bg-white p-5">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-{{ $color }}/40 text-xl">
                            {{ $icono }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <h3 class="font-medium text-ink">{{ __($e->label()) }}</h3>
                                <time class="text-xs text-warmgray">{{ $e->occurred_on->translatedFormat('d M Y') }}</time>
                            </div>
                            @if ($e->provider)
                                <p class="text-sm text-warmgray">{{ $e->provider }}</p>
                            @endif
                            @if ($e->notes)
                                <p class="mt-2 text-sm text-ink/90">{{ $e->notes }}</p>
                            @endif
                            @if ($e->valid_until)
                                <p class="mt-2 text-xs text-sage">
                                    ✓ {{ __('Vigente hasta el :fecha', ['fecha' => $e->valid_until->translatedFormat('d M Y')]) }}
                                </p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif

        <div class="mt-6">{{ $entradas->withQueryString()->links() }}</div>
    </div>
</x-app-layout>
