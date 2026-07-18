<x-guest-layout>
    <h1 class="mb-1 text-center font-serif text-2xl font-medium text-ink">{{ __('Inicia sesión') }}</h1>
    <p class="mb-6 text-center text-sm text-warmgray">{{ __('Bienvenido de vuelta a Kinvoo') }}</p>

    <!-- Session Status -->
    @if (session('status') === 'cuenta-eliminada')
        <div class="mb-4 rounded-2xl border border-sage/30 bg-sage/10 px-4 py-3 text-sm text-ink">
            <p><strong>{{ __('Tu cuenta fue eliminada.') }}</strong></p>
            <p class="mt-1 text-warmgray">
                {{ __('Se borraron tu perfil, tus contactos y tus archivos. Nos alegra que hayas sido parte de Kinvoo. Si quieres volver, puedes crear una cuenta nueva.') }}
            </p>
        </div>
    @elseif (session('status') === 'admin-elimino-cuenta')
        <div class="mb-4 rounded-2xl border border-lime/40 bg-lime/10 px-4 py-3 text-sm text-ink">
            <p><strong>{{ __('Tu cuenta ya no está activa.') }}</strong></p>
            <p class="mt-1 text-warmgray">
                {{ __('Kinvoo dio de baja tu cuenta. Si crees que fue un error, escríbenos a') }}
                <a href="mailto:hola@gokinvoo.com" class="text-sage underline">hola@gokinvoo.com</a>.
            </p>
        </div>
    @else
        <x-auth-session-status class="mb-4" :status="session('status')" />
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Correo -->
        <div>
            <x-input-label for="email" :value="__('Correo')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Contraseña -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Contraseña')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Recordarme -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-line text-sage shadow-sm focus:ring-sage" name="remember">
                <span class="ms-2 text-sm text-warmgray">{{ __('Recordarme') }}</span>
            </label>
        </div>

        <div class="mt-6 flex items-center justify-between">
            @if (Route::has('password.request'))
                <a class="text-sm text-warmgray underline hover:text-sage" href="{{ route('password.request') }}">
                    {{ __('¿Olvidaste tu contraseña?') }}
                </a>
            @endif

            <button type="submit"
                    class="rounded-full bg-sage px-6 py-2.5 text-sm font-semibold text-cream shadow-sm transition hover:bg-ink">
                {{ __('Entrar') }}
            </button>
        </div>

        <p class="mt-6 text-center text-sm text-warmgray">
            {{ __('¿No tienes cuenta?') }}
            <a href="{{ route('register') }}" class="text-sage underline">{{ __('Regístrate') }}</a>
        </p>
    </form>
</x-guest-layout>
