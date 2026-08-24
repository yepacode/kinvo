<x-mail::message>
@php $logoPath = public_path('img/kinvoo-logo.png'); @endphp
@if (file_exists($logoPath))
<p style="text-align:center;margin:0 0 24px 0;">
    <img src="{{ $message->embed($logoPath) }}" alt="Kinvoo — bolsa de talento" width="72" height="72">
</p>
@endif

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
