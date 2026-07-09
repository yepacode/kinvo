@props(['title' => 'Kinvoo · Bolsa de Talento', 'description' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title }}</title>
        @if ($description)
            <meta name="description" content="{{ $description }}">
        @endif
        <link rel="canonical" href="{{ url()->current() }}">
        <meta property="og:title" content="{{ $title }}">
        @if ($description)
            <meta property="og:description" content="{{ $description }}">
        @endif
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">

        {{ $head ?? '' }}

        @include('partials.head-assets')
    </head>
    <body class="min-h-screen bg-cream font-sans text-ink antialiased">
        <header class="border-b border-line/70">
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-2 px-4 py-4 sm:px-6">
                <a href="/" class="font-serif text-xl font-medium tracking-tight text-ink sm:text-2xl">Kinvoo</a>
                <nav class="flex items-center gap-3 text-sm sm:gap-4">
                    <a href="{{ route('talento.index') }}" class="text-warmgray hover:text-sage">
                        <span class="sm:hidden">Talento</span><span class="hidden sm:inline">Buscar talento</span>
                    </a>
                    <a href="{{ route('membresias.index') }}" class="text-warmgray hover:text-sage">Membresías</a>
                    @auth
                        <a href="{{ auth()->user()->homeRoute() }}" class="text-warmgray hover:text-sage">Mi cuenta</a>
                    @else
                        <a href="{{ route('login') }}" class="text-warmgray hover:text-sage">Entrar</a>
                        <a href="{{ route('register') }}"
                           class="rounded-full bg-sage px-4 py-2 font-medium text-cream hover:bg-ink">Únete</a>
                    @endauth
                </nav>
            </div>
        </header>

        <main>
            {{ $slot }}
        </main>

        <footer class="mt-16 border-t border-line/70">
            <div class="mx-auto flex max-w-5xl flex-col items-center gap-2 px-6 py-6 text-center text-sm text-warmgray">
                <nav class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1">
                    <a href="{{ route('membresias.index') }}" class="hover:text-sage">Membresías</a>
                    <a href="{{ route('legal.privacidad') }}" class="hover:text-sage">Aviso de Privacidad</a>
                    <a href="{{ route('legal.terminos') }}" class="hover:text-sage">Términos y Condiciones</a>
                </nav>
                <p>{{ landing('footer_copy') }}</p>
            </div>
        </footer>
    </body>
</html>
