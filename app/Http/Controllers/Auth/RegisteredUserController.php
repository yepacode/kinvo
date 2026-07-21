<?php

namespace App\Http\Controllers\Auth;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Http\Controllers\Controller;
use App\Mail\BienvenidaEstudio;
use App\Mail\BienvenidaTalento;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
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
            'acepta_legales.accepted' => __('Debes aceptar los Términos y Condiciones y el Aviso de Privacidad.'),
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
        // nivel/estado/locale no son mass-assignable: se setean explícitamente.
        // locale se toma del idioma activo en la sesión de registro para que el correo
        // de bienvenida se envíe en el idioma que el usuario estaba usando.
        // Guarda defensivo: si prod aún no corre `php artisan migrate` tras un
        // deploy con la nueva columna `locale`, el registro sigue funcionando
        // sin romper el flujo del cliente (el locale queda en 'es' por default).
        $atributos = [
            'nivel' => $rol,
            'estado' => EstadoUsuario::Pendiente,
        ];
        if (Schema::hasColumn('users', 'locale')) {
            $atributos['locale'] = in_array(app()->getLocale(), ['es', 'en'], true) ? app()->getLocale() : 'es';
        }
        $user->forceFill($atributos)->save();

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

        // Correo de bienvenida (queued por ShouldQueue). Un fallo aquí NO debe romper
        // el registro: se registra en el log y el usuario sigue al wizard.
        try {
            $mailable = $rol === RolUsuario::Contractor
                ? new BienvenidaEstudio($user)
                : new BienvenidaTalento($user);
            Mail::to($user->email)->send($mailable);
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar el correo de bienvenida', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        Auth::login($user);

        // Arranca el wizard de onboarding (bienvenida → perfil → confirmación).
        return redirect()->route($rol === RolUsuario::Contractor ? 'company.bienvenida' : 'professional.bienvenida');
    }
}
