<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ __('Mis contactos enviados') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        <p class="mb-4 text-sm text-warmgray">
            {{ __('Aquí ves los talentos a los que has contactado. Kinvoo hace el puente: cuando el profesional acepte, te avisamos por correo y aparecerá aquí como "Aceptado".') }}
        </p>

        @if ($contactos->isEmpty())
            <div class="rounded-2xl border border-line bg-white px-6 py-12 text-center">
                <div class="text-3xl" aria-hidden="true">✉️</div>
                <p class="mt-3 font-medium text-ink">{{ __('Aún no has contactado a ningún talento') }}</p>
                <p class="mt-1 text-sm text-warmgray">
                    {{ __('Cuando encuentres un perfil que te interese, usa el botón "Contactar" desde el buscador.') }}
                </p>
                <a href="{{ route('talento.index') }}"
                   class="mt-5 inline-flex items-center gap-2 rounded-full bg-sage px-5 py-2 text-sm font-semibold text-cream shadow-sm transition hover:bg-ink">
                    {{ __('Buscar talento') }}
                </a>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($contactos as $c)
                    @php
                        $talento = $c->professionalProfile;
                        $nombreTalento = $talento?->user?->name ?? $talento?->headline ?? __('Talento');
                        $aceptado = (bool) $c->professional_interesado_at;
                    @endphp
                    <div class="rounded-2xl border border-line bg-white p-5 sm:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-warmgray">{{ __('Enviado a') }}</p>
                                @if ($talento && $talento->esVisiblePublicamente())
                                    <a href="{{ route('talento.show', $talento->slug) }}"
                                       class="font-serif text-lg font-medium text-ink hover:text-sage">{{ $nombreTalento }}</a>
                                @else
                                    <p class="font-serif text-lg font-medium text-ink">{{ $nombreTalento }}</p>
                                @endif
                                <p class="text-sm text-warmgray">
                                    {{ $c->created_at->translatedFormat(app()->getLocale() === 'en' ? 'M j, Y H:i' : 'd/m/Y H:i') }}
                                </p>
                            </div>
                            @if ($aceptado)
                                <span class="rounded-full bg-sage/20 px-3 py-1 text-xs font-medium text-ink">
                                    <span aria-hidden="true">🤝</span> {{ __('Aceptado') }}
                                </span>
                            @else
                                <span class="rounded-full bg-lime/20 px-3 py-1 text-xs font-medium text-ink">
                                    {{ __('En gestión') }}
                                </span>
                            @endif
                        </div>

                        <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-ink/90">{{ $c->message }}</p>

                        @if ($aceptado)
                            <div class="mt-4 border-t border-line pt-4">
                                <div class="flex items-center gap-2 rounded-xl bg-sage/10 px-4 py-3 text-sm text-ink">
                                    <span aria-hidden="true">🤝</span>
                                    <span>
                                        <strong>{{ __(':name aceptó tu contacto.', ['name' => $nombreTalento]) }}</strong>
                                        {{ __('Kinvoo te contactará por correo para hacer el puente.') }}
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $contactos->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
