<x-public-layout :title="landing('membership_title').' · Kinvoo'" :description="landing('membership_body')">
    <div class="mx-auto max-w-5xl px-6 py-14 sm:py-20">
        @if (session('status') === 'membresia-requerida')
            <div class="mx-auto mb-10 max-w-2xl rounded-xl border border-lime/40 bg-lime/10 px-5 py-4 text-center text-sm text-ink">
                {{ landing('membresia_flash_directorio') }}
            </div>
        @elseif (session('status') === 'plan-necesario-ofertas')
            <div class="mx-auto mb-10 max-w-2xl rounded-xl border border-lime/40 bg-lime/10 px-5 py-4 text-center text-sm text-ink">
                {{ landing('membresia_flash_ofertas') }}
            </div>
        @elseif (session('status') === 'plan-necesario-mas-vacantes')
            <div class="mx-auto mb-10 max-w-2xl rounded-xl border border-lime/40 bg-lime/10 px-5 py-4 text-center text-sm text-ink">
                {{ landing('membresia_flash_mas_vacantes') }}
            </div>
        @elseif (session('status') === 'plan-necesario-contacto')
            <div class="mx-auto mb-10 max-w-2xl rounded-xl border border-lime/40 bg-lime/10 px-5 py-4 text-center text-sm text-ink">
                {{ landing('membresia_flash_contacto') }}
            </div>
        @elseif (session('status') === 'plan-necesario-contenido')
            <div class="mx-auto mb-10 max-w-2xl rounded-xl border border-lime/40 bg-lime/10 px-5 py-4 text-center text-sm text-ink">
                {{ landing('membresia_flash_contenido') }}
            </div>
        @elseif (session('status') === 'plan-necesario-bienestar')
            <div class="mx-auto mb-10 max-w-2xl rounded-xl border border-lime/40 bg-lime/10 px-5 py-4 text-center text-sm text-ink">
                {{ __('El expediente y evaluación de bienestar se desbloquea cuando contratas un plan de cuidado para tu equipo.') }}
            </div>
        @elseif (session('status') === 'plan-necesario-comunidad')
            <div class="mx-auto mb-10 max-w-2xl rounded-xl border border-lime/40 bg-lime/10 px-5 py-4 text-center text-sm text-ink">
                {{ landing('membresia_flash_comunidad') }}
            </div>
        @elseif (session('status') === 'plan-necesario-momentos')
            <div class="mx-auto mb-10 max-w-2xl rounded-xl border border-lime/40 bg-lime/10 px-5 py-4 text-center text-sm text-ink">
                {{ __('Publicar momentos en Comunidad es un beneficio de estudios con plan activo.') }}
            </div>
        @elseif (session('status') === 'plan-necesario-equipo')
            <div class="mx-auto mb-10 max-w-2xl rounded-xl border border-lime/40 bg-lime/10 px-5 py-4 text-center text-sm text-ink">
                {{ __('La gestión de equipo y su cuidado se activan al contratar un plan para tu estudio.') }}
            </div>
        @elseif (session('status') === 'plan-necesario-expediente')
            <div class="mx-auto mb-10 max-w-2xl rounded-xl border border-lime/40 bg-lime/10 px-5 py-4 text-center text-sm text-ink">
                {{ landing('membresia_flash_expediente') }}
            </div>
        @endif

        {{-- Flash de intentos de POST no válido (bypass UI o F5). --}}
        @if (session('status') === 'plan-no-es-para-tu-rol')
            <div class="mx-auto mb-10 max-w-2xl rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-center text-sm text-red-700">
                {{ __('Este plan no está disponible para tu tipo de cuenta. Elige uno de los planes de tu sección.') }}
            </div>
        @elseif (session('status') === 'ya-tienes-suscripcion')
            <div class="mx-auto mb-10 max-w-2xl rounded-xl border border-sage/40 bg-sage/10 px-5 py-4 text-center text-sm text-ink">
                {{ __('Ya tienes una suscripción activa. Cancélala desde tu panel antes de contratar otra.') }}
            </div>
        @elseif (session('status') === 'plan-sin-precio')
            <div class="mx-auto mb-10 max-w-2xl rounded-xl border border-yellow-200 bg-yellow-50 px-5 py-4 text-center text-sm text-ink">
                {{ __('Este plan aún no tiene precio configurado. Escríbenos y te ayudamos a contratarlo.') }}
            </div>
        @elseif (session('status') === 'admin-no-suscribe')
            <div class="mx-auto mb-10 max-w-2xl rounded-xl border border-sage/40 bg-sage/10 px-5 py-4 text-center text-sm text-ink">
                {{ __('Como administradora gestionas los planes, no te suscribes a ellos.') }}
            </div>
        @endif

        {{-- Aviso claro para administradores: no se suscriben, gestionan planes desde el panel. --}}
        @auth
            @if (auth()->user()->esAdmin())
                <div class="mx-auto mb-10 max-w-2xl rounded-xl border border-sage/40 bg-sage/10 px-5 py-4 text-center text-sm text-ink">
                    {!! __('Como administradora ya no necesitas suscribirte. Gestiona precios y beneficios desde <a href=":url" class="font-semibold text-ink underline decoration-sage decoration-2 underline-offset-2">el panel de administración</a>.', ['url' => url('/admin')]) !!}
                </div>
            @elseif (! auth()->user()->estaActivo())
                {{-- Cuenta pendiente: explicar por qué los botones están bloqueados. --}}
                <div class="mx-auto mb-10 max-w-2xl rounded-xl border border-yellow-200 bg-yellow-50 px-5 py-4 text-center text-sm text-ink">
                    <p class="font-medium">{{ landing('membresia_cuenta_revision_titulo') }}</p>
                    <p class="mt-1 text-warmgray">
                        {{ __('Podrás suscribirte cuando Kinvoo apruebe tu perfil. Mientras tanto puedes explorar los planes.') }}
                    </p>
                </div>
            @endif
        @endauth

        <header class="mx-auto max-w-2xl text-center">
            <p class="text-xs font-medium uppercase tracking-[0.24em] text-sage">{{ landing('membership_eyebrow') }}</p>
            <h1 class="mt-3 font-serif text-4xl font-medium tracking-tight text-ink sm:text-5xl">{{ landing('membership_title') }}</h1>
            <p class="mt-4 text-warmgray">{{ landing('membership_body') }}</p>
        </header>

        @php
            // H6 · sólo mostrar los planes que aplican al rol del usuario.
            // Anónimos y admin ven ambos grupos (marketing / gestión).
            $__u = auth()->user();
            $grupos = [];
            $verIndividual = ! $__u || $__u->esAdmin() || $__u->esProfesional();
            $verEstudio    = ! $__u || $__u->esAdmin() || $__u->esContratante();
            if ($verIndividual) {
                $grupos[] = ['titulo' => landing('membership_individual_title'), 'planes' => $individuales];
            }
            if ($verEstudio) {
                $grupos[] = ['titulo' => landing('membership_studio_title'), 'planes' => $estudios];
            }
        @endphp

        @foreach ($grupos as $grupo)
            @continue($grupo['planes']->isEmpty())
            <section class="mt-14">
                <h2 class="font-serif text-2xl font-medium text-ink">{{ $grupo['titulo'] }}</h2>
                <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($grupo['planes'] as $plan)
                        <div class="relative flex flex-col rounded-2xl border bg-white p-6 {{ $plan->destacado ? 'border-sage ring-1 ring-sage' : 'border-line' }}">
                            @if ($plan->destacado)
                                <span class="absolute -top-3 left-6 rounded-full bg-sage px-3 py-1 text-xs font-medium text-cream">{{ __('Recomendado') }}</span>
                            @endif

                            <h3 class="font-serif text-xl font-medium text-ink">{{ $plan->nombre }}</h3>

                            <p class="mt-2 text-ink">
                                @if (! is_null($plan->precio))
                                    <span class="text-2xl font-semibold">${{ number_format($plan->precio, 0) }}</span>
                                    <span class="text-sm text-warmgray">{{ $plan->moneda }} / {{ __($plan->periodoLabel()) }}</span>
                                @else
                                    <span class="text-lg font-medium text-warmgray">{{ __('A consultar') }}</span>
                                @endif
                            </p>

                            @if ($plan->descripcion)
                                <p class="mt-3 text-sm text-warmgray">{{ $plan->descripcion }}</p>
                            @endif

                            @if (! empty($plan->beneficios))
                                <ul class="mt-4 space-y-2 text-sm text-ink/90">
                                    @foreach ($plan->beneficios as $beneficio)
                                        <li class="flex items-start gap-2">
                                            <span class="mt-0.5 text-sage" aria-hidden="true">✓</span>
                                            <span>{{ $beneficio }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            @if ($plan->cobertura)
                                <p class="mt-4 border-t border-line pt-3 text-xs text-warmgray">
                                    <span class="font-medium text-ink">{{ __('Cobertura:') }}</span> {{ $plan->cobertura }}
                                </p>
                            @endif

                            @php
                                $user = auth()->user();
                                $planIndividual = $plan->audiencia === 'individual';
                                $planEstudio = $plan->audiencia === 'estudio';
                                $tienePrecio = filled($plan->precio) && (float) $plan->precio > 0;
                                $cuentaLista = $user && ($user->esAdmin() || $user->estaActivo());
                                $puedeSuscribirse = $user && $tienePrecio && $cuentaLista
                                    && (($planIndividual && $user->esProfesional())
                                     || ($planEstudio && $user->esContratante()));
                            @endphp

                            @if ($puedeSuscribirse)
                                <form method="POST" action="{{ route('billing.start', $plan) }}" class="mt-6">
                                    @csrf
                                    <button type="submit"
                                            class="w-full inline-flex items-center justify-center rounded-full px-5 py-2.5 text-sm font-semibold transition {{ $plan->destacado ? 'bg-sage text-cream hover:bg-ink' : 'border border-line text-ink hover:border-sage hover:text-sage' }}">
                                        {{ landing('membresia_cta_suscribirme') }}
                                    </button>
                                </form>
                            @elseif (! $user)
                                {{-- Sin sesión: invitar a registrarse. --}}
                                <a href="{{ route('register') }}"
                                   class="mt-6 inline-flex items-center justify-center rounded-full px-5 py-2.5 text-sm font-semibold transition {{ $plan->destacado ? 'bg-sage text-cream hover:bg-ink' : 'border border-line text-ink hover:border-sage hover:text-sage' }}">
                                    {{ __('Únete') }}
                                </a>
                            @else
                                {{-- Con sesión pero no puede suscribirse: admin, cuenta pendiente, plan sin precio o rol incompatible. --}}
                                @php
                                    if ($user->esAdmin()) {
                                        $mensajeDeshab = __('Los admins no se suscriben');
                                    } elseif (! $cuentaLista) {
                                        $mensajeDeshab = __('Tu cuenta está en revisión');
                                    } elseif (! $tienePrecio) {
                                        $mensajeDeshab = __('Plan sin precio — escríbenos');
                                    } elseif ($planIndividual) {
                                        $mensajeDeshab = __('Solo para perfiles de talento');
                                    } else {
                                        $mensajeDeshab = __('Solo para estudios y marcas');
                                    }
                                @endphp
                                <button type="button" disabled aria-disabled="true"
                                        class="mt-6 w-full inline-flex items-center justify-center rounded-full px-5 py-2.5 text-sm font-semibold border border-line text-warmgray bg-cream cursor-not-allowed opacity-70">
                                    <span aria-hidden="true" class="mr-1.5">🔒</span>
                                    {{ $mensajeDeshab }}
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        @if ($individuales->isEmpty() && $estudios->isEmpty())
            <p class="mt-14 text-center text-warmgray">{{ landing('membresia_empty_state') }}</p>
        @endif

        @if (landing('membership_note'))
            <p class="mt-12 text-center text-xs text-warmgray">{{ landing('membership_note') }}</p>
        @endif
    </div>
</x-public-layout>
