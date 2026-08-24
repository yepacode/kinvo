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
            'hero_pill_visible' => '1', // '1' = mostrar la etiqueta sobre la foto; '0' = ocultarla
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

            // Foto divisora (banda horizontal entre "Pilares" y "Sessions").
            // El eyebrow y título aparecen sobre la imagen; editables desde el admin.
            'divider_image' => null,
            'divider_eyebrow' => 'Kinvoo · Comunidad',
            'divider_title' => "Where talent\nmeets fitness.",

            // Cuenta y sesión: textos narrativos de las pantallas de auth
            // (Breeze) editables desde admin. Los labels de form (Correo,
            // Contraseña, Recordarme, etc.) se dejan en __() porque ya viven
            // en el sistema i18n de Laravel.
            'register_title'         => 'Crea tu cuenta',
            'register_subtitle'      => 'Únete a la red profesional del fitness',
            'register_type_label'    => 'Elige el tipo de cuenta',
            'register_type_help'     => 'Elige con cuidado: hoy una cuenta es de un solo tipo. Si te equivocas escríbenos y lo cambiamos.',
            'register_talent_emoji'  => '🧘‍♀️',
            'register_talent_title'  => 'Soy talento',
            'register_talent_body'   => 'Coach, instructor o staff que quiere que lo encuentren estudios y marcas.',
            'register_studio_emoji'  => '🏢',
            'register_studio_title'  => 'Soy estudio o marca',
            'register_studio_body'   => 'Gimnasio, estudio o marca que quiere buscar y contactar talento del fitness.',
            'login_title'            => 'Inicia sesión',
            'login_subtitle'         => 'Bienvenido de vuelta a Kinvoo',
            'forgot_title'           => 'Recupera tu contraseña',
            'forgot_body'            => 'Escribe tu correo y te enviaremos un enlace para crear una nueva contraseña.',
            'reset_title'            => 'Nueva contraseña',
            'verify_title'           => 'Verifica tu correo',
            'verify_body'            => '¡Gracias por unirte a Kinvoo! Antes de empezar, confirma tu correo dando clic en el enlace que te acabamos de enviar. Si no lo recibiste, con gusto te mandamos otro.',

            // Sessions
            'sessions_label' => 'Kinvoo Sessions',
            'sessions_heading' => 'La conversación que el talento *necesitaba tener.*',
            'sessions_body' => 'Encuentros íntimos y curados para hablar de lo que realmente importa. Con las personas correctas, en el momento correcto.',
            'sessions_cta' => 'Quiero asistir',
            'session_topic_1' => 'Crecimiento y desarrollo profesional',
            'session_topic_2' => 'Comunidad e identidad profesional',
            'session_topic_3' => 'Bienestar del talento',
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
            'join_tog1' => 'Soy talento',
            'join_tog2' => 'Soy estudio / marca',
            'join_tog3' => 'Quiero asistir a una sesión',

            // Pie
            'footer_tag' => 'Where talent meets fitness.',
            'footer_copy' => '© '.date('Y').' | Kinvoo - Todos los derechos reservados',

            // Fondo del sitio (páginas públicas). Editable desde el panel.
            'background_color' => '#F7F4EE',
            'background_image' => null,

            // Mensaje de bienvenida — Profesional (copy exacto del cliente, docx WEB KINVOO).
            'welcome_pro_title' => '¡Ya eres parte de Kinvoo!',
            'welcome_pro_body' => <<<'TXT'
Sabemos todo lo que das cada día: tu energía, tu tiempo, tu entrega a los demás. Aquí queremos hacer lo mismo por ti — acompañarte y sostenerte a ti también.

Llena tu perfil para que empecemos a conectar oportunidades contigo. Antes de publicarlo, te pedimos completarlo con honestidad: es lo que te representa ante los estudios.

• Sube una foto clara y reciente en donde podamos verte.
• Marca tu disponibilidad y horarios reales (AM/PM) para que lleguen las oportunidades que sí encajan contigo.
• En "Sobre ti" cuenta con tus palabras cómo se siente entrenar contigo. Lo auténtico conecta.
• Anota tus certificaciones reales. Podrás adjuntarlas para validación; ese archivo es privado, solo lo ve nuestro equipo.
• Verifica que tu correo y teléfono estén actualizados: serán el medio por el que te contactaremos cuando exista una oportunidad.

Tus datos de contacto siempre son privados: cuando un estudio quiera conectar, el contacto pasa por Kinvoo. Una vez que completes tu perfil, lo revisamos y, tras ser validado por Kinvoo, se publicará en un máximo de 24 horas.

Cualquier cosa, aquí estamos. — El equipo de Kinvoo.
Si tienes alguna duda, escríbenos: hola@gokinvoo.com
TXT,

            // Mensaje de bienvenida — Estudio / Cliente (copy exacto del cliente, docx WEB KINVOO).
            'welcome_studio_title' => '¡Tu registro fue exitoso!',
            'welcome_studio_body' => <<<'TXT'
Ya eres parte de Kinvoo. Sabemos que un estudio no es solo un espacio, es la gente que lo hace funcionar todos los días. Por eso, como parte de tu membresía, ya puedes empezar a buscar talento dentro de nuestra comunidad — porque cuidar a tu equipo es también cuidar tu negocio.

Completa tu perfil para comenzar a explorar la bolsa de talento y conectar con quienes pueden sumarse a tu equipo.

• Sube un logo claro y fotos que reflejen bien tu espacio.
• Mantén tus datos de contacto actualizados: son el medio por el que coordinamos cada conexión.
• Cuando encuentres un perfil que te interese, solicita el contacto a través de Kinvoo. Nosotros hacemos el puente.
• Mantén tu perfil y tus datos al día para aprovechar al máximo la red.

Una vez que completes el perfil de tu estudio, nuestro equipo lo revisará y lo activará en un máximo de 24 horas.

