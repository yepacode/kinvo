<x-guest-layout>
    <h1 class="mb-1 text-center font-serif text-2xl font-medium text-ink">{{ landing('login_title') }}</h1>
    <p class="mb-6 text-center text-sm text-warmgray">{{ landing('login_subtitle') }}</p>

    <!-- Session Status -->
    @if (session('status') === 'cuenta-eliminada')
        <div class="mb-4 rounded-2xl border border-sage/30 bg-sage/10 px-4 py-3 text-sm text-ink">
            <p><strong>{{ landing('login_flash_cuenta_eliminada_titulo') }}</strong></p>
            <p class="mt-1 text-warmgray">
                {{ landing('login_flash_cuenta_eliminada_body') }}
            </p>
        </div>
    @elseif (session('status') === 'admin-elimino-cuenta')
        <div class="mb-4 rounded-2xl border border-lime/40 bg-lime/10 px-4 py-3 text-sm text-ink">
            <p><strong>{{ landing('login_flash_admin_baja_titulo') }}</strong></p>
            <p class="mt-1 text-warmgray">
                {{ landing('login_flash_admin_baja_body') }}
                <a href="mailto:hola@gokinvoo.com" class="text-sage underline">hola@gokinvoo.com</a>.
            </p>
        </div>
    @else
        <x-auth-session-status class="mb-4" :status="session('status')" />
    @endif

    {{-- Feedback Karla 27-ago: cuando las credenciales fallan el error "no
         coinciden" es poco claro, no había ojito para ver la contraseña, y el
         link de recuperar quedaba escondido. Este bloque muestra un aviso
         claro arriba con la salida (link de recuperar). --}}
    @if ($errors->has('email') || $errors->has('password'))
        <div class="mb-4 rounded-2xl border border-terracotta/40 bg-terracotta/10 px-4 py-3 text-sm text-ink">
            <p class="font-medium">{{ __('No pudimos entrar con esos datos.') }}</p>
            <p class="mt-1 text-warmgray">
                {{ __('Revisa que el correo esté bien escrito y que la contraseña sea la que usas en Kinvoo.') }}
                @if (Route::has('password.request'))
                    {{ __('Si no la recuerdas,') }}
                    <a class="font-medium text-sage underline hover:text-ink" href="{{ route('password.request') }}">{{ __('recupérala aquí') }}</a>.
                @endif
            </p>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" x-data="{ mostrarPwd: false }">
        @csrf

        <!-- Correo -->
        <div>
            <x-input-label for="email" :value="__('Correo')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Contraseña con ojito para mostrar/ocultar -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Contraseña')" />
            <div class="relative mt-1">
                <input id="password" name="password" required autocomplete="current-password"
                       :type="mostrarPwd ? 'text' : 'password'"
                       class="block w-full rounded-md border border-line bg-white pr-11 shadow-sm focus:border-sage focus:ring-sage text-sm" />
                {{-- Textos de aria-label en spans ocultos para evitar el fragility
                     de comillas embebidas en Alpine (una traducción con apóstrofe
                     rompía el parse). --}}
                <span x-ref="mostrar" class="sr-only">{{ __('Mostrar contraseña') }}</span>
                <span x-ref="ocultar" class="sr-only">{{ __('Ocultar contraseña') }}</span>
                <button type="button"
                        @click="mostrarPwd = !mostrarPwd"
                        :aria-label="mostrarPwd ? $refs.ocultar.textContent : $refs.mostrar.textContent"
                        :aria-pressed="mostrarPwd.toString()"
                        class="absolute inset-y-0 right-0 flex items-center px-3 text-warmgray hover:text-sage focus:outline-none focus:text-sage">
                    {{-- Icono ojo abierto (Heroicons outline eye) --}}
                    <svg x-show="!mostrarPwd" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    {{-- Icono ojo tachado (Heroicons outline eye-slash) --}}
                    <svg x-show="mostrarPwd" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" style="display:none;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.243 4.243L9.88 9.88"/>
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Recordarme + link de recuperar en la misma fila (visible) -->
        <div class="mt-4 flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-line text-sage shadow-sm focus:ring-sage" name="remember">
                <span class="ms-2 text-sm text-warmgray">{{ __('Recordarme') }}</span>
            </label>
            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-sage underline hover:text-ink" href="{{ route('password.request') }}">
                    {{ __('¿Olvidaste tu contraseña?') }}
                </a>
            @endif
        </div>

        <div class="mt-6">
            <button type="submit"
                    class="w-full rounded-full bg-sage px-6 py-2.5 text-sm font-semibold text-cream shadow-sm transition hover:bg-ink">
                {{ __('Entrar') }}
            </button>
        </div>

        <p class="mt-6 text-center text-sm text-warmgray">
            {{ __('¿No tienes cuenta?') }}
            <a href="{{ route('register') }}" class="text-sage underline">{{ __('Regístrate') }}</a>
        </p>
    </form>
</x-guest-layout>
