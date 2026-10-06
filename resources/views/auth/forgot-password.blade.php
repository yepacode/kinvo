<x-guest-layout>
    <h1 class="mb-1 text-center font-serif text-2xl font-medium text-ink">{{ landing('forgot_title') }}</h1>
    <p class="mb-6 text-center text-sm text-warmgray">
        {{ landing('forgot_body') }}
    </p>

    {{-- Feedback Karla 06-10-2026 ("no se puede cambiar la contraseña"): el
         flash de confirmación era un texto delgado y pasaba desapercibido.
         Ahora: card verde grande + instrucciones claras (revisa spam, link
         vence en 60 min, correo de origen). --}}
    @if (session('status'))
        <div class="mb-6 rounded-2xl border border-sage/40 bg-sage/10 p-5 text-sm text-ink">
            <p class="font-semibold text-sage">✓ {{ session('status') }}</p>
            <ul class="mt-3 list-disc space-y-1 pl-5 text-warmgray">
                <li>{{ __('Revisa también la carpeta de SPAM o Promociones de tu correo.') }}</li>
                <li>{{ __('El enlace expira en 60 minutos.') }}</li>
                <li>{{ __('El correo llega desde hola@gokinvoo.com.') }}</li>
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Correo')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-6 flex items-center justify-between">
            <a href="{{ route('login') }}" class="text-sm text-warmgray underline hover:text-sage">{{ __('Volver a entrar') }}</a>
            <x-primary-button>
                {{ __('Enviar enlace') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
