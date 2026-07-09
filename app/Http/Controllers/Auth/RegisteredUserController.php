<?php

namespace App\Http\Controllers\Auth;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'in:professional,contractor'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'acepta_legales' => ['accepted'],
        ], [
            'acepta_legales.accepted' => 'Debes aceptar los Términos y Condiciones y el Aviso de Privacidad.',
        ]);

        // El tipo de registro define el rol; nunca se crea un Admin desde el registro público.
        $rol = $request->tipo === 'contractor'
            ? RolUsuario::Contractor
            : RolUsuario::Professional;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        // nivel/estado no son mass-assignable: se setean explícitamente.
        $user->forceFill([
            'nivel' => $rol,
            'estado' => EstadoUsuario::Pendiente, // aprobación activada
        ])->save();

        // Perfil vacío según el rol, listo para autoeditar.
        if ($rol === RolUsuario::Contractor) {
            $user->companyProfile()->create([
                'company_name' => $user->name,
            ]);
        } else {
            $user->professionalProfile()->create([
                'headline' => null,
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect($user->homeRoute());
    }
}
