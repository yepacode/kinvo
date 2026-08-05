<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">
            {{ $contenido->exists ? __('Editar contenido') : __('Nuevo contenido') }}
        </h2>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        @unless ($contenido->exists)
            <div class="mb-6 rounded-2xl border border-sage/40 bg-sage/10 px-5 py-4 text-sm text-ink">
                <p class="font-medium">{{ __('¿Qué tipo de contenido puedes subir?') }}</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-warmgray">
                    <li>{{ __('Videos (URL de YouTube/Vimeo o archivo MP4)') }}</li>
                    <li>{{ __('Documentos (PDF con guías, manuales, presentaciones)') }}</li>
                    <li>{{ __('Audio (podcasts, entrevistas)') }}</li>
                    <li>{{ __('Enlaces (a herramientas, artículos, recursos externos)') }}</li>
                </ul>
                <p class="mt-2 text-xs text-warmgray">
                    {{ __('Al publicar, tu contenido queda visible para todos los coaches y estudios de Kinvoo.') }}
                </p>
            </div>
        @endunless

        <form method="POST" enctype="multipart/form-data"
              action="{{ $contenido->exists ? route('contenido.actualizar', $contenido) : route('contenido.guardar') }}"
              class="space-y-6 rounded-2xl border border-line bg-white p-6 sm:p-8">
            @csrf
            @if ($contenido->exists) @method('PUT') @endif

            <div>
                <label for="title" class="block text-sm font-medium text-ink">{{ __('Título') }} *</label>
                <input id="title" name="title" type="text" required maxlength="180"
                       value="{{ old('title', $contenido->title) }}"
                       placeholder="{{ __('Ej: Guía de nutrición para atletas') }}"
                       class="mt-1 w-full min-h-[44px] rounded-xl border border-line px-3 py-2 text-sm">
                @error('title')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-ink">{{ __('Descripción (opcional)') }}</label>
                <textarea id="description" name="description" rows="3" maxlength="2000"
                          class="mt-1 w-full rounded-xl border border-line px-3 py-2 text-sm">{{ old('description', $contenido->description) }}</textarea>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="type" class="block text-sm font-medium text-ink">{{ __('Tipo') }} *</label>
                    <select id="type" name="type" required
                            class="mt-1 w-full min-h-[44px] rounded-xl border border-line px-3 py-2 text-sm">
                        <option value="video" @selected(old('type', $contenido->type) === 'video')>{{ __('Video') }}</option>
                        <option value="document" @selected(old('type', $contenido->type) === 'document')>{{ __('Documento') }}</option>
                        <option value="audio" @selected(old('type', $contenido->type) === 'audio')>{{ __('Audio') }}</option>
                        <option value="link" @selected(old('type', $contenido->type) === 'link')>{{ __('Enlace') }}</option>
                    </select>
                </div>
                <div>
                    <label for="category" class="block text-sm font-medium text-ink">{{ __('Categoría (opcional)') }}</label>
                    <input id="category" name="category" type="text" maxlength="80"
                           value="{{ old('category', $contenido->category) }}"
                           placeholder="{{ __('Ej: nutrición, entrenamiento') }}"
                           class="mt-1 w-full min-h-[44px] rounded-xl border border-line px-3 py-2 text-sm">
                </div>
            </div>

            <fieldset class="rounded-xl border border-line/60 bg-cream/40 p-4">
                <legend class="px-2 text-xs font-medium uppercase tracking-wider text-warmgray">{{ __('Contenido') }}</legend>
                <p class="mb-3 text-xs text-warmgray">
                    {{ __('Pega un enlace público (YouTube, Vimeo, Drive, Notion, etc.) O sube un archivo. Al menos uno de los dos.') }}
                </p>

                <div>
                    <label for="url" class="block text-sm font-medium text-ink">{{ __('URL externa') }}</label>
                    <input id="url" name="url" type="url" maxlength="500"
                           value="{{ old('url', $contenido->url) }}"
                           placeholder="https://youtube.com/watch?v=..."
                           class="mt-1 w-full min-h-[44px] rounded-xl border border-line px-3 py-2 text-sm">
                    @error('url')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="mt-4">
                    <label for="archivo" class="block text-sm font-medium text-ink">{{ __('O sube un archivo (máx. 25 MB)') }}</label>
                    <input id="archivo" name="archivo" type="file"
                           class="mt-1 w-full text-sm text-warmgray">
                    @if ($contenido->file_path)
                        <p class="mt-1 text-xs text-warmgray">
                            {{ __('Archivo actual:') }} <code class="text-ink">{{ basename($contenido->file_path) }}</code>
                            — {{ __('sube uno nuevo para reemplazarlo.') }}
                        </p>
                    @endif
                    @error('archivo')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </fieldset>

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-line pt-6">
                <a href="{{ route('contenido.mis-contenidos') }}"
                   class="rounded-full border border-line px-5 py-2.5 text-sm font-medium text-warmgray hover:border-sage hover:text-sage">
                    {{ __('Cancelar') }}
                </a>
                <button type="submit"
                        class="rounded-full bg-sage px-6 py-2.5 text-sm font-semibold text-cream hover:bg-ink">
                    {{ $contenido->exists ? __('Guardar cambios') : __('Publicar contenido') }}
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
