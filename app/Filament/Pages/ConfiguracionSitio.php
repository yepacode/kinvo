<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\HtmlString;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ConfiguracionSitio extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'Configuración del sitio';

    protected static ?string $title = 'Configuración del sitio';

    protected static ?int $navigationSort = 9;

    protected static string $view = 'filament.pages.configuracion-sitio';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(SiteSetting::allValues());
    }

    private function enfasisHint(): string
    {
        return 'Usa *palabra* para resaltarla (itálica/color). Los saltos de línea se respetan.';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make()->tabs([
                    Tabs\Tab::make('SEO')->icon('heroicon-o-magnifying-glass')->schema([
                        TextInput::make('seo_title')->label('Título (title)')->maxLength(70)
                            ->helperText('Aparece en la pestaña del navegador y en Google.'),
                        Textarea::make('seo_description')->label('Meta descripción')->rows(3)->maxLength(180)
                            ->helperText('Resumen para Google (ideal 150-160 caracteres).'),
                        FileUpload::make('seo_og_image')->label('Imagen para compartir (Open Graph)')
                            ->image()->disk('public')->directory('landing')->imageEditor(),
                    ]),

                    Tabs\Tab::make('Marca')->icon('heroicon-o-sparkles')->schema([
                        TextInput::make('brand_name')->label('Nombre de marca'),
                        TextInput::make('brand_tagline')->label('Tagline'),
                    ]),

                    Tabs\Tab::make('Hero')->icon('heroicon-o-photo')->schema([
                        TextInput::make('hero_eyebrow')->label('Antetítulo'),
                        Textarea::make('hero_title')->label('Título principal')->rows(3)->helperText($this->enfasisHint()),
                        Textarea::make('hero_body')->label('Texto')->rows(3),
                        TextInput::make('hero_cta1')->label('Botón principal'),
                        TextInput::make('hero_cta2')->label('Botón secundario'),
                        TextInput::make('hero_pill')->label('Etiqueta sobre la foto'),
                        Toggle::make('hero_pill_visible')
                            ->label('Mostrar la etiqueta sobre la foto')
                            ->helperText('Desactívalo si NO quieres que aparezca ese texto sobre la imagen del hero.')
                            ->formatStateUsing(fn ($state) => filter_var($state ?? true, FILTER_VALIDATE_BOOLEAN))
                            ->dehydrateStateUsing(fn ($state) => $state ? '1' : '0'),
                        FileUpload::make('hero_image')->label('Foto del hero')
                            ->image()->disk('public')->directory('landing')->imageEditor(),
                    ]),

                    Tabs\Tab::make('Misión')->icon('heroicon-o-flag')->schema([
                        TextInput::make('mission_label')->label('Etiqueta'),
                        Textarea::make('mission_text')->label('Texto')->rows(3)->helperText($this->enfasisHint()),
                    ]),

                    Tabs\Tab::make('Pilares')->icon('heroicon-o-squares-2x2')->schema([
                        TextInput::make('pillars_label')->label('Etiqueta'),
                        TextInput::make('pillars_heading')->label('Encabezado'),
                        ...$this->camposPilar(1),
                        ...$this->camposPilar(2),
                        ...$this->camposPilar(3),
                        ...$this->camposPilar(4),
                    ]),

                    Tabs\Tab::make('Sessions')->icon('heroicon-o-chat-bubble-left-right')->schema([
                        TextInput::make('sessions_label')->label('Etiqueta'),
                        Textarea::make('sessions_heading')->label('Encabezado')->rows(2)->helperText($this->enfasisHint()),
                        Textarea::make('sessions_body')->label('Texto')->rows(3),
                        TextInput::make('sessions_cta')->label('Botón'),
                        TextInput::make('session_topic_1')->label('Tema 1'),
                        TextInput::make('session_topic_2')->label('Tema 2'),
                        TextInput::make('session_topic_3')->label('Tema 3'),
                        TextInput::make('session_topic_4')->label('Tema 4'),
                        TextInput::make('session_topic_5')->label('Tema 5'),
                    ]),

                    Tabs\Tab::make('Para quién')->icon('heroicon-o-user-group')->schema([
                        TextInput::make('forwho_label')->label('Etiqueta'),
                        Textarea::make('forwho_heading')->label('Encabezado')->rows(2)->helperText($this->enfasisHint()),
                        Textarea::make('forwho_body')->label('Texto')->rows(3),
                        ...$this->camposTarjeta(1),
                        ...$this->camposTarjeta(2),
                        ...$this->camposTarjeta(3),
                    ]),

                    Tabs\Tab::make('Cita')->icon('heroicon-o-chat-bubble-bottom-center-text')->schema([
                        Textarea::make('quote_text')->label('Cita')->rows(3)->helperText($this->enfasisHint()),
                        TextInput::make('quote_attr')->label('Autor'),
                    ]),

                    Tabs\Tab::make('Únete')->icon('heroicon-o-user-plus')->schema([
                        TextInput::make('join_label')->label('Etiqueta'),
                        Textarea::make('join_heading')->label('Encabezado')->rows(2)->helperText($this->enfasisHint()),
                        Textarea::make('join_body')->label('Texto')->rows(3),
                        TextInput::make('join_cta')->label('Botón principal'),
                        TextInput::make('join_note')->label('Nota'),
                        TextInput::make('join_tog1')->label('Opción 1'),
                        TextInput::make('join_tog2')->label('Opción 2'),
                        TextInput::make('join_tog3')->label('Opción 3'),
                    ]),

                    Tabs\Tab::make('Pie e imágenes')->icon('heroicon-o-bars-3-bottom-left')->schema([
                        TextInput::make('footer_tag')->label('Tagline del pie'),
                        TextInput::make('footer_copy')->label('Copyright'),
                        Placeholder::make('divider_help')
                            ->label('')
                            ->content(new HtmlString(
                                '<p style="font-size:0.875rem;color:#6E6E5F;">'
                                .'Banda horizontal con foto entre los "Pilares" y "Sessions" en la landing. '
                                .'El antetítulo y el título aparecen sobre la imagen (esquina inferior).'
                                .'</p>'
                            )),
                        FileUpload::make('divider_image')->label('Foto divisora')
                            ->image()->disk('public')->directory('landing')->imageEditor(),
                        TextInput::make('divider_eyebrow')->label('Antetítulo (sobre la imagen)')
                            ->helperText('Ej. "Kinvoo · Comunidad". Aparece pequeño arriba del título.'),
                        Textarea::make('divider_title')->label('Título (sobre la imagen)')->rows(2)
                            ->helperText('Ej. "Where talent\nmeets fitness.". Usa Enter para saltar de línea.'),
                    ]),

                    Tabs\Tab::make('Fondo')->icon('heroicon-o-paint-brush')->schema([
                        Placeholder::make('background_help')
                            ->label('')
                            ->content(new HtmlString(
                                '<p style="font-size:0.875rem;color:#6E6E5F;">'
                                .'Controla el fondo de todas las páginas públicas del sitio.<br>'
                                .'Si subes una imagen, se muestra sobre el color. Si no, solo el color.'
                                .'</p>'
                            )),
                        ColorPicker::make('background_color')
                            ->label('Color de fondo')
                            ->helperText('Color base del sitio. Ej. #F7F4EE (crema). Se aplica a la landing y a todas las páginas públicas.'),
                        FileUpload::make('background_image')
                            ->label('Imagen de fondo (opcional)')
                            ->image()
                            ->disk('public')
                            ->directory('landing')
                            ->imageEditor()
                            ->helperText('Se coloca encima del color, cubriendo el ancho de la pantalla y fija al scroll. Déjala vacía para fondo liso.'),
                    ]),

                    Tabs\Tab::make('Cuenta y sesión')->icon('heroicon-o-user-circle')->schema([
                        Placeholder::make('auth_help')
                            ->label('')
                            ->content(new HtmlString(
                                '<p style="font-size:0.875rem;color:#6E6E5F;">'
                                .'Textos narrativos de las pantallas <strong>/register</strong>, <strong>/login</strong>, '
                                .'<strong>/forgot-password</strong>, <strong>/reset-password</strong> y <strong>/verify-email</strong>. '
                                .'Los labels de formulario (Correo, Contraseña, botones) se mantienen automáticos por idioma; '
                                .'aquí editas los títulos, subtítulos y descripciones.'
                                .'</p>'
                            )),
                        // Registro
                        TextInput::make('register_title')->label('Registro · Título'),
                        TextInput::make('register_subtitle')->label('Registro · Subtítulo'),
                        TextInput::make('register_type_label')->label('Registro · Encabezado del selector de tipo'),
                        Textarea::make('register_type_help')->label('Registro · Ayuda del selector')->rows(2),
                        TextInput::make('register_talent_emoji')->label('Registro · Tarjeta Talento — Emoji')->maxLength(8),
                        TextInput::make('register_talent_title')->label('Registro · Tarjeta Talento — Título'),
                        Textarea::make('register_talent_body')->label('Registro · Tarjeta Talento — Descripción')->rows(3),
                        TextInput::make('register_studio_emoji')->label('Registro · Tarjeta Estudio — Emoji')->maxLength(8),
                        TextInput::make('register_studio_title')->label('Registro · Tarjeta Estudio — Título'),
                        Textarea::make('register_studio_body')->label('Registro · Tarjeta Estudio — Descripción')->rows(3),
                        // Login
                        TextInput::make('login_title')->label('Login · Título'),
                        TextInput::make('login_subtitle')->label('Login · Subtítulo'),
                        // Recuperar
                        TextInput::make('forgot_title')->label('Recuperar contraseña · Título'),
                        Textarea::make('forgot_body')->label('Recuperar contraseña · Texto')->rows(2),
                        // Reset
                        TextInput::make('reset_title')->label('Nueva contraseña · Título'),
                        // Verificar
                        TextInput::make('verify_title')->label('Verificar correo · Título'),
                        Textarea::make('verify_body')->label('Verificar correo · Texto')->rows(4),
                    ]),

                    Tabs\Tab::make('Comparte un momento')->icon('heroicon-o-camera')->schema([
                        Placeholder::make('momento_help')
                            ->label('')
                            ->content(new HtmlString(
                                '<p style="font-size:0.875rem;color:#6E6E5F;">'
                                .'Textos de la página <strong>/mis-momentos</strong> (Wall del estudio en Comunidad). '
                                .'Se muestran al estudio antes de subir foto/video.'
                                .'</p>'
                            )),
                        TextInput::make('momento_pagina_titulo')->label('Título de la página'),
                        TextInput::make('momento_bloque_titulo')->label('Título del bloque de bienvenida'),
                        Textarea::make('momento_subtitulo')->label('Sub-frase destacada')->rows(2),
                        Textarea::make('momento_bloque_body')->label('Cuerpo del bloque')->rows(4),
                        TextInput::make('momento_bullet_1')->label('Viñeta 1'),
                        TextInput::make('momento_bullet_2')->label('Viñeta 2'),
                        Textarea::make('momento_bullet_3')->label('Viñeta 3 (cierre)')->rows(2),
                    ]),

                    Tabs\Tab::make('Desarrollo (Contenido)')->icon('heroicon-o-book-open')->schema([
                        Placeholder::make('desarrollo_help')
                            ->label('')
                            ->content(new HtmlString(
                                '<p style="font-size:0.875rem;color:#6E6E5F;">'
                                .'Textos de la página <strong>/desarrollo</strong>: título y bloque de introducción. '
                                .'Hay dos versiones del copy — una que ve el <strong>coach</strong> y otra que ve el '
                                .'<strong>estudio</strong> (el estudio piensa en su equipo, el coach en su carrera).'
                                .'</p>'
                            )),
                        TextInput::make('desarrollo_header_titulo')->label('Título de la página'),
                        TextInput::make('desarrollo_onboarding_titulo')->label('Título del bloque de bienvenida'),
                        Textarea::make('desarrollo_copy_coach_h1')->label('Copy coach · línea principal')->rows(2),
                        Textarea::make('desarrollo_copy_coach_h2')->label('Copy coach · línea secundaria')->rows(3),
                        Textarea::make('desarrollo_copy_estudio_h1')->label('Copy estudio · línea principal')->rows(2),
                        Textarea::make('desarrollo_copy_estudio_h2')->label('Copy estudio · línea secundaria')->rows(3),
                    ]),

                    Tabs\Tab::make('Membresías')->icon('heroicon-o-credit-card')->schema([
                        TextInput::make('membership_eyebrow')->label('Antetítulo'),
                        TextInput::make('membership_title')->label('Título'),
                        Textarea::make('membership_body')->label('Introducción')->rows(3),
                        TextInput::make('membership_individual_title')->label('Título grupo · Talento'),
                        TextInput::make('membership_studio_title')->label('Título grupo · Estudios'),
                        TextInput::make('membership_note')->label('Nota al pie'),
                    ]),

                    Tabs\Tab::make('Mensajes')->icon('heroicon-o-hand-raised')->schema([
                        TextInput::make('welcome_pro_title')->label('Bienvenida Profesional · Título (ES)'),
                        Textarea::make('welcome_pro_body')->label('Bienvenida Profesional · Texto (ES)')->rows(10)
                            ->helperText('Se muestra al profesional al editar su perfil. Usa "• " para viñetas.'),
                        TextInput::make('welcome_pro_title_en')->label('Bienvenida Profesional · Título (EN)'),
                        Textarea::make('welcome_pro_body_en')->label('Bienvenida Profesional · Texto (EN)')->rows(10)
                            ->helperText('Opcional. Se muestra solo cuando el usuario navega en inglés.'),
                        TextInput::make('welcome_studio_title')->label('Bienvenida Estudio · Título (ES)'),
                        Textarea::make('welcome_studio_body')->label('Bienvenida Estudio · Texto (ES)')->rows(8)
                            ->helperText('Se muestra al estudio/cliente al editar su perfil. Usa "• " para viñetas.'),
                        TextInput::make('welcome_studio_title_en')->label('Bienvenida Estudio · Título (EN)'),
                        Textarea::make('welcome_studio_body_en')->label('Bienvenida Estudio · Texto (EN)')->rows(8)
                            ->helperText('Opcional. Se muestra solo cuando el estudio navega en inglés.'),
                    ]),

                    Tabs\Tab::make('Textos de la app')->icon('heroicon-o-language')->schema([
                        Placeholder::make('app_texts_help')
                            ->label('')
                            ->content(new HtmlString(
                                '<p style="font-size:0.875rem;color:#6E6E5F;">'
                                .'Editorial de las páginas internas: <strong>títulos</strong>, <strong>estados vacíos</strong>, '
                                .'<strong>botones</strong> y <strong>mensajes de upsell</strong>. '
                                .'Agrupado por sección para no perderte. Las traducciones al inglés viven en la pestaña <strong>Idioma inglés</strong>.'
                                .'</p>'
                            )),

                        \Filament\Forms\Components\Section::make('Dashboard')->collapsible()->collapsed()->schema([
                            TextInput::make('dashboard_saludo')->label('Saludo (usa :name para el nombre)'),
                            TextInput::make('dashboard_coach_titulo_perfil')->label('Coach · Título "Tu perfil"'),
                            Textarea::make('dashboard_coach_perfil_publicado_msg')->label('Coach · Msg cuando perfil publicado')->rows(2),
                            Textarea::make('dashboard_coach_perfil_oculto_msg')->label('Coach · Msg cuando perfil oculto')->rows(2),
                            TextInput::make('dashboard_coach_cta_editar_perfil')->label('Coach · CTA editar perfil'),
                            TextInput::make('dashboard_coach_vistas_titulo')->label('Coach · Título "Quién vio tu perfil"'),
                            Textarea::make('dashboard_coach_vistas_empty')->label('Coach · Empty state vistas')->rows(2),
                            TextInput::make('dashboard_estudio_titulo')->label('Estudio · Título'),
                            Textarea::make('dashboard_estudio_descripcion')->label('Estudio · Descripción')->rows(2),
                            TextInput::make('dashboard_estudio_cta_talento')->label('Estudio · CTA buscar talento'),
                            TextInput::make('dashboard_estudio_cta_perfil')->label('Estudio · CTA editar empresa'),
                        ]),

                        \Filament\Forms\Components\Section::make('Mensajes de membresía / upsell')->collapsible()->collapsed()->schema([
                            Textarea::make('membresia_flash_directorio')->label('Flash · directorio requiere plan')->rows(2),
                            Textarea::make('membresia_flash_ofertas')->label('Flash · publicar ofertas requiere plan')->rows(2),
                            Textarea::make('membresia_flash_mas_vacantes')->label('Flash · más vacantes requieren plan')->rows(2),
                            Textarea::make('membresia_flash_contacto')->label('Flash · contactar requiere plan')->rows(2),
                            Textarea::make('membresia_flash_contenido')->label('Flash · contenido avanzado requiere plan')->rows(2),
                            Textarea::make('membresia_flash_comunidad')->label('Flash · comunidad requiere plan')->rows(2),
                            Textarea::make('membresia_flash_expediente')->label('Flash · expediente requiere plan')->rows(2),
                            TextInput::make('membresia_cuenta_revision_titulo')->label('Título cuenta en revisión'),
                            TextInput::make('membresia_cta_suscribirme')->label('CTA "Suscribirme"'),
                            Textarea::make('membresia_empty_state')->label('Empty state planes')->rows(2),
                        ]),

                        \Filament\Forms\Components\Section::make('Respaldo (telemedicina/fisio)')->collapsible()->collapsed()->schema([
                            TextInput::make('respaldo_header_titulo')->label('Título de la página'),
                            TextInput::make('respaldo_guia_titulo')->label('Guía · título'),
                            Textarea::make('respaldo_guia_body')->label('Guía · descripción')->rows(2),
                            TextInput::make('respaldo_flash_enviado_titulo')->label('Flash "Solicitud enviada"'),
                            TextInput::make('respaldo_cta_enviar')->label('CTA enviar solicitud'),
                            TextInput::make('respaldo_solicitudes_titulo')->label('Título "Mis solicitudes"'),
                            Textarea::make('respaldo_empty_state')->label('Empty state')->rows(2),
                        ]),

                        \Filament\Forms\Components\Section::make('Cuentas en revisión / suspendidas')->collapsible()->collapsed()->schema([
                            TextInput::make('pending_titulo_suspendida')->label('Título · cuenta suspendida'),
                            TextInput::make('pending_titulo_perfil_revision')->label('Título · perfil en revisión'),
                            TextInput::make('pending_titulo_cuenta_revision')->label('Título · cuenta en revisión'),
                            Textarea::make('pending_body_suspendida')->label('Body · suspendida')->rows(3),
                            Textarea::make('pending_body_perfil_pendiente')->label('Body · perfil pendiente')->rows(3),
                            Textarea::make('pending_body_cuenta_revision')->label('Body · cuenta en revisión')->rows(3),
                        ]),

                        \Filament\Forms\Components\Section::make('Oportunidades · detalle')->collapsible()->collapsed()->schema([
                            TextInput::make('ofertas_show_flash_enviada_titulo')->label('Flash · postulación enviada · título'),
                            Textarea::make('ofertas_show_flash_enviada_texto')->label('Flash · postulación enviada · texto')->rows(2),
                            Textarea::make('ofertas_show_flash_ya_postulaste')->label('Flash · ya postulaste')->rows(2),
                            TextInput::make('ofertas_show_postular_titulo')->label('Título sección Postular'),
                            Textarea::make('ofertas_show_intro_postular')->label('Intro postular')->rows(2),
                            TextInput::make('ofertas_show_cta_enviar')->label('CTA enviar postulación'),
                        ]),

                        \Filament\Forms\Components\Section::make('Pulso Kinvoo')->collapsible()->collapsed()->schema([
                            TextInput::make('pulso_coach_header_titulo')->label('Coach · Título'),
                            TextInput::make('pulso_coach_guia_titulo')->label('Coach · Guía título'),
                            Textarea::make('pulso_coach_guia_body')->label('Coach · Guía body')->rows(3),
                            TextInput::make('pulso_coach_cta_enviar')->label('Coach · CTA enviar'),
                            TextInput::make('pulso_coach_historial_titulo')->label('Coach · Título historial'),
                            Textarea::make('pulso_coach_empty_state')->label('Coach · Empty state')->rows(2),
                            TextInput::make('pulso_estudio_titulo')->label('Estudio · Título'),
                        ]),

                        \Filament\Forms\Components\Section::make('Comunidad / Momentos')->collapsible()->collapsed()->schema([
                            TextInput::make('wall_comunidad_header_titulo')->label('Comunidad · Título'),
                            TextInput::make('wall_comunidad_guia_titulo')->label('Comunidad · Guía título'),
                            Textarea::make('wall_comunidad_guia_body')->label('Comunidad · Guía body')->rows(3),
                            TextInput::make('wall_comunidad_cta_publicar')->label('Comunidad · CTA publicar'),
                            Textarea::make('wall_comunidad_empty_state')->label('Comunidad · Empty state')->rows(2),
                            TextInput::make('wall_mis_momentos_flash_enviado_titulo')->label('Mis momentos · Flash enviado título'),
                            Textarea::make('wall_mis_momentos_flash_enviado_body')->label('Mis momentos · Flash enviado body')->rows(2),
                            TextInput::make('wall_mis_momentos_cta_publicar')->label('Mis momentos · CTA publicar'),
                            Textarea::make('wall_mis_momentos_empty_state')->label('Mis momentos · Empty state')->rows(2),
                        ]),

                        \Filament\Forms\Components\Section::make('Mis beneficios')->collapsible()->collapsed()->schema([
                            TextInput::make('beneficios_header_titulo')->label('Título de la página'),
                            TextInput::make('beneficios_guia_titulo')->label('Guía título'),
                            Textarea::make('beneficios_guia_body')->label('Guía body')->rows(3),
                            TextInput::make('beneficios_activos_titulo')->label('Título "Qué tienes activo"'),
                            Textarea::make('beneficios_upgrade_texto')->label('Texto upsell (sube a plan mayor)')->rows(2),
                            TextInput::make('beneficios_upgrade_cta')->label('CTA "Ver planes"'),
                        ]),

                        \Filament\Forms\Components\Section::make('Mi equipo (estudio)')->collapsible()->collapsed()->schema([
                            TextInput::make('equipo_pagina_titulo')->label('Título de la página'),
                            TextInput::make('equipo_guia_titulo')->label('Guía título'),
                            Textarea::make('equipo_guia_intro')->label('Guía intro')->rows(3),
                            TextInput::make('equipo_eval_pregunta')->label('Pregunta de evaluación'),
                            TextInput::make('equipo_invitar_titulo')->label('Título "Agregar a alguien"'),
                            TextInput::make('equipo_listado_titulo')->label('Título "Miembros del equipo"'),
                            Textarea::make('equipo_empty_state')->label('Empty state')->rows(2),
                        ]),

                        \Filament\Forms\Components\Section::make('Login (mensajes)')->collapsible()->collapsed()->schema([
                            TextInput::make('login_flash_cuenta_eliminada_titulo')->label('Flash · cuenta eliminada · título'),
                            Textarea::make('login_flash_cuenta_eliminada_body')->label('Flash · cuenta eliminada · body')->rows(3),
                            TextInput::make('login_flash_admin_baja_titulo')->label('Flash · admin dio de baja · título'),
                            Textarea::make('login_flash_admin_baja_body')->label('Flash · admin dio de baja · body')->rows(2),
                        ]),

                        \Filament\Forms\Components\Section::make('Menú principal (coach)')->collapsible()->collapsed()->schema([
                            TextInput::make('nav_coach_mi_perfil')->label('Coach · "Mi perfil"'),
                            TextInput::make('nav_coach_contactos')->label('Coach · "Contactos"'),
                            TextInput::make('nav_coach_oportunidades')->label('Coach · "Oportunidades"'),
                            TextInput::make('nav_coach_desarrollo')->label('Coach · "Desarrollo"'),
                        ]),

                        \Filament\Forms\Components\Section::make('Oportunidades · listado, mis, form')->collapsible()->collapsed()->schema([
                            TextInput::make('ofertas_index_titulo')->label('Índice · Título'),
                            Textarea::make('ofertas_index_guia_texto1')->label('Índice · Guía texto')->rows(3),
                            Textarea::make('ofertas_index_empty')->label('Índice · Empty state')->rows(2),
                            TextInput::make('mis_ofertas_titulo')->label('Mis ofertas · Título'),
                            Textarea::make('mis_ofertas_intro')->label('Mis ofertas · Intro')->rows(2),
                            Textarea::make('mis_ofertas_empty_postulaciones')->label('Mis ofertas · Empty postulaciones')->rows(2),
                            Textarea::make('mis_ofertas_empty_general')->label('Mis ofertas · Empty general')->rows(2),
                            TextInput::make('ofertas_form_publicar_cta')->label('Form · CTA "Publicar oferta"'),
                            TextInput::make('mis_postulaciones_titulo')->label('Mis postulaciones · Título'),
                            Textarea::make('mis_postulaciones_empty')->label('Mis postulaciones · Empty state')->rows(2),
                        ]),

                        \Filament\Forms\Components\Section::make('Perfiles (coach + empresa)')->collapsible()->collapsed()->schema([
                            TextInput::make('perfil_edit_titulo')->label('Coach · Título'),
                            Textarea::make('perfil_edit_intro')->label('Coach · Intro')->rows(2),
                            Textarea::make('perfil_edit_estado_revision')->label('Coach · Msg estado revisión')->rows(3),
                            TextInput::make('perfil_edit_cta_guardar')->label('Coach · CTA guardar'),
                            TextInput::make('company_edit_titulo')->label('Empresa · Título'),
                            TextInput::make('company_cuenta_revision_titulo')->label('Empresa · Título revisión'),
                            Textarea::make('company_cuenta_revision_descripcion')->label('Empresa · Descripción revisión')->rows(3),
                        ]),

                        \Filament\Forms\Components\Section::make('Talento (listado + show)')->collapsible()->collapsed()->schema([
                            TextInput::make('talento_index_titulo')->label('Listado · Título'),
                            Textarea::make('talento_index_subtitulo')->label('Listado · Subtítulo')->rows(2),
                            TextInput::make('talento_index_empty_titulo')->label('Listado · Empty state título'),
                            TextInput::make('talento_show_cta_login')->label('Show · CTA login para contactar'),
                        ]),

                        \Filament\Forms\Components\Section::make('Desarrollo / contenido')->collapsible()->collapsed()->schema([
                            Textarea::make('contenido_index_empty')->label('Índice · Empty state')->rows(2),
                            TextInput::make('contenido_upsell_activa_plan')->label('Upsell · "Activa tu plan"'),
                            TextInput::make('contenido_form_titulo')->label('Form · Título'),
                            TextInput::make('contenido_form_boton_publicar')->label('Form · Botón publicar'),
                            TextInput::make('mis_contenidos_titulo')->label('Mis contenidos · Título'),
                            Textarea::make('mis_contenidos_intro')->label('Mis contenidos · Intro')->rows(3),
                            Textarea::make('mis_contenidos_empty_state')->label('Mis contenidos · Empty state')->rows(2),
                        ]),

                        \Filament\Forms\Components\Section::make('Expediente coach')->collapsible()->collapsed()->schema([
                            TextInput::make('expediente_header_titulo')->label('Título de la página'),
                            Textarea::make('expediente_intro_descripcion')->label('Intro descripción')->rows(3),
                            TextInput::make('expediente_charlas_titulo')->label('Título "Charlas asistidas"'),
                            Textarea::make('expediente_charlas_empty_state')->label('Empty state charlas')->rows(2),
                        ]),

                        \Filament\Forms\Components\Section::make('Notificaciones')->collapsible()->collapsed()->schema([
                            TextInput::make('notificaciones_header_titulo')->label('Título de la página'),
                            Textarea::make('notificaciones_empty_state')->label('Empty state')->rows(2),
                        ]),

                        \Filament\Forms\Components\Section::make('Reportes admin (Filament)')->collapsible()->collapsed()->schema([
                            TextInput::make('admin_reporte_coaches_titulo')->label('Coaches · Título'),
                            TextInput::make('admin_reporte_coaches_modal_titulo')->label('Coaches · Título modal (usa :name)'),
                            TextInput::make('admin_reporte_coaches_empty')->label('Coaches · Empty state'),
                            TextInput::make('admin_reporte_conversion_titulo')->label('Conversión · Título'),
                            TextInput::make('admin_reporte_conversion_empty')->label('Conversión · Empty state'),
                            TextInput::make('admin_reporte_estudios_titulo')->label('Estudios · Título'),
                            TextInput::make('admin_reporte_estudios_empty')->label('Estudios · Empty state'),
                        ]),

                        \Filament\Forms\Components\Section::make('Landing / público')->collapsible()->collapsed()->schema([
                            TextInput::make('nav_unete_cta')->label('CTA nav "Únete"'),
                        ]),
                    ]),

                    Tabs\Tab::make('Idioma inglés')->icon('heroicon-o-language')->schema([
                        Placeholder::make('en_help')
                            ->label('')
                            ->content(new HtmlString(
                                '<p style="font-size:0.875rem;color:#6E6E5F;">'
                                .'Traducciones al inglés de la landing. Se muestran cuando el usuario '
                                .'cambia el selector a EN. Si dejas un campo vacío, se muestra la versión '
                                .'en español como respaldo.'
                                .'</p>'
                            )),

                        // SEO
                        TextInput::make('seo_title_en')->label('SEO · Título')->maxLength(70),
                        Textarea::make('seo_description_en')->label('SEO · Meta descripción')->rows(3)->maxLength(180),

                        // Marca
                        TextInput::make('brand_tagline_en')->label('Marca · Tagline'),

                        // Hero
                        TextInput::make('hero_eyebrow_en')->label('Hero · Antetítulo'),
                        Textarea::make('hero_title_en')->label('Hero · Título principal')->rows(3)->helperText($this->enfasisHint()),
                        Textarea::make('hero_body_en')->label('Hero · Texto')->rows(3),
                        TextInput::make('hero_cta1_en')->label('Hero · Botón principal'),
                        TextInput::make('hero_cta2_en')->label('Hero · Botón secundario'),
                        TextInput::make('hero_pill_en')->label('Hero · Etiqueta sobre la foto'),

                        // Misión
                        TextInput::make('mission_label_en')->label('Misión · Etiqueta'),
                        Textarea::make('mission_text_en')->label('Misión · Texto')->rows(3)->helperText($this->enfasisHint()),

                        // Pilares
                        TextInput::make('pillars_label_en')->label('Pilares · Etiqueta'),
                        TextInput::make('pillars_heading_en')->label('Pilares · Encabezado'),
                        TextInput::make('pillar1_title_en')->label('Pilar 1 · Título'),
                        Textarea::make('pillar1_body_en')->label('Pilar 1 · Texto')->rows(2),
                        TextInput::make('pillar2_title_en')->label('Pilar 2 · Título'),
                        Textarea::make('pillar2_body_en')->label('Pilar 2 · Texto')->rows(2),
                        TextInput::make('pillar3_title_en')->label('Pilar 3 · Título'),
                        Textarea::make('pillar3_body_en')->label('Pilar 3 · Texto')->rows(2),
                        TextInput::make('pillar4_title_en')->label('Pilar 4 · Título'),
                        Textarea::make('pillar4_body_en')->label('Pilar 4 · Texto')->rows(2),

                        // Sessions
                        TextInput::make('sessions_label_en')->label('Sessions · Etiqueta'),
                        Textarea::make('sessions_heading_en')->label('Sessions · Encabezado')->rows(2)->helperText($this->enfasisHint()),
                        Textarea::make('sessions_body_en')->label('Sessions · Texto')->rows(3),
                        TextInput::make('sessions_cta_en')->label('Sessions · Botón'),
                        TextInput::make('session_topic_1_en')->label('Sessions · Tema 1'),
                        TextInput::make('session_topic_2_en')->label('Sessions · Tema 2'),
                        TextInput::make('session_topic_3_en')->label('Sessions · Tema 3'),
                        TextInput::make('session_topic_4_en')->label('Sessions · Tema 4'),
                        TextInput::make('session_topic_5_en')->label('Sessions · Tema 5'),

                        // Foto divisora
                        TextInput::make('divider_eyebrow_en')->label('Foto divisora · Antetítulo'),
                        Textarea::make('divider_title_en')->label('Foto divisora · Título')->rows(2),

                        // Cuenta y sesión
                        TextInput::make('register_title_en')->label('Registro · Título'),
                        TextInput::make('register_subtitle_en')->label('Registro · Subtítulo'),
                        TextInput::make('register_type_label_en')->label('Registro · Encabezado del selector'),
                        Textarea::make('register_type_help_en')->label('Registro · Ayuda del selector')->rows(2),
                        TextInput::make('register_talent_emoji_en')->label('Registro · Talento — Emoji')->maxLength(8),
                        TextInput::make('register_talent_title_en')->label('Registro · Talento — Título'),
                        Textarea::make('register_talent_body_en')->label('Registro · Talento — Descripción')->rows(3),
                        TextInput::make('register_studio_emoji_en')->label('Registro · Estudio — Emoji')->maxLength(8),
                        TextInput::make('register_studio_title_en')->label('Registro · Estudio — Título'),
                        Textarea::make('register_studio_body_en')->label('Registro · Estudio — Descripción')->rows(3),
                        TextInput::make('login_title_en')->label('Login · Título'),
                        TextInput::make('login_subtitle_en')->label('Login · Subtítulo'),
                        TextInput::make('forgot_title_en')->label('Recuperar contraseña · Título'),
                        Textarea::make('forgot_body_en')->label('Recuperar contraseña · Texto')->rows(2),
                        TextInput::make('reset_title_en')->label('Nueva contraseña · Título'),
                        TextInput::make('verify_title_en')->label('Verificar correo · Título'),
                        Textarea::make('verify_body_en')->label('Verificar correo · Texto')->rows(4),

                        // Para quién
                        TextInput::make('forwho_label_en')->label('Para quién · Etiqueta'),
                        Textarea::make('forwho_heading_en')->label('Para quién · Encabezado')->rows(2)->helperText($this->enfasisHint()),
                        Textarea::make('forwho_body_en')->label('Para quién · Texto')->rows(3),
                        TextInput::make('card1_label_en')->label('Tarjeta 1 · Etiqueta'),
                        TextInput::make('card1_title_en')->label('Tarjeta 1 · Título'),
                        Textarea::make('card1_body_en')->label('Tarjeta 1 · Texto')->rows(2),
                        TextInput::make('card2_label_en')->label('Tarjeta 2 · Etiqueta'),
                        TextInput::make('card2_title_en')->label('Tarjeta 2 · Título'),
                        Textarea::make('card2_body_en')->label('Tarjeta 2 · Texto')->rows(2),
                        TextInput::make('card3_label_en')->label('Tarjeta 3 · Etiqueta'),
                        TextInput::make('card3_title_en')->label('Tarjeta 3 · Título'),
                        Textarea::make('card3_body_en')->label('Tarjeta 3 · Texto')->rows(2),

                        // Cita
                        Textarea::make('quote_text_en')->label('Cita · Texto')->rows(3)->helperText($this->enfasisHint()),
                        TextInput::make('quote_attr_en')->label('Cita · Autor'),

                        // Únete
                        TextInput::make('join_label_en')->label('Únete · Etiqueta'),
                        Textarea::make('join_heading_en')->label('Únete · Encabezado')->rows(2)->helperText($this->enfasisHint()),
                        Textarea::make('join_body_en')->label('Únete · Texto')->rows(3),
                        TextInput::make('join_cta_en')->label('Únete · Botón principal'),
                        TextInput::make('join_note_en')->label('Únete · Nota'),
                        TextInput::make('join_tog1_en')->label('Únete · Opción 1'),
                        TextInput::make('join_tog2_en')->label('Únete · Opción 2'),
                        TextInput::make('join_tog3_en')->label('Únete · Opción 3'),

                        // Pie
                        TextInput::make('footer_tag_en')->label('Pie · Tagline'),
                        TextInput::make('footer_copy_en')->label('Pie · Copyright'),

                        // Membresías
                        TextInput::make('membership_eyebrow_en')->label('Membresías · Antetítulo'),
                        TextInput::make('membership_title_en')->label('Membresías · Título'),
                        Textarea::make('membership_body_en')->label('Membresías · Introducción')->rows(3),
                        TextInput::make('membership_individual_title_en')->label('Membresías · Título grupo · Talento'),
                        TextInput::make('membership_studio_title_en')->label('Membresías · Título grupo · Estudios'),
                        TextInput::make('membership_note_en')->label('Membresías · Nota al pie'),

                        // Legales EN (título y updated; cuerpo se mantiene en ES por jurisdicción mexicana)
                        TextInput::make('legal_privacy_title_en')->label('Aviso de Privacidad · Título (EN)'),
                        TextInput::make('legal_privacy_updated_en')->label('Aviso de Privacidad · Fecha/nota (EN)'),
                        TextInput::make('legal_terms_title_en')->label('Términos y Condiciones · Título (EN)'),
                        TextInput::make('legal_terms_updated_en')->label('Términos y Condiciones · Fecha/nota (EN)'),
                    ]),

                    Tabs\Tab::make('Legales')->icon('heroicon-o-scale')->schema([
                        TextInput::make('legal_privacy_title')->label('Aviso de Privacidad · Título'),
                        TextInput::make('legal_privacy_updated')->label('Aviso de Privacidad · Fecha/nota'),
                        Textarea::make('legal_privacy_body')->label('Aviso de Privacidad · Contenido')->rows(14)
                            ->helperText('Separa párrafos con una línea en blanco. Un párrafo que empiece con "1. Título" se muestra como encabezado.'),
                        TextInput::make('legal_terms_title')->label('Términos y Condiciones · Título'),
                        TextInput::make('legal_terms_updated')->label('Términos y Condiciones · Fecha/nota'),
                        Textarea::make('legal_terms_body')->label('Términos y Condiciones · Contenido')->rows(20)
                            ->helperText('Separa párrafos con una línea en blanco. Un párrafo que empiece con "1. Título" se muestra como encabezado.'),
                    ]),
                ])->columnSpanFull(),
            ])
            ->statePath('data');
    }

    private function camposPilar(int $n): array
    {
        return [
            TextInput::make("pillar{$n}_title")->label("Pilar $n · Título"),
            Textarea::make("pillar{$n}_body")->label("Pilar $n · Texto")->rows(2),
        ];
    }

    private function camposTarjeta(int $n): array
    {
        return [
            TextInput::make("card{$n}_label")->label("Tarjeta $n · Etiqueta"),
            TextInput::make("card{$n}_title")->label("Tarjeta $n · Título"),
            Textarea::make("card{$n}_body")->label("Tarjeta $n · Texto")->rows(2),
        ];
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            SiteSetting::set($key, $value);
        }

        Notification::make()->title('Cambios guardados')->success()->send();
    }
}
