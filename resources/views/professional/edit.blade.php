<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-serif text-2xl font-500 text-ink">Mi perfil profesional</h2>
            @if ($profile->is_published)
                <a href="{{ route('talento.show', $profile->slug) }}" target="_blank"
                   class="text-sm text-sage underline hover:text-ink">Ver perfil público ↗</a>
            @endif
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
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
                        <img src="{{ Storage::url($profile->photo_path) }}" alt="Foto" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-2xl text-warmgray">🏋️</div>
                    @endif
                </div>
                <div>
                    <x-input-label for="photo" :value="'Foto de perfil'" />
                    <input id="photo" name="photo" type="file" accept="image/*"
                           class="mt-1 block text-sm text-warmgray file:mr-3 file:rounded-full file:border-0 file:bg-sage file:px-4 file:py-2 file:text-sm file:font-500 file:text-cream hover:file:bg-ink">
                    <x-input-error :messages="$errors->get('photo')" class="mt-1" />
                </div>
            </div>

            {{-- Titular --}}
            <div>
                <x-input-label for="headline" :value="'Titular'" />
                <x-text-input id="headline" name="headline" type="text" class="mt-1 block w-full"
                              :value="old('headline', $profile->headline)" maxlength="120"
                              placeholder="Ej. Coach de fuerza y acondicionamiento" />
                <x-input-error :messages="$errors->get('headline')" class="mt-1" />
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

            {{-- Certificaciones --}}
            <div>
                <x-input-label :value="'Certificaciones'" />
                @php $selCert = old('certifications', $profile->certifications->pluck('id')->all()); @endphp
                <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                    @foreach ($certifications as $c)
                        <label class="flex items-center gap-2 rounded-lg border border-line px-3 py-2 text-sm text-ink has-[:checked]:border-sage has-[:checked]:bg-sage/5">
                            <input type="checkbox" name="certifications[]" value="{{ $c->id }}"
                                   class="rounded border-line text-sage focus:ring-sage"
                                   @checked(in_array($c->id, $selCert))>
                            {{ $c->nombre }}
                        </label>
                    @endforeach
                </div>
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
                        class="rounded-full bg-sage px-7 py-2.5 text-sm font-600 text-cream shadow-sm transition hover:bg-ink">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
