<x-guest-layout>
    <div class="mx-auto max-w-2xl px-4 py-16 text-center">
        <h1 class="font-serif text-3xl font-medium text-ink">{{ __('Confirma tu asistencia') }}</h1>

        <div class="mt-6 rounded-2xl border border-line bg-white p-6 text-left">
            <p class="text-sm text-warmgray">{{ __('Sesión:') }}</p>
            <p class="mt-1 font-medium text-ink">{{ $sesion->title }}</p>
            <p class="mt-3 text-sm text-warmgray">{{ $sesion->scheduled_at?->translatedFormat('l d M Y · H:i') }}</p>

            @if ($invitado->rsvp === 'accepted' || $invitado->rsvp === 'declined')
                <div class="mt-4 rounded-lg border border-line bg-cream px-3 py-2 text-sm text-warmgray">
                    {{ __('Ya respondiste anteriormente. Puedes cambiar tu respuesta si lo necesitas.') }}
                </div>
            @endif

            <form method="POST" action="{{ route('rsvp.confirmar', $invitado->rsvp_token) }}" class="mt-6 space-y-3">
                @csrf
                <button type="submit" name="r" value="accepted"
                        class="w-full rounded-full bg-sage px-6 py-3 text-sm font-semibold text-cream hover:bg-ink">
                    {{ $sugerido === 'accepted' ? __('Confirmar: sí asistiré') : __('Sí, voy a asistir') }}
                </button>
                <button type="submit" name="r" value="declined"
                        class="w-full rounded-full border border-line px-6 py-3 text-sm font-medium text-warmgray hover:border-danger hover:text-danger">
                    {{ $sugerido === 'declined' ? __('Confirmar: no podré') : __('No, no podré esta vez') }}
                </button>
            </form>
        </div>

        <p class="mt-6 text-xs text-warmgray">
            {{ __('Este paso extra evita que tu cliente de correo cambie tu respuesta sin que tú hicieras clic.') }}
        </p>

        <a href="{{ url('/') }}" class="mt-4 inline-block text-sm text-sage underline">
            {{ __('Volver a Kinvoo') }}
        </a>
    </div>
</x-guest-layout>
