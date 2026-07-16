<x-public-layout :title="'Contactar a '.$profile->user->name.' · Kinvoo'">
    <div class="mx-auto max-w-xl px-6 py-10">
        <a href="{{ route('talento.show', $profile->slug) }}" class="text-sm text-warmgray hover:text-sage">← Volver al perfil</a>

        <div class="mt-4 rounded-2xl border border-line bg-white p-6 sm:p-8">
            <h1 class="font-serif text-2xl font-medium text-ink">Contactar a {{ $profile->user->name }}</h1>
            <p class="mt-1 text-sm text-warmgray">Tu mensaje llega a Kinvoo y nosotros hacemos el puente con {{ $profile->user->name }}. Los datos de contacto de ambas partes se mantienen privados.</p>

            <form method="POST" action="{{ route('contacto.store', $profile->slug) }}" class="mt-6 space-y-5"
                  x-data="{ enviando: false }" @submit="enviando = true">
                @csrf

                <div>
                    <x-input-label for="contact_name" :value="'Tu nombre / empresa'" />
                    <x-text-input id="contact_name" name="contact_name" type="text" class="mt-1 block w-full"
                                  :value="old('contact_name', $prefillName)" required maxlength="150" />
                    <x-input-error :messages="$errors->get('contact_name')" class="mt-1" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="contact_email" :value="'Correo'" />
                        <x-text-input id="contact_email" name="contact_email" type="email" class="mt-1 block w-full"
                                      :value="old('contact_email', $prefillEmail)" required maxlength="150" />
                        <x-input-error :messages="$errors->get('contact_email')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="contact_phone" :value="'Teléfono (opcional)'" />
                        <x-text-input id="contact_phone" name="contact_phone" type="text" class="mt-1 block w-full"
                                      :value="old('contact_phone')" maxlength="40" />
                        <x-input-error :messages="$errors->get('contact_phone')" class="mt-1" />
                    </div>
                </div>

                <div>
                    <x-input-label for="message" :value="'Mensaje'" />
                    <textarea id="message" name="message" rows="5" required minlength="10" maxlength="2000"
                              class="mt-1 block w-full rounded-md border-line shadow-sm focus:border-sage focus:ring-sage"
                              placeholder="Cuéntale qué buscas, el tipo de colaboración y cómo contactarte.">{{ old('message') }}</textarea>
                    <x-input-error :messages="$errors->get('message')" class="mt-1" />
                </div>

                <div class="flex justify-end">
                    <button type="submit" x-bind:disabled="enviando"
                            class="rounded-full bg-sage px-7 py-2.5 text-sm font-semibold text-cream shadow-sm transition hover:bg-ink disabled:cursor-wait disabled:opacity-60">
                        <span x-show="!enviando">Enviar mensaje</span>
                        <span x-show="enviando" x-cloak>Enviando…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-public-layout>
