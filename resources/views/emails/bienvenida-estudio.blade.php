<x-mail::message>
<p style="text-align:center;margin:0 0 24px 0;">
    <img src="{{ $message->embed(public_path('img/kinvoo-logo.png')) }}" alt="Kinvoo — bolsa de talento fitness" width="72" height="72">
</p>

# ¡Tu registro fue exitoso! Ya eres parte de Kinvoo.

Sabemos que un estudio no es solo un espacio, es la gente que lo hace funcionar todos los días. Por eso, como parte de tu membresía, ya puedes empezar a buscar talento dentro de nuestra comunidad — porque cuidar a tu equipo es también cuidar tu negocio.

Completa tu perfil para comenzar a explorar la bolsa de talento y conectar con quienes pueden sumarse a tu equipo.

<x-mail::button :url="url('/mi-empresa/bienvenida')" color="success">
Completar mi perfil
</x-mail::button>

Cualquier duda, aquí estamos.

— El equipo de Kinvoo

Si tienes alguna duda, escríbenos: [hola@kinvoo.com](mailto:hola@kinvoo.com)
</x-mail::message>
