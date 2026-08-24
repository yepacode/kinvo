@component('mail::message')
{{-- Contenido editable desde el panel (plantilla `invitacion_sesion`).
     El body_override de la sesión tiene prioridad sobre la plantilla. --}}
@if (! empty($tpl['greeting']))
### {{ $tpl['greeting'] }}
@else
# {{ __('¡Hola :nombre!', ['nombre' => $nombre]) }}
@endif

@if ($body)
{{-- Cuerpo personalizado que el admin escribió al crear la sesión. --}}
{!! nl2br(e($body)) !!}
@elseif (! empty($tpl['body']))
{!! nl2br(e($tpl['body'])) !!}
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

{{ $tpl['outro'] ?? __('¿Podrás asistir? Confírmanos para reservarte el cupo:') }}

@component('mail::button', ['url' => $goingUrl, 'color' => 'success'])
{{ __('Voy ✓') }}
@endcomponent

@component('mail::button', ['url' => $declineUrl, 'color' => 'error'])
{{ __('No puedo') }}
@endcomponent

{{ __('Gracias,') }}
{{ __('El equipo de Kinvoo') }}
@endcomponent
