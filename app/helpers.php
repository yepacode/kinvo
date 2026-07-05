<?php

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

if (! function_exists('landing')) {
    /** Valor de texto plano de un setting de la landing/SEO. */
    function landing(string $key, mixed $fallback = ''): mixed
    {
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
        $raw = (string) SiteSetting::get($key, '');
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
