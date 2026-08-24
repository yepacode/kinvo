<nav x-data="{ open: false }" class="border-b border-line bg-white/90 backdrop-blur">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ url('/') }}" class="font-serif text-2xl font-medium tracking-tight text-ink">Kinvoo</a>
                </div>

                <!-- Navigation Links -->
                @php
                    // Fase 1 gate: si la cuenta NO está aprobada aún (Pendiente o
                    // PerfilPendiente), solo mostramos los links que su rol PUEDE
                    // usar sin la aprobación — el resto lo sacaría el middleware
                    // a "Perfil en revisión" y es una UX pobre mostrarlos.
                    $u = auth()->user();
                    $cuentaActiva = $u->estaActivo() || $u->esAdmin();
                @endphp
                <div class="hidden space-x-8 lg:-my-px lg:ms-10 lg:flex">
                    {{-- H2 · petición cliente: quitar "Inicio" del nav (redundante,
                         el logo Kinvoo a la izquierda ya lleva al home).
                         Pendientes: como antes el logo va a /, y el gate cuenta.activa
                         redirige a /cuenta/pendiente automáticamente. --}}
                    @if ($u->esProfesional())
                        <x-nav-link :href="route('professional.profile.edit')" :active="request()->routeIs('professional.profile.*')">
                            {{ landing('nav_coach_mi_perfil') }}
                        </x-nav-link>
                        @if ($cuentaActiva)
                            <x-nav-link :href="route('professional.contactos')" :active="request()->routeIs('professional.contactos')">
                                {{ landing('nav_coach_contactos') }}
                            </x-nav-link>
                            <x-nav-link :href="route('ofertas.index')" :active="request()->routeIs('ofertas.index','ofertas.show')">
                                {{ landing('nav_coach_oportunidades') }}
                            </x-nav-link>
                            {{-- LOW-5 · "Mis postulaciones" también en desktop
                                 (antes solo aparecía en el menú móvil, obligando
                                 al coach a entrar por el sub-menú de perfil). --}}
                            <x-nav-link :href="route('ofertas.mis-postulaciones')" :active="request()->routeIs('ofertas.mis-postulaciones')">
                                {{ __('Mis postulaciones') }}
                            </x-nav-link>
                            <x-nav-link :href="route('contenido.index')" :active="request()->routeIs('contenido.*')">
                                {{ landing('nav_coach_desarrollo') }}
                            </x-nav-link>
                            {{-- Matriz: enlaces coach paid — muestro si tiene beneficio. --}}
                            @if ($u->hasBenefit('comunidad_ver'))
                                <x-nav-link :href="route('wall.comunidad')" :active="request()->routeIs('wall.comunidad')">
                                    {{ __('Comunidad') }}
                                </x-nav-link>
                            @endif
                            @if ($u->hasBenefit('mis_beneficios'))
                                <x-nav-link :href="route('beneficios.index')" :active="request()->routeIs('beneficios.*','respaldo.*','pulso.coach')">
                                    {{ __('Mis beneficios') }}
                                </x-nav-link>
                            @endif
                            @if ($u->hasBenefit('expediente_propio'))
                                <x-nav-link :href="route('expediente.index')" :active="request()->routeIs('expediente.*')">
                                    {{ __('Expediente') }}
                                </x-nav-link>
                            @endif
                            @if ($u->serviciosIncluidos()->isNotEmpty())
                                <x-nav-link :href="route('servicios.index')" :active="request()->routeIs('servicios.*')">
                                    {{ __('Mis servicios') }}
                                </x-nav-link>
                            @endif
                        @endif
                    @elseif ($u->esContratante())
                        <x-nav-link :href="route('company.profile.edit')" :active="request()->routeIs('company.profile.*')">
                            {{ __('Mi empresa') }}
                        </x-nav-link>
                        @if ($cuentaActiva)
                            <x-nav-link :href="route('talento.index')" :active="request()->routeIs('talento.index')">
                                {{ __('Buscar talento') }}
                            </x-nav-link>
                            <x-nav-link :href="route('saves.index')" :active="request()->routeIs('saves.*')">
                                {{ __('Guardados') }}
                            </x-nav-link>
                            <x-nav-link :href="route('professional.contactos')" :active="request()->routeIs('professional.contactos')">
                                {{ __('Contactos') }}
                            </x-nav-link>
                            <x-nav-link :href="route('ofertas.mis-ofertas')" :active="request()->routeIs('ofertas.*')">
                                {{ __('Oportunidades') }}
                            </x-nav-link>
                            <x-nav-link :href="route('contenido.index')" :active="request()->routeIs('contenido.index','contenido.show')">
                                {{ __('Desarrollo') }}
                            </x-nav-link>
                            {{-- Petición cliente (ago-2026): "Mi desarrollo" del estudio
                                 se reemplaza por "Mis momentos" (wall). El catálogo formal
                                 de desarrollo lo administra Kinvoo desde Filament admin. --}}
                            @if ($u->hasBenefit('comunidad_publicar'))
                                <x-nav-link :href="route('wall.mis-momentos')" :active="request()->routeIs('wall.mis-momentos')">
                                    {{ __('Mis momentos') }}
                                </x-nav-link>
                            @endif
                            @if ($u->hasBenefit('comunidad_ver'))
                                <x-nav-link :href="route('wall.comunidad')" :active="request()->routeIs('wall.comunidad')">
                                    {{ __('Comunidad') }}
                                </x-nav-link>
                            @endif
                            @if ($u->hasBenefit('gestion_equipo'))
                                <x-nav-link :href="route('equipo.index')" :active="request()->routeIs('equipo.*','pulso.estudio')">
                                    {{ __('Mi equipo') }}
                                </x-nav-link>
                            @endif
                            @if ($u->serviciosIncluidos()->isNotEmpty())
                                <x-nav-link :href="route('servicios.index')" :active="request()->routeIs('servicios.*')">
                                    {{ __('Mis servicios') }}
                                </x-nav-link>
                            @endif
                        @endif
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden lg:flex lg:items-center lg:ms-6 lg:gap-2">
                <x-locale-switcher class="mr-1" />
                @if ($cuentaActiva)
                    @include('partials.notification-bell')
                @endif

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-warmgray bg-white hover:text-sage focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Mi cuenta') }}
                        </x-dropdown-link>
                        @if (! auth()->user()->esAdmin())
                            <x-dropdown-link :href="route('membresias.index')">
                                {{ __('Mi membresía') }}
                            </x-dropdown-link>
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Cerrar sesión') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Móvil/tablet: campana + hamburguesa (hasta lg, colapsamos el nav completo). -->
            <div class="flex items-center gap-1 lg:hidden">
                @if ($cuentaActiva)
                    @php $unreadNav = auth()->user()->unreadNotifications()->count(); @endphp
                    <a href="{{ route('notifications.index') }}"
                       class="relative flex h-10 w-10 items-center justify-center rounded-md text-warmgray hover:bg-beige"
                       aria-label="{{ $unreadNav > 0 ? __('Notifications, :n unread', ['n' => $unreadNav]) : __('Notifications') }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        </svg>
                        @if ($unreadNav > 0)
                            <span class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-lime px-1 text-[10px] font-semibold text-ink">{{ $unreadNav > 9 ? '9+' : $unreadNav }}</span>
                        @endif
                    </a>
                @endif
                <button @click="open = ! open" type="button"
                        aria-controls="mobile-menu" :aria-expanded="open ? 'true' : 'false'"
                        :aria-label="open ? @js(__('Cerrar menú')) : @js(__('Abrir menú'))"
                        class="inline-flex items-center justify-center p-2 rounded-md text-warmgray hover:bg-beige focus:outline-none focus-visible:ring-2 focus-visible:ring-sage transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden lg:hidden" id="mobile-menu">
        <div class="pt-2 pb-3 space-y-1">
            {{-- H2 · quitar "Inicio" (redundante — logo Kinvoo va al home). --}}
            @if (auth()->user()->esProfesional())
                <x-responsive-nav-link :href="route('professional.profile.edit')" :active="request()->routeIs('professional.profile.*')">
                    {{ __('Mi perfil') }}
                </x-responsive-nav-link>
                @if ($cuentaActiva)
                    <x-responsive-nav-link :href="route('professional.contactos')" :active="request()->routeIs('professional.contactos')">
                        {{ __('Contactos') }}
                    </x-responsive-nav-link>
                    {{-- Fase 2 · accesos móviles del coach --}}
                    <x-responsive-nav-link :href="route('ofertas.index')" :active="request()->routeIs('ofertas.index','ofertas.show')">
                        {{ __('Oportunidades') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('ofertas.mis-postulaciones')" :active="request()->routeIs('ofertas.mis-postulaciones')">
                        {{ __('Mis postulaciones') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('contenido.index')" :active="request()->routeIs('contenido.*')">
                        {{ __('Desarrollo y capacitaciones') }}
                    </x-responsive-nav-link>
                    {{-- HIGH-7 · Alinear el menú móvil con el desktop:
                         gates por hasBenefit para NO mostrar items paid al coach free.
                         Antes se mostraba "Mi expediente" al free y al hacer click
                         chocaba con un redirect a /membresias → mala UX. --}}
                    @if ($u->hasBenefit('comunidad_ver'))
                        <x-responsive-nav-link :href="route('wall.comunidad')" :active="request()->routeIs('wall.comunidad')">
                            {{ __('Comunidad') }}
                        </x-responsive-nav-link>
                    @endif
                    @if ($u->hasBenefit('mis_beneficios'))
                        <x-responsive-nav-link :href="route('beneficios.index')" :active="request()->routeIs('beneficios.*','respaldo.*','pulso.coach')">
                            {{ __('Mis beneficios') }}
                        </x-responsive-nav-link>
                    @endif
                    @if ($u->hasBenefit('expediente_propio'))
                        <x-responsive-nav-link :href="route('expediente.index')" :active="request()->routeIs('expediente.*')">
                            {{ __('Mi expediente de cuidado') }}
                        </x-responsive-nav-link>
                    @endif
                    @if ($u->serviciosIncluidos()->isNotEmpty())
                        <x-responsive-nav-link :href="route('servicios.index')" :active="request()->routeIs('servicios.*')">
                            {{ __('Mis servicios') }}
                        </x-responsive-nav-link>
                    @endif
                @endif
            @elseif ($u->esContratante())
                <x-responsive-nav-link :href="route('company.profile.edit')" :active="request()->routeIs('company.profile.*')">
                    {{ __('Mi empresa') }}
                </x-responsive-nav-link>
                @if ($cuentaActiva)
                    <x-responsive-nav-link :href="route('talento.index')" :active="request()->routeIs('talento.index')">
                        {{ __('Buscar talento') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('saves.index')" :active="request()->routeIs('saves.*')">
                        {{ __('Guardados') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('professional.contactos')" :active="request()->routeIs('professional.contactos')">
                        {{ __('Contactos') }}
                    </x-responsive-nav-link>
                    {{-- Fase 2 · accesos móviles del estudio --}}
                    <x-responsive-nav-link :href="route('ofertas.mis-ofertas')" :active="request()->routeIs('ofertas.mis-ofertas')">
                        {{ __('Mis oportunidades') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('contenido.index')" :active="request()->routeIs('contenido.index','contenido.show')">
                        {{ __('Desarrollo y capacitaciones') }}
                    </x-responsive-nav-link>
                    {{-- HIGH-7 · Gates hasBenefit iguales al desktop para
                         no exponer al estudio free items a los que va a chocar. --}}
                    @if ($u->hasBenefit('comunidad_publicar'))
                        <x-responsive-nav-link :href="route('wall.mis-momentos')" :active="request()->routeIs('wall.mis-momentos')">
                            {{ __('Mis momentos') }}
                        </x-responsive-nav-link>
                    @endif
                    @if ($u->hasBenefit('comunidad_ver'))
                        <x-responsive-nav-link :href="route('wall.comunidad')" :active="request()->routeIs('wall.comunidad')">
                            {{ __('Comunidad') }}
                        </x-responsive-nav-link>
                    @endif
                    @if ($u->tieneMembresiaActiva())
                        <x-responsive-nav-link :href="route('contenido.mis-contenidos')" :active="request()->routeIs('contenido.mis-contenidos','contenido.crear','contenido.editar')">
                            {{ __('Mi desarrollo') }}
                        </x-responsive-nav-link>
                    @endif
                    @if ($u->hasBenefit('gestion_equipo'))
                        <x-responsive-nav-link :href="route('equipo.index')" :active="request()->routeIs('equipo.*','pulso.estudio')">
                            {{ __('Mi equipo') }}
                        </x-responsive-nav-link>
                    @endif
                    @if ($u->serviciosIncluidos()->isNotEmpty())
                        <x-responsive-nav-link :href="route('servicios.index')" :active="request()->routeIs('servicios.*')">
                            {{ __('Mis servicios') }}
                        </x-responsive-nav-link>
                    @endif
                @endif
            @endif
        </div>

        <div class="pt-4 pb-1 border-t border-line">
            <div class="px-4">
                <div class="font-medium text-base text-ink">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-warmgray">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Mi cuenta') }}
                </x-responsive-nav-link>
                @if (! auth()->user()->esAdmin())
                    <x-responsive-nav-link :href="route('membresias.index')" :active="request()->routeIs('membresias.*')">
                        {{ __('Mi membresía') }}
                    </x-responsive-nav-link>
                @endif

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Cerrar sesión') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
