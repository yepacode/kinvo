@component('mail::message')
# {{ __('¡Hola :nombre!', ['nombre' => $nombre]) }}

@if ($body)
{{-- Cuerpo personalizado que el admin escribió desde el panel. --}}
{!! nl2br(e($body)) !!}
@else
{{ __('Te invitamos a nuestra próxima sesión en Kinvoo:') }}

**{{ $title }}**
🗓 {{ $fecha }}
@endif

@if ($link)
@component('mail::button', ['url' => $link])
{{ __('Unirme a la sesión') }}
@endcomponent
@endif

---

{{ __('¿Podrás asistir? Confírmanos para reservarte el cupo:') }}

@component('mail::button', ['url' => $goingUrl, 'color' => 'success'])
{{ __('Voy ✓') }}
@endcomponent

@component('mail::button', ['url' => $declineUrl, 'color' => 'error'])
{{ __('No puedo') }}
@endcomponent

{{ __('Gracias,') }}
{{ __('El equipo de Kinvoo') }}
@endcomponent
