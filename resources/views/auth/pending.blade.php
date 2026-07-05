<x-guest-layout>
    <div class="text-center">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-beige">
            <span class="text-2xl">⏳</span>
        </div>

        <h1 class="font-serif text-2xl font-medium text-ink">Cuenta en revisión</h1>

        <p class="mt-3 text-sm text-warmgray">
            @if (auth()->user()?->estado === \App\Enums\EstadoUsuario::Suspendido)
                Tu cuenta está suspendida. Escríbenos a
                <a href="mailto:hola@gokinvoo.com" class="text-sage underline">hola@gokinvoo.com</a>
                para más información.
            @else
                ¡Gracias por registrarte en Kinvoo! Un administrador revisará tu perfil
                antes de activarlo. Te avisaremos por correo cuando esté aprobado.
            @endif
        </p>

        <form method="POST" action="{{ route('logout') }}" class="mt-8">
            @csrf
            <button type="submit"
                    class="rounded-full border border-line px-5 py-2 text-sm font-medium text-warmgray transition hover:border-sage hover:text-sage">
                Cerrar sesión
            </button>
        </form>
    </div>
</x-guest-layout>
