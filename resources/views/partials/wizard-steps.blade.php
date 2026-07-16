{{-- Indicador de pasos del onboarding. Uso: @include('partials.wizard-steps', ['paso' => 1..3]) --}}
@php $pasos = ['Bienvenida', 'Tu perfil', 'Listo']; @endphp
<ol class="mb-6 flex flex-wrap items-center gap-x-3 gap-y-2 text-xs" aria-label="Progreso del registro">
    @foreach ($pasos as $i => $nombre)
        @php $n = $i + 1; @endphp
        <li class="flex items-center gap-2">
            <span aria-hidden="true"
                  class="flex h-6 w-6 items-center justify-center rounded-full border text-[0.7rem] font-medium {{ $n <= $paso ? 'border-sage bg-sage text-cream' : 'border-line text-warmgray' }}">{{ $n }}</span>
            <span class="{{ $n === $paso ? 'font-medium text-ink' : 'text-warmgray' }}">{{ $nombre }}</span>
        </li>
        @if (! $loop->last)
            <li aria-hidden="true" class="text-line">—</li>
        @endif
    @endforeach
</ol>
