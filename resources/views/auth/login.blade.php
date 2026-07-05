<x-guest-layout>
    <h1 class="mb-1 text-center font-serif text-2xl font-medium text-ink">Inicia sesión</h1>
    <p class="mb-6 text-center text-sm text-warmgray">Bienvenido de vuelta a Kinvoo</p>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Correo -->
        <div>
            <x-input-label for="email" :value="'Correo'" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Contraseña -->
        <div class="mt-4">
            <x-input-label for="password" :value="'Contraseña'" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Recordarme -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-line text-sage shadow-sm focus:ring-sage" name="remember">
                <span class="ms-2 text-sm text-warmgray">Recordarme</span>
            </label>
        </div>

        <div class="mt-6 flex items-center justify-between">
            @if (Route::has('password.request'))
                <a class="text-sm text-warmgray underline hover:text-sage" href="{{ route('password.request') }}">
                    ¿Olvidaste tu contraseña?
                </a>
            @endif

            <button type="submit"
                    class="rounded-full bg-sage px-6 py-2.5 text-sm font-semibold text-cream shadow-sm transition hover:bg-ink">
                Entrar
            </button>
        </div>

        <p class="mt-6 text-center text-sm text-warmgray">
            ¿No tienes cuenta?
            <a href="{{ route('register') }}" class="text-sage underline">Regístrate</a>
        </p>
    </form>
</x-guest-layout>
