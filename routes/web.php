<?php

use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\ProfessionalProfileController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TalentoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Buscador y vista pública del talento (solo perfiles publicados).
Route::get('/talento', [TalentoController::class, 'index'])->name('talento.index');
Route::get('/talento/{professionalProfile:slug}', [TalentoController::class, 'show'])->name('talento.show');

// Aviso de cuenta pendiente/suspendida (no pasa por el gate de estado).
Route::get('/cuenta/pendiente', function () {
    return view('auth.pending');
})->middleware('auth')->name('account.pending');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'cuenta.activa'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Perfiles autoeditables (requieren cuenta activa).
Route::middleware(['auth', 'cuenta.activa'])->group(function () {
    Route::get('/mi-perfil', [ProfessionalProfileController::class, 'edit'])->name('professional.profile.edit');
    Route::put('/mi-perfil', [ProfessionalProfileController::class, 'update'])->name('professional.profile.update');

    Route::get('/mi-empresa', [CompanyProfileController::class, 'edit'])->name('company.profile.edit');
    Route::put('/mi-empresa', [CompanyProfileController::class, 'update'])->name('company.profile.update');
});

require __DIR__.'/auth.php';
