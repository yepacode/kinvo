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

if (! function_exists('landing_bool')) {
    /**
     * Setting booleano (toggles del panel): interpreta '1'/'true'/1/true como
     * verdadero y '0'/''/null/'false' como falso, sin importar cómo se guardó.
     * Úsalo para banderas de visibilidad (p. ej. hero_pill_visible).
     */
    function landing_bool(string $key, bool $default = false): bool
    {
        return filter_var(SiteSetting::get($key, $default), FILTER_VALIDATE_BOOLEAN);
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

if (! function_exists('enum_label')) {
    /**
     * Traduce un valor de enum (BD, en inglés técnico como 'in_contact', 'hibrido',
     * 'full_time', 'month') a la etiqueta ES legible que sí existe en lang/en.json.
     * Después la corre por __() para el switch ES/EN.
     *
     * Uso: {{ enum_label('modality', $offer->modality) }}
     *      {{ enum_label('application_status', $app->status) }}
     */
    function enum_label(string $group, ?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $mapa = [
            'modality' => [
                'presencial' => 'Presencial', 'online' => 'Online',
                'remoto' => 'Online', 'hibrido' => 'Híbrido',
            ],
            'salary_period' => [
                'hour' => 'Hora', 'month' => 'Mes', 'year' => 'Año', 'project' => 'Proyecto',
            ],
            'contract_type' => [
                'full_time' => 'Tiempo completo', 'part_time' => 'Medio tiempo',
                'freelance' => 'Freelance',
            ],
            'application_status' => [
                'submitted' => 'Enviada', 'seen' => 'Vista',
                'in_contact' => 'En contacto', 'accepted' => 'Aceptada',
                'rejected' => 'Rechazada', 'withdrawn' => 'Retirada',
            ],
            'offer_status' => [
                'draft' => 'Borrador', 'published' => 'Publicada',
                'paused' => 'Pausada', 'closed' => 'Cerrada', 'expired' => 'Vencida',
            ],
            'content_type' => [
                'video' => 'Video', 'document' => 'Documento',
                'audio' => 'Audio', 'link' => 'Enlace',
            ],
            'team_status' => [
                'active' => 'Activo', 'invited' => 'Invitado',
                'declined' => 'Rechazado', 'removed' => 'Removido',
            ],
            'wellness_type' => [
                'telemedicine' => 'Telemedicina', 'physio' => 'Fisioterapia',
                'talk' => 'Charla', 'insurance' => 'Seguro', 'other' => 'Otro',
            ],
        ];

        $etiquetaEs = $mapa[$group][$value] ?? ucfirst(str_replace('_', ' ', $value));

        return __($etiquetaEs);
    }
}
