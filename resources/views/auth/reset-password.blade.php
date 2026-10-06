<x-guest-layout>
    <h1 class="mb-6 text-center font-serif text-2xl font-medium text-ink">{{ landing('reset_title') }}</h1>

    {{-- Feedback Karla 06-10-2026 ("no se puede cambiar la contraseña"):
         resumen de errores arriba del form + ayuda visible de los requisitos
         de contraseña. Antes los errores salían pequeños debajo de cada
         campo y era fácil pasarlos por alto, sobre todo el "contraseña en
         filtración pública" que tiene redacción larga. --}}
    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="font-semibold">{{ __('No pudimos actualizar tu contraseña:') }}</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('status'))
        <div class="mb-5 rounded-xl border border-sage/40 bg-sage/10 p-4 text-sm text-ink">
            ✓ {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-input-label for="email" :value="__('Correo')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Nueva contraseña')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-password-strength for="password" />
            <p class="mt-2 text-xs text-warmgray">
                {{ __('Mínimo 8 caracteres, con mayúscula, minúscula, número y símbolo. Evita contraseñas que ya hayas usado en otros sitios (las revisamos contra filtraciones públicas).') }}
            </p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirmar contraseña')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-6 flex items-center justify-end">
            <x-primary-button>
                {{ __('Guardar contraseña') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
