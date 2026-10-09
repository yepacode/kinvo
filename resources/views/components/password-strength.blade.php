@props(['for' => 'password'])

{{--
    Medidor visual de fuerza + checklist en vivo para inputs de contraseña.
    Uso:
        <x-text-input id="password" name="password" type="password" />
        <x-password-strength for="password" />

    Se ata al input por `id="{{ $for }}"`. Alpine.js puro, sin backend.
    Los mismos criterios que Password::defaults()->min(8)->mixedCase()->numbers()->symbols():
--}}

<div x-data="passwordStrength_{{ $for }}()"
     x-init="init()"
     class="mt-2 space-y-2 text-xs">

    {{-- Barra de fuerza --}}
    <div>
        <div class="h-1.5 w-full rounded-full bg-line overflow-hidden" role="progressbar"
             :aria-valuenow="score" aria-valuemin="0" aria-valuemax="5"
             :aria-label="'{{ __('Fuerza de la contraseña') }}: ' + label">
            {{-- width y color por :style con hex directos: Tailwind (CDN o build)
                 no compila clases dinámicas escondidas en JS, y las utilities de
                 color usadas serían nuevas al scanner. Con estilos inline se ve
                 siempre igual sin depender del pipeline de CSS. --}}
            <div class="h-full"
                 :style="`width: ${score * 20}%; background-color: ${barHex};`"></div>
        </div>
        <p class="mt-1 font-medium" :style="`color: ${labelHex}`" x-text="label" x-show="typed"></p>
    </div>

    {{-- Checklist --}}
    <ul class="space-y-1">
        <template x-for="(rule, key) in rules" :key="key">
            <li class="flex items-center gap-2"
                :style="checks[key] ? 'color: #5C7A5F' : 'color: #6E6A63'">
                <span aria-hidden="true"
                      class="inline-flex h-4 w-4 items-center justify-center rounded-full text-[10px] font-bold"
                      :style="checks[key] ? 'background-color: #5C7A5F; color: #F7F4EE;' : 'background-color: #FFFFFF; color: #6E6A63; border: 1px solid #E0DDD5;'">
                    <span x-show="checks[key]">✓</span>
                    <span x-show="!checks[key]">○</span>
                </span>
                <span x-text="rule"></span>
            </li>
        </template>
    </ul>

    {{-- Aviso de filtración en vivo. El servidor rechaza estas contraseñas al
         guardar; mostrarlo aquí evita que el usuario descubra el problema
         recién al pulsar el botón. --}}
    <div x-show="filtrada" x-cloak
         class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-900">
        <span aria-hidden="true">⚠️</span>
        {{ __('Esta contraseña aparece en filtraciones públicas de otros sitios. No es culpa tuya, pero elige otra — o genera una con el botón de abajo.') }}
    </div>

    {{-- Feedback Karla 08-10-2026: botón para generar una contraseña al azar.
         Por construcción no está en ninguna filtración y cumple todos los
         criterios, así que resuelve el caso sin que el usuario tenga que
         adivinar qué le molesta al validador. Se copia también al campo de
         confirmación para no teclearla dos veces. --}}
    <div class="flex flex-wrap items-center gap-2 pt-1">
        <button type="button" x-on:click="generar()"
                class="inline-flex items-center gap-1.5 rounded-full border border-line px-3 py-1.5 text-xs font-medium text-ink transition hover:border-sage hover:text-sage">
            🎲 {{ __('Generar contraseña segura') }}
        </button>
        <span x-show="generada" x-cloak class="text-xs text-sage" x-text="'{{ __('Lista. Guárdala en un lugar seguro.') }}'"></span>
    </div>
</div>

