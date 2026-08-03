<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-medium text-ink">{{ __('Mis ofertas') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
        <p class="mb-6 text-sm text-warmgray">
            {{ __('Las ofertas se crean desde el panel de administración. Aquí ves tus ofertas activas y las postulaciones recibidas.') }}
        </p>

        @forelse ($ofertas as $o)
            <div class="mb-4 rounded-2xl border border-line bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <a href="{{ route('ofertas.show', $o->slug) }}" class="min-w-0">
                        <h3 class="font-serif text-lg font-medium text-ink hover:text-sage">{{ $o->title }}</h3>
                    </a>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="rounded-full bg-beige px-3 py-1 font-medium text-ink">{{ __(ucfirst($o->status)) }}</span>
                        <span class="rounded-full bg-cream px-3 py-1 font-medium text-warmgray">
                            {{ $o->applications_count }} {{ __('postulaciones') }}
                        </span>
                    </div>
                </div>
                <p class="mt-2 text-xs text-warmgray">
                    {{ __('Publicada:') }} {{ $o->published_at?->translatedFormat('d M Y') ?? '—' }}
                    · {{ __('Vence:') }} {{ $o->expires_on?->translatedFormat('d M Y') ?? __('sin fecha') }}
                </p>

                @if ($o->applications_count > 0)
                    <details class="mt-4">
                        <summary class="cursor-pointer text-sm font-medium text-sage">{{ __('Ver postulaciones') }}</summary>
                        <ul class="mt-3 space-y-2">
                            @foreach ($o->applications()->with('professional')->latest()->get() as $app)
                                <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-line/60 px-3 py-2 text-sm">
                                    <span>{{ $app->professional?->name }} — <em class="text-warmgray">{{ $app->created_at->translatedFormat('d M') }}</em></span>
                                    <form method="POST" action="{{ route('ofertas.postulacion.estado', $app) }}" class="flex items-center gap-2">
                                        @csrf
                                        <select name="status" class="rounded-full border border-line px-2 py-1 text-xs">
                                            <option value="seen" @selected($app->status==='seen')>{{ __('Vista') }}</option>
                                            <option value="in_contact" @selected($app->status==='in_contact')>{{ __('En contacto') }}</option>
                                            <option value="accepted" @selected($app->status==='accepted')>{{ __('Aceptada') }}</option>
                                            <option value="rejected" @selected($app->status==='rejected')>{{ __('Rechazada') }}</option>
                                        </select>
                                        <button type="submit" class="rounded-full bg-sage px-3 py-1 text-xs font-medium text-cream">{{ __('Guardar') }}</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            </div>
        @empty
            <div class="rounded-2xl border border-line bg-white px-6 py-12 text-center">
                <p class="text-warmgray">{{ __('Aún no has publicado ofertas. Créalas desde el panel de administración de Kinvoo.') }}</p>
            </div>
        @endforelse

        <div class="mt-6">{{ $ofertas->links() }}</div>
    </div>
</x-app-layout>
