<x-mail::message>
<p style="text-align:center;margin:0 0 24px 0;">
    <img src="{{ $message->embed(public_path('img/kinvoo-logo.png')) }}" alt="Kinvoo — bolsa de talento fitness" width="72" height="72">
</p>

# {{ __('¡Ya eres parte de Kinvoo!') }}

{{ __('Sabemos todo lo que das cada día: tu energía, tu tiempo, tu entrega a los demás. Aquí queremos hacer lo mismo por ti — acompañarte y sostenerte a ti también.') }}

{{ __('Llena tu perfil para que empecemos a conectar oportunidades contigo.') }}

<x-mail::button :url="url('/mi-perfil/bienvenida')" color="success">
{{ __('Completar mi perfil') }}
</x-mail::button>

{{ __('Cualquier cosa, aquí estamos.') }}

— {{ __('El equipo de Kinvoo') }}

{{ __('Si tienes alguna duda, escríbenos:') }} [hola@gokinvoo.com](mailto:hola@gokinvoo.com)
</x-mail::message>
