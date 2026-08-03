<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ $offer->title }}</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        @if (session('status') === 'postulacion-enviada')
            <div class="mb-6 rounded-xl border border-sage/40 bg-sage/10 px-5 py-3 text-sm text-ink">
                <strong>{{ __('¡Postulación enviada!') }}</strong> {{ __('El estudio verá tu candidatura y te contactará si le interesa.') }}
            </div>
        @endif
        @if (session('status') === 'ya-postulaste')
            <div class="mb-6 rounded-xl border border-line bg-white px-5 py-3 text-sm text-warmgray">
                {{ __('Ya postulaste a esta oferta. Puedes ver el estado en Mis postulaciones.') }}
            </div>
        @endif

        <div class="rounded-2xl border border-line bg-white p-6 sm:p-8">
            <p class="text-sm text-warmgray">
                {{ $offer->contractor?->companyProfile?->company_name ?? $offer->contractor?->name }}
                @if ($offer->location) · {{ $offer->location->ciudad }} @endif
                · {{ __(ucfirst($offer->modality)) }}
                @if ($offer->contract_type) · {{ __(ucfirst(str_replace('_', ' ', $offer->contract_type))) }} @endif
            </p>

            @if ($offer->salary_min_cents || $offer->salary_max_cents)
                <p class="mt-2 font-medium text-sage">
                    ${{ number_format(($offer->salary_min_cents ?? 0) / 100, 0) }} – ${{ number_format(($offer->salary_max_cents ?? 0) / 100, 0) }} {{ $offer->salary_currency }} / {{ __(ucfirst($offer->salary_period)) }}
                </p>
            @endif

            <div class="mt-5">
                <h3 class="font-medium text-ink">{{ __('Descripción') }}</h3>
                <p class="mt-2 whitespace-pre-line text-sm text-ink/90">{{ $offer->description }}</p>
            </div>

            @if ($offer->requirements)
                <div class="mt-5">
                    <h3 class="font-medium text-ink">{{ __('Requisitos') }}</h3>
                    <p class="mt-2 whitespace-pre-line text-sm text-ink/90">{{ $offer->requirements }}</p>
                </div>
            @endif
        </div>

        @auth
            @if (auth()->user()->esProfesional())
                <div class="mt-6 rounded-2xl border border-line bg-white p-6">
                    <h3 class="font-medium text-ink">{{ __('Postular') }}</h3>
                    @if ($miPostulacion)
                        <p class="mt-2 text-sm text-warmgray">
                            {{ __('Ya postulaste el :fecha. Estado actual:', ['fecha' => $miPostulacion->created_at->translatedFormat('d M Y')]) }}
                            <span class="ml-1 rounded-full bg-sage/10 px-3 py-1 text-xs font-medium text-sage">{{ __(ucfirst(str_replace('_', ' ', $miPostulacion->status))) }}</span>
                        </p>
                    @else
                        <form method="POST" action="{{ route('ofertas.postular', $offer->slug) }}" class="mt-3">
                            @csrf
                            <label class="block text-sm font-medium text-ink">{{ __('Carta breve (opcional)') }}</label>
                            <textarea name="cover_letter" rows="4" maxlength="2000"
                                      class="mt-1 w-full rounded-xl border border-line px-3 py-2 text-sm"
                                      placeholder="{{ __('Cuenta por qué te interesa la oferta...') }}"></textarea>
                            <button type="submit" class="mt-3 rounded-full bg-sage px-5 py-2 text-sm font-semibold text-cream hover:bg-ink">
                                {{ __('Enviar postulación') }}
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        @endauth
    </div>
</x-app-layout>
