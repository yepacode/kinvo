<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ __('Bienvenido a Kinvoo') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6">
        @include('partials.wizard-steps', ['paso' => 1])

        <div class="rounded-2xl border border-line bg-white p-6 sm:p-8">
            <h3 class="font-serif text-2xl font-medium text-ink">{{ landing('welcome_studio_title') }}</h3>
            <div class="mt-3 space-y-3 whitespace-pre-line text-sm leading-relaxed text-warmgray">{{ landing('welcome_studio_body') }}</div>

            <p class="mt-4 text-sm text-warmgray">
                {{ __('Antes de comenzar, revisa nuestros') }}
                <a href="{{ route('legal.terminos') }}" target="_blank" class="text-sage underline hover:text-ink">{{ __('Términos y Condiciones') }}</a>
                {{ __('y el') }}
                <a href="{{ route('legal.privacidad') }}" target="_blank" class="text-sage underline hover:text-ink">{{ __('Aviso de Privacidad') }}</a>.
            </p>

            <p class="mt-3 rounded-xl bg-beige px-4 py-3 text-xs text-warmgray">
                <strong class="text-ink">{{ __('¿Ibas a registrarte como talento?') }}</strong>
                {{ __('Escríbenos a') }}
                <a href="mailto:hola@gokinvoo.com" class="text-sage underline hover:text-ink">hola@gokinvoo.com</a>
                {{ __('y te cambiamos el tipo de cuenta sin perder tu correo ni tu contraseña.') }}
            </p>

            <div class="mt-8 flex justify-end">
                <a href="{{ route('company.profile.edit') }}"
                   class="rounded-full bg-sage px-7 py-2.5 text-sm font-semibold text-cream shadow-sm transition hover:bg-ink">
                    {{ __('Siguiente →') }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
