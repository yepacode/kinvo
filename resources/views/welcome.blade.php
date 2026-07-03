<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Kinvoo · Bolsa de Talento</title>

        @include('partials.head-assets')
    </head>
    <body class="min-h-screen bg-cream font-sans text-ink antialiased">
        <div class="flex min-h-screen flex-col">
            {{-- Header --}}
            <header class="flex items-center justify-between px-6 py-5 sm:px-10">
                <span class="font-serif text-2xl font-500 tracking-tight text-ink">Kinvoo</span>
                <a href="/admin"
                   class="rounded-full border border-line px-4 py-2 text-sm font-500 text-warmgray transition hover:border-sage hover:text-sage">
                    Panel del owner
                </a>
            </header>

            {{-- Hero --}}
            <main class="flex flex-1 items-center px-6 sm:px-10">
                <div class="mx-auto max-w-3xl py-16 text-center">
                    <p class="mb-4 text-sm font-500 uppercase tracking-[0.2em] text-sage">
                        Where talent meets fitness
                    </p>
                    <h1 class="font-serif text-5xl font-400 leading-tight text-ink sm:text-6xl">
                        La red profesional para la<br class="hidden sm:block">
                        <span class="italic text-sage">industria fitness</span>
                    </h1>
                    <p class="mx-auto mt-6 max-w-xl text-lg text-warmgray">
                        Conecta a profesionales del fitness —coaches, instructores y staff—
                        con estudios, gimnasios y marcas que buscan talento.
                    </p>

                    <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a href="{{ route('register') }}"
                           class="rounded-full bg-sage px-7 py-3 text-sm font-600 text-cream shadow-sm transition hover:bg-ink">
                            Soy profesional
                        </a>
                        <a href="{{ route('talento.index') }}"
                           class="rounded-full bg-lime px-7 py-3 text-sm font-600 text-ink shadow-sm transition hover:brightness-95">
                            Busco talento
                        </a>
                    </div>

                    {{-- Muestra de paleta (verificación de tema) --}}
                    <div class="mt-16 flex items-center justify-center gap-2">
                        @foreach (['bg-sage','bg-sage-light','bg-lime','bg-ink','bg-beige','bg-warmgray','bg-line'] as $c)
                            <span class="h-8 w-8 rounded-full border border-line {{ $c }}"></span>
                        @endforeach
                    </div>
                    <p class="mt-3 text-xs uppercase tracking-widest text-warmgray">Paleta de marca Kinvoo</p>
                </div>
            </main>

            {{-- Footer --}}
            <footer class="border-t border-line px-6 py-5 text-center text-sm text-warmgray sm:px-10">
                Kinvoo · Bolsa de Talento — plataforma en construcción
            </footer>
        </div>
    </body>
</html>
