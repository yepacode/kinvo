<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\View\View;

class MembresiaController extends Controller
{
    /** Página pública con los planes de membresía activos. */
    public function index(): View
    {
        $plans = Plan::where('activo', true)->orderBy('orden')->orderBy('id')->get();

        return view('membresias.index', [
            'individuales' => $plans->where('audiencia', 'individual'),
            'estudios' => $plans->where('audiencia', 'estudio'),
        ]);
    }
}
