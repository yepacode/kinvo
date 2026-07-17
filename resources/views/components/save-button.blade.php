@props(['profile'])

@auth
    @php $guardado = auth()->user()->haGuardado($profile); @endphp
    <form method="POST" action="{{ route('saves.toggleProfile', $profile->slug) }}">
        @csrf
        <button type="submit"
                class="flex items-center justify-center gap-2 rounded-full border px-6 py-3 text-sm font-semibold transition
                       {{ $guardado ? 'border-sage bg-sage/10 text-sage' : 'border-line text-warmgray hover:border-sage hover:text-sage' }}">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="{{ $guardado ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
            </svg>
            {{ $guardado ? __('Guardado') : __('Guardar') }}
        </button>
    </form>
@endauth
