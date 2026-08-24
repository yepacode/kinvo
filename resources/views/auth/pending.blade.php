{{-- 2026-08-06 · Petición cliente (B5): "en esta pantalla desaparece
     el menú, en las otras permanece". Cambio de guest-layout a
     app-layout para mantener consistencia. El nav filtra automática-
     mente a solo "Inicio + Mi empresa" (o Mi perfil) cuando la
     cuenta está pendiente, gracias al fix H1 previo. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">
            @php $u = auth()->user(); @endphp
            @if ($u?->estado === \App\Enums\EstadoUsuario::Suspendido)
                {{ landing('pending_titulo_suspendida') }}
            @elseif ($u?->estado === \App\Enums\EstadoUsuario::PerfilPendiente)
                {{ landing('pending_titulo_perfil_revision') }}
            @else
                {{ landing('pending_titulo_cuenta_revision') }}
            @endif
        </h2>
    </x-slot>

    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 text-center">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-beige">
            <span class="text-2xl" aria-hidden="true">⏳</span>
        </div>

        @if ($u?->estado === \App\Enums\EstadoUsuario::Suspendido)
            <p class="mt-3 text-sm text-warmgray">
                {{-- landing_rich escapa el input y respeta *cursiva*/saltos. --}}
                {!! landing_rich('pending_body_suspendida') !!}
            </p>
        @elseif ($u?->estado === \App\Enums\EstadoUsuario::PerfilPendiente)
            {{-- Contratista aprobado que ya llenó (o va a llenar) su perfil de
                 empresa. Le explicamos la 2ª revisión y le damos acceso al perfil. --}}
            <p class="mt-3 text-sm text-warmgray">
                {{ landing('pending_body_perfil_pendiente') }}
            </p>
            <p class="mt-3 text-sm text-warmgray">
                {{ __('Mientras tanto puedes seguir ajustando tu perfil o revisar los planes de membresía.') }}
            </p>
            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('company.profile.edit') }}"
                   class="inline-block rounded-full bg-sage px-5 py-2 text-sm font-semibold text-cream shadow-sm transition hover:bg-ink">
                    {{ __('Editar mi perfil') }}
                </a>
                <a href="{{ route('membresias.index') }}"
                   class="inline-block rounded-full border border-line px-5 py-2 text-sm font-medium text-ink transition hover:border-sage hover:text-sage">
                    {{ __('Ver planes') }}
                </a>
            </div>
        @else
            <p class="mt-3 text-sm text-warmgray">
                {{ landing('pending_body_cuenta_revision') }}
            </p>
        @endif

    </div>
</x-app-layout>
