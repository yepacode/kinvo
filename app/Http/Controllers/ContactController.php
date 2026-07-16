<?php

namespace App\Http\Controllers;

use App\Enums\EstadoContacto;
use App\Mail\NuevoContacto;
use App\Models\ProfessionalProfile;
use App\Notifications\NuevoContactoNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    /** Bandeja del profesional: contactos que le han enviado (los marca como leídos). */
    public function recibidos(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->esProfesional(), 403);

        $profile = $user->professionalProfile()->firstOrCreate([]);
        $contactos = $profile->contacts()->latest()->paginate(15);

        // Marcar como leídos los no leídos (después de paginar, para que la vista
        // aún pueda resaltar cuáles llegaron sin leer en esta visita).
        $profile->contacts()->where('estado', EstadoContacto::NoLeido->value)
            ->update(['estado' => EstadoContacto::Leido->value]);

        return view('professional.contactos', ['contactos' => $contactos]);
    }

    /** Muestra el formulario para contactar a un profesional. */
    public function create(Request $request, ProfessionalProfile $professionalProfile): View
    {
        $this->autorizar($request, $professionalProfile);

        $professionalProfile->load('user');
        $contratante = $request->user();

        return view('contacto.create', [
            'profile' => $professionalProfile,
            'prefillName' => $contratante->companyProfile?->company_name ?: $contratante->name,
            'prefillEmail' => $contratante->email,
        ]);
    }

    /** Registra el contacto y notifica por correo. */
    public function store(Request $request, ProfessionalProfile $professionalProfile): RedirectResponse
    {
        $this->autorizar($request, $professionalProfile);

        $data = $request->validate([
            'contact_name' => ['required', 'string', 'max:150'],
            'contact_email' => ['required', 'email', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $contact = $professionalProfile->contacts()->create([
            'contractor_user_id' => $request->user()->id,
            'contact_name' => $data['contact_name'],
            'contact_email' => $data['contact_email'],
            'contact_phone' => $data['contact_phone'] ?? null,
            'message' => $data['message'],
            'estado' => EstadoContacto::NoLeido,
        ]);

        $professionalProfile->loadMissing('user');

        // Aviso por correo al profesional y al owner. El mailable está en cola
        // (ShouldQueue), así que NO se conecta al SMTP durante la petición.
        // Además va en try/catch: si el correo/cola falla, el contacto YA quedó
        // guardado y el usuario ve el "enviado" — nunca un 500.
        try {
            Mail::to($professionalProfile->user->email)
                ->cc(config('mail.owner_address', 'hola@gokinvoo.com'))
                ->queue(new NuevoContacto($contact, $professionalProfile));
        } catch (\Throwable $e) {
            report($e);
        }

        // Notificación in-app (campana) para el profesional.
        try {
            $professionalProfile->user->notify(new NuevoContactoNotification($contact));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()
            ->route('talento.show', $professionalProfile->slug)
            ->with('status', 'contacto-enviado');
    }

    /** Solo contratantes con cuenta activa pueden contactar perfiles publicados. */
    private function autorizar(Request $request, ProfessionalProfile $profile): void
    {
        abort_unless($profile->esVisiblePublicamente(), 404);

        $user = $request->user();
        abort_unless($user && $user->esContratante() && $user->estaActivo(), 403);
    }
}
