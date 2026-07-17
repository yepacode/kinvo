<x-guest-layout>
    <h1 class="mb-1 text-center font-serif text-2xl font-medium text-ink">{{ __('Crea tu cuenta') }}</h1>
    <p class="mb-6 text-center text-sm text-warmgray">{{ __('Únete a la red profesional del fitness') }}</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Tipo de usuario -->
        <fieldset>
            <legend class="block text-sm font-medium text-ink">{{ __('Elige el tipo de cuenta') }}</legend>
            <p class="mt-1 text-xs text-warmgray">
                {{ __('Elige con cuidado: hoy una cuenta es de un solo tipo. Si te equivocas escríbenos y lo cambiamos.') }}
            </p>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                @php $tipo = old('tipo', 'professional'); @endphp
                <label class="cursor-pointer">
                    <input type="radio" name="tipo" value="professional" class="peer sr-only"
                           {{ $tipo === 'professional' ? 'checked' : '' }} required>
                    <div class="h-full rounded-xl border border-line bg-white p-4 transition peer-checked:border-sage peer-checked:bg-sage/5 peer-checked:ring-1 peer-checked:ring-sage peer-focus-visible:ring-2 peer-focus-visible:ring-sage peer-focus-visible:ring-offset-1">
                        <div class="flex items-center gap-2">
                            <span class="text-lg" aria-hidden="true">🧘‍♀️</span>
                            <span class="text-sm font-semibold text-ink">{{ __('Soy talento') }}</span>
                        </div>
                        <p class="mt-2 text-xs leading-relaxed text-warmgray">
                            {{ __('Coach, instructor o staff que quiere que lo encuentren estudios y marcas.') }}
                        </p>
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="tipo" value="contractor" class="peer sr-only"
                           {{ $tipo === 'contractor' ? 'checked' : '' }}>
                    <div class="h-full rounded-xl border border-line bg-white p-4 transition peer-checked:border-sage peer-checked:bg-sage/5 peer-checked:ring-1 peer-checked:ring-sage peer-focus-visible:ring-2 peer-focus-visible:ring-sage peer-focus-visible:ring-offset-1">
                        <div class="flex items-center gap-2">
                            <span class="text-lg" aria-hidden="true">🏢</span>
                            <span class="text-sm font-semibold text-ink">{{ __('Soy estudio o marca') }}</span>
                        </div>
                        <p class="mt-2 text-xs leading-relaxed text-warmgray">
                            {{ __('Gimnasio, estudio o marca que quiere buscar y contactar talento del fitness.') }}
                        </p>
                    </div>
                </label>
            </div>
            <x-input-error :messages="$errors->get('tipo')" class="mt-2" />
        </fieldset>

        <!-- Nombre -->
        <div class="mt-4">
            <x-input-label for="name" :value="__('Nombre')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Correo -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Correo')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Contraseña -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Contraseña')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <p class="mt-1 text-xs text-warmgray">{{ __('Mínimo 8 caracteres, con mayúsculas, minúsculas, números y un símbolo.') }}</p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirmar contraseña -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirmar contraseña')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Aceptación de legales -->
        <div class="mt-6">
            <label for="acepta_legales" class="flex items-start gap-3">
                <input type="checkbox" id="acepta_legales" name="acepta_legales" value="1"
                       {{ old('acepta_legales') ? 'checked' : '' }} required
                       class="mt-0.5 h-5 w-5 rounded border-line text-sage focus:ring-sage">
                <span class="text-sm text-warmgray">
                    {{ __('He leído y acepto los') }}
                    <a href="{{ route('legal.terminos') }}" target="_blank" class="text-sage underline hover:text-ink">{{ __('Términos y Condiciones') }}</a>
                    {{ __('y el') }}
                    <a href="{{ route('legal.privacidad') }}" target="_blank" class="text-sage underline hover:text-ink">{{ __('Aviso de Privacidad') }}</a>.
                </span>
            </label>
            <x-input-error :messages="$errors->get('acepta_legales')" class="mt-2" />
        </div>

        <div class="mt-6 flex items-center justify-between">
            <a class="text-sm text-warmgray underline hover:text-sage" href="{{ route('login') }}">
                {{ __('¿Ya tienes cuenta?') }}
            </a>

            <button type="submit"
                    class="rounded-full bg-sage px-6 py-2.5 text-sm font-semibold text-cream shadow-sm transition hover:bg-ink">
                {{ __('Registrarme') }}
            </button>
        </div>
    </form>
</x-guest-layout>
