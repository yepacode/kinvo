<?php

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

if (! function_exists('landing')) {
    /**
     * Valor de texto plano de un setting de la landing/SEO.
     * Soporta variantes por idioma: si el locale es EN, busca primero `<key>_en`
     * y cae a la clave base (ES) si está vacío. Así el owner puede definir el
     * copy inglés en el panel sin afectar el español.
     */
    function landing(string $key, mixed $fallback = ''): mixed
    {
        if (app()->getLocale() === 'en' && ! str_ends_with($key, '_en')) {
            $enValue = SiteSetting::get($key.'_en', null);
            if (filled($enValue)) {
                return $enValue;
            }
        }

        return SiteSetting::get($key, $fallback);
    }
}

if (! function_exists('landing_rich')) {
    /**
     * Texto con énfasis: *palabra* → <em>palabra</em> y saltos de línea → <br>.
     * Escapa el contenido antes de aplicar el formato (seguro contra XSS).
     */
    function landing_rich(string $key): HtmlString
    {
        $raw = (string) landing($key, '');
        $safe = e($raw);
        // *palabra* -> <em>palabra</em>. Sólo pares en la misma línea, sin anidar,
        // para no generar <em> mal formados con asteriscos sueltos o dobles.
        $safe = preg_replace('/\*([^*\n]+)\*/', '<em>$1</em>', $safe);
        $safe = nl2br($safe);

        return new HtmlString($safe);
    }
}

if (! function_exists('landing_image')) {
    /** URL de una imagen de la landing: la subida o, si no hay, el asset por defecto. */
    function landing_image(string $key, string $defaultAsset): string
    {
        $path = SiteSetting::get($key);

        return $path ? Storage::disk('public')->url($path) : asset($defaultAsset);
    }
}
