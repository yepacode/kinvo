<?php

use App\Http\Controllers\Billing\CheckoutController;
use App\Http\Controllers\Billing\WebhookController;
use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MembresiaController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\ProfessionalProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RsvpController;
use App\Http\Controllers\SaveController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\TalentoController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\WellnessController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// SEO técnico
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

// Selector de idioma (guarda cookie `locale` y vuelve a la vista anterior).
Route::post('/idioma/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

// Membresías (planes públicos).
Route::get('/membresias', [MembresiaController::class, 'index'])->name('membresias.index');

// Páginas legales (públicas, editables desde el panel).
Route::get('/aviso-de-privacidad', [LegalController::class, 'privacidad'])->name('legal.privacidad');
Route::get('/terminos-y-condiciones', [LegalController::class, 'terminos'])->name('legal.terminos');

// Descarga del adjunto privado de certificaciones (solo admin; validado en el controller).
Route::get('/panel/certificacion/{professionalProfile}', [ProfessionalProfileController::class, 'certificacion'])
    ->middleware('auth')->name('admin.certificacion');

// Directorio y perfiles de talento — PRIVADOS: solo estudios con membresía vigente
// y el admin (el profesional puede ver su propio perfil). No visibles al público.
Route::middleware(['auth', 'cuenta.activa', 'acceso.directorio', 'nocache'])->group(function () {
    Route::get('/talento', [TalentoController::class, 'index'])->name('talento.index');
    Route::get('/talento/{professionalProfile:slug}', [TalentoController::class, 'show'])->name('talento.show');
});

// Perfil del estudio — privado: solo usuarios autenticados (no público general).
// El perfil de estudio también es privado: solo contratantes con membresía y
// admin lo ven. Cliente pidió que "los perfiles no sean públicos para nadie".
Route::get('/estudio/{companyProfile:slug}', [CompanyProfileController::class, 'show'])
    ->middleware(['auth', 'cuenta.activa', 'acceso.directorio', 'nocache'])->name('estudio.show');

// Aviso de cuenta pendiente/suspendida (no pasa por el gate de estado).
Route::get('/cuenta/pendiente', function () {
    return view('auth.pending');
})->middleware(['auth', 'nocache'])->name('account.pending');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'cuenta.activa', 'nocache'])->name('dashboard');

