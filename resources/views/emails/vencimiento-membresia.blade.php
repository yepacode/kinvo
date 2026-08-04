<x-mail::message>
# {{ __('Tu membresía venció') }}

{{ __('Hola :name,', ['name' => $user->name]) }}

{{ __('Tu membresía de Kinvoo llegó a su fecha de vencimiento y no pudimos cobrar la renovación después de varios intentos.') }}

{{ __('Puedes reactivarla en cualquier momento eligiendo un plan:') }}

<x-mail::button :url="url('/membresias')">{{ __('Ver planes disponibles') }}</x-mail::button>

{{ __('Nos alegra tenerte y estamos aquí cuando quieras volver.') }}

— {{ __('El equipo de Kinvoo') }}
</x-mail::message>
