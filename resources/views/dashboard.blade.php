<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-500 text-ink">Hola, {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
        @if (auth()->user()->esProfesional())
            @php $profile = auth()->user()->professionalProfile; @endphp
            <div class="rounded-2xl border border-line bg-white p-8">
                <p class="text-sm font-500 uppercase tracking-widest text-sage">Profesional</p>
                <h3 class="mt-2 font-serif text-2xl font-500 text-ink">Tu perfil en Kinvoo</h3>
                <p class="mt-2 text-warmgray">
                    @if ($profile && $profile->is_published)
                        Tu perfil está <strong class="text-sage">publicado</strong> y visible para contratantes.
                    @else
                        Tu perfil está <strong>oculto</strong>. Complétalo y publícalo para que te encuentren.
                    @endif
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('professional.profile.edit') }}"
                       class="rounded-full bg-sage px-6 py-2.5 text-sm font-600 text-cream transition hover:bg-ink">
                        Editar mi perfil
                    </a>
                    @if ($profile && $profile->is_published)
                        <a href="{{ route('talento.show', $profile->slug) }}" target="_blank"
                           class="rounded-full border border-line px-6 py-2.5 text-sm font-500 text-warmgray transition hover:border-sage hover:text-sage">
                            Ver perfil público ↗
                        </a>
                    @endif
                </div>
            </div>
        @elseif (auth()->user()->esContratante())
            <div class="rounded-2xl border border-line bg-white p-8">
                <p class="text-sm font-500 uppercase tracking-widest text-sage">Contratante</p>
                <h3 class="mt-2 font-serif text-2xl font-500 text-ink">Encuentra talento fitness</h3>
                <p class="mt-2 text-warmgray">Completa los datos de tu empresa. El buscador de talento estará disponible muy pronto.</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('company.profile.edit') }}"
                       class="rounded-full bg-sage px-6 py-2.5 text-sm font-600 text-cream transition hover:bg-ink">
                        Editar mi empresa
                    </a>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
