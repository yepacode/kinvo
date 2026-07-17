<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ __('Contactos recibidos') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        <p class="mb-4 text-sm text-warmgray">
            {{ __('Aquí ves a los estudios y marcas que te contactaron. El puente lo hace Kinvoo: nosotros nos comunicamos con ellos por ti, no publicamos tus datos ni los suyos.') }}
        </p>

        @if (session('status') === 'interesado-registrado')
            <div class="mb-6 rounded-2xl border border-sage/30 bg-sage/10 px-5 py-4 text-sm text-ink">
                <span aria-hidden="true">🤝</span>
                <strong>{{ __('Listo.') }}</strong> {{ __('Listo. Kinvoo ya lo sabe. Nos comunicamos con el estudio y en breve haremos el puente contigo.') }}
            </div>
        @elseif (session('status') === 'ya-interesado')
            <div class="mb-6 rounded-2xl border border-line bg-white px-5 py-4 text-sm text-warmgray">
                {{ __('Ya nos habías dicho que te interesa este contacto. Seguimos gestionando el puente.') }}
            </div>
        @endif

        @if ($contactos->isEmpty())
            <div class="rounded-2xl border border-line bg-white px-6 py-12 text-center">
                <div class="text-3xl" aria-hidden="true">✉️</div>
                <p class="mt-3 font-medium text-ink">{{ __('Aún no tienes contactos') }}</p>
                <p class="mt-1 text-sm text-warmgray">{{ __('Cuando un estudio te contacte, aparecerá aquí con su mensaje.') }}</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($contactos as $c)
                    <div class="rounded-2xl border border-line bg-white p-5 sm:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p class="font-serif text-lg font-medium text-ink">{{ $c->contact_name }}</p>
                                <p class="text-sm text-warmgray">{{ $c->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            @if ($c->estado === \App\Enums\EstadoContacto::NoLeido)
                                <span class="rounded-full bg-lime/20 px-3 py-1 text-xs font-medium text-ink">{{ __('Nuevo') }}</span>
                            @endif
                        </div>

                        <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-ink/90">{{ $c->message }}</p>

                        <div class="mt-4 border-t border-line pt-4">
                            @if ($c->esInteresado())
                                <div class="flex items-center gap-2 rounded-xl bg-sage/10 px-4 py-3 text-sm text-ink">
                                    <span aria-hidden="true">🤝</span>
                                    <span>
                                        <strong>{{ __('Kinvoo está gestionando el puente con :name.', ['name' => $c->contact_name]) }}</strong>
                                        {{ __('Marcado el :date.', ['date' => $c->professional_interesado_at->format('d/m/Y H:i')]) }}
                                        {{ __('Te avisaremos cuando el estudio esté listo para conectarse.') }}
                                    </span>
                                </div>
                            @else
                                <p class="text-xs text-warmgray">
                                    {{ __('¿Te interesa esta oportunidad? Kinvoo hace el puente por ti.') }}
                                </p>
                                <form method="POST" action="{{ route('professional.contactos.interesado', $c) }}"
                                      class="mt-3" x-data="{ enviando: false }" @submit="enviando = true">
                                    @csrf
                                    <button type="submit" x-bind:disabled="enviando"
                                            class="inline-flex items-center gap-2 rounded-full bg-sage px-5 py-2 text-sm font-semibold text-cream shadow-sm transition hover:bg-ink disabled:cursor-wait disabled:opacity-60">
                                        <span aria-hidden="true">🤝</span>
                                        <span x-show="!enviando">{{ __('Me interesa, conéctame con el estudio') }}</span>
                                        <span x-show="enviando" x-cloak>{{ __('Avisando a Kinvoo…') }}</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $contactos->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
