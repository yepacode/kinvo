<div class="space-y-2">
    @forelse ($vistas as $v)
        <div class="flex items-start justify-between gap-3 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
            <div>
                <p class="font-medium text-gray-900 dark:text-gray-100">
                    {{ $v->contentItem?->title ?? '(contenido eliminado)' }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $v->contentItem?->type ?? '—' }}
                    @if ($v->contentItem?->category)
                        · {{ $v->contentItem->category }}
                    @endif
                </p>
            </div>
            <span class="whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                {{ $v->viewed_at?->translatedFormat('d M Y H:i') }}
            </span>
        </div>
    @empty
        <p class="rounded-lg border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
            Sin historial de vistas registrado.
        </p>
    @endforelse
    @if ($vistas->count() >= 100)
        <p class="mt-2 text-center text-xs text-gray-500 dark:text-gray-400">
            Se muestran las 100 vistas más recientes.
        </p>
    @endif
</div>
