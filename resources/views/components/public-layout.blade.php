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
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
                <a href="/" class="font-serif text-2xl font-500 tracking-tight text-ink">Kinvoo</a>
                <nav class="flex items-center gap-4 text-sm">
                    <a href="{{ route('talento.index') }}" class="text-warmgray hover:text-sage">Buscar talento</a>
                    @auth
                        <a href="{{ auth()->user()->homeRoute() }}" class="text-warmgray hover:text-sage">Mi cuenta</a>
                    @else
                        <a href="{{ route('login') }}" class="text-warmgray hover:text-sage">Entrar</a>
                        <a href="{{ route('register') }}"
                           class="rounded-full bg-sage px-4 py-2 font-500 text-cream hover:bg-ink">Únete</a>
                    @endauth
                </nav>
            </div>
        </header>

        <main>
            {{ $slot }}
        </main>

        <footer class="mt-16 border-t border-line/70">
            <div class="mx-auto max-w-5xl px-6 py-6 text-center text-sm text-warmgray">
                Kinvoo · La red profesional para la industria fitness
            </div>
        </footer>
    </body>
</html>
