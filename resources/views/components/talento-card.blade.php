@props(['profile'])

<a href="{{ route('talento.show', $profile->slug) }}"
   class="group flex flex-col rounded-2xl border border-line bg-white p-5 transition hover:border-sage hover:shadow-sm">
    <div class="flex items-center gap-4">
        <div class="h-16 w-16 shrink-0 overflow-hidden rounded-full border border-line bg-beige">
            @if ($profile->photo_path)
                <img src="{{ Storage::url($profile->photo_path) }}" alt="{{ $profile->user->name }}" class="h-full w-full object-cover">
            @else
                <img src="{{ asset('img/kinvoo-logo.png') }}" alt="Kinvoo" class="h-full w-full object-cover p-2">
            @endif
        </div>
        <div class="min-w-0">
            <h3 class="truncate font-serif text-lg font-medium text-ink group-hover:text-sage">{{ $profile->user->name }} <x-verified-badge :profile="$profile" /></h3>
            @if ($profile->headline)
                <p class="truncate text-sm text-warmgray">{{ $profile->headline }}</p>
            @endif
        </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-warmgray">
        @if ($profile->location)
            <span><span aria-hidden="true">📍</span> {{ $profile->location->etiqueta() }}</span>
        @endif
        @if ($profile->modalidad)
            <span>· {{ $profile->modalidad->label() }}</span>
        @endif
    </div>

    @if ($profile->disciplines->isNotEmpty())
        <div class="mt-3 flex flex-wrap gap-1.5">
            @foreach ($profile->disciplines->take(3) as $d)
                <span class="rounded-full bg-sage/10 px-2.5 py-0.5 text-xs text-sage">{{ $d->nombre }}</span>
            @endforeach
            @if ($profile->disciplines->count() > 3)
                <span class="rounded-full bg-beige px-2.5 py-0.5 text-xs text-warmgray">+{{ $profile->disciplines->count() - 3 }}</span>
            @endif
        </div>
    @endif
</a>
