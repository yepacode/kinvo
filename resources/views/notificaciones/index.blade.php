<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-serif text-2xl font-500 text-ink">Notificaciones</h2>
            @if (auth()->user()->unreadNotifications()->count() > 0)
                <form method="POST" action="{{ route('notifications.readAll') }}">
                    @csrf
                    <button type="submit" class="text-sm text-sage hover:underline">Marcar todo leído</button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6">
        <div class="overflow-hidden rounded-2xl border border-line bg-white">
            @forelse ($notifications as $n)
                <a href="{{ route('notifications.open', $n->id) }}"
                   class="flex gap-4 border-b border-line/60 px-5 py-4 transition hover:bg-cream {{ is_null($n->read_at) ? 'bg-sage/5' : '' }}">
                    <span class="text-2xl">{{ $n->data['icono'] ?? '🔔' }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-500 text-ink">{{ $n->data['titulo'] ?? 'Notificación' }}</p>
                        <p class="text-sm text-warmgray">{{ $n->data['mensaje'] ?? '' }}</p>
                        <p class="mt-1 text-xs text-warmgray">{{ $n->created_at->diffForHumans() }}</p>
                    </div>
                    @if (is_null($n->read_at))
                        <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-lime" title="No leída"></span>
                    @endif
                </a>
            @empty
                <p class="px-5 py-16 text-center text-warmgray">Aún no tienes notificaciones.</p>
            @endforelse
        </div>

        @if ($notifications->hasPages())
            <div class="mt-6">{{ $notifications->links() }}</div>
        @endif
    </div>
</x-app-layout>
