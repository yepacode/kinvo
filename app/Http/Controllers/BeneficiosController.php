<?php

namespace App\Http\Controllers;

use App\Models\BenefitRequest;
use App\Models\ContentView;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * H6 · "Mis beneficios" del coach (matriz de reglas de negocio):
 * dashboard con estatus de qué tiene activo su plan y contadores de uso.
 */
class BeneficiosController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->esProfesional(), 403);
        if (! $user->hasBenefit('mis_beneficios')) {
            return redirect()->route('membresias.index')
                ->with('status', 'plan-necesario-beneficios');
        }

        $beneficios = [
            [
                'key'     => 'contenido_avanzado',
                'titulo'  => __('Desarrollo Nivel 2 y 3'),
                'detalle' => __('Contenido premium de negocio, soft skills y ruta emprendedora.'),
                'activo'  => $user->hasBenefit('contenido_avanzado'),
                'icono'   => '📚',
            ],
            [
                'key'     => 'comunidad_ver',
                'titulo'  => __('Comunidad Kinvoo'),
                'detalle' => __('Ves los momentos publicados por los estudios de la red.'),
                'activo'  => $user->hasBenefit('comunidad_ver'),
                'icono'   => '💚',
            ],
            [
                'key'     => 'respaldo_telemed',
                'titulo'  => __('Respaldo · Telemedicina'),
                'detalle' => __('Consultas médicas a distancia, gestionadas por Kinvoo.'),
                'activo'  => $user->hasBenefit('respaldo_telemed'),
                'icono'   => '🩺',
            ],
            [
                'key'     => 'respaldo_fisio',
                'titulo'  => __('Respaldo · Fisioterapia'),
                'detalle' => __('Sesiones de fisio (solo Plan Plus).'),
                'activo'  => $user->hasBenefit('respaldo_fisio'),
                'icono'   => '💪',
            ],
            [
                'key'     => 'expediente_propio',
                'titulo'  => __('Mi expediente de cuidado'),
                'detalle' => __('Timeline de tus consultas, sesiones y beneficios facilitados.'),
                'activo'  => $user->hasBenefit('expediente_propio'),
                'icono'   => '📋',
            ],
            [
                'key'     => 'pulso_contestar',
                'titulo'  => __('Encuesta de Pulso Kinvoo'),
                'detalle' => __('Contestas y ayudas al estudio a cuidar mejor a su equipo.'),
                'activo'  => $user->hasBenefit('pulso_contestar'),
                'icono'   => '🌡️',
            ],
        ];

        // Contadores rápidos.
        // LOW-10 · Si el coach NO tiene fisio (Plan Esencial), mostrar `null`
        // en vez de 0 — la vista puede pintar "—" con label "Solo Plan Plus"
        // en vez de un "0 sesiones" que sugiere que tuvo el beneficio y no
        // lo usó. Igual para telemed si por algún motivo no tiene ese gate.
        $usos = [
            'telemed_usadas' => $user->hasBenefit('respaldo_telemed')
                ? BenefitRequest::where('user_id', $user->id)
                    ->where('type', BenefitRequest::TYPE_TELEMEDICINE)
                    ->where('status', BenefitRequest::STATUS_DONE)->count()
                : null,
            'fisio_usadas'   => $user->hasBenefit('respaldo_fisio')
                ? BenefitRequest::where('user_id', $user->id)
                    ->where('type', BenefitRequest::TYPE_PHYSIO)
                    ->where('status', BenefitRequest::STATUS_DONE)->count()
                : null,
            'contenido_visto' => ContentView::where('user_id', $user->id)->count(),
        ];

        return view('beneficios.index', compact('beneficios', 'usos'));
    }
}
