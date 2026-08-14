<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                // MED-G6 · Un usuario SUSPENDIDO no debe poder resetear su
                // contraseña — abortar antes de tocar la BD para que Laravel
                // devuelva el status genérico y evite dar señal al atacante.
                if (method_exists($user, 'estaActivo') && ! $user->estaActivo() && ! $user->esAdmin()) {
                    throw ValidationException::withMessages([
                        'email' => __(Password::INVALID_USER),
                    ]);
                }
                // Cambiar remember_token invalida los tokens "recordarme" en
                // otros dispositivos (login-remember del atacante). Adicional-
                // mente disparamos PasswordReset (audit) y logoutOtherDevices
                // para cerrar las sesiones que usaban el hash anterior.
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // MED-G2 · Cerrar sesiones activas en otros dispositivos post-reset.
        // Sólo si el reset realmente sucedió (status ok) y hay un user
        // autenticado en el request. Si el flujo del reset es completamente
        // anónimo, este bloque no aplica.
        if ($status === Password::PASSWORD_RESET && $request->user()) {
            try {
                \Illuminate\Support\Facades\Auth::logoutOtherDevices($request->password);
            } catch (\Throwable $e) { report($e); }
        }

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $status == Password::PASSWORD_RESET
                    ? redirect()->route('login')->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
