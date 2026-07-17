<x-guest-layout>
    <h1 class="mb-1 text-center font-serif text-2xl font-medium text-ink">{{ __('Verifica tu correo') }}</h1>
    <p class="mb-4 text-sm text-warmgray">
        {{ __('¡Gracias por unirte a Kinvoo! Antes de empezar, confirma tu correo dando clic en el enlace que te acabamos de enviar. Si no lo recibiste, con gusto te mandamos otro.') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 text-sm font-medium text-sage">
            {{ __('Te enviamos un nuevo enlace de verificación al correo que usaste al registrarte.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>
                {{ __('Reenviar verificación') }}
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-warmgray underline hover:text-sage rounded-md focus:outline-none">
                {{ __('Cerrar sesión') }}
            </button>
        </form>
    </div>
</x-guest-layout>
