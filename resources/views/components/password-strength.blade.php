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
            init() {
                const input = document.getElementById('{{ $for }}');
                if (! input) return;
                input.addEventListener('input', (e) => this.update(e.target.value));
                // Si el campo llegó ya con valor (por old() tras validación fallida), evaluar.
                if (input.value) this.update(input.value);
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
