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

    {{-- 2026-10-14 · La rama de "cuenta en revisión" era un callejón sin
         salida: un párrafo suelto sobre un bloque de color, sin un solo
         botón. Es la primera pantalla que ve alguien recién registrado, así
         que ahora explica qué sigue y le da algo que hacer mientras espera. --}}
    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6">
      <div class="rounded-3xl border border-line bg-white px-6 py-10 text-center shadow-sm sm:px-10">
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

            {{-- Qué sigue: la espera se lleva mejor sabiendo qué va a pasar. --}}
            <ol class="mx-auto mt-8 max-w-md space-y-3 text-left text-sm text-ink/90">
                @foreach ([
                    __('Revisamos que tu perfil esté completo y sea de alguien real.'),
                    __('Te avisamos por correo en cuanto quede aprobado.'),
                    __('A partir de ahí puedes contactar, postularte y contratar tu membresía.'),
                ] as $i => $paso)
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-sage/15 text-xs font-semibold text-sage">{{ $i + 1 }}</span>
                        <span>{{ $paso }}</span>
                    </li>
                @endforeach
            </ol>

            <p class="mt-8 text-sm text-warmgray">
                {{ __('Mientras tanto, deja tu perfil lo más completo posible: es lo que revisamos.') }}
            </p>

            <div class="mt-5 flex flex-wrap items-center justify-center gap-3">
                @if ($u?->esContratante())
                    <a href="{{ route('company.profile.edit') }}"
                       class="inline-block rounded-full bg-sage px-5 py-2 text-sm font-semibold text-cream shadow-sm transition hover:bg-ink">
                        {{ __('Completar mi perfil') }}
                    </a>
                @else
                    <a href="{{ route('professional.profile.edit') }}"
                       class="inline-block rounded-full bg-sage px-5 py-2 text-sm font-semibold text-cream shadow-sm transition hover:bg-ink">
                        {{ __('Completar mi perfil') }}
                    </a>
                @endif
                <a href="{{ route('membresias.index') }}"
                   class="inline-block rounded-full border border-line px-5 py-2 text-sm font-medium text-ink transition hover:border-sage hover:text-sage">
                    {{ __('Ver planes') }}
                </a>
            </div>
        @endif

      </div>
    </div>
</x-app-layout>
