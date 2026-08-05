<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ __('Mis ofertas') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
        {{-- Flashes de resultado de las acciones del CRUD. --}}
        @foreach (['oferta-creada' => __('Oferta publicada. Ya la ven los coaches.'),
                   'oferta-actualizada' => __('Cambios guardados.'),
                   'oferta-cerrada' => __('Oferta cerrada. Ya no recibe postulaciones.'),
                   'oferta-estado-actualizado' => __('Estado de la oferta actualizado.')] as $flag => $mensaje)
            @if (session('status') === $flag)
                <div class="mb-6 rounded-xl border border-sage/40 bg-sage/10 px-5 py-3 text-sm text-ink">{{ $mensaje }}</div>
            @endif
        @endforeach

        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-warmgray">
                {{ __('Aquí publicas tus vacantes, gestionas las postulaciones y cambias el estado de cada candidato.') }}
            </p>
            <a href="{{ route('ofertas.crear') }}"
               class="inline-flex min-h-[44px] items-center rounded-full bg-sage px-5 py-2.5 text-sm font-semibold text-cream hover:bg-ink">
                + {{ __('Publicar oferta') }}
            </a>
        </div>

        @forelse ($ofertas as $o)
            <div class="mb-4 rounded-2xl border border-line bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <a href="{{ route('ofertas.show', $o->slug) }}" class="min-w-0">
                        <h3 class="font-serif text-lg font-medium text-ink hover:text-sage">{{ $o->title }}</h3>
                    </a>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="rounded-full bg-beige px-3 py-1 font-medium text-ink">{{ enum_label('offer_status', $o->status) }}</span>
                        <span class="rounded-full bg-cream px-3 py-1 font-medium text-warmgray">
                            {{ $o->applications_count }} {{ __('postulaciones') }}
                        </span>
                    </div>
                </div>
                <p class="mt-2 text-xs text-warmgray">
                    {{ __('Publicada:') }} {{ $o->published_at?->translatedFormat('d M Y') ?? '—' }}
                    · {{ __('Vence:') }} {{ $o->expires_on?->translatedFormat('d M Y') ?? __('sin fecha') }}
                </p>

                {{-- Acciones de la oferta: editar / cerrar. --}}
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('ofertas.editar', $o) }}"
                       class="inline-flex min-h-[36px] items-center rounded-full border border-line px-3 py-1.5 text-xs font-medium text-ink hover:border-sage hover:text-sage">
                        {{ __('Editar') }}
                    </a>
                    @if ($o->status !== \App\Models\Offer::STATUS_CLOSED)
                        <form method="POST" action="{{ route('ofertas.eliminar', $o) }}"
                              onsubmit="return confirm('{{ __('¿Cerrar esta oferta? Ya no recibirá nuevas postulaciones.') }}');">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="inline-flex min-h-[36px] items-center rounded-full border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50">
                                {{ __('Cerrar oferta') }}
                            </button>
                        </form>
                    @endif
                </div>

                @if ($o->applications_count > 0)
                    <details class="mt-4">
                        <summary class="cursor-pointer text-sm font-medium text-sage">{{ __('Ver postulaciones') }}</summary>
                        <ul class="mt-3 space-y-2">
                            @foreach ($o->applications()->with('professional')->latest()->get() as $app)
                                <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-line/60 px-3 py-2 text-sm">
                                    <span>{{ $app->professional?->name }} — <em class="text-warmgray">{{ $app->created_at->translatedFormat('d M') }}</em></span>
                                    <form method="POST" action="{{ route('ofertas.postulacion.estado', $app) }}" class="flex items-center gap-2">
                                        @csrf
                                        <select name="status" class="min-h-[44px] rounded-full border border-line px-3 py-2 text-sm">
                                            <option value="seen" @selected($app->status==='seen')>{{ __('Vista') }}</option>
                                            <option value="in_contact" @selected($app->status==='in_contact')>{{ __('En contacto') }}</option>
                                            <option value="accepted" @selected($app->status==='accepted')>{{ __('Aceptada') }}</option>
                                            <option value="rejected" @selected($app->status==='rejected')>{{ __('Rechazada') }}</option>
                                        </select>
                                        <button type="submit" class="min-h-[44px] rounded-full bg-sage px-4 py-2 text-sm font-medium text-cream">{{ __('Guardar') }}</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            </div>
        @empty
            <div class="rounded-2xl border border-line bg-white px-6 py-12 text-center">
                <p class="text-warmgray">{{ __('Aún no has publicado ofertas. Presiona “+ Publicar oferta” arriba para comenzar.') }}</p>
            </div>
        @endforelse

        <div class="mt-6">{{ $ofertas->links() }}</div>
    </div>
</x-app-layout>
