<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">¡Listo!</h2>
    </x-slot>

    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6">
        @include('partials.wizard-steps', ['paso' => 3])

        <div class="rounded-2xl border border-line bg-white p-8 text-center sm:p-10">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-sage/10">
                <span class="text-2xl" aria-hidden="true">🌿</span>
            </div>

            @if ($profile->is_published)
                <h3 class="mt-4 font-serif text-2xl font-medium text-ink">Tu perfil está publicado</h3>
                <p class="mt-2 text-sm text-warmgray">Los estudios ya pueden encontrarte en la bolsa de talento.</p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('talento.show', $profile->slug) }}" target="_blank"
                       class="rounded-full border border-line px-6 py-2.5 text-sm font-medium text-ink transition hover:border-sage hover:text-sage">
                        Ver mi perfil ↗
                    </a>
                    <a href="{{ route('dashboard') }}"
                       class="rounded-full bg-sage px-6 py-2.5 text-sm font-semibold text-cream transition hover:bg-ink">
                        Ir a mi panel
                    </a>
                </div>
            @else
                <h3 class="mt-4 font-serif text-2xl font-medium text-ink">Perfil enviado a revisión</h3>
                <p class="mt-2 text-sm leading-relaxed text-warmgray">
                    Tu perfil será revisado para su publicación. Te avisaremos cuando esté activo.
                </p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('professional.profile.edit') }}"
                       class="rounded-full border border-line px-6 py-2.5 text-sm font-medium text-ink transition hover:border-sage hover:text-sage">
                        Seguir editando
                    </a>
                    <a href="{{ route('account.pending') }}"
                       class="rounded-full bg-sage px-6 py-2.5 text-sm font-semibold text-cream transition hover:bg-ink">
                        Entendido
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