Cualquier duda, aquí estamos. — El equipo de Kinvoo.
Si tienes alguna duda, escríbenos: hola@gokinvoo.com
TXT,

            // ================================================================
            // Traducciones EN de la landing (editables desde el panel).
            // El helper landing() busca automáticamente la variante `_en`
            // cuando app()->getLocale() === 'en' y cae al ES si está vacío.
            // ================================================================

            // SEO EN
            'seo_title_en' => 'Kinvoo — Where talent meets fitness',
            'seo_description_en' => 'Kinvoo, the professional network for the fitness industry. Community, opportunities and support for coaches, instructors and studios.',

            // Marca EN
            'brand_tagline_en' => 'Where talent meets fitness.',

            // Hero EN
            'hero_eyebrow_en' => 'Welcome to Kinvoo',
            'hero_title_en' => "The professional network\nfor the *fitness*\nindustry.",
            'hero_body_en' => 'For the people who sustain wellness every single day. Community, opportunities and support — all in one place.',
            'hero_cta1_en' => 'Join the community',
            'hero_cta2_en' => 'Explore the talent',
            'hero_pill_en' => 'Where talent meets fitness.',

            // Misión EN
            'mission_label_en' => 'Our mission',
            'mission_text_en' => "Fitness already changed the world.\nKinvoo empowers the people *who make it happen every day.*",

            // Pilares EN
            'pillars_label_en' => 'Why Kinvoo',
            'pillars_heading_en' => 'What professional fitness needs now.',
            'pillar1_title_en' => 'Community',
            'pillar1_body_en' => 'A network built for you. Real connection, real belonging.',
            'pillar2_title_en' => 'Opportunities',
            'pillar2_body_en' => 'Connect with studios and brands. Find your next step.',
            'pillar3_title_en' => 'Benefits',
            'pillar3_body_en' => 'Health, legal support and wellbeing. So the fitness experience feels as good inside as it does outside.',
            'pillar4_title_en' => 'Growth',
            'pillar4_body_en' => 'Your career deserves structure, development and a place to belong.',

            // Sessions EN
            'sessions_label_en' => 'Kinvoo Sessions',
            'sessions_heading_en' => 'The conversation the wellness talent *needed to have.*',
            'sessions_body_en' => 'Intimate, curated gatherings to talk about what really matters. With the right people, at the right time.',
            'sessions_cta_en' => 'I want to attend',
            'session_topic_1_en' => 'Growth and professional development',
            'session_topic_2_en' => 'Community and professional identity',
            'session_topic_3_en' => 'Talent well-being',
            'session_topic_4_en' => 'Benefits and professional backing',
            'session_topic_5_en' => 'The future of work and wellbeing',

            // Foto divisora EN
            'divider_eyebrow_en' => 'Kinvoo · Community',
            'divider_title_en' => "Where talent\nmeets fitness.",

            // Cuenta y sesión EN
            'register_title_en'         => 'Create your account',
            'register_subtitle_en'      => 'Join the professional fitness network',
            'register_type_label_en'    => 'Choose your account type',
            'register_type_help_en'     => 'Choose carefully: an account has one type only. If you pick wrong, write us and we will switch it.',
            'register_talent_emoji_en'  => '🧘‍♀️',
            'register_talent_title_en'  => 'I am talent',
            'register_talent_body_en'   => 'Coach, instructor or staff who wants studios and brands to find them.',
            'register_studio_emoji_en'  => '🏢',
            'register_studio_title_en'  => 'I am a studio or brand',
            'register_studio_body_en'   => 'Gym, studio or brand looking to find and contact fitness talent.',
            'login_title_en'            => 'Sign in',
            'login_subtitle_en'         => 'Welcome back to Kinvoo',
            'forgot_title_en'           => 'Recover your password',
            'forgot_body_en'            => 'Enter your email and we will send you a link to create a new password.',
            'reset_title_en'            => 'New password',
            'verify_title_en'           => 'Verify your email',
            'verify_body_en'            => 'Thanks for joining Kinvoo! Before you start, confirm your email by clicking the link we just sent. If you did not get it, we will happily send another.',

            // Para quién EN
            'forwho_label_en' => 'For whom',
            'forwho_heading_en' => "Built for those who\n*move fitness.*",
            'forwho_body_en' => 'The ecosystem where fitness talent and opportunities meet, grow and sustain each other.',
            'card1_label_en' => 'Talent',
            'card1_title_en' => 'Coaches & Instructors',
            'card1_body_en' => 'The ones who build experiences every day and deserve a community that matches them.',
            'card2_label_en' => 'Brands',
            'card2_title_en' => 'Studios & Brands',
            'card2_body_en' => 'Find and retain the best talent. Build a team that grows with you.',
            'card3_label_en' => 'Operations',
            'card3_title_en' => 'The talent that keeps studios alive',
            'card3_body_en' => 'Front desk, studio managers, operations staff — those who sustain the experience from within.',

            // Cita EN
            'quote_text_en' => '"Fitness has evolved. The way we take care of those who sustain it, *has too.*"',
            'quote_attr_en' => '— Kinvoo Community',

            // Únete EN
            'join_label_en' => 'Join Kinvoo',
            'join_heading_en' => "Where *talent*\nmeets fitness.",
            'join_body_en' => 'Be part of this from the beginning. We are building something the industry needed — and we want you in.',
            'join_cta_en' => 'Create my account',
            'join_note_en' => 'Are you talent or a studio? Choose below.',
            'join_tog1_en' => 'I am fitness talent',
            'join_tog2_en' => 'I am a studio / brand',
            'join_tog3_en' => 'I want to attend a session',

            // Pie EN
            'footer_tag_en' => 'Where talent meets fitness.',
            'footer_copy_en' => '© '.date('Y').' | Kinvoo - All rights reserved',

            // Membresías EN
            'membership_eyebrow_en' => 'Memberships',
            'membership_title_en' => 'Choose your membership',
            'membership_body_en' => 'Plans designed for talent and for studios. Join the network and get access to the Kinvoo community, opportunities and support.',
            'membership_note_en' => 'Prices and benefits may vary. Reach out if you have questions.',
            'membership_individual_title_en' => 'For talent (individuals)',
            'membership_studio_title_en' => 'For studios and brands',

            // Legales EN — Los cuerpos oficiales están en ES por jurisdicción mexicana.
            // Traducimos títulos y fecha, y en el body dejamos una nota + copy en español.
            'legal_privacy_title_en' => 'Privacy Notice',
            'legal_privacy_updated_en' => 'Last updated: 2026',
            'legal_terms_title_en' => 'Terms and Conditions',
            'legal_terms_updated_en' => 'Last updated: 2026',

            // Bienvenidas en inglés — copy inicial (editable desde el panel).
            // El helper landing() elige la variante _en cuando el locale es EN.
            'welcome_pro_title_en' => 'You\'re now part of Kinvoo!',
            'welcome_pro_body_en' => <<<'TXT'
We know how much you give every day: your energy, your time, your dedication to others. Here we want to do the same for you — to support and back you up.

Fill in your profile so we can start connecting opportunities with you. Before publishing, please complete it honestly: it's what represents you before studios.

• Upload a clear, recent photo where we can see you.
• Mark your real availability and time slots (AM/PM) so the right opportunities reach you.
• In "About you", write in your own words what it feels like to train with you. Authentic connects.
• List your real certifications. You can attach them for validation; that file is private, only our team sees it.
• Make sure your email and phone are up to date: they'll be how we contact you when an opportunity comes up.

Your contact information is always private: when a studio wants to connect, the contact goes through Kinvoo. Once you complete your profile, we'll review it and, once validated by Kinvoo, it'll be published within 24 hours.

