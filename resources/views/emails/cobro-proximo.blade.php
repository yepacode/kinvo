<x-mail::message>
# {{ __('Tu renovación se acerca') }}

{{ __('Hola :name,', ['name' => $user->name]) }}

{{ __('Tu suscripción a Kinvoo se renueva el :date. El cobro se hará automáticamente con tu método de pago registrado.', ['date' => $subscription->current_period_end?->translatedFormat('d M Y') ?? '—']) }}

{{ __('Si quieres pausar o cancelar antes de la renovación, puedes hacerlo desde tu panel:') }}

<x-mail::button :url="url('/dashboard')">{{ __('Ir a mi panel') }}</x-mail::button>

— {{ __('El equipo de Kinvoo') }}
</x-mail::message>
