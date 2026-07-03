<?php

namespace App\Http\Controllers;

use App\Models\ProfessionalProfile;
use Illuminate\View\View;

class TalentoController extends Controller
{
    /** Vista pública del perfil de un profesional (solo si está publicado). */
    public function show(ProfessionalProfile $professionalProfile): View
    {
        abort_unless($professionalProfile->is_published, 404);

        $professionalProfile->load(['disciplines', 'certifications', 'location', 'user']);

        return view('talento.show', ['profile' => $professionalProfile]);
    }
}
