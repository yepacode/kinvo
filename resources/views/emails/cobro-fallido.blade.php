<x-mail::message>
# {{ __('Tu cobro no se pudo completar') }}

{{ __('Hola :name,', ['name' => $user->name]) }}

{{ __('Intentamos cobrar tu suscripción a Kinvoo pero la pasarela rechazó el pago. Esto suele deberse a fondos insuficientes, tarjeta vencida o un bloqueo temporal del banco.') }}

{{ __('Vamos a reintentar el cobro automáticamente en las próximas 24-72 horas. Si prefieres actualizar tu tarjeta ahora, puedes hacerlo en tu panel:') }}

<x-mail::button :url="url('/dashboard')" color="error">{{ __('Actualizar mi método de pago') }}</x-mail::button>

{{ __('Cualquier duda, escríbenos.') }}

— {{ __('El equipo de Kinvoo') }}
</x-mail::message>
