<x-app-layout>
    <x-slot name="header">
        @php
            // Si el usuario capturó su nombre con salutación (Sr., Sra., Ing., Dr., Mtra.),
            // saltamos esa primera palabra para saludar por su nombre real.
            $palabras = preg_split('/\s+/', trim(auth()->user()->name));
            $primera = $palabras[0] ?? '';
            $saltar = ['Sr.', 'Sra.', 'Srta.', 'Ing.', 'Dr.', 'Dra.', 'Lic.', 'Mtro.', 'Mtra.', 'Mr.', 'Mrs.', 'Ms.'];
            $primerNombre = (in_array($primera, $saltar, true) && count($palabras) > 1) ? $palabras[1] : $primera;
        @endphp
        <h2 class="font-serif text-2xl font-medium text-ink">{{ __('Hola, :name', ['name' => $primerNombre]) }}</h2>
    </x-slot>

    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        @if (auth()->user()->esProfesional())
            @php $profile = auth()->user()->professionalProfile; @endphp
            <div class="grid gap-6 lg:grid-cols-2 lg:items-start">
            <div class="rounded-2xl border border-line bg-white p-6 sm:p-8">
                <p class="text-sm font-medium uppercase tracking-widest text-sage">{{ __('Profesional') }}</p>
                <h3 class="mt-2 font-serif text-2xl font-medium text-ink">{{ __('Tu perfil en Kinvoo') }}</h3>
                <p class="mt-2 text-warmgray">
                    @if ($profile && $profile->is_published)
                        {!! __('Tu perfil está :status y visible para contratantes.', ['status' => '<strong class="text-sage">'.__('publicado').'</strong>']) !!}
                    @else
                        {!! __('Tu perfil está :status. Complétalo y publícalo para que te encuentren.', ['status' => '<strong>'.__('oculto').'</strong>']) !!}
                    @endif
                </p>
                @if ($profile)
                    @php $pct = $profile->porcentajeCompleto(); $faltan = $profile->faltantesPerfil(); @endphp
                    <div class="mt-5">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-ink">{{ __('Perfil completo') }}</span>
                            <span class="font-semibold text-sage">{{ $pct }}%</span>
                        </div>
                        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-beige">
                            <div class="h-full rounded-full bg-sage transition-all" style="width: {{ $pct }}%"></div>
                        </div>
                        @if (! empty($faltan))
                            <p class="mt-2 text-xs text-warmgray">
                                {{ __('Te falta: :items.', ['items' => implode(', ', array_map(fn ($f) => __($f), $faltan))]) }}
                            </p>
                        @endif
                    </div>
                @endif

                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('professional.profile.edit') }}"
                       class="rounded-full bg-sage px-6 py-2.5 text-sm font-semibold text-cream transition hover:bg-ink">
                        {{ __('Editar mi perfil') }}
                    </a>
                    @if ($profile && $profile->is_published)
                        <a href="{{ route('talento.show', $profile->slug) }}" target="_blank"
                           class="rounded-full border border-line px-6 py-2.5 text-sm font-medium text-warmgray transition hover:border-sage hover:text-sage">
                            {{ __('Ver perfil público ↗') }}
                        </a>
                    @endif
                </div>
            </div>

            {{-- Quién vio tu perfil --}}
            @if ($profile)
                @php
                    $totalVistas = $profile->views()->count();
                    $vistasRecientes = $profile->views()->with('viewer')->latest()->take(6)->get();
                @endphp
                <div class="rounded-2xl border border-line bg-white p-6">
                    <div class="flex items-baseline justify-between">
                        <h3 class="font-serif text-xl font-medium text-ink">{{ __('Quién vio tu perfil') }}</h3>
                        <span class="text-2xl font-medium text-sage">{{ $totalVistas }}</span>
                    </div>
                    @if ($vistasRecientes->isEmpty())
                        <p class="mt-2 text-sm text-warmgray">{{ __('Aún nadie ha visto tu perfil. Publícalo y compártelo para empezar.') }}</p>
                    @else
                        <ul class="mt-4 divide-y divide-line/60">
                            @foreach ($vistasRecientes as $v)
                                <li class="flex items-center justify-between py-2 text-sm">
                                    <span class="text-ink">{{ $v->viewer?->name ?? __('Alguien') }}</span>
                                    <span class="text-warmgray">{{ $v->created_at->diffForHumans() }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif
            </div>{{-- /grid dashboard talento --}}
        @elseif (auth()->user()->esContratante())
            <div class="rounded-2xl border border-line bg-white p-6 sm:p-8">
                <p class="text-sm font-medium uppercase tracking-widest text-sage">{{ __('Contratante') }}</p>
                <h3 class="mt-2 font-serif text-2xl font-medium text-ink">{{ __('Encuentra talento fitness') }}</h3>
                <p class="mt-2 text-warmgray">{{ __('Explora perfiles de profesionales o completa los datos de tu empresa.') }}</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('talento.index') }}"
                       class="rounded-full bg-sage px-6 py-2.5 text-sm font-semibold text-cream transition hover:bg-ink">
                        {{ __('Buscar talento') }}
                    </a>
                    <a href="{{ route('company.profile.edit') }}"
                       class="rounded-full border border-line px-6 py-2.5 text-sm font-medium text-warmgray transition hover:border-sage hover:text-sage">
                        {{ __('Editar mi empresa') }}
                    </a>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
