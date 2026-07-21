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
            'footer_copy' => '© '.date('Y').' | Kinvoo Wellness - Todos los derechos reservados',

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
            'session_topic_3_en' => 'Wellness of the wellness talent',
            'session_topic_4_en' => 'Benefits and professional backing',
            'session_topic_5_en' => 'The future of work and wellbeing',

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
            'footer_copy_en' => '© '.date('Y').' | Kinvoo Wellness - All rights reserved',

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
