{{-- Vista genérica de correo (Punto 13). Renderiza cualquier EmailTemplate.
     Los placeholders (nombre de estudio, mensaje del contacto, etc.) llegan
     sin escape desde EmailTemplate::replace() y pueden venir de un formulario
     público — ver NuevoContacto. Aquí ESCAPAMOS con e() antes de pasar el
     párrafo por nl2br, para bloquear inyección de HTML en el correo del
     admin (feedback auditoría seguridad 21-sep MED-1). Los `**negrita**` que
     vengan en el template se ven como texto porque Laravel Mail procesa
     Markdown del wrapping <x-mail::message>, no del contenido escapado. --}}
<x-mail::message>
@php $logoPath = public_path('img/kinvoo-logo.png'); @endphp
@if (file_exists($logoPath))
<p style="text-align:center;margin:0 0 24px 0;">
    <img src="{{ $message->embed($logoPath) }}" alt="Kinvoo" width="72" height="72">
</p>
@endif

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
