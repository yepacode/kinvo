<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ContentItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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

    // ================================================================
    // CRUD del estudio: subir su propio contenido (visible a todos los
    // users con sesión activa — coaches y estudios de Kinvoo).
    // ================================================================

    /** Panel del estudio con sus propios contenidos. */
    public function misContenidos(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->esContratante(), 403);

        $items = ContentItem::where('uploader_user_id', $user->id)
            ->latest()
            ->paginate(15);

        return view('contenido.mis-contenidos', compact('items'));
    }

    /** Formulario para nuevo contenido subido por estudio. */
    public function crear(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->esContratante(), 403);
        $this->autorizarSuscripcionActiva($user);

        return view('contenido.form', ['contenido' => new ContentItem()]);
    }

    /** Guarda un nuevo contenido del estudio. */
    public function guardar(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esContratante(), 403);
        $this->autorizarSuscripcionActiva($user);

        $data = $this->validarContenido($request);

        // Si subió archivo, lo guarda en disk público y usa el path.
        if ($request->hasFile('archivo')) {
            $data['file_path'] = $request->file('archivo')->store('contenido-estudios', 'public');
        }
        unset($data['archivo']);

        $data['uploader_user_id'] = $user->id;
        $data['gate_role'] = null;   // público interno — todos los users lo ven
        $data['gate_plan_id'] = null;
        $data['is_published'] = true;
        $data['published_at'] = now();

        $item = ContentItem::create($data);
        AuditLog::record($user, $item, 'contenido_publicado', new: ['title' => $item->title]);

        return redirect()->route('contenido.mis-contenidos')->with('status', 'contenido-creado');
    }

    /** Formulario para editar contenido propio. */
    public function editar(Request $request, ContentItem $contenido): View
    {
        $user = $request->user();
        abort_unless($user->esContratante() && $contenido->uploader_user_id === $user->id, 403);
        $this->autorizarSuscripcionActiva($user);

        return view('contenido.form', compact('contenido'));
    }

    /** Actualiza contenido propio. */
    public function actualizar(Request $request, ContentItem $contenido): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esContratante() && $contenido->uploader_user_id === $user->id, 403);
        $this->autorizarSuscripcionActiva($user);

        $data = $this->validarContenido($request);

        // Si subió archivo nuevo, borra el viejo del disco.
        if ($request->hasFile('archivo')) {
            if ($contenido->file_path) {
                Storage::disk('public')->delete($contenido->file_path);
            }
            $data['file_path'] = $request->file('archivo')->store('contenido-estudios', 'public');
        }
        unset($data['archivo']);

        $contenido->update($data);
        AuditLog::record($user, $contenido, 'contenido_editado');

        return redirect()->route('contenido.mis-contenidos')->with('status', 'contenido-actualizado');
    }

    /** Elimina contenido propio (hard delete — no hay revisar). */
    public function eliminar(Request $request, ContentItem $contenido): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esContratante() && $contenido->uploader_user_id === $user->id, 403);

        if ($contenido->file_path) {
            Storage::disk('public')->delete($contenido->file_path);
        }
        $titulo = $contenido->title;
        $contenido->delete();
        AuditLog::record($user, $user, 'contenido_eliminado', new: ['title' => $titulo]);

        return redirect()->route('contenido.mis-contenidos')->with('status', 'contenido-eliminado');
    }

    /** Reglas comunes crear/actualizar. */
    private function validarContenido(Request $request): array
    {
        return $request->validate([
            'title'       => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category'    => ['nullable', 'string', 'max:80'],
            'type'        => ['required', Rule::in([
                ContentItem::TYPE_VIDEO, ContentItem::TYPE_DOCUMENT,
                ContentItem::TYPE_AUDIO, ContentItem::TYPE_LINK,
            ])],
            'url'         => ['nullable', 'url', 'max:500', 'required_without:archivo'],
            'archivo'     => ['nullable', 'file', 'max:25600', 'required_without:url'], // 25 MB
        ]);
    }

    /** Bloqueo: solo estudios con suscripción activa pueden publicar/editar contenido. */
    private function autorizarSuscripcionActiva(\App\Models\User $user): void
    {
        $vigente = \App\Models\Subscription::where('user_id', $user->id)
            ->whereIn('status', [\App\Models\Subscription::STATUS_ACTIVE, \App\Models\Subscription::STATUS_TRIALING])
            ->where(function ($q) {
                $q->whereNull('current_period_end')
                  ->orWhere('current_period_end', '>=', now());
            })
            ->exists();

        if (! $vigente && ! $user->tieneMembresiaActiva()) {
            abort(redirect()->route('membresias.index')->with('status', 'membresia-requerida'));
        }
    }
}
