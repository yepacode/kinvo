{{-- Vista genérica de correo (Punto 13). Renderiza cualquier EmailTemplate.
     Los placeholders (nombre de estudio, mensaje del contacto, etc.) llegan
     sin escape desde EmailTemplate::replace() y pueden venir de un formulario
     público — ver NuevoContacto. Aquí ESCAPAMOS con e() antes de pasar el
     párrafo por nl2br, para bloquear inyección de HTML en el correo del
     admin (feedback auditoría seguridad 21-sep MED-1). Los `**negrita**` que
     vengan en el template se ven como texto porque Laravel Mail procesa
     Markdown del wrapping <x-mail::message>, no del contenido escapado. --}}
<x-mail::message>
{{-- Feedback Karla 01-10-2026: header de marca en HTML puro.
     Antes dependía de $message->embed(public_path('img/kinvoo-logo.png')),
     pero en producción el logo se veía como una "K" cuadrada verde sin
     identidad. Ahora el header es texto serif con fondo de marca:
     se renderiza igual en Gmail, Outlook, Apple Mail y clientes móviles,
     sin necesidad de archivo. --}}
<p style="text-align:center;margin:0 0 28px 0;padding:0;">
    <span style="display:inline-block;background:#5C7A5F;color:#F7F4EE;padding:14px 32px;border-radius:12px;font-family:Georgia,'Times New Roman',serif;font-size:28px;font-weight:500;letter-spacing:1px;">
        Kinvoo
    </span>
    <br>
    <span style="display:inline-block;margin-top:8px;color:#4A6450;font-family:'Helvetica Neue',Arial,sans-serif;font-size:11px;font-weight:600;letter-spacing:4px;text-transform:uppercase;">
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
