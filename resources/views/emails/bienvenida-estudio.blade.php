<x-mail::message>
{{-- Header de marca en HTML puro (feedback Karla 01-10-2026). Audit 02-10:
     fontfamily del wordmark alineado con tpl-generic. Subtítulo oscurecido a
     #4A6450 para subir contraste y pasar WCAG AA. --}}
<p style="text-align:center;margin:0 0 28px 0;">
    <span style="display:inline-block;background:#5C7A5F;color:#F7F4EE;padding:14px 32px;border-radius:12px;font-family:Georgia,'Times New Roman',serif;font-size:28px;font-weight:500;letter-spacing:1px;">Kinvoo</span>
    <br>
    <span style="display:inline-block;margin-top:8px;color:#4A6450;font-family:'Helvetica Neue',Arial,sans-serif;font-size:11px;font-weight:600;letter-spacing:4px;text-transform:uppercase;">Bolsa de talento fitness</span>
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
