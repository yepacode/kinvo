<x-mail::message>
@php $logoPath = public_path('img/kinvoo-logo.png'); @endphp
@if (file_exists($logoPath))
<p style="text-align:center;margin:0 0 24px 0;">
    <img src="{{ $message->embed($logoPath) }}" alt="Kinvoo — bolsa de talento fitness" width="72" height="72">
</p>
@endif

# {{ __('¡Tu registro fue exitoso! Ya eres parte de Kinvoo.') }}

{{ __('Sabemos que un estudio no es solo un espacio, es la gente que lo hace funcionar todos los días. Por eso, como parte de tu membresía, ya puedes empezar a buscar talento dentro de nuestra comunidad — porque cuidar a tu equipo es también cuidar tu negocio.') }}

{{ __('Completa tu perfil para comenzar a explorar la bolsa de talento y conectar con quienes pueden sumarse a tu equipo.') }}

<x-mail::button :url="url('/mi-empresa/bienvenida')" color="success">
{{ __('Completar mi perfil') }}
</x-mail::button>

{{ __('Cualquier duda, aquí estamos.') }}

— {{ __('El equipo de Kinvoo') }}

{{ __('Si tienes alguna duda, escríbenos:') }} [hola@gokinvoo.com](mailto:hola@gokinvoo.com)
</x-mail::message>
