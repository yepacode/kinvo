<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $table = 'settings';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    private const CACHE_KEY = 'site_settings';

    /**
     * Valores por defecto = contenido actual de la landing.
     * El asterisco *palabra* se renderiza como énfasis (itálica/color).
     * Los saltos de línea se respetan.
     */
    public static function defaults(): array
    {
        return [
            // SEO
            'seo_title' => 'Kinvoo — Where talent meets fitness',
            'seo_description' => 'Kinvoo, la red profesional para la industria fitness. Comunidad, oportunidades y respaldo para coaches, instructores y estudios.',
            'seo_og_image' => null,

            // Marca
            'brand_name' => 'kinvoo',
            'brand_tagline' => 'Where talent meets fitness.',

            // Hero
            'hero_eyebrow' => 'Bienvenido a Kinvoo',
            'hero_title' => "La red profesional\npara la industria\n*fitness.*",
            'hero_body' => 'Para las personas que sostienen el wellness todos los días. Comunidad, oportunidades y respaldo — en un solo lugar.',
            'hero_cta1' => 'Únete a la comunidad',
            'hero_cta2' => 'Explora el talento',
            'hero_pill' => 'Where talent meets fitness.',
            'hero_image' => null,

            // Misión
            'mission_label' => 'Nuestra misión',
            'mission_text' => "El fitness ya cambió el mundo.\nKinvoo impulsa a las personas *que lo hacen posible cada día.*",

            // Pilares
            'pillars_label' => 'Por qué Kinvoo',
            'pillars_heading' => 'Lo que el fitness profesional necesita ahora.',
            'pillar1_title' => 'Comunidad',
            'pillar1_body' => 'Una red hecha para ti. Conexión real, pertenencia real.',
            'pillar2_title' => 'Oportunidades',
            'pillar2_body' => 'Conecta con estudios y marcas. Encuentra tu siguiente paso.',
            'pillar3_title' => 'Beneficios',
            'pillar3_body' => 'Salud, respaldo legal y bienestar. Para que la experiencia fitness sea tan buena por dentro como por fuera.',
            'pillar4_title' => 'Crecimiento',
            'pillar4_body' => 'Tu carrera merece estructura, desarrollo y un lugar al que pertenecer.',

            // Foto divisora
            'divider_image' => null,

            // Sessions
            'sessions_label' => 'Kinvoo Sessions',
            'sessions_heading' => 'La conversación que el talento wellness *necesitaba tener.*',
            'sessions_body' => 'Encuentros íntimos y curados para hablar de lo que realmente importa. Con las personas correctas, en el momento correcto.',
            'sessions_cta' => 'Quiero asistir',
            'session_topic_1' => 'Crecimiento y desarrollo profesional',
            'session_topic_2' => 'Comunidad e identidad profesional',
            'session_topic_3' => 'Bienestar del talento wellness',
            'session_topic_4' => 'Beneficios y respaldo profesional',
            'session_topic_5' => 'El futuro del trabajo y el bienestar',

            // Para quién
            'forwho_label' => 'Para quién',
            'forwho_heading' => "Hecho para quienes\n*mueven el fitness.*",
            'forwho_body' => 'El ecosistema donde el talento y las oportunidades del fitness se encuentran, crecen y se sostienen juntos.',
            'card1_label' => 'Talento',
            'card1_title' => 'Coaches e Instructores',
            'card1_body' => 'Los que construyen experiencias todos los días y merecen una comunidad a su altura.',
            'card2_label' => 'Marcas',
            'card2_title' => 'Estudios y Marcas',
            'card2_body' => 'Encuentra y retén el mejor talento. Construye un equipo que crezca contigo.',
            'card3_label' => 'Operación',
            'card3_title' => 'El talento que hace vivir los estudios',
            'card3_body' => 'Front desk, studio managers, staff operativo — los que sostienen la experiencia desde adentro.',

            // Cita
            'quote_text' => '"El fitness evolucionó. La manera en que cuidamos a quienes lo sostienen, *también.*"',
            'quote_attr' => '— Kinvoo Community',

            // Únete
            'join_label' => 'Únete a Kinvoo',
            'join_heading' => "Where *talent*\nmeets fitness.",
            'join_body' => 'Sé parte desde el inicio. Estamos construyendo algo que la industria necesitaba — y queremos que estés adentro.',
            'join_cta' => 'Crear mi cuenta',
            'join_note' => '¿Eres talento o estudio? Elige abajo.',
            'join_tog1' => 'Soy talento fitness',
            'join_tog2' => 'Soy estudio / marca',
            'join_tog3' => 'Quiero asistir a una sesión',

            // Pie
            'footer_tag' => 'Where talent meets fitness.',
            'footer_copy' => '© 2026 Kinvoo. Todos los derechos reservados.',
        ];
    }

    /** Mapa de overrides guardados en BD (cacheado). Tolerante si la tabla no existe aún. */
    public static function overrides(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Valor de un setting: override guardado o default. */
    public static function get(string $key, mixed $fallback = null): mixed
    {
        $overrides = static::overrides();
        if (array_key_exists($key, $overrides) && ! is_null($overrides[$key])) {
            return $overrides[$key];
        }

        return static::defaults()[$key] ?? $fallback;
    }

    /** Guarda un valor y limpia la caché. */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::CACHE_KEY);
    }

    /** Todos los valores efectivos (defaults + overrides), para poblar formularios. */
    public static function allValues(): array
    {
        return array_merge(static::defaults(), array_filter(
            static::overrides(),
            fn ($v) => ! is_null($v)
        ));
    }
}
