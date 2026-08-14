<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        // MED-G1 · Cerrar sesiones activas en otros dispositivos tras cambiar
        // contraseña. Sin esto, un atacante con sesión secuestrada mantiene
        // acceso incluso después de que el usuario legítimo actualiza la clave.
        // Laravel invalida las demás sesiones que usen el hash anterior.
        try {
            Auth::logoutOtherDevices($validated['password']);
        } catch (\Throwable $e) {
            // Solo falla si `session.driver=array` (dev) — no rompe el flujo.
            report($e);
        }
        // También registrar en la bitácora legal por si acaso.
        \App\Models\AuditLog::record($request->user(), $request->user(), 'password_changed', new: [
            'ip' => $request->ip(),
        ]);

        return back()->with('status', 'password-updated');
    }
}
