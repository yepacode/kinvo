<?php

use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\LegalController;
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

// Páginas legales (públicas, editables desde el panel).
Route::get('/aviso-de-privacidad', [LegalController::class, 'privacidad'])->name('legal.privacidad');
Route::get('/terminos-y-condiciones', [LegalController::class, 'terminos'])->name('legal.terminos');

// Descarga del adjunto privado de certificaciones (solo admin; validado en el controller).
Route::get('/panel/certificacion/{professionalProfile}', [ProfessionalProfileController::class, 'certificacion'])
    ->middleware('auth')->name('admin.certificacion');

// Buscador y vista pública del talento (solo perfiles publicados).
Route::get('/talento', [TalentoController::class, 'index'])->name('talento.index');
Route::get('/talento/{professionalProfile:slug}', [TalentoController::class, 'show'])->name('talento.show');

// Página pública del estudio (solo si el dueño está activo).
Route::get('/estudio/{companyProfile:slug}', [CompanyProfileController::class, 'show'])->name('estudio.show');

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

// Áreas del producto — requieren cuenta ACTIVA (aprobada).
Route::middleware(['auth', 'cuenta.activa', 'nocache'])->group(function () {
    Route::get('/mi-perfil', [ProfessionalProfileController::class, 'edit'])->name('professional.profile.edit');
    Route::put('/mi-perfil', [ProfessionalProfileController::class, 'update'])->name('professional.profile.update');

    // Bandeja de contactos recibidos (profesional).
    Route::get('/mis-contactos', [ContactController::class, 'recibidos'])->name('professional.contactos');

    Route::get('/mi-empresa', [CompanyProfileController::class, 'edit'])->name('company.profile.edit');
    Route::put('/mi-empresa', [CompanyProfileController::class, 'update'])->name('company.profile.update');

    // Contactar a un profesional (solo contratantes, validado en el controller). Con rate limit anti-spam.
    Route::get('/talento/{professionalProfile:slug}/contactar', [ContactController::class, 'create'])->name('contacto.create');
    Route::post('/talento/{professionalProfile:slug}/contactar', [ContactController::class, 'store'])
        ->middleware('throttle:8,1')->name('contacto.store');

    // Notificaciones (campana)
    Route::get('/notificaciones', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notificaciones/leer-todo', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::get('/notificaciones/{id}/abrir', [NotificationController::class, 'open'])->name('notifications.open');

    // Guardados / favoritos
    Route::get('/guardados', [SaveController::class, 'index'])->name('saves.index');
    Route::post('/talento/{professionalProfile:slug}/guardar', [SaveController::class, 'toggleProfile'])->name('saves.toggleProfile');
});

require __DIR__.'/auth.php';
