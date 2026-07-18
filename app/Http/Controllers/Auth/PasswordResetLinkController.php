<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Enviamos el enlace de reset. `Password::sendResetLink` retorna un status
        // distinto si el email no existe (INVALID_USER) — mostrar ese mensaje
        // permite ENUMERACIÓN de cuentas. Siempre respondemos con el mensaje de
        // "te enviamos el enlace", independientemente del status real.
        Password::sendResetLink($request->only('email'));

        return back()->with('status', __(Password::RESET_LINK_SENT));
    }
}
