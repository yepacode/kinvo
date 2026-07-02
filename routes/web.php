<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

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

require __DIR__.'/auth.php';