Anything you need, we're here. — The Kinvoo team.
Any questions? Write to us: hola@gokinvoo.com
TXT,
            'welcome_studio_title_en' => 'Registration successful!',
            'welcome_studio_body_en' => <<<'TXT'
You're now part of Kinvoo. We know a studio isn't just a space — it's the people who make it run every day. That's why, as part of your membership, you can already start finding talent inside our community — because taking care of your team is also taking care of your business.

Complete your profile to start exploring the talent pool and connect with those who could join your team.

• Upload a clear logo and photos that reflect your space.
• Keep your contact information up to date: it's how we coordinate each connection.
• When you find a profile you like, request the contact through Kinvoo. We build the bridge.
• Keep your profile and details current to make the most of the network.

Once you complete your studio profile, our team will review and activate it within 24 hours.

Any questions, we're here. — The Kinvoo team.
Write to us: hola@gokinvoo.com
TXT,

            // Membresías (página pública de planes)
            'membership_eyebrow' => 'Membresías',
            'membership_title' => 'Elige tu membresía',
            'membership_body' => 'Planes pensados para el talento y para los estudios. Únete a la red y accede a la comunidad, las oportunidades y el respaldo de Kinvoo.',
            'membership_note' => 'Los precios y beneficios pueden variar. Escríbenos si tienes dudas.',
            'membership_individual_title' => 'Para talento (persona física)',
            'membership_studio_title' => 'Para estudios y marcas',

            // Página /mis-momentos (wall "Comparte un momento") — editable desde admin.
            'momento_pagina_titulo'    => 'Comparte un momento',
            'momento_bloque_titulo'    => '¿Para qué es este espacio?',
            'momento_subtitulo'        => 'Así nuestra comunidad conoce cómo se vive tu estudio, antes de pisarlo.',
            'momento_bloque_body'      => 'Esto es tu cultura, en vivo. Una clase llena, un evento o momento especial, la energía de un lunes por la mañana — muéstranos cómo se vive ser parte de tu estudio.',
            'momento_bullet_1'         => 'Foto o video corto.',
            'momento_bullet_2'         => 'Una frase, no necesitas más.',
            'momento_bullet_3'         => 'Al publicar, tu momento pasa por moderación y luego aparece en Comunidad, visible para todos los coaches de Kinvoo.',

            // Página /desarrollo (contenido) — editable desde admin.
            'desarrollo_header_titulo'      => 'Desarrollo y capacitaciones',
            'desarrollo_onboarding_titulo'  => '💡 ¿Qué encuentro aquí?',
            'desarrollo_copy_coach_h1'      => 'Clases, tutoriales y material para crecer en tu carrera.',
            'desarrollo_copy_coach_h2'      => 'Clases grabadas, guías y capacitaciones. Cada pieza tiene su formato (video, PDF, audio, enlace) y categorías para filtrar.',
            'desarrollo_copy_estudio_h1'    => 'Clases, tutoriales y material para el desarrollo de tu equipo.',
            'desarrollo_copy_estudio_h2'    => 'Los coaches que forman parte de tu equipo desbloquean este contenido con la membresía que tú cubres.',

            // Legales — Aviso de Privacidad
            'legal_privacy_title' => 'Aviso de Privacidad',
            'legal_privacy_updated' => 'Última actualización: 2026',
            'legal_privacy_body' => <<<'TXT'
KINVOO, S.A.S ("KINVOO") establece y pone a su disposición este aviso de privacidad ("Aviso de Privacidad") a efecto de proteger los datos personales en posesión de los particulares y sobre todo la de garantizar la privacidad y el derecho a la autodeterminación informativa de las personas, en cumplimiento de lo establecido por la Ley Federal de Protección de Datos Personales en Posesión de los Particulares y su respectivo Reglamento, con la finalidad de garantizar la privacidad de sus clientes y cualesquiera persona(s) que comparta(n) datos personales ("TITULAR") con KINVOO. Si comparte datos personales con KINVOO, se entenderá que ha leído y aceptado los términos del Aviso de Privacidad y que para cualquier controversia o reclamación derivada del mismo, se somete a la jurisdicción y competencia de los tribunales Federales de la Ciudad de México.

KINVOO señala para los efectos señalados anteriormente como domicilio el ubicado en la calle de Mariano Escobedo 345, oficina 2, Colonia Polanco, C.P. 11560, en la Ciudad de México. Del mismo modo, informa a sus clientes, proveedores, remitentes y destinatarios que mantengan correspondencia electrónica —vía Email, base de datos o usuarios por medio de la web https://gokinvoo.com/— y/o cualesquiera persona(s) (TITULAR) que hayan proporcionado sus datos personales a KINVOO, que esta última se obliga a guardar la confidencialidad de dichos datos.

Por virtud de lo anterior, KINVOO no venderá, cederá, compartirá ni transferirá a terceras personas ajenas a KINVOO sin el previo consentimiento del TITULAR, o sin que esto se derive de una obligación legal de KINVOO, sus datos personales. KINVOO únicamente se reserva el derecho de compartir sus datos personales con sus CLIENTES, CONSULTORES, PROVEEDORES y EMPLEADOS.

Sus datos personales proporcionados a KINVOO, con o sin ser necesarios para una relación profesional, comercial y/o de servicios, o bien un pacto jurídico formal, serán utilizados por KINVOO con la finalidad de comunicar información general y de temas comerciales, técnicos o legales de interés para KINVOO como ENCARGADO de datos personales.

Como titular de datos personales, usted o su representante legal pueden ejercitar los derechos de Acceso, Rectificación, Cancelación u Oposición (derechos ARCO), mediante correspondencia dirigida al correo hola@gokinvoo.com o al domicilio de KINVOO.

KINVOO se reserva el derecho a modificar los términos del Aviso de Privacidad mediante su publicación en su Sitio Web, señalado en el párrafo que antecede, y recomienda revisar el Aviso de Privacidad en cada visita a su Sitio Web a efecto de conocer cualquier modificación que se hubiere realizado al mismo.
TXT,

            // Legales — Términos y Condiciones
            'legal_terms_title' => 'Términos y Condiciones',
            'legal_terms_updated' => 'Última actualización: 2026',
            'legal_terms_body' => <<<'TXT'
KINVOO — Plataforma de mediación en comercio electrónico.

1. Identificación del Mediador-Operador
KINVOO, S.A.S., con domicilio en Mariano Escobedo 345, Col. Polanco, C.P. 11560, RFC KWE260527NE8 (en adelante, la "Plataforma" o el "Mediador-Operador"), pone a disposición el sitio web/app https://gokinvoo.com/, a través del cual se facilita la interacción entre el mediador y los miembros de la membresía KINVOO.

