{{-- Vista genérica de correo (Punto 13). Renderiza cualquier EmailTemplate.
     El body se procesa como MARKDOWN (interpreta **negrita** y > citas). El
     escape HTML lo hace Blade en cada línea; los placeholders vienen sin
     escape desde EmailTemplate::replace() para no doblarlo. --}}
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
{!! $parrafo !!}

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
