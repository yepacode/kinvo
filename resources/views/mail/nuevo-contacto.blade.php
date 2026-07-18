<x-mail::message>
# {{ __('Tienes un nuevo contacto') }}

{!! __('Hola :nombre, un estudio te contactó a través de tu perfil en **Kinvoo**.', ['nombre' => e($profile->user->name)]) !!}

**{{ __('De:') }}** {{ $contact->contact_name }}

**{{ __('Mensaje:') }}**

> {{ $contact->message }}

<x-mail::button :url="route('professional.contactos')">
{{ __('Ver en mi bandeja') }}
</x-mail::button>

{{ __('En Kinvoo cuidamos los datos de ambas partes: si quieres avanzar con esta oportunidad, respóndenos y nosotros hacemos el puente con el estudio. No compartimos ni tu correo ni el suyo hasta que ambas partes lo confirmen.') }}

{{ __('Gracias,') }}<br>
{{ __('El equipo de Kinvoo') }}
</x-mail::message>