2. Naturaleza del Servicio (Mediación)
La Plataforma actúa exclusivamente como intermediario de e-commerce que facilita el contacto y las transacciones entre las partes en territorio mexicano ("México"), destinado a personas físicas y/o morales ("Terceros"). La Plataforma no es parte directa en la oferta o compraventa de productos o servicios que se muestran en la página web y que finalmente eligen los usuarios/membresía. Los vendedores son responsables directos de la oferta, calidad, entrega, aplicación y cumplimiento de sus productos o servicios.

Kinvoo desarrolla, evoluciona y diversifica en su plataforma web (where talent meets fitness) el ecosistema de servicios digitales, a través de membresías que dan acceso a: eventos virtuales o plataformas híbridas —gyms, centros de entrenamiento u otros espacios de acondicionamiento, presenciales o virtuales, incluidos estudios boutique comunitarios—; la gestión de talento; y servicios independientes prestados por coaches, de forma virtual o híbrida, en horarios flexibles. Las comunidades que participen estarán afiliadas mediante la membresía al ecosistema Kinvoo, lo que les permitirá tener acceso a múltiples beneficios y talento dentro del mercado mexicano.

3. Aceptación de los Términos
El acceso, registro o uso de la Plataforma implica la aceptación expresa de estos Términos y Condiciones, constituyendo un contrato legal válido conforme a las disposiciones legales previstas en el Código de Comercio, el Código Civil Federal y el Código Federal de Procedimientos Civiles, así como los tratados internacionales de los que México es parte en esta materia.

4. Registro y Cuenta de Usuario en la membresía KINVOO
Para acceder a ciertos servicios, el usuario deberá registrarse en la plataforma https://gokinvoo.com. El usuario se obliga a proporcionar información veraz y actualizada. El uso de la cuenta es personal e intransferible. La Plataforma podrá suspender cuentas por falta de cumplimiento de los términos y condiciones o por la violación de las disposiciones aplicables.

5. Reglas de Uso de la Plataforma
Los usuarios se obligan a: no utilizar la plataforma para fines ilícitos o fraudulentos; respetar la normativa aplicable y los derechos de terceros; y no publicar contenido engañoso o ilegal.

6. Condiciones para Vendedores
Los vendedores deberán: publicar información veraz sobre productos/servicios; cumplir con la legislación aplicable (especialmente en consumo y datos personales); atender devoluciones, garantías y reclamaciones; y emitir facturación conforme a la normativa fiscal.

7. Condiciones para Compradores
Los compradores se obligan a: proporcionar datos correctos; realizar pagos/cuotas conforme a los términos pactados; y utilizar los productos y/o servicios conforme a su calidad, eficacia y naturaleza.

8. Pagos de cuotas, Comisiones y Tarifas
La Plataforma podrá cobrar comisiones por: publicación de productos, venta realizada y procesamiento de pagos. Todas las tarifas serán informadas previamente. La compra de tarjetas de membresía podrá realizarse en línea mediante cuenta bancaria o transferencia electrónica.

9. Proceso de Compra
Las membresías se ofrecen para persona física (Esencial, Pro) y para estudios y marcas — personas morales (Esencial, Plus, Pro). El comprador realiza una orden a través de la Plataforma; el Mediador-Operador es responsable de la entrega; la Plataforma podrá facilitar medios de pago; y la confirmación de compra constituye aceptación de la oferta.

10. Política de Devoluciones y Reembolsos
Las condiciones específicas serán definidas por cada vendedor. La Plataforma podrá intervenir como mediador en controversias, previamente a la aplicación de instancias administrativas o judiciales. En caso de conflicto, se aplicarán las políticas publicadas.

11. Resolución de Disputas (Mediación)
La Plataforma podrá ofrecer servicios de mediación o resolución de conflictos. Las decisiones de la Plataforma podrán ser vinculantes cuando así se establezca. No se garantiza resolución favorable para ninguna de las partes.

12. Limitación de Responsabilidad
La Plataforma no garantiza la calidad, disponibilidad o legalidad de los productos; no será responsable por incumplimientos de los vendedores; y no responde por daños indirectos derivados del uso del sitio. Las partes son personas físicas o morales independientes; nada en esta plataforma crea asociación, empresa conjunta, agencia, franquicia o relación laboral entre las partes, ni existen terceros beneficiarios.

13. Propiedad Intelectual
El contenido de la plataforma es propiedad del Operador o de sus licenciantes. Los vendedores otorgan licencia para usar sus contenidos dentro del sistema. Cada parte garantiza a la otra el uso legítimo de sus marcas, nombres comerciales, patentes, programas de cómputo, secretos industriales y demás información de su propiedad o bajo licencia, obligándose a sacar en paz y a salvo a la otra parte ante cualquier reclamación de terceros derivada de dicho uso.

14. Protección de Datos Personales
La Plataforma tratará los datos personales conforme a la Ley Federal de Protección de Datos Personales en Posesión de los Particulares. Se transcribe a continuación el Aviso de Privacidad:

KINVOO WELLNESS, S.A.S. ("KINVOO") establece y pone a su disposición este aviso de privacidad ("Aviso de Privacidad") a efecto de proteger los datos personales en posesión de los particulares y sobre todo la de garantizar la privacidad y el derecho a la autodeterminación informativa de las personas ("TITULAR"), en cumplimiento de lo establecido por la Ley Federal de Protección de Datos Personales en Posesión de los Particulares y su respectivo Reglamento, con la finalidad de garantizar la privacidad de sus clientes y cualesquiera persona(s) que comparta(n) datos personales con KINVOO. Si comparte datos personales con KINVOO, se entenderá que ha leído y aceptado los términos del Aviso de Privacidad y que para cualquier controversia o reclamación derivada del mismo, se somete a la jurisdicción y competencia de los tribunales Federales de la Ciudad de México.

KINVOO señala como domicilio el ubicado en la calle de Mariano Escobedo 345, despacho 2, Colonia Polanco, C.P. 11560, en la Ciudad de México. Del mismo modo, informa a sus clientes, proveedores, remitentes y destinatarios que mantengan correspondencia electrónica —vía Email, base de datos o usuarios por medio de la web https://gokinvoo.com/— y/o cualesquiera persona(s) (TITULAR) que hayan proporcionado sus datos personales a KINVOO, que esta última se obliga a guardar la confidencialidad de dichos datos.

Por virtud de lo anterior, KINVOO no venderá, cederá, compartirá ni transferirá a terceras personas ajenas a KINVOO sin el previo consentimiento del TITULAR, o sin que esto se derive de una obligación legal de KINVOO, sus datos personales. KINVOO únicamente se reserva el derecho de compartir sus datos personales con sus CLIENTES, CONSULTORES, PROVEEDORES y EMPLEADOS.

