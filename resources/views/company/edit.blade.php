<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-500 text-ink">Mi empresa</h2>
    </x-slot>

    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6">
        @if (session('status') === 'empresa-actualizada')
            <div class="mb-6 rounded-xl border border-sage/30 bg-sage/10 px-4 py-3 text-sm text-sage">
                ✓ Los datos de tu empresa se guardaron correctamente.
            </div>
        @endif

        <form method="POST" action="{{ route('company.profile.update') }}" enctype="multipart/form-data"
              class="space-y-6 rounded-2xl border border-line bg-white p-6 sm:p-8">
            @csrf
            @method('PUT')

            {{-- Logo --}}
            <div class="flex items-center gap-5">
                <div class="h-20 w-20 shrink-0 overflow-hidden rounded-2xl border border-line bg-beige">
                    @if ($profile->logo_path)
                        <img src="{{ Storage::url($profile->logo_path) }}" alt="Logo" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-2xl text-warmgray">🏢</div>
                    @endif
                </div>
                <div>
                    <x-input-label for="logo" :value="'Logo'" />
                    <input id="logo" name="logo" type="file" accept="image/*"
                           class="mt-1 block text-sm text-warmgray file:mr-3 file:rounded-full file:border-0 file:bg-sage file:px-4 file:py-2 file:text-sm file:font-500 file:text-cream hover:file:bg-ink">
                    <x-input-error :messages="$errors->get('logo')" class="mt-1" />
                </div>
            </div>

            <div>
                <x-input-label for="company_name" :value="'Nombre de la empresa'" />
                <x-text-input id="company_name" name="company_name" type="text" class="mt-1 block w-full"
                              :value="old('company_name', $profile->company_name)" required maxlength="150" />
                <x-input-error :messages="$errors->get('company_name')" class="mt-1" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="sector" :value="'Sector'" />
                    <x-text-input id="sector" name="sector" type="text" class="mt-1 block w-full"
                                  :value="old('sector', $profile->sector)" placeholder="Gimnasio, estudio, marca..." />
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

            <div>
                <x-input-label for="website" :value="'Sitio web'" />
                <x-text-input id="website" name="website" type="url" class="mt-1 block w-full"
                              :value="old('website', $profile->website)" placeholder="https://" />
                <x-input-error :messages="$errors->get('website')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="description" :value="'Descripción'" />
                <textarea id="description" name="description" rows="4" maxlength="2000"
                          class="mt-1 block w-full rounded-md border-line shadow-sm focus:border-sage focus:ring-sage"
                          placeholder="Cuenta qué hace tu empresa y qué tipo de talento buscas.">{{ old('description', $profile->description) }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-1" />
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="rounded-full bg-sage px-7 py-2.5 text-sm font-600 text-cream shadow-sm transition hover:bg-ink">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