// Ajustes de cuenta (Breeze) — accesible aunque la cuenta esté pendiente.
Route::middleware(['auth', 'nocache'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Onboarding / edición de perfil — accesible aunque la cuenta esté pendiente,
// para que el usuario pueda LLENAR su perfil antes de la (única) aprobación.
Route::middleware(['auth', 'nocache'])->group(function () {
    // Wizard del profesional: bienvenida → perfil → confirmación.
    Route::get('/mi-perfil/bienvenida', [ProfessionalProfileController::class, 'bienvenida'])->name('professional.bienvenida');
    Route::get('/mi-perfil', [ProfessionalProfileController::class, 'edit'])->name('professional.profile.edit');
    Route::put('/mi-perfil', [ProfessionalProfileController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('professional.profile.update');
    Route::get('/mi-perfil/enviado', [ProfessionalProfileController::class, 'enviado'])->name('professional.enviado');

    // Wizard del estudio: bienvenida → perfil → confirmación.
    Route::get('/mi-empresa/bienvenida', [CompanyProfileController::class, 'bienvenida'])->name('company.bienvenida');
    Route::get('/mi-empresa', [CompanyProfileController::class, 'edit'])->name('company.profile.edit');
    Route::put('/mi-empresa', [CompanyProfileController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('company.profile.update');
    Route::get('/mi-empresa/enviado', [CompanyProfileController::class, 'enviado'])->name('company.enviado');
});

// Áreas del producto — requieren cuenta ACTIVA (aprobada).
Route::middleware(['auth', 'cuenta.activa', 'nocache'])->group(function () {
    // Bandeja de contactos recibidos (profesional).
    Route::get('/mis-contactos', [ContactController::class, 'recibidos'])->name('professional.contactos');
    Route::post('/mis-contactos/{contact}/me-interesa', [ContactController::class, 'marcarInteresado'])
        ->middleware('throttle:20,1')
        ->name('professional.contactos.interesado');

    // Contactar a un profesional (solo contratantes, validado en el controller). Con rate limit anti-spam.
    Route::get('/talento/{professionalProfile:slug}/contactar', [ContactController::class, 'create'])
        ->middleware('membresia')->name('contacto.create');
    Route::post('/talento/{professionalProfile:slug}/contactar', [ContactController::class, 'store'])
        ->middleware(['membresia:plan-necesario-contacto', 'throttle:8,1'])->name('contacto.store');

    // Notificaciones (campana)
    Route::get('/notificaciones', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notificaciones/leer-todo', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::get('/notificaciones/{id}/abrir', [NotificationController::class, 'open'])->name('notifications.open');

    // Guardados / favoritos
    // Guardados: función del directorio → solo estudios con membresía + admin.
    Route::get('/guardados', [SaveController::class, 'index'])->middleware('acceso.directorio')->name('saves.index');
    Route::post('/talento/{professionalProfile:slug}/guardar', [SaveController::class, 'toggleProfile'])
        ->middleware('acceso.directorio')->name('saves.toggleProfile');
});

// ============================================================
// Fase 2 · Billing (checkout, webhook, fake sandbox)
// ============================================================
Route::middleware(['auth', 'cuenta.activa', 'nocache'])->group(function () {
    Route::post('/suscripcion/{plan}', [CheckoutController::class, 'start'])
        ->middleware('throttle:6,1')
        ->name('billing.start');
    Route::get('/suscripcion/exitosa', [CheckoutController::class, 'exitosa'])
        ->name('billing.exitosa');
    Route::get('/suscripcion/fallida', [CheckoutController::class, 'fallida'])
        ->name('billing.fallida');
    // El propio user cancela su suscripción (Fase 2 · UX + Seguridad MED-2 agente 8).
    Route::post('/suscripcion/cancelar', [CheckoutController::class, 'cancelarPropia'])
        ->middleware('throttle:6,1')
        ->name('billing.cancelar');

    // Fake gateway solo — pantalla de simulación de pago.
    Route::get('/billing/fake-checkout/{token}', [CheckoutController::class, 'fakeCheckout'])
        ->name('billing.fake.checkout');
    Route::post('/billing/fake-checkout/{token}/confirmar', [CheckoutController::class, 'fakeConfirm'])
        ->name('billing.fake.confirm');
});

// Webhook público (sin auth, la firma HMAC lo protege).
Route::post('/webhooks/billing', [WebhookController::class, 'handle'])
    ->name('billing.webhook');

// RSVP público (link firmado por invitado en el correo de invitación a sesiones).
// MED-G10 · GET solo muestra confirmación; POST muta el estado (anti-prefetch).
Route::get('/rsvp/{token}', [RsvpController::class, 'responder'])
    ->name('rsvp.responder');
Route::post('/rsvp/{token}', [RsvpController::class, 'confirmar'])
    ->middleware('throttle:20,1')
    ->name('rsvp.confirmar');

// ============================================================
// Fase 2 · Hito 3 — Producto y bolsa de trabajo
// ============================================================
Route::middleware(['auth', 'cuenta.activa', 'nocache'])->group(function () {
    // Bolsa de trabajo (2.10) — renombrada "Oportunidades" (H2 · petición cliente).
    // Se mantienen los NAMES `ofertas.*` para no cambiar 20+ archivos que hacen
    // route('ofertas.index'); las URLs viejas /ofertas y /mis-ofertas se
    // redirigen 301 más abajo para no romper bookmarks.
    Route::get('/oportunidades', [OfferController::class, 'index'])->name('ofertas.index');
    Route::get('/oportunidades/{offer:slug}', [OfferController::class, 'show'])->name('ofertas.show');
    Route::post('/oportunidades/{offer:slug}/postular', [OfferController::class, 'postular'])
        ->middleware('throttle:6,1')->name('ofertas.postular');
    Route::get('/mis-postulaciones', [OfferController::class, 'misPostulaciones'])->name('ofertas.mis-postulaciones');
    Route::get('/mis-oportunidades', [OfferController::class, 'misOfertas'])->name('ofertas.mis-ofertas');
    Route::post('/postulaciones/{application}/estado', [OfferController::class, 'cambiarEstado'])
        ->name('ofertas.postulacion.estado');

    // H6 · CRUD del estudio: SIN gate global de membresía. El controller
    // aplica la regla free (1 vacante activa + expira 60d) vs. paid (ilimitado).
    Route::get('/mis-oportunidades/nueva', [OfferController::class, 'crear'])->name('ofertas.crear');
    Route::post('/mis-oportunidades', [OfferController::class, 'guardar'])
        ->middleware('throttle:6,1')->name('ofertas.guardar');
    Route::get('/mis-oportunidades/{oferta}/editar', [OfferController::class, 'editar'])->name('ofertas.editar');
    Route::put('/mis-oportunidades/{oferta}', [OfferController::class, 'actualizar'])
        ->middleware('throttle:6,1')->name('ofertas.actualizar');
    // Cambio de estado y cierre no requieren membresía activa (permite al
    // estudio administrar oportunidades antiguas aunque haya vencido su plan).
    // HIGH-18 · throttle para evitar spam al toggle publicar/despublicar/cerrar.
    Route::post('/mis-oportunidades/{oferta}/estado', [OfferController::class, 'cambiarEstadoOferta'])
        ->middleware('throttle:20,1')
        ->name('ofertas.cambiar-estado');
    Route::delete('/mis-oportunidades/{oferta}', [OfferController::class, 'eliminar'])
        ->name('ofertas.eliminar');

    // Desarrollo (2.9) — renombrado "Desarrollo" (H2 · petición cliente).
    // Names `contenido.*` se mantienen por la misma razón.
    Route::get('/desarrollo', [ContentController::class, 'index'])->name('contenido.index');
    Route::get('/desarrollo/{content:slug}', [ContentController::class, 'show'])->name('contenido.show');

    // CRUD del estudio: subir su propio contenido (visible a todos los usuarios activos).
    Route::get('/mi-desarrollo', [ContentController::class, 'misContenidos'])->name('contenido.mis-contenidos');
    Route::middleware(['membresia.activa'])->group(function () {
        Route::get('/mi-desarrollo/nuevo', [ContentController::class, 'crear'])->name('contenido.crear');
        Route::post('/mi-desarrollo', [ContentController::class, 'guardar'])
            ->middleware('throttle:6,1')->name('contenido.guardar');
        Route::get('/mi-desarrollo/{contenido}/editar', [ContentController::class, 'editar'])->name('contenido.editar');
        Route::put('/mi-desarrollo/{contenido}', [ContentController::class, 'actualizar'])
            ->middleware('throttle:6,1')->name('contenido.actualizar');
    });
    Route::delete('/mi-desarrollo/{contenido}', [ContentController::class, 'eliminar'])->name('contenido.eliminar');

    // H6/M2 · Mis beneficios (coach paid).
    Route::get('/mis-beneficios', [\App\Http\Controllers\BeneficiosController::class, 'index'])
        ->name('beneficios.index');

    // H6/M3 · Respaldo (telemedicina + fisio).
    Route::get('/mi-respaldo', [\App\Http\Controllers\RespaldoController::class, 'index'])
        ->name('respaldo.index');
    Route::post('/mi-respaldo/solicitar', [\App\Http\Controllers\RespaldoController::class, 'solicitar'])
        ->middleware('throttle:6,1')->name('respaldo.solicitar');

    // H6/M4 · Encuesta de Pulso Kinvoo.
    Route::get('/encuesta-pulso', [\App\Http\Controllers\PulseController::class, 'coach'])
        ->name('pulso.coach');
    Route::post('/encuesta-pulso', [\App\Http\Controllers\PulseController::class, 'guardar'])
        ->middleware('throttle:6,1')->name('pulso.guardar');
    Route::get('/pulso-equipo', [\App\Http\Controllers\PulseController::class, 'estudio'])
        ->name('pulso.estudio');

    // H4 · Wall "Comparte un momento" (petición cliente).
    Route::get('/comunidad', [\App\Http\Controllers\WallController::class, 'comunidad'])->name('wall.comunidad');
    Route::get('/mis-momentos', [\App\Http\Controllers\WallController::class, 'misMomentos'])->name('wall.mis-momentos');
    Route::post('/mis-momentos', [\App\Http\Controllers\WallController::class, 'guardar'])
        ->middleware('throttle:6,1')->name('wall.guardar');
    Route::delete('/mis-momentos/{post}', [\App\Http\Controllers\WallController::class, 'archivar'])
        ->name('wall.archivar');

    // LOW-14 · Redirects 301 MOVIDOS fuera del grupo `auth` (más abajo).
    // Antes, un bookmark público a /ofertas o un enlace en un correo viejo
    // llevaba al login (por estar dentro de `auth`) en vez de al 301 al
    // path nuevo — rompía la promesa del rename.

    // Expediente coach (2.11)
    Route::get('/mi-expediente', [WellnessController::class, 'index'])->name('expediente.index');

    // Equipo estudio (2.12) + Panel impacto (2.13)
    Route::get('/mi-equipo', [TeamController::class, 'index'])->name('equipo.index');
    Route::post('/mi-equipo/invitar', [TeamController::class, 'invitar'])
        ->middleware('throttle:10,1')->name('equipo.invitar');
    Route::post('/mi-equipo/{miembro}/remover', [TeamController::class, 'remover'])->name('equipo.remover');
    // H3 · nota + calificación de bienestar del estudio.
    Route::post('/mi-equipo/bienestar/nota', [TeamController::class, 'guardarNotaBienestar'])
        ->middleware('throttle:20,1')->name('equipo.bienestar.nota');
    Route::post('/invitaciones/{miembro}/aceptar', [TeamController::class, 'aceptar'])->name('equipo.aceptar');
    Route::post('/invitaciones/{miembro}/rechazar', [TeamController::class, 'rechazar'])->name('equipo.rechazar');
});

// LOW-14 · Redirects 301 públicos (fuera del grupo auth) — URLs viejas del
// rename Ofertas→Oportunidades / Contenido→Desarrollo. Sin auth para que
// los bookmarks y enlaces externos redirijan aunque el user no esté logueado
// (la URL nueva los llevará al login si aplica).
Route::redirect('/ofertas', '/oportunidades', 301);
Route::redirect('/mis-ofertas', '/mis-oportunidades', 301);
Route::redirect('/contenido', '/desarrollo', 301);
Route::redirect('/mi-contenido', '/mi-desarrollo', 301);
Route::get('/ofertas/{slug}', fn ($slug) => redirect("/oportunidades/{$slug}", 301));
Route::get('/mis-ofertas/{oferta}/editar', fn ($o) => redirect("/mis-oportunidades/{$o}/editar", 301));
Route::get('/contenido/{slug}', fn ($slug) => redirect("/desarrollo/{$slug}", 301));
Route::get('/mi-contenido/{contenido}/editar', fn ($c) => redirect("/mi-desarrollo/{$c}/editar", 301));

require __DIR__.'/auth.php';
