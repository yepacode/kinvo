<x-mail::message>
<p style="text-align:center;margin:0 0 24px 0;">
    <img src="{{ $message->embed(public_path('img/kinvoo-logo.png')) }}" alt="Kinvoo — bolsa de talento fitness" width="72" height="72">
</p>

# ¡Ya eres parte de Kinvoo!

Sabemos todo lo que das cada día: tu energía, tu tiempo, tu entrega a los demás. Aquí queremos hacer lo mismo por ti — acompañarte y sostenerte a ti también.

Llena tu perfil para que empecemos a conectar oportunidades contigo.

<x-mail::button :url="url('/mi-perfil/bienvenida')" color="success">
Completar mi perfil
</x-mail::button>

Cualquier cosa, aquí estamos.

— El equipo de Kinvoo

Si tienes alguna duda, escríbenos: [hola@kinvoo.com](mailto:hola@kinvoo.com)
</x-mail::message>
