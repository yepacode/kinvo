<?php

namespace App\Http\Controllers;

use App\Enums\EstadoContacto;
use App\Mail\NuevoContacto;
use App\Models\Contact;
use App\Models\ProfessionalProfile;
use App\Models\User;
use App\Notifications\NuevoContactoNotification;
use App\Notifications\ProfesionalInteresadoNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    /**
     * El profesional marca "Me interesa, conéctame con el estudio". Al hacerlo,
     * Kinvoo (los admins) reciben una notificación para gestionar el puente
     * manualmente. Idempotente: si ya estaba marcado, no vuelve a notificar.
     */
    public function marcarInteresado(Request $request, Contact $contact): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esProfesional(), 403);

        $contact->loadMissing('professionalProfile');
        abort_unless($contact->professionalProfile?->user_id === $user->id, 403);

        if ($contact->professional_interesado_at) {
            // Ya lo marcó antes: idempotente, no duplicamos aviso.
            return back()->with('status', 'ya-interesado');
        }

        $contact->forceFill([
            'professional_interesado_at' => now(),
            'estado' => EstadoContacto::Leido,
        ])->save();

        // Notifica a todos los admins activos (email + campanita). El correo
        // va queued dentro de la Notification; envuelto en try/catch para que
        // un fallo de correo no rompa el flujo del profesional.
        try {
            $admins = User::query()
                ->where('nivel', \App\Enums\RolUsuario::Admin)
                ->where('estado', \App\Enums\EstadoUsuario::Activo)
                ->get();
            foreach ($admins as $admin) {
                $admin->notify(new ProfesionalInteresadoNotification($contact));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('status', 'interesado-registrado');
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

        // Dedupe: si el mismo contratante ya contactó a este profesional en los últimos
        // 30 segundos, no creamos un nuevo registro (evita triples-clicks del botón, F5
        // que reenvía el POST y retries del navegador). Cubierto por transacción con
        // lockForUpdate para bloquear POSTs simultáneos en dos tabs / dos dispositivos.
        $contact = DB::transaction(function () use ($request, $professionalProfile, $data) {
            $ventana = now()->subSeconds(30);
            $existente = $professionalProfile->contacts()
                ->where('contractor_user_id', $request->user()->id)
                ->where('created_at', '>=', $ventana)
                ->lockForUpdate()
                ->first();

            if ($existente) {
                return $existente;
            }

            return $professionalProfile->contacts()->create([
                'contractor_user_id' => $request->user()->id,
                'contact_name' => $data['contact_name'],
                'contact_email' => $data['contact_email'],
                'contact_phone' => $data['contact_phone'] ?? null,
                'message' => $data['message'],
                'estado' => EstadoContacto::NoLeido,
            ]);
        });

        // Si fue dedupe (contacto ya existía), no volvemos a mandar correo/notificación.
        if (! $contact->wasRecentlyCreated) {
            return redirect()
                ->route('talento.show', $professionalProfile->slug)
                ->with('status', 'contacto-enviado');
        }

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
