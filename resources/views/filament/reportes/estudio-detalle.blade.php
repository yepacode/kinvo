<div class="space-y-5 text-sm">
    {{-- Coaches activos --}}
    <div>
        <h4 class="font-semibold text-gray-950 dark:text-white">
            {{ __('Coaches activos') }} ({{ $coaches->count() }})
        </h4>
        @if ($coaches->isEmpty())
            <p class="mt-1 text-gray-500 dark:text-gray-400">{{ __('Sin coaches activos.') }}</p>
        @else
            <ul class="mt-2 space-y-1">
                @foreach ($coaches as $tm)
                    <li class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-2 dark:border-white/10">
                        <span class="text-gray-950 dark:text-white">{{ $tm->professional?->name ?? '—' }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            @if ($tm->joined_at){{ __('Desde') }} {{ $tm->joined_at->translatedFormat('d M Y') }}@endif
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Vacantes --}}
    <div>
        <h4 class="font-semibold text-gray-950 dark:text-white">
            {{ __('Vacantes') }} ({{ $ofertas->count() }})
        </h4>
        @if ($ofertas->isEmpty())
            <p class="mt-1 text-gray-500 dark:text-gray-400">{{ __('Sin vacantes.') }}</p>
        @else
            <ul class="mt-2 space-y-1">
                @foreach ($ofertas as $o)
                    <li class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-2 dark:border-white/10">
                        <span class="text-gray-950 dark:text-white">{{ $o->title }}</span>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-xs
                            {{ $o->status === \App\Models\Offer::STATUS_PUBLISHED
                                ? 'bg-primary-100 text-primary-700 dark:bg-primary-400/10 dark:text-primary-400'
                                : 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-400' }}">
                            {{ enum_label('offer_status', $o->status) }}
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
