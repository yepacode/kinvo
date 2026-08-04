<x-mail::message>
# {{ __('Cancelaste tu membresía') }}

{{ __('Hola :name,', ['name' => $user->name]) }}

{{ __('Recibimos tu solicitud de baja. Tu acceso a Kinvoo se mantiene hasta el final del periodo que ya pagaste.') }}

{{ __('Si fue un error o quieres volver, puedes reactivar tu suscripción cuando gustes:') }}

<x-mail::button :url="url('/membresias')">{{ __('Reactivar mi suscripción') }}</x-mail::button>

{{ __('Gracias por haber sido parte de Kinvoo.') }}

— {{ __('El equipo de Kinvoo') }}
</x-mail::message>
