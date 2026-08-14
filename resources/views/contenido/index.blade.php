<x-app-layout>
    <x-slot name="header">
        {{-- Título editable desde /admin/configuracion-sitio (tab Desarrollo). --}}
        <h2 class="font-serif text-2xl font-medium text-ink">{{ landing('desarrollo_header_titulo') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
        <x-back-link :href="route('dashboard')" :value="__('← Volver al panel')" />

        {{-- Copy editable desde admin (Configuración del sitio · Desarrollo).
             Hay 2 versiones — estudio y coach — que el admin edita por separado. --}}
        <x-guia-inline :titulo="landing('desarrollo_onboarding_titulo')" tono="beige">
            @if (auth()->user()?->esContratante())
                <p class="font-medium text-ink">{{ landing('desarrollo_copy_estudio_h1') }}</p>
                <p class="mt-1">{{ landing('desarrollo_copy_estudio_h2') }}</p>
            @else
                <p class="font-medium text-ink">{{ landing('desarrollo_copy_coach_h1') }}</p>
                <p class="mt-1">{{ landing('desarrollo_copy_coach_h2') }}</p>
            @endif
            @auth
                @if (auth()->user()->esContratante())
                    <p class="mt-2 text-sm">
                        {{ __('¿Tienes material propio? Compártelo en') }}
                        <a href="{{ route('contenido.mis-contenidos') }}" class="font-semibold text-sage underline">{{ __('Mi desarrollo') }}</a>.
                    </p>
                @endif
            @endauth
        </x-guia-inline>

        <div class="mt-6">
        @if ($categorias->isNotEmpty())
            <div class="mb-6 flex flex-wrap gap-2">
                <a href="{{ route('contenido.index') }}"
                   class="rounded-full border border-line bg-white px-4 py-1.5 text-sm text-ink {{ ! request('categoria') ? 'border-sage text-sage' : '' }}">
                    {{ __('Todas') }}
                </a>
                @foreach ($categorias as $cat)
                    <a href="{{ route('contenido.index', ['categoria' => $cat]) }}"
                       class="rounded-full border border-line bg-white px-4 py-1.5 text-sm text-ink {{ request('categoria') === $cat ? 'border-sage text-sage' : '' }}">
                        {{ __($cat) }}
                    </a>
                @endforeach
            </div>
        @endif

        @if ($items->isEmpty())
            <div class="rounded-2xl border border-line bg-white px-6 py-12 text-center">
                <p class="text-warmgray">{{ landing('contenido_index_empty') }}</p>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($items as $item)
                    @php
                        // H6 · badge de nivel + candado si el ítem es premium y
                        // el user no paga (no lo bloqueamos aquí: al hacer clic
                        // el show redirige a /membresias con mensaje de upsell).
                        $nivel = $item->access_level ?? 1;
                        $esPremium = $nivel > 1;
                        $requierePago = $esPremium
                            && auth()->user() && ! auth()->user()->tieneMembresiaActiva()
                            && ! auth()->user()->esAdmin();
                    @endphp
                    <a href="{{ route('contenido.show', $item->slug) }}"
                       class="block rounded-2xl border border-line bg-white p-5 transition hover:border-sage {{ $requierePago ? 'opacity-90' : '' }}">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="rounded-full bg-beige px-3 py-1 text-xs font-medium text-ink">
                                {{ enum_label('content_type', $item->type) }}
                            </span>
                            @if ($esPremium)
                                <span class="inline-flex items-center gap-1 rounded-full bg-lime/20 px-3 py-1 text-xs font-medium text-ink" title="{{ __('Contenido premium') }}">
                                    @if ($requierePago) 🔒 @else ✨ @endif
                                    {{ __('Nivel :n', ['n' => $nivel]) }}
                                </span>
                            @endif
                            @if ($item->category)
                                <span class="ml-1 text-xs text-warmgray">· {{ __($item->category) }}</span>
                            @endif
                        </div>
                        <h3 class="mt-3 font-serif text-lg font-medium text-ink">{{ $item->title }}</h3>
                        @if ($item->description)
                            <p class="mt-2 line-clamp-3 text-sm text-warmgray">{{ $item->description }}</p>
                        @endif
                        @if ($requierePago)
                            <p class="mt-3 text-xs font-medium text-sage">{{ landing('contenido_upsell_activa_plan') }}</p>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif

        <div class="mt-6">{{ $items->withQueryString()->links() }}</div>
        </div>{{-- /grupo con margen tras la guía --}}
    </div>
</x-app-layout>
