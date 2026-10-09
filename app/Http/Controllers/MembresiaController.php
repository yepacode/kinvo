<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\View\View;

class MembresiaController extends Controller
{
    /**
     * Página pública con los planes de membresía activos.
     *
     * Se listan también los que no tienen precio: aparecen como "A consultar"
     * para los planes que se negocian caso por caso. Quien decide si se puede
     * contratar o no es la propia tarjeta, según `esGratuito`/`sinPrecio`.
     */
    public function index(): View
    {
        $plans = Plan::where('activo', true)->orderBy('orden')->orderBy('id')->get();

        return view('membresias.index', [
            'individuales' => $plans->where('audiencia', 'individual'),
            'estudios' => $plans->where('audiencia', 'estudio'),
        ]);
    }
}