<script>
    function passwordStrength_{{ $for }}() {
        return {
            value: '',
            typed: false,
            {{-- json_encode evita el doble-escape de Blade (que convertía "&" en "&amp;amp;") --}}
            rules: {!! json_encode([
                'length' => __('Mínimo 8 caracteres'),
                'upper'  => __('Una letra mayúscula (A–Z)'),
                'lower'  => __('Una letra minúscula (a–z)'),
                'number' => __('Un número (0–9)'),
                'symbol' => __('Un símbolo (! @ # $ % & *)'),
            ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!},
            checks: { length: false, upper: false, lower: false, number: false, symbol: false },
            generada: false,
            filtrada: false,   // aparece en alguna filtración pública conocida
            turno: 0,          // descarta respuestas de consultas ya superadas
            debounce: null,
            init() {
                const input = document.getElementById('{{ $for }}');
                if (! input) return;
                input.addEventListener('input', (e) => this.update(e.target.value));
                // Si el campo llegó ya con valor (por old() tras validación fallida), evaluar.
                if (input.value) this.update(input.value);
            },
            /**
             * Arma una contraseña de 16 caracteres con crypto.getRandomValues
             * (no Math.random, que es predecible). Garantiza al menos uno de
             * cada tipo colocándolos primero y barajando después.
             */
            generar() {
                const may = 'ABCDEFGHJKLMNPQRSTUVWXYZ';   // sin I ni O
                const min = 'abcdefghijkmnpqrstuvwxyz';   // sin l ni o
                const num = '23456789';                   // sin 0 ni 1
                const sim = '!@#$%&*';
                const todo = may + min + num + sim;
                const azar = (set, n = 1) => {
                    const buf = new Uint32Array(n);
                    crypto.getRandomValues(buf);
                    return Array.from(buf, (x) => set[x % set.length]).join('');
                };
                const base = (azar(may) + azar(min) + azar(num) + azar(sim) + azar(todo, 12)).split('');
                // Barajado Fisher-Yates para que el patrón no sea siempre
                // "mayúscula, minúscula, número, símbolo, resto".
                const mez = new Uint32Array(base.length);
                crypto.getRandomValues(mez);
                for (let i = base.length - 1; i > 0; i--) {
                    const j = mez[i] % (i + 1);
                    [base[i], base[j]] = [base[j], base[i]];
                }
                const pwd = base.join('');

                const input = document.getElementById('{{ $for }}');
                if (input) {
                    // Setter nativo + evento input: así React/Alpine y cualquier
                    // listener del formulario se enteran del cambio.
                    const setter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
                    setter.call(input, pwd);
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.type = 'text'; // mostrarla para que la pueda copiar
                }
                const conf = document.getElementById('{{ $for }}_confirmation');
                if (conf) {
                    const setter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
                    setter.call(conf, pwd);
                    conf.dispatchEvent(new Event('input', { bubbles: true }));
                }
                this.update(pwd);
                this.generada = true;
            },
            update(v) {
                this.value = v;
                this.typed = v.length > 0;
                this.checks = {
                    length: v.length >= 8,
                    upper:  /[A-Z]/.test(v),
                    lower:  /[a-z]/.test(v),
                    number: /[0-9]/.test(v),
                    symbol: /[^A-Za-z0-9]/.test(v),
                };
                this.revisarFiltraciones(v);
            },
            /**
             * Comprueba la contraseña contra filtraciones públicas MIENTRAS se
             * escribe, que es la misma validación que hace el servidor al
             * guardar. Sin esto el medidor decía "excelente" y después el
             * servidor rechazaba la contraseña: el usuario no entendía por qué.
             *
             * Usa k-anonymity: se envían sólo los 5 primeros caracteres del
             * hash SHA-1, nunca la contraseña ni el hash completo. El servicio
             * devuelve todos los hashes que empiezan igual y la comparación
             * final ocurre aquí, en el navegador.
             */
            async revisarFiltraciones(v) {
                if (v.length < 8 || !window.crypto?.subtle) {
                    this.filtrada = false;
                    return;
                }
                const miTurno = ++this.turno;   // descarta respuestas viejas
                clearTimeout(this.debounce);
                this.debounce = setTimeout(async () => {
                    try {
                        const datos = new TextEncoder().encode(v);
                        const buf = await crypto.subtle.digest('SHA-1', datos);
                        const hash = Array.from(new Uint8Array(buf))
                            .map((b) => b.toString(16).padStart(2, '0')).join('').toUpperCase();
                        const prefijo = hash.slice(0, 5);
                        const resto = hash.slice(5);

                        const r = await fetch('https://api.pwnedpasswords.com/range/' + prefijo);
                        if (!r.ok) return;                 // sin red: no estorbar
                        const txt = await r.text();
                        if (miTurno !== this.turno) return; // ya se escribió otra cosa

                        this.filtrada = txt.split('\n').some((linea) => linea.split(':')[0].trim() === resto);
                    } catch (e) {
                        this.filtrada = false;              // ante la duda, no bloquear
                    }
                }, 450);
            },
            get score() {
                return Object.values(this.checks).filter(Boolean).length;
            },
            get label() {
                return {!! json_encode([
                    __('Vacía'),
                    __('Muy débil'),
                    __('Débil'),
                    __('Media'),
                    __('Fuerte'),
                    __('Excelente'),
                ], JSON_UNESCAPED_UNICODE) !!}[this.score];
            },
            get labelHex() {
                return {
                    0: '#6E6A63', // warmgray
                    1: '#B91C1C', // rojo intenso
                    2: '#C2410C', // naranja
                    3: '#A16207', // ámbar
                    4: '#4D7C0F', // lima oscuro
                    5: '#5C7A5F', // sage
                }[this.score];
            },
            get barHex() {
                return {
                    0: '#E0DDD5', // line
                    1: '#EF4444', // rojo
                    2: '#FB923C', // naranja
                    3: '#FACC15', // amarillo
                    4: '#84CC16', // lima
                    5: '#5C7A5F', // sage
                }[this.score];
            },
        };
    }
</script>
