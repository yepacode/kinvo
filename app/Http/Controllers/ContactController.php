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

        // Aviso al profesional y al owner. En dev el driver 'log' escribe en laravel.log.
        Mail::to($professionalProfile->user->email)
            ->cc(config('mail.owner_address', 'hola@gokinvoo.com'))
            ->send(new NuevoContacto($contact, $professionalProfile));

        // Notificación in-app (campana) para el profesional.
        $professionalProfile->user->notify(new NuevoContactoNotification($contact));

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
