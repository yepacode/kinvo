<?php

use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\MembresiaController;
use App\Http\Controllers\ProfessionalProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SaveController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\TalentoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// SEO técnico
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

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
Route::get('/estudio/{companyProfile:slug}', [CompanyProfileController::class, 'show'])
    ->middleware(['auth', 'cuenta.activa', 'nocache'])->name('estudio.show');

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
        ->middleware(['membresia', 'throttle:8,1'])->name('contacto.store');

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

require __DIR__.'/auth.php';
