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

        // MED-G3 · Anti-enumeración por timing: enviar SMTP tarda ~200-800 ms;
        // no enviar retorna en ~10 ms. Un atacante que mide diferencia sabe si
        // el email existe. Nivelar con un delay aleatorio de rango similar
        // para que la respuesta siempre tarde lo mismo desde afuera.
        $start = microtime(true);

        Password::sendResetLink($request->only('email'));

        // Si tardamos menos de 400 ms (email no existe → no envió correo),
        // añadimos un pad aleatorio hasta 400-800 ms para simular el envío.
        $elapsed_ms = (microtime(true) - $start) * 1000;
        if ($elapsed_ms < 400) {
            $target = random_int(400, 800);
            usleep((int) (($target - $elapsed_ms) * 1000));
        }

        return back()->with('status', __(Password::RESET_LINK_SENT));
    }
}
