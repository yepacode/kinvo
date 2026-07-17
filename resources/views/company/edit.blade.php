<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-serif text-2xl font-medium text-ink">Mi empresa</h2>
            @if (auth()->user()->estaActivo() && $profile->slug)
                <a href="{{ route('estudio.show', $profile->slug) }}" target="_blank"
                   class="text-sm text-sage underline hover:text-ink">Ver perfil público ↗</a>
            @endif
        </div>
    </x-slot>

    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6">
        @include('partials.wizard-steps', ['paso' => 2])

        <div class="mb-6 flex items-center justify-between">
            <a href="{{ route('company.bienvenida') }}" class="text-sm text-warmgray hover:text-sage">← Atrás</a>
            <p class="text-sm text-warmgray">Completa el perfil de tu estudio y guarda.</p>
        </div>

        @if (auth()->user()->tieneMembresiaActiva())
            <div class="mb-6 rounded-xl border border-sage/30 bg-sage/10 px-4 py-3 text-sm text-sage">
                ✓ Tu membresía está activa{{ auth()->user()->membership_expires_at ? ' hasta el '.auth()->user()->membership_expires_at->format('d/m/Y') : '' }}. Tienes acceso al directorio de talento.
            </div>
        @else
            <div class="mb-6 rounded-xl border border-lime/40 bg-lime/10 px-4 py-3 text-sm text-ink">
                Tu membresía no está activa. <a href="{{ route('membresias.index') }}" class="font-medium text-sage underline hover:text-ink">Ver planes</a> para acceder al directorio de talento.
            </div>
        @endif

        @if (session('status') === 'empresa-actualizada')
            <div class="mb-6 rounded-xl border border-sage/30 bg-sage/10 px-4 py-3 text-sm text-sage">
                ✓ Los datos de tu empresa se guardaron correctamente.
            </div>
        @endif

        <form method="POST" action="{{ route('company.profile.update') }}" enctype="multipart/form-data"
              class="space-y-6 rounded-2xl border border-line bg-white p-6 sm:p-8">
            @csrf
            @method('PUT')

            {{-- Logo: preview inmediato al seleccionar (Alpine + FileReader). --}}
            <div class="flex items-center gap-5"
                 x-data="{
                     preview: null,
                     onSelect(e) {
                         const f = e.target.files?.[0];
                         if (!f) { this.preview = null; return; }
                         const r = new FileReader();
                         r.onload = ev => { this.preview = ev.target.result };
                         r.readAsDataURL(f);
                     }
                 }">
                <div class="h-20 w-20 shrink-0 overflow-hidden rounded-2xl border border-line bg-beige">
                    <template x-if="preview">
                        <img :src="preview" alt="Vista previa" class="h-full w-full object-cover">
                    </template>
                    <template x-if="!preview">
                        <div class="h-full w-full">
                            @if ($profile->logo_path)
                                <img src="{{ Storage::url($profile->logo_path) }}" alt="Logo de la empresa" class="h-full w-full object-cover">
                            @else
                                <img src="{{ asset('img/kinvoo-logo.png') }}" alt="Kinvoo" class="h-full w-full object-cover p-2">
                            @endif
                        </div>
                    </template>
                </div>
                <div class="min-w-0 flex-1">
                    <x-input-label for="logo" :value="'Logo'" />
                    <input id="logo" name="logo" type="file" accept="image/*"
                           @change="onSelect($event)"
                           class="mt-1 block w-full max-w-full text-sm text-warmgray file:mr-3 file:rounded-full file:border-0 file:bg-sage file:px-4 file:py-2 file:text-sm file:font-medium file:text-cream hover:file:bg-ink">
                    <p class="mt-1 text-xs text-warmgray" x-show="preview" x-cloak>
                        Así se verá tu logo. Guarda los cambios para publicarlo.
                    </p>
                    <x-input-error :messages="$errors->get('logo')" class="mt-1" />
                </div>
            </div>

            <div>
                <x-input-label for="company_name" :value="'Nombre del estudio / gym'" />
                <x-text-input id="company_name" name="company_name" type="text" class="mt-1 block w-full"
                              :value="old('company_name', $profile->company_name)" required maxlength="150" />
                <x-input-error :messages="$errors->get('company_name')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="disciplines_text" :value="'Disciplina'" />
                <x-text-input id="disciplines_text" name="disciplines_text" type="text" class="mt-1 block w-full"
                              :value="old('disciplines_text', $profile->disciplines_text)" maxlength="300"
                              placeholder="Ej. Yoga, Spinning, Crossfit, Pilates..." />
                <x-input-error :messages="$errors->get('disciplines_text')" class="mt-1" />
            </div>

            {{-- Ubicación: estado de México + dirección con CP --}}
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="estado" :value="'Estado (México)'" />
                    <select id="estado" name="estado"
                            class="mt-1 block w-full rounded-md border-line shadow-sm focus:border-sage focus:ring-sage">
                        <option value="">— Selecciona —</option>
                        @foreach ($estados as $estado)
                            <option value="{{ $estado }}" @selected(old('estado', $profile->estado) === $estado)>{{ $estado }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('estado')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="postal_code" :value="'Código Postal (CP)'" />
                    <x-text-input id="postal_code" name="postal_code" type="text" inputmode="numeric" class="mt-1 block w-full"
                                  :value="old('postal_code', $profile->postal_code)" maxlength="10" placeholder="11560" />
                    <p class="mt-1 text-xs text-warmgray">Nos ayuda a ubicar la colonia exacta.</p>
                    <x-input-error :messages="$errors->get('postal_code')" class="mt-1" />
                </div>
            </div>

            <div>
                <x-input-label for="address" :value="'Dirección del estudio'" />
                <x-text-input id="address" name="address" type="text" class="mt-1 block w-full"
                              :value="old('address', $profile->address)" maxlength="255"
                              placeholder="Calle y número" />
                <x-input-error :messages="$errors->get('address')" class="mt-1" />
                <label for="show_address" class="mt-2 flex items-start gap-2 text-sm text-warmgray">
                    <input type="hidden" name="show_address" value="0">
                    <input type="checkbox" id="show_address" name="show_address" value="1"
                           class="mt-0.5 rounded border-line text-sage focus:ring-sage"
                           @checked(old('show_address', $profile->show_address))>
                    Mostrar mi dirección exacta en mi perfil público. Si lo dejas desmarcado, solo se mostrará el estado.
                </label>
            </div>

            <div>
                <x-input-label for="colonia" :value="'Colonia'" />
                <x-text-input id="colonia" name="colonia" type="text" class="mt-1 block w-full"
                              :value="old('colonia', $profile->colonia)" maxlength="120"
                              placeholder="Ej. Roma Norte, Del Valle, Polanco…" />
                <p class="mt-1 text-xs text-warmgray">Opcional. Se muestra en tu perfil público solo si activas mostrar dirección.</p>
                <x-input-error :messages="$errors->get('colonia')" class="mt-1" />
            </div>

            {{-- Datos de contacto (privados, solo los ve Kinvoo) --}}
            <fieldset class="rounded-xl border border-line bg-cream/40 p-4">
                <legend class="px-1 text-sm font-semibold text-ink">Datos de contacto</legend>
                <p class="mb-3 text-xs text-warmgray"><span aria-hidden="true">🔒</span> Son privados: solo los ve Kinvoo para coordinar cada conexión.</p>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <x-input-label for="contact_name" :value="'Nombre de contacto'" />
                        <x-text-input id="contact_name" name="contact_name" type="text" class="mt-1 block w-full"
                                      :value="old('contact_name', $profile->contact_name)" maxlength="150" />
                    </div>
                    <div>
                        <x-input-label for="contact_phone" :value="'Teléfono'" />
                        <x-text-input id="contact_phone" name="contact_phone" type="text" class="mt-1 block w-full"
                                      :value="old('contact_phone', $profile->contact_phone)" placeholder="+52 ..." />
                    </div>
                    <div>
                        <x-input-label for="contact_email" :value="'Email'" />
                        <x-text-input id="contact_email" name="contact_email" type="email" class="mt-1 block w-full"
                                      :value="old('contact_email', $profile->contact_email)" />
                        <x-input-error :messages="$errors->get('contact_email')" class="mt-1" />
                    </div>
                </div>
            </fieldset>

            <div>
                <x-input-label for="website" :value="'Sitio web'" />
                <x-text-input id="website" name="website" type="url" class="mt-1 block w-full"
                              :value="old('website', $profile->website)" placeholder="https://" />
                <x-input-error :messages="$errors->get('website')" class="mt-1" />
            </div>

            {{-- Contenido multimedia: enlace externo O archivo subido (25MB máx). --}}
            <div class="rounded-md border border-line bg-cream/40 p-5">
                <div class="flex items-baseline justify-between gap-4">
                    <h3 class="font-serif text-lg font-medium text-ink">Contenido multimedia (opcional)</h3>
                    <span class="text-xs text-warmgray">Elige una opción o ambas</span>
                </div>

                <div class="mt-4">
                    <x-input-label for="media_url" :value="'Enlace externo'" />
                    <x-text-input id="media_url" name="media_url" type="url" class="mt-1 block w-full"
                                  :value="old('media_url', $profile->media_url)" placeholder="https://... video o galería" />
                    <x-input-error :messages="$errors->get('media_url')" class="mt-1" />
                </div>

                <div class="mt-5">
                    <x-input-label for="media_file" :value="'O sube un archivo (video o imagen, máx. 25 MB)'" />
                    <input id="media_file" name="media_file" type="file"
                           accept="video/mp4,video/webm,video/quicktime,video/x-m4v,image/*"
                           class="mt-1 block w-full max-w-full text-sm text-ink file:mr-4 file:rounded-md file:border-0 file:bg-sage file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-sage/90" />
                    <p class="mt-1 text-xs text-warmgray">Formatos: MP4, WebM, MOV, JPG, PNG, WebP, GIF.</p>
                    <x-input-error :messages="$errors->get('media_file')" class="mt-1" />

                    @if ($profile->media_path)
                        <div class="mt-3 flex items-center gap-3 rounded border border-line bg-white p-3 text-sm">
                            @if ($profile->media_type === 'video')
                                <video class="h-16 w-24 rounded object-cover" muted preload="metadata">
                                    <source src="{{ Storage::url($profile->media_path) }}">
                                </video>
                            @else
                                <img class="h-16 w-24 rounded object-cover" src="{{ Storage::url($profile->media_path) }}" alt="Multimedia actual">
                            @endif
                            <div class="flex-1">
                                <p class="text-ink">Archivo actual: <span class="text-warmgray">{{ basename($profile->media_path) }}</span></p>
                                <label for="remove_media_file" class="mt-1 flex items-center gap-2 text-xs text-warmgray">
                                    <input type="hidden" name="remove_media_file" value="0">
                                    <input type="checkbox" id="remove_media_file" name="remove_media_file" value="1"
                                           class="rounded border-line text-sage focus:ring-sage">
                                    Quitar este archivo
                                </label>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div>
                <x-input-label for="description" :value="'Descripción'" />
                <textarea id="description" name="description" rows="4" maxlength="2000"
                          class="mt-1 block w-full rounded-md border-line shadow-sm focus:border-sage focus:ring-sage"
                          placeholder="Cuenta qué hace tu estudio y qué tipo de talento buscas.">{{ old('description', $profile->description) }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-1" />
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="rounded-full bg-sage px-7 py-2.5 text-sm font-semibold text-cream shadow-sm transition hover:bg-ink">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
