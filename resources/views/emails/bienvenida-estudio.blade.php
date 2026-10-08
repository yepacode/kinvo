<x-mail::message>
{{-- Feedback Karla 08-10-2026: wordmark real de Kinvoo por URL absoluta.
     El alt lleva el nombre de la marca para los clientes que bloquean imágenes. --}}
<p style="text-align:center;margin:0 0 30px 0;">
    <img src="{{ url('/img/kinvoo-wordmark.png') }}" alt="kinvoo" width="163" height="80"
         style="display:inline-block;border:0;outline:none;text-decoration:none;max-width:163px;height:auto;">
    <br>
    <span style="display:inline-block;margin-top:6px;color:#6E6A63;font-family:'Helvetica Neue',Arial,sans-serif;font-size:11px;font-weight:600;letter-spacing:4px;text-transform:uppercase;">Bolsa de talento fitness</span>
</p>

{{-- Contenido editable desde el panel (plantilla `bienvenida_estudio`). --}}
@if (! empty($tpl['greeting']))
### {{ $tpl['greeting'] }}
@else
# {{ __('¡Tu registro fue exitoso! Ya eres parte de Kinvoo.') }}
@endif

@if (! empty($tpl['body']))
{!! nl2br(e($tpl['body'])) !!}
@else
{{ __('Sabemos que un estudio no es solo un espacio, es la gente que lo hace funcionar todos los días. Por eso, como parte de tu membresía, ya puedes empezar a buscar talento dentro de nuestra comunidad — porque cuidar a tu equipo es también cuidar tu negocio.') }}

{{ __('Completa tu perfil para comenzar a explorar la bolsa de talento y conectar con quienes pueden sumarse a tu equipo.') }}
@endif

@if (! empty($tpl['action_label']) && ! empty($tpl['action_url']))
<x-mail::button :url="$tpl['action_url']" color="success">
{{ $tpl['action_label'] }}
</x-mail::button>
@else
<x-mail::button :url="url('/mi-empresa/bienvenida')" color="success">
{{ __('Completar mi perfil') }}
</x-mail::button>
@endif

{{ $tpl['outro'] ?? __('Cualquier duda, aquí estamos.') }}

— {{ __('El equipo de Kinvoo') }}

{{ __('Si tienes alguna duda, escríbenos:') }} [hola@gokinvoo.com](mailto:hola@gokinvoo.com)
</x-mail::message>
