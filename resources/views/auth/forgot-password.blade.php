<x-guest-layout>
    <h1 class="mb-1 text-center font-serif text-2xl font-medium text-ink">Recupera tu contraseña</h1>
    <p class="mb-6 text-center text-sm text-warmgray">
        Escribe tu correo y te enviaremos un enlace para crear una nueva contraseña.
    </p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div>
            <x-input-label for="email" :value="'Correo'" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-6 flex items-center justify-between">
            <a href="{{ route('login') }}" class="text-sm text-warmgray underline hover:text-sage">Volver a entrar</a>
            <x-primary-button>
                Enviar enlace
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