Sus datos personales proporcionados a KINVOO, con o sin ser necesarios para una relación profesional, comercial y/o de servicios, o bien un pacto jurídico formal, serán utilizados por KINVOO con la finalidad de comunicar información general y de temas comerciales, técnicos o legales de interés para KINVOO como ENCARGADO de datos personales. Como titular de datos personales, usted o su representante legal pueden ejercitar los derechos de Acceso, Rectificación, Cancelación u Oposición, mediante correspondencia dirigida al correo hola@gokinvoo.com o al domicilio de KINVOO.

KINVOO se reserva el derecho a modificar los términos del Aviso de Privacidad mediante su publicación en su Sitio Web, y recomienda revisarlo en cada visita a efecto de conocer cualquier modificación que se hubiere realizado.

15. Aspectos Laborales
Conforme a la regulación laboral y a la doctrina, no existe relación ni dependencia laboral cuando el usuario, miembro o tercero preste servicios con autonomía organizativa y técnica y asuma el riesgo económico de su propia actividad; cuando exista ausencia de poder de mando, fiscalización o supervisión; cuando haya inexistencia de potestad disciplinaria; cuando el tercero decida de forma libre sus métodos de trabajo, herramientas y horarios; y bajo el principio de ajenidad invertida (trabajo por cuenta propia). Estos supuestos están excluidos de la doctrina laboral bajo contratos de índole comercial, mercantil o civil.

16. Suspensión y Terminación
El Mediador-Operador podrá suspender cuentas, retirar contenido y cancelar el acceso por incumplimiento.

17. Ley Aplicable y Jurisdicción
Estos términos se rigen por las leyes de los Estados Unidos Mexicanos, en particular: el Código de Comercio, la Ley Federal de Protección al Consumidor, la Ley de Protección de Datos Personales, así como la normativa de COFEPRIS, la Comisión Nacional de Seguros y Fianzas y la CONDUSEF, según corresponda.

