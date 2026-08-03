<?php

namespace App\Http\Controllers;

use App\Models\WellnessEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Fase 2 · Expediente de cuidado (2.11).
 * El coach ve su timeline; el admin lo alimenta desde Filament.
 */
class WellnessController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->esProfesional(), 403);

        $q = WellnessEntry::where('professional_user_id', $user->id);

        if ($tipo = $request->string('tipo')->toString()) {
            $q->where('type', $tipo);
        }

        $entradas = $q->orderBy('occurred_on', 'desc')->paginate(20);

        return view('expediente.index', [
            'entradas' => $entradas,
            'tipos' => WellnessEntry::TYPES,
        ]);
    }
}
