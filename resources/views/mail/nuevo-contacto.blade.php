<x-mail::message>
# Tienes un nuevo contacto

Hola {{ $profile->user->name }}, un contratante te contactó a través de tu perfil en **Kinvoo**.

**De:** {{ $contact->contact_name }}
**Correo:** {{ $contact->contact_email }}
@if ($contact->contact_phone)
**Teléfono:** {{ $contact->contact_phone }}
@endif

**Mensaje:**

> {{ $contact->message }}

<x-mail::button :url="route('talento.show', $profile->slug)">
Ver mi perfil
</x-mail::button>

Puedes responder directamente a **{{ $contact->contact_email }}**.

Gracias,<br>
El equipo de Kinvoo
</x-mail::message>
