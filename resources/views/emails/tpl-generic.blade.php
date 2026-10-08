{{-- Vista genérica de correo (Punto 13). Renderiza cualquier EmailTemplate.
     Los placeholders (nombre de estudio, mensaje del contacto, etc.) llegan
     sin escape desde EmailTemplate::replace() y pueden venir de un formulario
     público — ver NuevoContacto. Aquí ESCAPAMOS con e() antes de pasar el
     párrafo por nl2br, para bloquear inyección de HTML en el correo del
     admin (feedback auditoría seguridad 21-sep MED-1). Los `**negrita**` que
     vengan en el template se ven como texto porque Laravel Mail procesa
     Markdown del wrapping <x-mail::message>, no del contenido escapado. --}}
<x-mail::message>
{{-- Feedback Karla 08-10-2026: wordmark real de Kinvoo.
     Se sirve por URL absoluta (no embebido) para que no dependa de que el
     cliente de correo acepte adjuntos inline. El alt lleva el nombre de la
     marca, así que si el destinatario tiene las imágenes bloqueadas sigue
     leyendo "kinvoo" en lugar de un recuadro vacío. --}}
<p style="text-align:center;margin:0 0 30px 0;padding:0;">
    <img src="{{ url('/img/kinvoo-wordmark.png') }}" alt="kinvoo" width="163" height="80"
         style="display:inline-block;border:0;outline:none;text-decoration:none;max-width:163px;height:auto;">
    <br>
    <span style="display:inline-block;margin-top:6px;color:#6E6A63;font-family:'Helvetica Neue',Arial,sans-serif;font-size:11px;font-weight:600;letter-spacing:4px;text-transform:uppercase;">
        Bolsa de talento fitness
    </span>
</p>

@if (! empty($tpl['greeting']))
### {{ $tpl['greeting'] }}
@endif

@if (! empty($tpl['body']))
@foreach (explode("\n\n", $tpl['body']) as $parrafo)
@php $parrafo = trim($parrafo); @endphp
@if ($parrafo !== '')
{!! nl2br(e($parrafo)) !!}

@endif
@endforeach
@endif

@if (! empty($tpl['action_label']) && ! empty($tpl['action_url']))
<x-mail::button :url="$tpl['action_url']" :color="$tpl['action_color'] ?? 'success'">
{{ $tpl['action_label'] }}
</x-mail::button>
@endif

@if (! empty($tpl['outro']))
{{ $tpl['outro'] }}
@endif

— {{ __('El equipo de Kinvoo') }}

{{ __('Si tienes alguna duda, escríbenos:') }} [hola@gokinvoo.com](mailto:hola@gokinvoo.com)
</x-mail::message>
