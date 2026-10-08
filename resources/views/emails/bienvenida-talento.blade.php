<x-mail::message>
{{-- Feedback Karla 08-10-2026: wordmark real de Kinvoo por URL absoluta.
     El alt lleva el nombre de la marca para los clientes que bloquean imágenes. --}}
<p style="text-align:center;margin:0 0 30px 0;">
    <img src="{{ url('/img/kinvoo-wordmark.png') }}" alt="kinvoo" width="163" height="80"
         style="display:inline-block;border:0;outline:none;text-decoration:none;max-width:163px;height:auto;">
    <br>
    <span style="display:inline-block;margin-top:6px;color:#6E6A63;font-family:'Helvetica Neue',Arial,sans-serif;font-size:11px;font-weight:600;letter-spacing:4px;text-transform:uppercase;">Bolsa de talento fitness</span>
</p>
{{-- Contenido editable desde el panel (plantilla `bienvenida_talento`).
     Fallback: copy original hardcoded si no hay plantilla activa. --}}
@if (! empty($tpl['greeting']))
### {{ $tpl['greeting'] }}
@else
# {{ __('¡Ya eres parte de Kinvoo!') }}
@endif

@if (! empty($tpl['body']))
{!! nl2br(e($tpl['body'])) !!}
@else
{{ __('Sabemos todo lo que das cada día: tu energía, tu tiempo, tu entrega a los demás. Aquí queremos hacer lo mismo por ti — acompañarte y sostenerte a ti también.') }}

{{ __('Llena tu perfil para que empecemos a conectar oportunidades contigo.') }}
@endif

@if (! empty($tpl['action_label']) && ! empty($tpl['action_url']))
<x-mail::button :url="$tpl['action_url']" color="success">
{{ $tpl['action_label'] }}
</x-mail::button>
@else
<x-mail::button :url="url('/mi-perfil/bienvenida')" color="success">
{{ __('Completar mi perfil') }}
</x-mail::button>
@endif

{{ $tpl['outro'] ?? __('Cualquier cosa, aquí estamos.') }}

— {{ __('El equipo de Kinvoo') }}

{{ __('Si tienes alguna duda, escríbenos:') }} [hola@gokinvoo.com](mailto:hola@gokinvoo.com)
</x-mail::message>