18. Modificaciones
La Plataforma podrá modificar estos términos en cualquier momento, notificando a los usuarios.
TXT,

            // =============================================================
            // M20 · Bloque 4 · Textos de la app (títulos/empty/CTA/headers)
            // 88 keys ALTA identificadas por auditoría multi-agente M15.
            // Editables desde admin (pestaña "Textos de la app").
            // =============================================================

            // Dashboard (coach + estudio)
            'dashboard_saludo'                     => 'Hola, :name',
            'dashboard_coach_titulo_perfil'        => 'Tu perfil en Kinvoo',
            'dashboard_coach_perfil_publicado_msg' => 'Tu perfil está :status y visible para contratantes.',
            'dashboard_coach_perfil_oculto_msg'    => 'Tu perfil está :status. Complétalo y publícalo para que te encuentren.',
            'dashboard_coach_cta_editar_perfil'    => 'Editar mi perfil',
            'dashboard_coach_vistas_titulo'        => 'Quién vio tu perfil',
            'dashboard_coach_vistas_empty'         => 'Aún nadie ha visto tu perfil. Publícalo y compártelo para empezar.',
            'dashboard_estudio_titulo'             => 'Encuentra talento',
            'dashboard_estudio_descripcion'        => 'Explora perfiles de profesionales o completa los datos de tu empresa.',
            'dashboard_estudio_cta_talento'        => 'Buscar talento',
            'dashboard_estudio_cta_perfil'         => 'Editar mi empresa',

            // Membresías (flashes upsell + CTA)
            'membresia_flash_directorio'      => 'Para acceder al directorio de talento necesitas una membresía activa. Elige un plan abajo o escríbenos.',
            'membresia_flash_ofertas'         => 'Para publicar o editar ofertas de trabajo necesitas un plan activo. Elige uno abajo.',
            'membresia_flash_mas_vacantes'    => 'Con el plan gratis solo puedes tener 1 vacante activa a la vez. Actualiza para publicar más y sin límite de tiempo.',
            'membresia_flash_contacto'        => 'Para contactar directamente al talento necesitas un plan activo. Elige uno abajo.',
            'membresia_flash_contenido'       => 'Para acceder a este contenido de nivel avanzado necesitas un plan activo.',
            'membresia_flash_comunidad'       => 'Comunidad es un espacio de miembros. Activa tu plan para ver los momentos que comparten los estudios.',
            'membresia_flash_expediente'      => 'Tu expediente de cuidado se activa con un plan de talento. Ahí queda registro de tus consultas, sesiones y beneficios.',
            'membresia_cuenta_revision_titulo'=> 'Tu cuenta está en revisión',
            'membresia_cta_suscribirme'       => 'Suscribirme',
            'membresia_empty_state'           => 'Pronto publicaremos nuestros planes de membresía.',

            // Respaldo (telemedicina/fisio)
            'respaldo_header_titulo'        => 'Mi respaldo',
            'respaldo_guia_titulo'          => '🩺 ¿Cómo funciona el respaldo?',
            'respaldo_guia_body'            => 'Pide una consulta de telemedicina cuando lo necesites. Kinvoo la agenda y te avisa por correo. Si tu plan incluye fisio, también puedes pedir sesión.',
            'respaldo_flash_enviado_titulo' => '¡Solicitud enviada!',
            'respaldo_cta_enviar'           => 'Enviar solicitud',
            'respaldo_solicitudes_titulo'   => 'Mis solicitudes',
            'respaldo_empty_state'          => 'Aún no has solicitado ninguna sesión. Cuando lo necesites, usa el formulario de arriba.',

            // Pantalla /pending (cuentas en estado especial)
            'pending_titulo_suspendida'       => 'Cuenta suspendida',
            'pending_titulo_perfil_revision'  => 'Perfil en revisión',
            'pending_titulo_cuenta_revision'  => 'Cuenta en revisión',
            'pending_body_suspendida'         => 'Tu cuenta está suspendida. Escríbenos a hola@gokinvoo.com si crees que fue un error.',
            'pending_body_perfil_pendiente'   => '¡Gracias por llenar tu perfil! Nuestro equipo lo está revisando y quedará activo en máximo 24 horas.',
            'pending_body_cuenta_revision'    => '¡Gracias por registrarte en Kinvoo! Un administrador revisará tu perfil antes de activarlo. Te avisamos por correo.',

            // Ofertas · detalle
            'ofertas_show_flash_enviada_titulo' => '¡Postulación enviada!',
            'ofertas_show_flash_enviada_texto'  => 'El estudio verá tu candidatura y te contactará si le interesa.',
            'ofertas_show_flash_ya_postulaste'  => 'Ya postulaste a esta oferta. Puedes ver el estado en Mis postulaciones.',
            'ofertas_show_postular_titulo'      => 'Postular',
            'ofertas_show_intro_postular'       => 'No buscamos la respuesta perfecta — solo que ambos sepan si hacen buen equipo.',
            'ofertas_show_cta_enviar'           => 'Enviar postulación',

            // Pulso Kinvoo (coach + estudio)
            'pulso_coach_header_titulo'      => 'Evalúa a tu estudio',
            'pulso_coach_guia_titulo'        => '⭐ ¿Cómo va tu estudio?',
            'pulso_coach_guia_body'          => 'Tu evaluación ayuda a que tu estudio mejore. No se comparten tus respuestas exactas — sólo los agregados.',
            'pulso_coach_cta_enviar'         => 'Enviar mi evaluación',
            'pulso_coach_historial_titulo'   => 'Mis respuestas anteriores',
            'pulso_coach_empty_state'        => 'Aún no has contestado. Empieza con tu primer pulso arriba.',
            'pulso_estudio_titulo'           => 'Cómo te evalúa tu equipo',

            // Wall / Comunidad
            'wall_comunidad_header_titulo'    => 'Comunidad',
            'wall_comunidad_guia_titulo'      => 'Momentos de la comunidad Kinvoo',
            'wall_comunidad_guia_body'        => 'Aquí ves cómo se vive cada estudio de la comunidad. Fotos y videos cortos publicados por los estudios y aprobados por el equipo Kinvoo.',
            'wall_comunidad_cta_publicar'     => '+ Publicar tu momento',
            'wall_comunidad_empty_state'      => 'Aún no hay momentos publicados. Vuelve pronto — la comunidad Kinvoo está por empezar.',
            'wall_mis_momentos_flash_enviado_titulo' => '¡Momento enviado!',
            'wall_mis_momentos_flash_enviado_body'   => 'El equipo Kinvoo lo revisa y lo publica en Comunidad si todo está bien.',
            'wall_mis_momentos_cta_publicar'         => 'Publicar momento',
            'wall_mis_momentos_empty_state'          => 'Aún no has compartido ningún momento. Usa el formulario de arriba para publicar el primero.',

            // Mis beneficios
            'beneficios_header_titulo'   => 'Mis beneficios',
            'beneficios_guia_titulo'     => '🌱 Todo lo que tu plan te da',
            'beneficios_guia_body'       => 'Tu plan de Kinvoo se traduce en cuidado real. Aquí ves qué tienes activo y cuánto lo has usado.',
            'beneficios_activos_titulo'  => 'Qué tienes activo',
            'beneficios_upgrade_texto'   => '¿Faltó algo? Sube a un plan mayor para desbloquear más beneficios.',
            'beneficios_upgrade_cta'     => 'Ver planes →',

            // Mi equipo (estudio)
            'equipo_pagina_titulo'     => 'Mi equipo',
            'equipo_guia_titulo'       => '💚 ¿Cómo se arma tu equipo?',
            'equipo_guia_intro'        => 'Invita a tus coaches por correo. En cuanto aceptan, su cuidado empieza a sumar aquí, en tu panel de bienestar.',
            'equipo_eval_pregunta'     => '¿Cómo evalúas el bienestar de tu equipo este período?',
            'equipo_invitar_titulo'    => 'Agregar a alguien al equipo',
            'equipo_listado_titulo'    => 'Miembros del equipo',
            'equipo_empty_state'       => 'Aún no tienes miembros en tu equipo. Empieza invitando a un profesional por correo.',

            // Login (flashes)
            'login_flash_cuenta_eliminada_titulo' => 'Tu cuenta fue eliminada.',
            'login_flash_cuenta_eliminada_body'   => 'Se borraron tu perfil, tus contactos y tus archivos. Nos alegra que hayas sido parte de Kinvoo. Si quieres volver, puedes crear una cuenta nueva.',
            'login_flash_admin_baja_titulo'       => 'Tu cuenta ya no está activa.',
            'login_flash_admin_baja_body'         => 'Kinvoo dio de baja tu cuenta. Si crees que fue un error, escríbenos a',

            // Menú principal (etiquetas coach)
            'nav_coach_mi_perfil'      => 'Mi perfil',
            'nav_coach_contactos'      => 'Contactos',
            'nav_coach_oportunidades'  => 'Oportunidades',
            'nav_coach_desarrollo'     => 'Desarrollo',

            // Ofertas · listado + mis ofertas + form
            'ofertas_index_titulo'          => 'Oportunidades',
            'ofertas_index_guia_texto1'     => 'Aquí verás las vacantes que publican los estudios. Podrás aplicar en cuanto tu perfil esté completo — así los estudios conocerán quién eres.',
            'ofertas_index_empty'           => 'No hay ofertas publicadas por ahora.',
            'mis_ofertas_titulo'            => 'Mis oportunidades',
            'mis_ofertas_intro'             => 'Aquí publicas tus vacantes, gestionas las postulaciones y cambias el estado de cada una.',
            'mis_ofertas_empty_postulaciones' => 'Aún no hay postulaciones. Cuando algún coach postule, aparecerá aquí y te llegará un correo.',
            'mis_ofertas_empty_general'     => 'Aún no has publicado ofertas. Presiona "+ Publicar oferta" arriba para comenzar.',
            'ofertas_form_publicar_cta'     => 'Publicar oferta',
            'mis_postulaciones_titulo'      => 'Mis postulaciones',
            'mis_postulaciones_empty'       => 'Aún no has postulado a ninguna oferta.',

            // Perfil profesional
            'perfil_edit_titulo'          => 'Mi perfil profesional',
            'perfil_edit_intro'           => 'Completa tu perfil y guarda para enviarlo a revisión.',
            'perfil_edit_estado_revision' => 'Cuando completes tu perfil, el equipo de Kinvoo lo revisará y lo publicará. Te avisamos por correo.',
            'perfil_edit_cta_guardar'     => 'Guardar y continuar →',

            // Perfil empresa
            'company_edit_titulo'                 => 'Mi empresa',
            'company_cuenta_revision_titulo'      => '⏳ Tu cuenta está en revisión',
            'company_cuenta_revision_descripcion' => 'Completa el perfil de tu estudio y guárdalo. Cuando Kinvoo lo apruebe podrás publicar ofertas, buscar talento, gestionar tu equipo y suscribirte a un plan. Te avisaremos por correo.',

            // Talento (listado + show)
            'talento_index_titulo'      => 'Encuentra talento',
            'talento_index_subtitulo'   => 'Filtra por disciplina, ubicación o modalidad.',
            'talento_index_empty_titulo'=> 'Sin resultados',
            'talento_show_cta_login'    => 'Inicia sesión como contratante para contactar',

            // Contenido / desarrollo
            'contenido_index_empty'        => 'No hay contenido disponible para tu cuenta en esta categoría.',
            'contenido_upsell_activa_plan' => 'Activa tu plan para ver este nivel →',
            'contenido_form_titulo'        => 'Editar contenido / Nuevo contenido',
            'contenido_form_boton_publicar'=> 'Publicar contenido',
            'mis_contenidos_titulo'        => 'Mi desarrollo',
            'mis_contenidos_intro'         => 'Sube guías, videos o enlaces para compartir con la comunidad Kinvoo. Todos los contenidos que subes son visibles para coaches y estudios de la comunidad.',
            'mis_contenidos_empty_state'   => 'Aún no has subido contenido. Comparte tu primer video, PDF o enlace con la comunidad.',

            // Expediente coach
            'expediente_header_titulo'         => 'Mi expediente',
            'expediente_intro_descripcion'     => 'Aquí ves el estatus de tu cuidado dentro de Kinvoo — qué beneficios tienes activos y qué has usado.',
            'expediente_charlas_titulo'        => 'Charlas a las que has asistido',
            'expediente_charlas_empty_state'   => 'Todavía no tienes charlas ni capacitaciones registradas. Kinvoo irá agregándolas conforme participes.',

            // Notificaciones
            'notificaciones_header_titulo' => 'Notificaciones',
            'notificaciones_empty_state'   => 'Aún no tienes notificaciones.',

            // Reportes Filament (títulos y empty states)
            'admin_reporte_coaches_titulo'      => 'Reporte · Actividad de coaches en Desarrollo',
            'admin_reporte_coaches_modal_titulo'=> 'Historial de contenido — :name',
            'admin_reporte_coaches_empty'       => 'Sin coaches con actividad todavía',
            'admin_reporte_conversion_titulo'   => 'Reporte · Conversión de usuarios',
            'admin_reporte_conversion_empty'    => 'Sin usuarios registrados',
            'admin_reporte_estudios_titulo'     => 'Reporte · Actividad por estudio',
            'admin_reporte_estudios_empty'      => 'Sin estudios registrados',

            // Landing/public — CTA "Únete"
            'nav_unete_cta' => 'Únete',

            // =============================================================
            // Variante EN de las 88 keys anteriores. landing() cae al ES si vacío.
            // =============================================================

            // Dashboard EN
            'dashboard_saludo_en'                     => 'Hi :name',
            'dashboard_coach_titulo_perfil_en'        => 'Your profile on Kinvoo',
            'dashboard_coach_perfil_publicado_msg_en' => 'Your profile is :status and visible to contractors.',
            'dashboard_coach_perfil_oculto_msg_en'    => 'Your profile is :status. Complete and publish it so studios can find you.',
            'dashboard_coach_cta_editar_perfil_en'    => 'Edit my profile',
            'dashboard_coach_vistas_titulo_en'        => 'Who viewed your profile',
            'dashboard_coach_vistas_empty_en'         => "No one has viewed your profile yet. Publish it and share it to get started.",
            'dashboard_estudio_titulo_en'             => 'Find talent',
            'dashboard_estudio_descripcion_en'        => "Browse professional profiles or complete your company details.",
            'dashboard_estudio_cta_talento_en'        => 'Search talent',
            'dashboard_estudio_cta_perfil_en'         => 'Edit my company',

            // Membresías EN
            'membresia_flash_directorio_en'      => 'To access the talent directory you need an active membership. Choose a plan below.',
            'membresia_flash_ofertas_en'         => 'To publish or edit job offers you need an active plan. Choose one below.',
            'membresia_flash_mas_vacantes_en'    => "The free plan allows only 1 active vacancy at a time. Upgrade to post more.",
            'membresia_flash_contacto_en'        => 'To contact talent directly you need an active plan. Choose one below.',
            'membresia_flash_contenido_en'       => "To access this advanced-level content you need an active plan.",
            'membresia_flash_comunidad_en'       => "Community is a members' space. Activate your plan to see moments shared by studios.",
            'membresia_flash_expediente_en'      => "Your care record activates with a talent plan. It logs every consult, session and benefit received.",
            'membresia_cuenta_revision_titulo_en'=> 'Your account is under review',
            'membresia_cta_suscribirme_en'       => 'Subscribe',
            'membresia_empty_state_en'           => "We'll publish our membership plans soon.",

            // Respaldo EN
            'respaldo_header_titulo_en'        => 'My support',
            'respaldo_guia_titulo_en'          => '🩺 How does support work?',
            'respaldo_guia_body_en'            => "Request a telemedicine consult whenever you need it. Kinvoo schedules it and emails you.",
            'respaldo_flash_enviado_titulo_en' => 'Request sent!',
            'respaldo_cta_enviar_en'           => 'Send request',
            'respaldo_solicitudes_titulo_en'   => 'My requests',
            'respaldo_empty_state_en'          => "You haven't requested any session yet. When you need one, use the form above.",

            // Pending EN
            'pending_titulo_suspendida_en'       => 'Account suspended',
            'pending_titulo_perfil_revision_en'  => 'Profile under review',
            'pending_titulo_cuenta_revision_en'  => 'Account under review',
            'pending_body_suspendida_en'         => 'Your account is suspended. Write to hola@gokinvoo.com if you think this is a mistake.',
            'pending_body_perfil_pendiente_en'   => "Thanks for filling in your profile! Our team is reviewing it and it will go live within 24 hours.",
            'pending_body_cuenta_revision_en'    => "Thanks for signing up to Kinvoo! An administrator will review your profile before activating it. We'll email you.",

            // Ofertas · detalle EN
            'ofertas_show_flash_enviada_titulo_en' => 'Application sent!',
            'ofertas_show_flash_enviada_texto_en'  => "The studio will see your application and get in touch if interested.",
            'ofertas_show_flash_ya_postulaste_en'  => "You already applied to this offer. You can see the status in My applications.",
            'ofertas_show_postular_titulo_en'      => 'Apply',
            'ofertas_show_intro_postular_en'       => "We're not looking for the perfect answer — just so both know if it's a good fit.",
            'ofertas_show_cta_enviar_en'           => 'Send application',

            // Pulso EN
            'pulso_coach_header_titulo_en'      => 'Rate your studio',
            'pulso_coach_guia_titulo_en'        => '⭐ How is your studio doing?',
            'pulso_coach_guia_body_en'          => "Your feedback helps your studio improve. Your individual answers are not shared, only the average.",
            'pulso_coach_cta_enviar_en'         => 'Send my rating',
            'pulso_coach_historial_titulo_en'   => 'My previous answers',
            'pulso_coach_empty_state_en'        => "You haven't answered yet. Start with your first pulse above.",
            'pulso_estudio_titulo_en'           => "How your team rates you",

            // Wall EN
            'wall_comunidad_header_titulo_en'    => 'Community',
            'wall_comunidad_guia_titulo_en'      => 'Kinvoo community moments',
            'wall_comunidad_guia_body_en'        => "See how each studio in the community lives it. Short photos and videos published by studios and approved by the Kinvoo team.",
            'wall_comunidad_cta_publicar_en'     => '+ Publish your moment',
            'wall_comunidad_empty_state_en'      => "No moments published yet. Come back soon — the Kinvoo community is just getting started.",
            'wall_mis_momentos_flash_enviado_titulo_en' => 'Moment sent!',
            'wall_mis_momentos_flash_enviado_body_en'   => "The Kinvoo team reviews it and publishes it in Community if everything looks good.",
            'wall_mis_momentos_cta_publicar_en'         => 'Publish moment',
            'wall_mis_momentos_empty_state_en'          => "You haven't shared any moment yet. Use the form above to publish your first.",

            // Mis beneficios EN
            'beneficios_header_titulo_en'   => 'My benefits',
            'beneficios_guia_titulo_en'     => '🌱 Everything your plan gives you',
            'beneficios_guia_body_en'       => "Your Kinvoo plan translates into real care. Here you see what you have active and how much you've used.",
            'beneficios_activos_titulo_en'  => "What's active",
            'beneficios_upgrade_texto_en'   => 'Missing something? Upgrade to unlock more benefits.',
            'beneficios_upgrade_cta_en'     => 'See plans →',

            // Mi equipo EN
            'equipo_pagina_titulo_en'     => 'My team',
            'equipo_guia_titulo_en'       => '💚 How is your team built?',
            'equipo_guia_intro_en'        => "Invite your coaches by email. As soon as they accept, their care starts adding to your wellbeing panel.",
            'equipo_eval_pregunta_en'     => "How do you rate your team's wellbeing this period?",
            'equipo_invitar_titulo_en'    => 'Add someone to the team',
            'equipo_listado_titulo_en'    => 'Team members',
            'equipo_empty_state_en'       => "You don't have team members yet. Start by inviting a professional by email.",

            // Login EN
            'login_flash_cuenta_eliminada_titulo_en' => 'Your account was deleted.',
            'login_flash_cuenta_eliminada_body_en'   => "Your profile, contacts and files were removed. We're glad you were part of Kinvoo. If you want to come back, you can create a new account.",
            'login_flash_admin_baja_titulo_en'       => 'Your account is no longer active.',
            'login_flash_admin_baja_body_en'         => "Kinvoo deactivated your account. If you think this is a mistake, write to",

            // Menú EN
            'nav_coach_mi_perfil_en'     => 'My profile',
            'nav_coach_contactos_en'     => 'Contacts',
            'nav_coach_oportunidades_en' => 'Opportunities',
            'nav_coach_desarrollo_en'    => 'Development',

            // Ofertas EN
            'ofertas_index_titulo_en'          => 'Opportunities',
            'ofertas_index_guia_texto1_en'     => "Here you'll see the vacancies studios post. You can apply once your profile is complete — so studios know who you are.",
            'ofertas_index_empty_en'           => 'No offers published right now.',
            'mis_ofertas_titulo_en'            => 'My opportunities',
            'mis_ofertas_intro_en'             => 'Here you publish vacancies, manage applications and change the status of each one.',
            'mis_ofertas_empty_postulaciones_en' => "No applications yet. When a coach applies, they'll show up here and you'll get an email.",
            'mis_ofertas_empty_general_en'     => 'You haven\'t published offers yet. Press "+ Publish offer" above to start.',
            'ofertas_form_publicar_cta_en'     => 'Publish offer',
            'mis_postulaciones_titulo_en'      => 'My applications',
            'mis_postulaciones_empty_en'       => "You haven't applied to any offer yet.",

            // Perfil EN
            'perfil_edit_titulo_en'          => 'My professional profile',
            'perfil_edit_intro_en'           => 'Complete your profile and save to send it for review.',
            'perfil_edit_estado_revision_en' => "When you complete your profile, the Kinvoo team will review and publish it. We'll email you.",
            'perfil_edit_cta_guardar_en'     => 'Save and continue →',

            // Company EN
            'company_edit_titulo_en'                 => 'My company',
            'company_cuenta_revision_titulo_en'      => '⏳ Your account is under review',
            'company_cuenta_revision_descripcion_en' => 'Complete your studio profile and save it. Once Kinvoo approves, you can post vacancies and contact talent.',

            // Talento EN
            'talento_index_titulo_en'      => 'Find talent',
            'talento_index_subtitulo_en'   => 'Filter by discipline, location or modality.',
            'talento_index_empty_titulo_en'=> 'No results',
            'talento_show_cta_login_en'    => 'Sign in as contractor to contact',

            // Contenido EN
            'contenido_index_empty_en'        => 'No content available for your account in this category.',
            'contenido_upsell_activa_plan_en' => 'Activate your plan to see this level →',
            'contenido_form_titulo_en'        => 'Edit content / New content',
            'contenido_form_boton_publicar_en'=> 'Publish content',
            'mis_contenidos_titulo_en'        => 'My development',
            'mis_contenidos_intro_en'         => 'Upload guides, videos or links to share with the Kinvoo community. All content you upload is visible to coaches and studios in the community.',
            'mis_contenidos_empty_state_en'   => "You haven't uploaded content yet. Share your first video, PDF or link with the community.",

            // Expediente EN
            'expediente_header_titulo_en'         => 'My record',
            'expediente_intro_descripcion_en'     => "Here you see the status of your care within Kinvoo — which benefits are active and what you've used.",
            'expediente_charlas_titulo_en'        => "Talks you've attended",
            'expediente_charlas_empty_state_en'   => "You don't have talks or training registered yet. Kinvoo will add them as you participate.",

            // Notificaciones EN
            'notificaciones_header_titulo_en' => 'Notifications',
            'notificaciones_empty_state_en'   => "You don't have notifications yet.",

            // Reportes admin EN
            'admin_reporte_coaches_titulo_en'      => 'Report · Coach activity in Development',
            'admin_reporte_coaches_modal_titulo_en'=> 'Content history — :name',
            'admin_reporte_coaches_empty_en'       => 'No coaches with activity yet',
            'admin_reporte_conversion_titulo_en'   => 'Report · User conversion',
            'admin_reporte_conversion_empty_en'    => 'No users registered',
            'admin_reporte_estudios_titulo_en'     => 'Report · Activity per studio',
            'admin_reporte_estudios_empty_en'      => 'No studios registered',

            // Landing/public EN
            'nav_unete_cta_en' => 'Join',
        ];
    }

    /** Mapa de overrides guardados en BD (cacheado 1h). Tolerante si la tabla no existe aún. */
    public static function overrides(): array
    {
        try {
            // TTL de seguridad: si alguien edita `settings` por fuera de set(), se auto-sana en 1h.
            return Cache::remember(self::CACHE_KEY, 3600, fn () => static::query()->pluck('value', 'key')->all());
        } catch (\Throwable $e) {
            report($e); // no romper la landing, pero dejar rastro del fallo real
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
