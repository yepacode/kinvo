{{-- Fuentes de marca + CSS. Si hay build de Vite se usa; si no (App Control bloquea
     los binarios nativos localmente), se cae a Tailwind v4 por CDN + Alpine para DEV. --}}

<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="preconnect" href="https://api.fontshare.com" crossorigin>
<link href="https://api.fontshare.com/v2/css?f[]=satoshi@500,700,900&display=swap" rel="stylesheet">

{{-- Fondo de marca: manchas orgánicas sutiles, fijas (efecto "medio perceptivo"
     al hacer scroll). Se ve en las páginas públicas, no en el panel Filament. --}}
<style>
    [x-cloak] { display: none !important; }
    body {
        background-color: #F7F4EE;
        background-image: url('{{ asset('img/kinvoo-pattern.svg') }}');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: fixed;
    }
</style>

@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@else
    {{-- Fallback DEV: Tailwind v4 en el navegador + Alpine (Breeze usa Alpine). --}}
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
    <style type="text/tailwindcss">
        @theme {
            --font-serif: 'Satoshi', 'Inter', ui-sans-serif, system-ui, sans-serif;
            --font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif;
            --color-sage: #5C7A5F;
            --color-sage-light: #A8BBA8;
            --color-lime: #C8C040;
            --color-ink: #1C1C1A;
            --color-cream: #F7F4EE;
            --color-beige: #EFECE4;
            --color-warmgray: #6E6E5F;
            --color-line: #E0DDD5;
        }
    </style>
@endif
