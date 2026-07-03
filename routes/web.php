<?php

use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\ContactController;
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

// Buscador y vista pública del talento (solo perfiles publicados).
Route::get('/talento', [TalentoController::class, 'index'])->name('talento.index');
Route::get('/talento/{professionalProfile:slug}', [TalentoController::class, 'show'])->name('talento.show');

// Aviso de cuenta pendiente/suspendida (no pasa por el gate de estado).
Route::get('/cuenta/pendiente', function () {
    return view('auth.pending');
})->middleware(['auth', 'nocache'])->name('account.pending');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'cuenta.activa', 'nocache'])->name('dashboard');

Route::middleware(['auth', 'nocache'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notificaciones (campana)
    Route::get('/notificaciones', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notificaciones/leer-todo', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::get('/notificaciones/{id}/abrir', [NotificationController::class, 'open'])->name('notifications.open');

    // Guardados / favoritos
    Route::get('/guardados', [SaveController::class, 'index'])->name('saves.index');
    Route::post('/talento/{professionalProfile:slug}/guardar', [SaveController::class, 'toggleProfile'])->name('saves.toggleProfile');
});

// Perfiles autoeditables (requieren cuenta activa).
Route::middleware(['auth', 'cuenta.activa', 'nocache'])->group(function () {
    Route::get('/mi-perfil', [ProfessionalProfileController::class, 'edit'])->name('professional.profile.edit');
    Route::put('/mi-perfil', [ProfessionalProfileController::class, 'update'])->name('professional.profile.update');

    Route::get('/mi-empresa', [CompanyProfileController::class, 'edit'])->name('company.profile.edit');
    Route::put('/mi-empresa', [CompanyProfileController::class, 'update'])->name('company.profile.update');

    // Contactar a un profesional (solo contratantes, validado en el controller).
    Route::get('/talento/{professionalProfile:slug}/contactar', [ContactController::class, 'create'])->name('contacto.create');
    Route::post('/talento/{professionalProfile:slug}/contactar', [ContactController::class, 'store'])->name('contacto.store');
});

require __DIR__.'/auth.php';
