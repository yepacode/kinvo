<x-mail::message>
# Tienes un nuevo contacto

Hola {{ $profile->user->name }}, un estudio te contactó a través de tu perfil en **Kinvoo**.

**De:** {{ $contact->contact_name }}

**Mensaje:**

> {{ $contact->message }}

<x-mail::button :url="route('professional.contactos')">
Ver en mi bandeja
</x-mail::button>

En Kinvoo cuidamos los datos de ambas partes: si quieres avanzar con esta oportunidad,
respóndenos y nosotros hacemos el puente con el estudio. No compartimos ni tu correo
ni el suyo hasta que ambas partes lo confirmen.

Gracias,<br>
El equipo de Kinvoo
</x-mail::message>
