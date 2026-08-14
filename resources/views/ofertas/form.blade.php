<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">
            {{ $oferta->exists ? __('Editar oferta') : __('Nueva oferta') }}
        </h2>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        <x-back-link :href="route('ofertas.mis-ofertas')" :value="__('← Volver a mis ofertas')" />

        {{-- Mini-guía para que el estudio nuevo sepa qué esperar. --}}
        @unless ($oferta->exists)
            <div class="mb-6 rounded-2xl border border-sage/40 bg-sage/10 px-5 py-4 text-sm text-ink">
                <p class="font-medium">{{ __('¿Cómo funciona una oferta?') }}</p>
                <ol class="mt-2 list-decimal space-y-1 pl-5 text-warmgray">
                    <li>{{ __('Llenas los datos y publicas.') }}</li>
                    <li>{{ __('Los coaches la ven en /ofertas y se postulan.') }}</li>
                    <li>{{ __('Te llega notificación por cada postulación. Cambias el estado (Vista, En contacto, Aceptada, Rechazada) desde tu panel.') }}</li>
                </ol>
            </div>
        @endunless

        <form method="POST"
              action="{{ $oferta->exists ? route('ofertas.actualizar', $oferta) : route('ofertas.guardar') }}"
              class="space-y-6 rounded-2xl border border-line bg-white p-6 sm:p-8">
            @csrf
            @if ($oferta->exists) @method('PUT') @endif

            <div>
                <label for="title" class="block text-sm font-medium text-ink">{{ __('Título de la oferta') }} *</label>
                <input id="title" name="title" type="text" required maxlength="180"
                       value="{{ old('title', $oferta->title) }}"
                       placeholder="{{ __('Ej: Instructor de Pilates fin de semana') }}"
                       class="mt-1 w-full min-h-[44px] rounded-xl border border-line px-3 py-2 text-sm">
                @error('title')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-ink">{{ __('Descripción') }} *</label>
                <textarea id="description" name="description" required rows="5" maxlength="5000"
                          placeholder="{{ __('Cuéntanos qué buscas: horarios, tipo de clases, tu estudio...') }}"
                          class="mt-1 w-full rounded-xl border border-line px-3 py-2 text-sm">{{ old('description', $oferta->description) }}</textarea>
                @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="requirements" class="block text-sm font-medium text-ink">{{ __('Requisitos (opcional)') }}</label>
                <textarea id="requirements" name="requirements" rows="3" maxlength="3000"
                          placeholder="{{ __('Certificaciones, años de experiencia, etc.') }}"
                          class="mt-1 w-full rounded-xl border border-line px-3 py-2 text-sm">{{ old('requirements', $oferta->requirements) }}</textarea>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="discipline_id" class="block text-sm font-medium text-ink">{{ __('Disciplina') }}</label>
                    <select id="discipline_id" name="discipline_id"
                            class="mt-1 w-full min-h-[44px] rounded-xl border border-line px-3 py-2 text-sm">
                        <option value="">{{ __('— Elige —') }}</option>
                        @foreach (\App\Models\Discipline::orderBy('nombre')->get() as $d)
                            <option value="{{ $d->id }}" @selected(old('discipline_id', $oferta->discipline_id) == $d->id)>{{ $d->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="location_id" class="block text-sm font-medium text-ink">{{ __('Ciudad') }}</label>
                    <select id="location_id" name="location_id"
                            class="mt-1 w-full min-h-[44px] rounded-xl border border-line px-3 py-2 text-sm">
                        <option value="">{{ __('— Elige —') }}</option>
                        @foreach (\App\Models\Location::orderBy('nombre')->get() as $l)
                            <option value="{{ $l->id }}" @selected(old('location_id', $oferta->location_id) == $l->id)>{{ $l->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- H3 · petición cliente: colonia + disponibilidad como en el perfil profesional. --}}
            <div>
                <label for="colonia" class="block text-sm font-medium text-ink">{{ __('Colonia (opcional)') }}</label>
                <input id="colonia" name="colonia" type="text" maxlength="120"
                       value="{{ old('colonia', $oferta->colonia) }}"
                       placeholder="{{ __('Ej: Roma Norte, Chapinero, Palermo...') }}"
                       class="mt-1 w-full min-h-[44px] rounded-xl border border-line px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-warmgray">{{ __('Ayuda al coach a ubicar tu estudio de forma más precisa.') }}</p>
            </div>

            <fieldset class="rounded-xl border border-line/60 bg-cream/40 p-4">
                <legend class="px-2 text-xs font-medium uppercase tracking-wider text-warmgray">
                    {{ __('Días y franjas generales (opcional)') }}
                </legend>
                <p class="text-xs text-warmgray">{{ __('Marca AM/PM por día para el matching automático con el coach. Para horas exactas, usa la sección de abajo.') }}</p>
                @php $sel = old('availability', $oferta->availability ?? []); @endphp
                <div class="mt-3 grid gap-2 sm:grid-cols-3">
                    @foreach (\App\Models\ProfessionalProfile::DIAS as $diaKey => $diaLabel)
                        <div class="rounded-lg border border-line/60 bg-white p-3">
                            <p class="text-sm font-medium text-ink">{{ __($diaLabel) }}</p>
                            <div class="mt-2 flex gap-3">
                                @foreach (\App\Models\ProfessionalProfile::FRANJAS as $franjaKey => $franjaLabel)
                                    @php $slot = $diaKey.'_'.$franjaKey; @endphp
                                    <label class="inline-flex items-center gap-1.5 text-xs text-ink">
                                        <input type="checkbox" name="availability[]" value="{{ $slot }}"
                                               @checked(in_array($slot, $sel, true))
                                               class="h-4 w-4 rounded border-line text-sage focus:ring-sage">
                                        {{ $franjaLabel }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </fieldset>

            {{-- H3 · petición cliente (chat, ago-2026): horarios EXACTOS.
                 Ejemplo: "Lunes 07:00 → 09:00". Rangos dinámicos con Alpine.js
                 (ya presente en el layout para el menú móvil). --}}
            <fieldset class="rounded-xl border border-line/60 bg-cream/40 p-4"
                      x-data="{
                          rangos: {{ Illuminate\Support\Js::from(old('schedule_ranges', $oferta->schedule_ranges ?? [])) }},
                          agregar() { this.rangos.push({day: 'lun', from: '', to: ''}); },
                          quitar(i) { this.rangos.splice(i, 1); }
                      }">
                <legend class="px-2 text-xs font-medium uppercase tracking-wider text-warmgray">
                    {{ __('Horarios exactos (opcional)') }}
                </legend>
                <p class="text-xs text-warmgray">{{ __('Si necesitas horas concretas — por ejemplo, "Lunes 7:00 a 9:00" — agrégalas aquí. Puedes poner más de una franja por día.') }}</p>

                <template x-for="(r, i) in rangos" :key="i">
                    <div class="mt-3 flex flex-wrap items-center gap-2 rounded-lg border border-line/60 bg-white p-3">
                        <select :name="`schedule_ranges[${i}][day]`" x-model="r.day"
                                class="min-h-[44px] rounded-xl border border-line px-3 py-2 text-sm">
                            @foreach (\App\Models\ProfessionalProfile::DIAS as $diaKey => $diaLabel)
                                <option value="{{ $diaKey }}">{{ __($diaLabel) }}</option>
                            @endforeach
                        </select>
                        <input type="time" :name="`schedule_ranges[${i}][from]`" x-model="r.from" required
                               class="min-h-[44px] w-32 rounded-xl border border-line px-3 py-2 text-sm">
                        <span class="text-warmgray">→</span>
                        <input type="time" :name="`schedule_ranges[${i}][to]`" x-model="r.to" required
                               class="min-h-[44px] w-32 rounded-xl border border-line px-3 py-2 text-sm">
                        <button type="button" @click="quitar(i)"
                                class="ml-auto rounded-full border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50"
                                :aria-label="`{{ __('Quitar franja') }} ${i + 1}`">
                            {{ __('Quitar') }}
                        </button>
                    </div>
                </template>

                <button type="button" @click="agregar()"
                        class="mt-3 inline-flex min-h-[36px] items-center rounded-full border border-sage/60 bg-white px-4 py-1.5 text-sm font-medium text-sage hover:bg-sage/10">
                    + {{ __('Agregar franja horaria') }}
                </button>
                @error('schedule_ranges.*.to')<p class="mt-2 text-xs text-red-600">{{ __('La hora "hasta" debe ser mayor que "desde".') }}</p>@enderror

                <div class="mt-4">
                    <label for="schedule_notes" class="block text-sm font-medium text-ink">{{ __('Notas del horario (opcional)') }}</label>
                    <textarea id="schedule_notes" name="schedule_notes" rows="2" maxlength="1000"
                              placeholder="{{ __('Ej: sábados alternos, primera semana del mes, prefiero puntualidad estricta...') }}"
                              class="mt-1 w-full rounded-xl border border-line px-3 py-2 text-sm">{{ old('schedule_notes', $oferta->schedule_notes) }}</textarea>
                </div>
            </fieldset>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="modality" class="block text-sm font-medium text-ink">{{ __('Modalidad') }} *</label>
                    <select id="modality" name="modality" required
                            class="mt-1 w-full min-h-[44px] rounded-xl border border-line px-3 py-2 text-sm">
                        <option value="presencial" @selected(old('modality', $oferta->modality) === 'presencial')>{{ __('Presencial') }}</option>
                        <option value="online" @selected(old('modality', $oferta->modality) === 'online')>{{ __('Online') }}</option>
                        <option value="hibrido" @selected(old('modality', $oferta->modality) === 'hibrido')>{{ __('Híbrido') }}</option>
                    </select>
                </div>
                <div>
                    <label for="contract_type" class="block text-sm font-medium text-ink">{{ __('Tipo de contrato') }}</label>
                    <select id="contract_type" name="contract_type"
                            class="mt-1 w-full min-h-[44px] rounded-xl border border-line px-3 py-2 text-sm">
                        <option value="">{{ __('— Elige —') }}</option>
                        <option value="full_time" @selected(old('contract_type', $oferta->contract_type) === 'full_time')>{{ __('Tiempo completo') }}</option>
                        <option value="part_time" @selected(old('contract_type', $oferta->contract_type) === 'part_time')>{{ __('Medio tiempo') }}</option>
                        <option value="freelance" @selected(old('contract_type', $oferta->contract_type) === 'freelance')>{{ __('Freelance') }}</option>
                    </select>
                </div>
            </div>

            <fieldset class="rounded-xl border border-line/60 bg-cream/40 p-4">
                <legend class="px-2 text-xs font-medium uppercase tracking-wider text-warmgray">{{ __('Compensación') }}</legend>
                <div class="grid gap-3 sm:grid-cols-3">
                    <div>
                        <label for="salary_min_cents" class="block text-xs text-warmgray">{{ __('Mínimo (centavos)') }}</label>
                        <input id="salary_min_cents" name="salary_min_cents" type="number" min="0"
                               value="{{ old('salary_min_cents', $oferta->salary_min_cents) }}"
                               placeholder="1500000"
                               class="mt-1 w-full min-h-[44px] rounded-xl border border-line px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label for="salary_max_cents" class="block text-xs text-warmgray">{{ __('Máximo (centavos)') }}</label>
                        <input id="salary_max_cents" name="salary_max_cents" type="number" min="0"
                               value="{{ old('salary_max_cents', $oferta->salary_max_cents) }}"
                               placeholder="2500000"
                               class="mt-1 w-full min-h-[44px] rounded-xl border border-line px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label for="salary_period" class="block text-xs text-warmgray">{{ __('Periodo') }}</label>
                        <select id="salary_period" name="salary_period"
                                class="mt-1 w-full min-h-[44px] rounded-xl border border-line px-3 py-2 text-sm">
                            <option value="month" @selected(old('salary_period', $oferta->salary_period ?? 'month') === 'month')>{{ __('Mes') }}</option>
                            <option value="hour" @selected(old('salary_period', $oferta->salary_period) === 'hour')>{{ __('Hora') }}</option>
                            <option value="year" @selected(old('salary_period', $oferta->salary_period) === 'year')>{{ __('Año') }}</option>
                            <option value="project" @selected(old('salary_period', $oferta->salary_period) === 'project')>{{ __('Proyecto') }}</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <label for="salary_currency" class="block text-xs text-warmgray">{{ __('Moneda') }} *</label>
                    <input id="salary_currency" name="salary_currency" type="text" required maxlength="3"
                           value="{{ old('salary_currency', $oferta->salary_currency ?? 'MXN') }}"
                           class="mt-1 w-24 min-h-[44px] rounded-xl border border-line px-3 py-2 text-sm uppercase">
                </div>
                <p class="mt-2 text-xs text-warmgray">{{ __('Ejemplo: 1500000 centavos = $15,000 MXN.') }}</p>
            </fieldset>

            <div>
                <label for="expires_on" class="block text-sm font-medium text-ink">{{ __('Vence el (opcional)') }}</label>
                <input id="expires_on" name="expires_on" type="date"
                       value="{{ old('expires_on', $oferta->expires_on?->format('Y-m-d')) }}"
                       min="{{ now()->addDay()->format('Y-m-d') }}"
                       class="mt-1 w-full min-h-[44px] rounded-xl border border-line px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-warmgray">{{ __('Si la dejas vacía, la oferta se queda publicada hasta que la cierres.') }}</p>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-line pt-6">
                <a href="{{ route('ofertas.mis-ofertas') }}"
                   class="rounded-full border border-line px-5 py-2.5 text-sm font-medium text-warmgray hover:border-sage hover:text-sage">
                    {{ __('Cancelar') }}
                </a>
                <button type="submit"
                        class="rounded-full bg-sage px-6 py-2.5 text-sm font-semibold text-cream hover:bg-ink">
                    {{ $oferta->exists ? __('Guardar cambios') : landing('ofertas_form_publicar_cta') }}
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
