{{-- Keepalive del token CSRF para el panel Filament (feedback Karla 16-sep).
     Vive fuera de head-assets porque el panel usa su propio layout. Misma
     lógica: renueva el meta[name=csrf-token] y los inputs _token cada 15 min
     si el user tuvo actividad, y refresca al submit. --}}
<script>
(function () {
    const RENEW_MS = 15 * 60 * 1000;
    let ultimoUso = Date.now();
    ['click','keydown','scroll','touchstart'].forEach(ev =>
        document.addEventListener(ev, () => { ultimoUso = Date.now(); }, {passive: true})
    );

    async function refrescarCsrf() {
        try {
            const r = await fetch('/csrf-token', { credentials: 'same-origin', cache: 'no-store' });
            if (! r.ok) return;
            const { token } = await r.json();
            document.querySelectorAll('meta[name="csrf-token"]').forEach(m => m.setAttribute('content', token));
            document.querySelectorAll('input[name="_token"]').forEach(i => { i.value = token; });
        } catch (_) {}
    }

    setInterval(() => {
        if (Date.now() - ultimoUso < 20 * 60 * 1000) refrescarCsrf();
    }, RENEW_MS);

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (! form || form.tagName !== 'FORM') return;
        const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (! metaToken) return;
        form.querySelectorAll('input[name="_token"]').forEach(i => { i.value = metaToken; });
    }, true);
})();
</script>
