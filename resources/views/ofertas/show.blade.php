<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ $offer->title }}</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        <x-back-link :href="route('ofertas.index')" :value="__('← Volver a oportunidades')" />

        @if (session('status') === 'postulacion-enviada')
            <div class="mb-6 rounded-xl border border-sage/40 bg-sage/10 px-5 py-3 text-sm text-ink">
                <strong>{{ landing('ofertas_show_flash_enviada_titulo') }}</strong> {{ landing('ofertas_show_flash_enviada_texto') }}
            </div>
        @endif
        @if (session('status') === 'ya-postulaste')
            <div class="mb-6 rounded-xl border border-line bg-white px-5 py-3 text-sm text-warmgray">
                {{ landing('ofertas_show_flash_ya_postulaste') }}
            </div>
        @endif

        <div class="rounded-2xl border border-line bg-white p-6 sm:p-8">
            @php
                // H3 · logo del estudio en cabecera del detalle también.
                $logoRelDet = $offer->contractor?->companyProfile?->logo_path;
                $logoUrlDet = $logoRelDet ? asset('storage/'.$logoRelDet) : null;
                $companyDet = $offer->contractor?->companyProfile?->company_name ?? $offer->contractor?->name;
            @endphp
            <div class="flex items-start gap-3">
                @if ($logoUrlDet)
                    <img src="{{ $logoUrlDet }}" alt="{{ $companyDet }}"
                         class="h-14 w-14 shrink-0 rounded-lg border border-line bg-cream object-cover">
                @endif
                <p class="text-sm text-warmgray">
                    <span class="font-medium text-ink">{{ $companyDet }}</span>
                    @if ($offer->location) · {{ $offer->location->ciudad }} @endif
                    @if ($offer->colonia) · {{ $offer->colonia }} @endif
                    · {{ enum_label('modality', $offer->modality) }}
                    @if ($offer->contract_type) · {{ enum_label('contract_type', $offer->contract_type) }} @endif
                </p>
            </div>

            @if ($offer->salary_min_cents || $offer->salary_max_cents)
                <p class="mt-2 font-medium text-sage">
                    ${{ number_format(($offer->salary_min_cents ?? 0) / 100, 0) }} – ${{ number_format(($offer->salary_max_cents ?? 0) / 100, 0) }} {{ $offer->salary_currency }} / {{ enum_label('salary_period', $offer->salary_period) }}
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

            {{-- H3 · disponibilidad requerida (opcional, solo si el estudio la marcó). --}}
            @if (! empty($offer->availability))
                <div class="mt-5">
                    <h3 class="font-medium text-ink">{{ __('Franjas generales') }}</h3>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach (\App\Models\ProfessionalProfile::DIAS as $diaKey => $diaLabel)
                            @php
                                $franjas = collect(\App\Models\ProfessionalProfile::FRANJAS)
                                    ->filter(fn ($_, $fk) => in_array($diaKey.'_'.$fk, $offer->availability, true))
                                    ->values()->all();
                            @endphp
                            @if (! empty($franjas))
                                <span class="rounded-full border border-line bg-cream/40 px-3 py-1 text-xs text-ink">
                                    {{ __($diaLabel) }} · {{ implode(' · ', $franjas) }}
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- H3 · horarios exactos + notas del horario. --}}
            @if (! empty($offer->schedule_ranges) || $offer->schedule_notes)
                <div class="mt-5">
                    <h3 class="font-medium text-ink">{{ __('Horarios exactos') }}</h3>
                    @if (! empty($offer->schedule_ranges))
                        <ul class="mt-2 space-y-1 text-sm text-ink/90">
                            @foreach ($offer->schedule_ranges as $r)
                                @php $dl = \App\Models\ProfessionalProfile::DIAS[$r['day'] ?? ''] ?? ($r['day'] ?? ''); @endphp
                                <li class="flex items-center gap-2">
                                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-sage"></span>
                                    <span class="font-medium">{{ __($dl) }}</span>
                                    <span class="text-warmgray">{{ $r['from'] ?? '' }} → {{ $r['to'] ?? '' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @if ($offer->schedule_notes)
                        <p class="mt-2 whitespace-pre-line rounded-lg border border-line/60 bg-cream/40 px-3 py-2 text-sm text-ink/90">
                            {{ $offer->schedule_notes }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        @auth
            @if (auth()->user()->esProfesional())
                <div class="mt-6 rounded-2xl border border-line bg-white p-6">
                    <h3 class="font-medium text-ink">{{ landing('ofertas_show_postular_titulo') }}</h3>
                    @if ($miPostulacion)
                        <p class="mt-2 text-sm text-warmgray">
                            {{ __('Ya postulaste el :fecha. Estado actual:', ['fecha' => $miPostulacion->created_at->translatedFormat('d M Y')]) }}
                            <span class="ml-1 rounded-full bg-sage/10 px-3 py-1 text-xs font-medium text-sage">{{ enum_label('application_status', $miPostulacion->status) }}</span>
                        </p>
                    @else
                        {{-- H2 · copy del cliente (docx PRUEBA KINVOO, jul-2026). El intro
                             "No buscamos la respuesta perfecta..." ya no va arriba: ahora es
                             parte del placeholder del textarea (petición cliente ago-2026). --}}
                        <p class="mt-2 text-xs text-warmgray">{{ __('El estudio verá esto junto con tu perfil completo.') }}</p>
                        <form method="POST" action="{{ route('ofertas.postular', $offer->slug) }}" class="mt-3">
                            @csrf
                            <label class="block text-sm font-medium text-ink">{{ __('Cuéntanos sobre tu interés y la manera en que harías equipo en este estudio: (opcional)') }}</label>
                            <textarea name="cover_letter" rows="4" maxlength="2000"
                                      class="mt-1 w-full rounded-xl border border-line px-3 py-2 text-sm"
                                      placeholder="{{ landing('ofertas_show_intro_postular') }}"></textarea>
                            <button type="submit" class="mt-3 rounded-full bg-sage px-5 py-2 text-sm font-semibold text-cream hover:bg-ink">
                                {{ landing('ofertas_show_cta_enviar') }}
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        @endauth
    </div>
</x-app-layout>
