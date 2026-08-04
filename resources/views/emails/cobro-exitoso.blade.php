<x-mail::message>
# {{ __('¡Recibimos tu pago!') }}

{{ __('Hola :name,', ['name' => $user->name]) }}

{{ __('Tu suscripción a Kinvoo quedó activa.') }}
@if ($subscription && $subscription->plan)
- **{{ __('Plan') }}:** {{ $subscription->plan->nombre }}
- **{{ __('Próxima renovación') }}:** {{ $subscription->current_period_end?->translatedFormat('d M Y') ?? '—' }}
@endif

{{ __('Puedes revisar tu historial de pagos y suscripciones en tu panel:') }}

<x-mail::button :url="url('/dashboard')">{{ __('Ir a mi panel') }}</x-mail::button>

{{ __('Gracias por seguir con Kinvoo.') }}

— {{ __('El equipo de Kinvoo') }}
</x-mail::message>
