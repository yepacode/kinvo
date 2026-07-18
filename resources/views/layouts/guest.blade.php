<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Kinvoo') }}</title>

        @include('partials.head-assets')
    </head>
    <body class="font-sans text-ink antialiased">
        <div class="fixed right-4 top-4 z-20">
            <x-locale-switcher />
        </div>
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
            <div>
                <a href="/" class="font-serif text-3xl font-medium tracking-tight text-ink">Kinvoo</a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white border border-line shadow-sm overflow-hidden sm:rounded-2xl">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
