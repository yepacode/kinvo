<x-guest-layout>
    <div class="mx-auto max-w-2xl px-4 py-16 text-center">
        <h1 class="font-serif text-3xl font-medium text-ink">{{ __('¡Gracias por responder!') }}</h1>

        <div class="mt-6 rounded-2xl border border-line bg-white p-6 text-left">
            <p class="text-sm text-warmgray">{{ __('Sesión:') }}</p>
            <p class="mt-1 font-medium text-ink">{{ $sesion->title }}</p>
            <p class="mt-3 text-sm text-warmgray">{{ $sesion->scheduled_at?->translatedFormat('l d M Y · H:i') }}</p>

            @if ($invitado->rsvp === 'accepted')
                <div class="mt-4 rounded-lg border border-sage/40 bg-sage/10 px-3 py-2 text-sm text-ink">
                    {{ __('Registramos tu asistencia. ¡Te vemos ahí!') }}
                    @if ($sesion->link)
                        <a href="{{ $sesion->link }}" class="mt-2 block font-medium text-sage underline">
                            {{ __('Guardar el link de la sesión') }}
                        </a>
                    @endif
                </div>
            @elseif ($invitado->rsvp === 'declined')
                <div class="mt-4 rounded-lg border border-line bg-cream px-3 py-2 text-sm text-warmgray">
                    {{ __('Registramos que no podrás asistir. Nos vemos en la próxima.') }}
                </div>
            @else
                <div class="mt-4 rounded-lg border border-line bg-cream px-3 py-2 text-sm text-warmgray">
                    {{ __('No recibimos una respuesta válida. Revisa el enlace del correo.') }}
                </div>
            @endif
        </div>

        <a href="{{ url('/') }}" class="mt-6 inline-block text-sm text-sage underline">
            {{ __('Volver a Kinvoo') }}
        </a>
    </div>
</x-guest-layout>
