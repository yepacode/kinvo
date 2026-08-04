<?php

namespace App\Http\Controllers;

use App\Models\ContentItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Fase 2 · Contenido: grabaciones, capacitaciones, documentos.
 * El admin sube desde el panel; los users ven según su rol y plan.
 */
class ContentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Base: solo publicados, filtrados por gate_role (si aplica).
        $q = ContentItem::where('is_published', true);

        if ($user && ! $user->esAdmin()) {
            $q->where(function ($qq) use ($user) {
                $rol = $user->esProfesional() ? 'professional' : ($user->esContratante() ? 'contractor' : null);
                $qq->whereNull('gate_role')
                   ->when($rol, fn ($qqq) => $qqq->orWhere('gate_role', $rol));
            });
        }

        if ($cat = $request->string('categoria')->toString()) {
            $q->where('category', $cat);
        }

        $items = $q->latest('published_at')->paginate(12);
        // Filtrar los que requieren plan (esta lógica se resuelve en el modelo).
        $accesibles = $items->getCollection()->filter(fn ($item) => $item->esAccesiblePor($user));
        $items->setCollection($accesibles);

        return view('contenido.index', [
            'items' => $items,
            'categorias' => ContentItem::where('is_published', true)
                ->whereNotNull('category')->distinct()->pluck('category'),
        ]);
    }

    public function show(Request $request, ContentItem $content): View
    {
        abort_unless($content->esAccesiblePor($request->user()), 403);

        // Contador de vistas (denormalizado)
        $content->increment('views_count');

        return view('contenido.show', ['item' => $content]);
    }
}
