<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-serif text-2xl font-medium text-ink">{{ landing('notificaciones_header_titulo') }}</h2>
            @if (auth()->user()->unreadNotifications()->count() > 0)
                <form method="POST" action="{{ route('notifications.readAll') }}">
                    @csrf
                    <button type="submit" class="text-sm text-sage hover:underline">{{ __('Marcar todo leído') }}</button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6">
        <div class="overflow-hidden rounded-2xl border border-line bg-white">
            @forelse ($notifications as $n)
                @php
                    $titulo = isset($n->data['titulo_key'])
                        ? __($n->data['titulo_key'], $n->data['titulo_params'] ?? [])
                        : ($n->data['titulo'] ?? __('Notificación'));
                    $mensaje = isset($n->data['mensaje_key'])
                        ? __($n->data['mensaje_key'], $n->data['mensaje_params'] ?? [])
                        : ($n->data['mensaje'] ?? '');
                    // Invitación de equipo: si sigue vigente y es para este coach,
                    // mostramos Aceptar/Rechazar aquí mismo (Punto 7).
                    $tmInvitacion = (($n->data['tipo'] ?? null) === 'invitacion_equipo' && isset($n->data['team_member_id']))
                        ? \App\Models\TeamMember::find($n->data['team_member_id'])
                        : null;
                    $invitacionPendiente = $tmInvitacion
                        && $tmInvitacion->status === \App\Models\TeamMember::STATUS_INVITED
                        && $tmInvitacion->professional_user_id === auth()->id();
                @endphp
                <div class="border-b border-line/60 {{ is_null($n->read_at) ? 'bg-sage/5' : '' }}">
                    <a href="{{ route('notifications.open', $n->id) }}"
                       class="flex gap-4 px-5 py-4 transition hover:bg-cream">
                        <span class="text-2xl" aria-hidden="true">{{ $n->data['icono'] ?? '🔔' }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-ink">{{ $titulo }}</p>
                            <p class="text-sm text-warmgray">{{ $mensaje }}</p>
                            <p class="mt-1 text-xs text-warmgray">{{ $n->created_at->diffForHumans() }}</p>
                        </div>
                        @if (is_null($n->read_at))
                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-lime" title="{{ __('No leída') }}"></span>
                        @endif
                    </a>
                    @if ($invitacionPendiente)
                        <div class="flex flex-wrap gap-2 px-5 pb-4 pl-16">
                            <form method="POST" action="{{ route('equipo.aceptar', $tmInvitacion) }}">
                                @csrf
                                <button type="submit"
                                        class="min-h-[40px] rounded-full bg-sage px-5 py-2 text-sm font-semibold text-cream hover:bg-ink">
                                    {{ __('Aceptar invitación') }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('equipo.rechazar', $tmInvitacion) }}">
                                @csrf
                                <button type="submit"
                                        class="min-h-[40px] rounded-full border border-line bg-white px-5 py-2 text-sm font-medium text-warmgray hover:bg-cream">
                                    {{ __('Rechazar') }}
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @empty
                <p class="px-5 py-16 text-center text-warmgray">{{ landing('notificaciones_empty_state') }}</p>
            @endforelse
        </div>

        @if ($notifications->hasPages())
            <div class="mt-6">{{ $notifications->links() }}</div>
        @endif
    </div>
</x-app-layout>
