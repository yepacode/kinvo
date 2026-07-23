<x-guest-layout>
    <div class="text-center">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-beige">
            <span class="text-2xl" aria-hidden="true">⏳</span>
        </div>

        @php $u = auth()->user(); @endphp

        @if ($u?->estado === \App\Enums\EstadoUsuario::Suspendido)
            <h1 class="font-serif text-2xl font-medium text-ink">{{ __('Cuenta suspendida') }}</h1>
            <p class="mt-3 text-sm text-warmgray">
                {{ __('Tu cuenta está suspendida. Escríbenos a') }}
                <a href="mailto:hola@gokinvoo.com" class="text-sage underline">hola@gokinvoo.com</a>
                {{ __('para más información.') }}
            </p>
        @elseif ($u?->estado === \App\Enums\EstadoUsuario::PerfilPendiente)
            {{-- Contratista aprobado que ya llenó (o va a llenar) su perfil de
                 empresa. Le explicamos la 2ª revisión y le damos acceso al perfil. --}}
            <h1 class="font-serif text-2xl font-medium text-ink">{{ __('Perfil en revisión') }}</h1>
            <p class="mt-3 text-sm text-warmgray">
                {{ __('¡Gracias por llenar tu perfil! Nuestro equipo lo está revisando y quedará activo en un máximo de 24 horas. Te avisaremos por correo cuando esté publicado.') }}
            </p>
            <p class="mt-3 text-sm text-warmgray">
                {{ __('Mientras tanto puedes seguir ajustando tu perfil.') }}
            </p>
            <a href="{{ route('company.profile.edit') }}"
               class="mt-6 inline-block rounded-full bg-sage px-5 py-2 text-sm font-semibold text-cream shadow-sm transition hover:bg-ink">
                {{ __('Editar mi perfil') }}
            </a>
        @else
            <h1 class="font-serif text-2xl font-medium text-ink">{{ __('Cuenta en revisión') }}</h1>
            <p class="mt-3 text-sm text-warmgray">
                {{ __('¡Gracias por registrarte en Kinvoo! Un administrador revisará tu perfil antes de activarlo. Te avisaremos por correo cuando esté aprobado.') }}
            </p>
        @endif

        <form method="POST" action="{{ route('logout') }}" class="mt-8">
            @csrf
            <button type="submit"
                    class="rounded-full border border-line px-5 py-2 text-sm font-medium text-warmgray transition hover:border-sage hover:text-sage">
                {{ __('Cerrar sesión') }}
            </button>
        </form>
    </div>
</x-guest-layout>
