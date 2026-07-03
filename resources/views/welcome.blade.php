<x-public-layout title="Kinvoo · La red profesional para la industria fitness"
                 description="Kinvoo conecta a coaches, instructores y staff del fitness con estudios, gimnasios y marcas que buscan talento.">
    <x-slot name="head">
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Kinvoo',
            'description' => 'La red profesional para la industria fitness.',
            'url' => url('/'),
            'logo' => asset('favicon.svg'),
            'email' => 'hola@gokinvoo.com',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
        </script>
    </x-slot>

    {{-- Hero --}}
    <section class="px-6">
        <div class="mx-auto max-w-3xl py-20 text-center sm:py-28">
            <p class="mb-4 text-xs font-500 uppercase tracking-[0.25em] text-sage sm:text-sm">
                Where talent meets fitness
            </p>
            <h1 class="font-serif text-4xl font-400 leading-tight text-ink sm:text-6xl">
                La red profesional para la
                <span class="italic text-sage">industria fitness</span>
            </h1>
            <p class="mx-auto mt-6 max-w-xl text-base text-warmgray sm:text-lg">
                Conecta a profesionales del fitness —coaches, instructores y staff— con estudios,
                gimnasios y marcas que buscan talento.
            </p>
            <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ route('register') }}"
                   class="w-full rounded-full bg-sage px-7 py-3 text-sm font-600 text-cream shadow-sm transition hover:bg-ink sm:w-auto">
                    Soy profesional
                </a>
                <a href="{{ route('talento.index') }}"
                   class="w-full rounded-full bg-lime px-7 py-3 text-sm font-600 text-ink shadow-sm transition hover:brightness-95 sm:w-auto">
                    Busco talento
                </a>
            </div>
        </div>
    </section>

    {{-- Cómo funciona --}}
    <section class="px-6">
        <div class="mx-auto max-w-5xl border-t border-line/70 py-16 sm:py-20">
            <h2 class="text-center font-serif text-3xl font-500 text-ink">Cómo funciona</h2>
            <div class="mt-10 grid gap-8 sm:grid-cols-3">
                @foreach ([
                    ['🧩', 'Crea tu perfil', 'Publica tu experiencia, disciplinas y certificaciones en minutos.'],
                    ['🔍', 'Aparece en el buscador', 'Los contratantes te encuentran por disciplina, ubicación y modalidad.'],
                    ['✉️', 'Recibe oportunidades', 'Estudios y marcas te contactan directo. Sin intermediarios.'],
                ] as [$icon, $titulo, $texto])
                    <div class="text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-beige text-2xl">{{ $icon }}</div>
                        <h3 class="mt-4 font-serif text-xl font-500 text-ink">{{ $titulo }}</h3>
                        <p class="mt-2 text-sm text-warmgray">{{ $texto }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Para quién --}}
    <section class="px-6">
        <div class="mx-auto max-w-5xl border-t border-line/70 py-16 sm:py-20">
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="rounded-2xl border border-line bg-white p-8">
                    <div class="text-3xl">🏋️</div>
                    <h3 class="mt-3 font-serif text-2xl font-500 text-ink">Para profesionales</h3>
                    <p class="mt-2 text-warmgray">Coaches, instructores y staff de operaciones. Muestra tu talento y deja que las oportunidades lleguen a ti.</p>
                    <a href="{{ route('register') }}" class="mt-5 inline-block rounded-full bg-sage px-6 py-2.5 text-sm font-600 text-cream transition hover:bg-ink">Crear mi perfil</a>
                </div>
                <div class="rounded-2xl border border-line bg-white p-8">
                    <div class="text-3xl">🏢</div>
                    <h3 class="mt-3 font-serif text-2xl font-500 text-ink">Para estudios y marcas</h3>
                    <p class="mt-2 text-warmgray">Encuentra al profesional ideal filtrando por disciplina, ubicación y modalidad. Contáctalo directo.</p>
                    <a href="{{ route('talento.index') }}" class="mt-5 inline-block rounded-full border border-line px-6 py-2.5 text-sm font-600 text-warmgray transition hover:border-sage hover:text-sage">Buscar talento</a>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA final --}}
    <section class="px-6">
        <div class="mx-auto max-w-3xl py-16 text-center sm:py-20">
            <h2 class="font-serif text-3xl font-500 text-ink sm:text-4xl">Únete a la comunidad Kinvoo</h2>
            <p class="mx-auto mt-3 max-w-lg text-warmgray">Conexión real, pertenencia real. La red que impulsa a la industria fitness.</p>
            <a href="{{ route('register') }}"
               class="mt-8 inline-block rounded-full bg-sage px-8 py-3 text-sm font-600 text-cream shadow-sm transition hover:bg-ink">
                Crear mi cuenta gratis
            </a>
        </div>
    </section>
</x-public-layout>
