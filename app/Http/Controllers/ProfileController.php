<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     *
     * Un usuario Admin (el owner de Kinvoo) NO puede eliminarse desde aquí:
     * la baja del owner es una operación crítica que se hace fuera del flujo
     * de autoservicio. El resto de usuarios sí puede darse de baja y arrastra
     * consigo su perfil, contactos, guardados y archivos subidos.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($user->esAdmin()) {
            return Redirect::route('profile.edit')
                ->with('status', 'admin-no-se-elimina');
        }

        // HIGH-5 · Bitácora legal: registrar el autoborrado ANTES de eliminar
        // al user, para conservar contexto (email, rol, estado, IP, UA).
        // Después del delete la fila de auditoría queda con actor/subject
        // apuntando a un user inexistente, pero eso es aceptable: es un log
        // append-only, y la información sensible ya está en `changes`.
        \App\Models\AuditLog::record($user, $user, 'user_self_deleted', old: [
            'email'    => $user->email,
            'nivel'    => $user->nivel?->value,
            'estado'   => $user->estado?->value,
            'name'     => $user->name,
        ]);

        // Orden importante: primero desloguear (para que el guard no intente
        // actualizar remember_token sobre un user ya borrado), luego borrar al
        // usuario con su limpieza de archivos y notifications polimórficas.
        // El resto de datos relacionados (perfil, contactos, guardados, vistas)
        // se borra por cascada de FK.
        Auth::logout();

        $user->deleteConLimpieza();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::route('login')->with('status', 'cuenta-eliminada');
    }
}
