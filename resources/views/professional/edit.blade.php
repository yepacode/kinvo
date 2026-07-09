<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-serif text-2xl font-medium text-ink">Mi perfil profesional</h2>
            @if ($profile->is_published)
                <a href="{{ route('talento.show', $profile->slug) }}" target="_blank"
                   class="text-sm text-sage underline hover:text-ink">Ver perfil público ↗</a>
            @endif
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        {{-- Mensaje de bienvenida --}}
        <div class="mb-6 rounded-2xl border border-sage/30 bg-sage/5 px-5 py-5">
            <h3 class="font-serif text-lg font-medium text-ink">{{ landing('welcome_pro_title') }}</h3>
            <div class="mt-2 space-y-2 whitespace-pre-line text-sm leading-relaxed text-warmgray">{{ landing('welcome_pro_body') }}</div>
            <p class="mt-3 text-sm text-warmgray">Antes de publicar, revisa nuestros
                <a href="{{ route('legal.terminos') }}" target="_blank" class="text-sage underline hover:text-ink">Términos y Condiciones</a> y el
                <a href="{{ route('legal.privacidad') }}" target="_blank" class="text-sage underline hover:text-ink">Aviso de Privacidad</a>.
            </p>
        </div>

        @if (session('status') === 'perfil-actualizado')
            <div class="mb-6 rounded-xl border border-sage/30 bg-sage/10 px-4 py-3 text-sm text-sage">
                ✓ Tu perfil se guardó correctamente.
            </div>
        @endif

        @if (! $profile->is_published)
            <div class="mb-6 rounded-xl border border-line bg-beige px-4 py-3 text-sm text-warmgray">
                Tu perfil está <strong>oculto</strong>. Complétalo y actívalo abajo para que los contratantes puedan encontrarte.
            </div>
        @endif

        <form method="POST" action="{{ route('professional.profile.update') }}" enctype="multipart/form-data"
              class="space-y-8 rounded-2xl border border-line bg-white p-6 sm:p-8">
            @csrf
            @method('PUT')

            {{-- Foto --}}
            <div class="flex items-center gap-5">
                <div class="h-20 w-20 shrink-0 overflow-hidden rounded-full border border-line bg-beige">
                    @if ($profile->photo_path)
                        <img src="{{ Storage::url($profile->photo_path) }}" alt="Foto de perfil actual" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-2xl text-warmgray" aria-hidden="true">🏋️</div>
                    @endif
                </div>
                <div>
                    <x-input-label for="photo" :value="'Foto de perfil'" />
                    <input id="photo" name="photo" type="file" accept="image/*"
                           class="mt-1 block text-sm text-warmgray file:mr-3 file:rounded-full file:border-0 file:bg-sage file:px-4 file:py-2 file:text-sm file:font-medium file:text-cream hover:file:bg-ink">
                    <x-input-error :messages="$errors->get('photo')" class="mt-1" />
                    @if ($profile->photo_path)
                        <label for="remove_photo" class="mt-2 flex items-center gap-2 text-xs text-warmgray">
                            <input type="checkbox" id="remove_photo" name="remove_photo" value="1"
                                   class="rounded border-line text-sage focus:ring-sage">
                            Eliminar la foto actual
                        </label>
                    @endif
                </div>
            </div>

            {{-- Nombre completo --}}
            <div>
                <x-input-label for="full_name" :value="'Nombre completo'" />
                <x-text-input id="full_name" name="full_name" type="text" class="mt-1 block w-full"
                              :value="old('full_name', $profile->full_name)" maxlength="150"
                              placeholder="Nombre(s) y apellidos" />
                <p class="mt-1 text-xs text-warmgray">Tu nombre completo tal como aparece en tu identificación.</p>
                <x-input-error :messages="$errors->get('full_name')" class="mt-1" />
            </div>

            {{-- Titular --}}
            <div>
                <x-input-label for="headline" :value="'Titular'" />
                <x-text-input id="headline" name="headline" type="text" class="mt-1 block w-full"
                              :value="old('headline', $profile->headline)" maxlength="120"
                              placeholder="Ej. Coach de fuerza y acondicionamiento" />
                <x-input-error :messages="$errors->get('headline')" class="mt-1" />
            </div>

            {{-- Fecha de nacimiento (18+) --}}
            <div>
                <x-input-label for="birthdate" :value="'Fecha de nacimiento'" />
                <x-text-input id="birthdate" name="birthdate" type="date" class="mt-1 block w-full sm:w-60"
                              :value="old('birthdate', optional($profile->birthdate)->format('Y-m-d'))"
                              max="{{ now()->subYears(18)->format('Y-m-d') }}" />
                <p class="mt-1 text-xs text-warmgray">Debes ser mayor de 18 años.</p>
                <x-input-error :messages="$errors->get('birthdate')" class="mt-1" />
            </div>

            {{-- Bio --}}
            <div>
                <x-input-label for="bio" :value="'Sobre ti'" />
                <textarea id="bio" name="bio" rows="4" maxlength="2000"
                          class="mt-1 block w-full rounded-md border-line shadow-sm focus:border-sage focus:ring-sage"
                          placeholder="Cuenta tu experiencia, especialidad y estilo de trabajo.">{{ old('bio', $profile->bio) }}</textarea>
                <x-input-error :messages="$errors->get('bio')" class="mt-1" />
            </div>

            {{-- Experiencia + Modalidad + Ubicación --}}
            <div class="grid gap-5 sm:grid-cols-3">
                <div>
                    <x-input-label for="years_experience" :value="'Años de experiencia'" />
                    <x-text-input id="years_experience" name="years_experience" type="number" min="0" max="70"
                                  class="mt-1 block w-full" :value="old('years_experience', $profile->years_experience)" />
                    <x-input-error :messages="$errors->get('years_experience')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="modalidad" :value="'Modalidad'" />
                    <select id="modalidad" name="modalidad"
                            class="mt-1 block w-full rounded-md border-line shadow-sm focus:border-sage focus:ring-sage">
                        <option value="">—</option>
                        @foreach ($modalidades as $val => $label)
                            <option value="{{ $val }}" @selected(old('modalidad', $profile->modalidad?->value) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="location_id" :value="'Ubicación'" />
                    <select id="location_id" name="location_id"
                            class="mt-1 block w-full rounded-md border-line shadow-sm focus:border-sage focus:ring-sage">
                        <option value="">—</option>
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->id }}" @selected((int) old('location_id', $profile->location_id) === $loc->id)>{{ $loc->etiqueta() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Disciplinas --}}
            <div>
                <x-input-label :value="'Disciplinas'" />
                @php $selDisc = old('disciplines', $profile->disciplines->pluck('id')->all()); @endphp
                <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                    @foreach ($disciplines as $d)
                        <label class="flex items-center gap-2 rounded-lg border border-line px-3 py-2 text-sm text-ink has-[:checked]:border-sage has-[:checked]:bg-sage/5">
                            <input type="checkbox" name="disciplines[]" value="{{ $d->id }}"
                                   class="rounded border-line text-sage focus:ring-sage"
                                   @checked(in_array($d->id, $selDisc))>
                            {{ $d->nombre }}
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Disponibilidad (días × AM/PM) --}}
            <div>
                <x-input-label :value="'Disponibilidad'" />
                <p class="mt-1 text-xs text-warmgray">Marca los días y franjas en que puedes trabajar.</p>
                @php $selSlots = old('availability', $profile->availability ?? []); @endphp
                <div class="mt-3 overflow-hidden rounded-xl border border-line">
                    @foreach (\App\Models\ProfessionalProfile::DIAS as $diaKey => $diaLabel)
                        <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-2.5 last:border-b-0 odd:bg-cream/50">
                            <span class="text-sm text-ink">{{ $diaLabel }}</span>
                            <div class="flex gap-2">
                                @foreach (\App\Models\ProfessionalProfile::FRANJAS as $fKey => $fLabel)
                                    @php $slot = $diaKey.'_'.$fKey; @endphp
                                    <label class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-line px-3 py-1 text-xs font-medium text-warmgray has-[:checked]:border-sage has-[:checked]:bg-sage/10 has-[:checked]:text-sage">
                                        <input type="checkbox" name="availability[]" value="{{ $slot }}"
                                               class="h-3.5 w-3.5 rounded border-line text-sage focus:ring-sage"
                                               @checked(in_array($slot, $selSlots, true))>
                                        {{ $fLabel }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Idiomas --}}
            <div>
                <x-input-label :value="'Idiomas'" />
                @php $selIdiomas = old('languages', $profile->languages ?? []); @endphp
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach (\App\Models\ProfessionalProfile::IDIOMAS as $idKey => $idLabel)
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-line px-4 py-2 text-sm text-ink has-[:checked]:border-sage has-[:checked]:bg-sage/5">
                            <input type="checkbox" name="languages[]" value="{{ $idKey }}"
                                   class="rounded border-line text-sage focus:ring-sage"
                                   @checked(in_array($idKey, $selIdiomas, true))>
                            {{ $idLabel }}
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Certificaciones (texto libre + adjunto privado) --}}
            <div>
                <x-input-label for="certifications_text" :value="'Certificaciones'" />
                <textarea id="certifications_text" name="certifications_text" rows="3" maxlength="2000"
                          class="mt-1 block w-full rounded-md border-line shadow-sm focus:border-sage focus:ring-sage"
                          placeholder="Ej. Certificación en Yoga (RYT-200), Instructor de Spinning, TRX...">{{ old('certifications_text', $profile->certifications_text) }}</textarea>
                <x-input-error :messages="$errors->get('certifications_text')" class="mt-1" />

                <div class="mt-3 rounded-xl border border-line bg-cream/50 px-4 py-3">
                    <x-input-label for="certification_file" :value="'Adjuntar certificación (opcional, privado)'" />
                    <input id="certification_file" name="certification_file" type="file" accept=".pdf,image/*"
                           class="mt-1 block text-sm text-warmgray file:mr-3 file:rounded-full file:border-0 file:bg-ink file:px-4 file:py-2 file:text-sm file:font-medium file:text-cream hover:file:bg-sage">
                    <p class="mt-1 text-xs text-warmgray">
                        <span aria-hidden="true">🔒</span> Este archivo es <strong>privado</strong>: solo lo ve el equipo de Kinvoo para validarte. PDF o imagen, máx. 5 MB.
                        @if ($profile->certification_file_path)
                            <span class="text-sage">· Ya tienes un archivo cargado.</span>
                        @endif
                    </p>
                    @if ($profile->certification_file_path)
                        <label for="remove_certification_file" class="mt-2 flex items-center gap-2 text-xs text-warmgray">
                            <input type="checkbox" id="remove_certification_file" name="remove_certification_file" value="1"
                                   class="rounded border-line text-sage focus:ring-sage">
                            Eliminar el archivo adjunto actual
                        </label>
                    @endif
                    <x-input-error :messages="$errors->get('certification_file')" class="mt-1" />
                </div>
            </div>

            {{-- Contenido multimedia (único campo opcional) --}}
            <div>
                <x-input-label for="media_url" :value="'Contenido multimedia (opcional)'" />
                <x-text-input id="media_url" name="media_url" type="url" class="mt-1 block w-full"
                              :value="old('media_url', $profile->media_url)"
                              placeholder="https://youtube.com/... o enlace a tu reel" />
                <p class="mt-1 text-xs text-warmgray">Enlace a un video o reel que muestre tu estilo (YouTube, Vimeo, Drive...).</p>
                <x-input-error :messages="$errors->get('media_url')" class="mt-1" />
            </div>

            {{-- Contacto / redes --}}
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="phone" :value="'Teléfono'" />
                    <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full"
                                  :value="old('phone', $profile->phone)" placeholder="+52 ..." />
                </div>
                <div>
                    <x-input-label for="web" :value="'Sitio web'" />
                    <x-text-input id="web" name="web" type="url" class="mt-1 block w-full"
                                  :value="old('web', $profile->socials['web'] ?? '')" placeholder="https://" />
                    <x-input-error :messages="$errors->get('web')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="instagram" :value="'Instagram'" />
                    <x-text-input id="instagram" name="instagram" type="text" class="mt-1 block w-full"
                                  :value="old('instagram', $profile->socials['instagram'] ?? '')" placeholder="@usuario" />
                </div>
                <div>
                    <x-input-label for="tiktok" :value="'TikTok'" />
                    <x-text-input id="tiktok" name="tiktok" type="text" class="mt-1 block w-full"
                                  :value="old('tiktok', $profile->socials['tiktok'] ?? '')" placeholder="@usuario" />
                </div>
            </div>

            {{-- Publicar --}}
            <label class="flex items-center gap-3 rounded-xl border border-line bg-cream px-4 py-3">
                <input type="hidden" name="is_published" value="0">
                <input type="checkbox" name="is_published" value="1"
                       class="rounded border-line text-sage focus:ring-sage"
                       @checked(old('is_published', $profile->is_published))>
                <span class="text-sm text-ink">
                    <strong>Publicar mi perfil</strong> — visible para contratantes en el buscador.
                </span>
            </label>

            <div class="flex justify-end">
                <button type="submit"
                        class="rounded-full bg-sage px-7 py-2.5 text-sm font-semibold text-cream shadow-sm transition hover:bg-ink">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
