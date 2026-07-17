<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
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

        // Nombres de archivo en disco antes de borrar el usuario, para poder
        // limpiar el disco sin depender del modelo relacionado (que se borra
        // en cascada). Multimedia y logo/foto van en `public`; certificaciones
        // en `local` (privado, solo el admin descarga).
        $publicFiles = collect([
            $user->professionalProfile?->photo_path,
            $user->professionalProfile?->media_path,
            $user->companyProfile?->logo_path,
            $user->companyProfile?->media_path,
        ])->filter()->all();

        $localFiles = collect([
            $user->professionalProfile?->certification_file_path,
        ])->filter()->all();

        // Orden importante: primero desloguear (para que el guard no intente
        // actualizar remember_token sobre un user ya borrado), luego limpiar
        // notifications polimórficas (sin FK) y por último borrar al usuario.
        // El resto de datos relacionados (perfil, contactos, guardados, vistas)
        // se borra por cascada de FK.
        Auth::logout();

        DB::table('notifications')
            ->where('notifiable_type', $user::class)
            ->where('notifiable_id', $user->id)
            ->delete();

        $user->delete();

        // Limpieza de archivos huérfanos.
        foreach ($publicFiles as $path) {
            Storage::disk('public')->delete($path);
        }
        foreach ($localFiles as $path) {
            Storage::disk('local')->delete($path);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::route('login')->with('status', 'cuenta-eliminada');
    }
}
