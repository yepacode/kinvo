@php
    $unread = auth()->user()->unreadNotifications()->count();
    $ultimas = auth()->user()->notifications()->latest()->take(6)->get();
@endphp

<div x-data="{ open: false }" class="relative">
    <button @click="open = ! open" class="relative flex h-9 w-9 items-center justify-center rounded-full text-warmgray transition hover:bg-beige hover:text-sage" aria-label="Notificaciones">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>
        @if ($unread > 0)
            <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-lime px-1 text-[10px] font-600 text-ink">{{ $unread > 9 ? '9+' : $unread }}</span>
        @endif
    </button>

    <div x-show="open" x-cloak @click.outside="open = false" x-transition
         class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-xl border border-line bg-white shadow-lg">
        <div class="flex items-center justify-between border-b border-line px-4 py-2.5">
            <span class="text-sm font-600 text-ink">Notificaciones</span>
            @if ($unread > 0)
                <form method="POST" action="{{ route('notifications.readAll') }}">
                    @csrf
                    <button type="submit" class="text-xs text-sage hover:underline">Marcar todo leído</button>
                </form>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse ($ultimas as $n)
                <a href="{{ route('notifications.open', $n->id) }}"
                   class="flex gap-3 border-b border-line/60 px-4 py-3 transition hover:bg-cream {{ is_null($n->read_at) ? 'bg-sage/5' : '' }}">
                    <span class="text-lg">{{ $n->data['icono'] ?? '🔔' }}</span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-500 text-ink">{{ $n->data['titulo'] ?? 'Notificación' }}</p>
                        <p class="truncate text-xs text-warmgray">{{ $n->data['mensaje'] ?? '' }}</p>
                        <p class="mt-0.5 text-[11px] text-warmgray">{{ $n->created_at->diffForHumans() }}</p>
                    </div>
                </a>
            @empty
                <p class="px-4 py-8 text-center text-sm text-warmgray">Aún no tienes notificaciones.</p>
            @endforelse
        </div>

        <a href="{{ route('notifications.index') }}" class="block border-t border-line px-4 py-2.5 text-center text-sm text-sage hover:underline">
            Ver todas
        </a>
    </div>
</div>
