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

        // MED-J1 · Registro atómico: sin transacción, si la creación del
        // perfil (companyProfile o professionalProfile) fallaba tras crear el
        // User, quedaba un user huérfano sin perfil que bloqueaba futuros
        // registros con el mismo email (UNIQUE) y hacía crashear vistas que
        // asumen perfil no-null. Todo en una transacción: si algo falla,
        // rollback deja al email libre para reintentar.
        $user = \Illuminate\Support\Facades\DB::transaction(function () use ($request, $rol) {
            $u = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
            // Feedback Karla 27-ago: el estudio tenía DOS aprobaciones
            //   (Pendiente → PerfilPendiente → Activo). Ahora entra directo
            //   como PerfilPendiente para poder llenar el perfil desde el
            //   primer login; el admin lo aprueba una sola vez cuando el
            //   perfil está listo. El coach conserva su flujo estándar
            //   (Pendiente → Activo).
            $atributos = [
                'nivel' => $rol,
                'estado' => $rol === RolUsuario::Contractor
                    ? EstadoUsuario::PerfilPendiente
                    : EstadoUsuario::Pendiente,
            ];
            if (Schema::hasColumn('users', 'locale')) {
                $atributos['locale'] = in_array(app()->getLocale(), ['es', 'en'], true) ? app()->getLocale() : 'es';
            }
            $u->forceFill($atributos)->save();

            // Perfil vacío según el rol, listo para autoeditar.
            if ($rol === RolUsuario::Contractor) {
                $u->companyProfile()->create([
                    'company_name' => $u->name,
                ]);
            } else {
                $u->professionalProfile()->create([
                    'headline' => null,
                ]);
            }
            return $u;
        });

        event(new Registered($user));

        // Correo de bienvenida: SÍNCRONO (Mail::send, no queue).
        // En Hostinger compartido la cola requiere un cron cada minuto y
        // 'queue:work --stop-when-empty'. Cuando ese cron falla o no está,
        // los correos encolados nunca salen. El SMTP responde en <2 s, así
        // que enviar en el mismo request no afecta la UX del registro.
        // report($e) manda el error real al log (canal STACK) si SMTP falla.
        try {
            $mailable = $rol === RolUsuario::Contractor
                ? new BienvenidaEstudio($user)
                : new BienvenidaTalento($user);

            Mail::to($user->email)->send($mailable);
        } catch (\Throwable $e) {
            report($e);
        }

        Auth::login($user);

        // HIGH-1 · Prevención de session fixation: regenerar el session ID
        // tras login. Sin esto, un atacante que fijó el cookie de sesión ANTES
        // del registro queda autenticado como la víctima. Espeja el patrón de
        // AuthenticatedSessionController::store().
        $request->session()->regenerate();

        // Arranca el wizard de onboarding (bienvenida → perfil → confirmación).
        return redirect()->route($rol === RolUsuario::Contractor ? 'company.bienvenida' : 'professional.bienvenida');
    }
}
