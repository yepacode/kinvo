{{-- Fuentes de marca + CSS. Si hay build de Vite se usa; si no (App Control bloquea
     los binarios nativos localmente), se cae a Tailwind v4 por CDN + Alpine para DEV. --}}

<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="preconnect" href="https://api.fontshare.com" crossorigin>
<link href="https://api.fontshare.com/v2/css?f[]=satoshi@500,700,900&display=swap" rel="stylesheet">

{{-- Fondo del sitio (editable desde el panel: Configuración del sitio → Fondo).
     Se muestra un color base + opcionalmente una imagen encima. Solo páginas públicas. --}}
@php
    // Sanitizamos el color: solo aceptamos hex #RGB / #RRGGBB / #RRGGBBAA para
    // impedir que un admin (por error o malicia) inyecte CSS extra en el body.
    $bgColorRaw = landing('background_color', '#F7F4EE');
    $bgColor = preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6}|[0-9A-Fa-f]{8})$/', (string) $bgColorRaw) ? $bgColorRaw : '#F7F4EE';
    $bgImageSetting = landing('background_image');
    $bgImage = $bgImageSetting ? \Illuminate\Support\Facades\Storage::disk('public')->url($bgImageSetting) : null;
@endphp
<style>
    [x-cloak] { display: none !important; }
    body {
        background-color: {{ $bgColor }};
        @if ($bgImage)
        background-image: url('{{ $bgImage }}');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: fixed;
        @endif
    }
</style>

{{-- Keepalive CSRF (feedback Karla 16-sep, error 419 al enviar formularios).
     Renueva el token cada 15 min si el user sigue activo, y reintenta una
     vez ante un 419 pescando el token fresco antes de rendirse. --}}
@auth
<script>
(function () {
    const RENEW_MS = 15 * 60 * 1000; // 15 min
    let ultimoUso = Date.now();
    ['click','keydown','scroll','touchstart'].forEach(ev => document.addEventListener(ev, () => { ultimoUso = Date.now(); }, {passive: true}));

    async function refrescarCsrf() {
        try {
            const r = await fetch('/csrf-token', { credentials: 'same-origin', cache: 'no-store' });
            if (! r.ok) return;
            const { token } = await r.json();
            document.querySelectorAll('meta[name="csrf-token"]').forEach(m => m.setAttribute('content', token));
            document.querySelectorAll('input[name="_token"]').forEach(i => { i.value = token; });
        } catch (_) { /* silencioso: si falla, el token viejo aún puede servir */ }
    }

    setInterval(() => {
        // Solo renovar si el user tuvo actividad en los últimos 20 min.
        if (Date.now() - ultimoUso < 20 * 60 * 1000) refrescarCsrf();
    }, RENEW_MS);

    // Renovar también justo antes de que el user envíe cualquier form clásico
    // POST/PUT/DELETE: garantiza que _token está fresco justo en el submit
    // (además del keepalive de 15 min). No interceptamos el submit — sólo
    // sobrescribimos el input _token con el token vigente. Si el user acaba
    // de estar activo, el meta ya está actualizado por el keepalive; si no,
    // este blindaje evita el "Page Expired" ocasional al reactivarse tras
    // horas de inactividad.
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (! form || form.tagName !== 'FORM') return;
        const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (! metaToken) return;
        form.querySelectorAll('input[name="_token"]').forEach(i => { i.value = metaToken; });
    }, true);
})();
</script>
@endauth

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
