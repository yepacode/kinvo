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

        // HIGH-9 · Filtrar accesibilidad en la QUERY (no post-paginate). Antes
        // paginate(12) traía 12 items y después filter() dejaba páginas con
        // 0/1/2 items visibles al coach free — paginación rota. Ahora
        // reproducimos la lógica de ContentItem::esAccesiblePor a nivel SQL.
        $q = ContentItem::where('is_published', true);

        if ($user && ! $user->esAdmin()) {
            $rol = $user->esProfesional() ? 'professional'
                : ($user->esContratante() ? 'contractor' : null);

            // Gate por rol.
            $q->where(function ($qq) use ($rol) {
                $qq->whereNull('gate_role');
                if ($rol) $qq->orWhere('gate_role', $rol);
            });

            // Gate por plan específico.
            $q->where(function ($qq) use ($user) {
                $qq->whereNull('gate_plan_id')
                   ->orWhere('gate_plan_id', $user->membership_plan_id);
            });

            // Matriz del cliente: estudio sin membresía NO ve NADA de contenido.
            // Coach sin membresía sólo ve nivel 1. Coach/estudio con membresía
            // ven todos los niveles.
            if ($user->esContratante() && ! $user->tieneMembresiaActiva()) {
                $q->whereRaw('1 = 0'); // sin resultados
            } elseif (! $user->tieneMembresiaActiva()) {
                $q->where(function ($qq) {
                    $qq->whereNull('access_level')->orWhere('access_level', '<=', 1);
                });
            }
        } elseif (! $user) {
            $q->whereRaw('1 = 0'); // anónimo no ve nada
        }

        if ($cat = $request->string('categoria')->toString()) {
            $q->where('category', $cat);
        }

        $items = $q->latest('published_at')->paginate(12);

        return view('contenido.index', [
            'items' => $items,
            'categorias' => ContentItem::where('is_published', true)
                ->whereNotNull('category')->distinct()->pluck('category'),
        ]);
    }

    public function show(Request $request, ContentItem $content)
    {
        $user = $request->user();
        // H6 · si el bloqueo es sólo por nivel free/paid, redirigir a planes
        // con mensaje de upsell (mejor UX que un 403 seco).
        if ($user && $content->is_published && ($content->access_level ?? 1) > 1
            && ! $user->tieneMembresiaActiva() && ! $user->esAdmin()) {
            return redirect()->route('membresias.index')
                ->with('status', 'plan-necesario-contenido');
        }
        abort_unless($content->esAccesiblePor($user), 403);

        // MED-G9/G11 · Dedup de vistas: 1 vista contable por user por día
        // (antes cada F5 inflaba `views_count` y creaba una nueva ContentView
        // fila, distorsionando los agregados y permitiendo a un bot autenticado
        // simular popularidad). Todo dentro de transacción para que un fallo
        // no deje `views_count` incrementado sin la fila que lo respalda.
        if ($user) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($content, $user) {
                $yaHoy = \App\Models\ContentView::where('user_id', $user->id)
                    ->where('content_item_id', $content->id)
                    ->whereDate('viewed_at', today())
                    ->exists();
                if (! $yaHoy) {
                    \App\Models\ContentView::create([
                        'user_id'         => $user->id,
                        'content_item_id' => $content->id,
                        'viewed_at'       => now(),
                    ]);
                    $content->increment('views_count');
                }
            });
        } else {
            // Anónimo (raro — este endpoint suele requerir auth): incremento
            // simple sin bitácora.
            $content->increment('views_count');
        }

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
        // Contenido subido por un estudio: público interno para toda la
        // comunidad Kinvoo (coaches + estudios), con nivel de acceso 1 por
        // defecto (visible a todos los coaches, y a estudios con membresía
        // — el estudio sin mem no ve contenido, gate en ContentItem::esAccesiblePor).
        // Si el estudio quisiera restringir a un plan específico, es una
        // decisión de negocio a futuro (por ahora, un solo nivel N1).
        $data['gate_role'] = null;
        $data['gate_plan_id'] = null;
        $data['access_level'] = 1;
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

        // HIGH-25 · Guardar el nuevo ANTES de borrar el viejo. Antes se
        // borraba el viejo primero: si store() fallaba (disco lleno, permisos),
        // el usuario perdía el archivo original sin recibir el nuevo. Ahora
        // sólo se borra el viejo tras un store exitoso.
        if ($request->hasFile('archivo')) {
            $nuevoPath = $request->file('archivo')->store('contenido-estudios', 'public');
            if ($nuevoPath) {
                $viejo = $contenido->file_path;
                $data['file_path'] = $nuevoPath;
                if ($viejo) {
                    Storage::disk('public')->delete($viejo);
                }
            }
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

        // HIGH-19 · AuditLog ANTES del delete y con el ContentItem como
        // subject (antes el subject era $user, perdiendo la trazabilidad
        // de QUÉ contenido se eliminó desde la tabla audit_logs).
        AuditLog::record($user, $contenido, 'contenido_eliminado',
            old: ['title' => $contenido->title, 'type' => $contenido->type, 'file_path' => $contenido->file_path]);
        if ($contenido->file_path) {
            Storage::disk('public')->delete($contenido->file_path);
        }
        $contenido->delete();

        return redirect()->route('contenido.mis-contenidos')->with('status', 'contenido-eliminado');
    }

    /** Reglas comunes crear/actualizar. */
    private function validarContenido(Request $request): array
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category'    => ['nullable', 'string', 'max:80'],
            'type'        => ['required', Rule::in([
                ContentItem::TYPE_VIDEO, ContentItem::TYPE_DOCUMENT,
                ContentItem::TYPE_AUDIO, ContentItem::TYPE_LINK,
            ])],
            // MED-G8 · `url` sin restricción de scheme aceptaba `javascript:`,
            // `data:` o `file:` — un estudio malicioso podía publicar
            // "contenido" cuyo click ejecutaba JS en el navegador del coach.
            // Restringimos a http/https únicamente.
            'url'         => ['nullable', 'url', 'starts_with:http://,https://', 'max:500', 'required_without:archivo'],
            // CRITICAL-5: sin whitelist estricta se aceptaban .php / .svg /
            // .html en el disco público → RCE/XSS. Lista basada en los tipos
            // realmente soportados por el reproductor de la vista y por los
            // tipos declarados en ContentItem::TYPE_*.
            'archivo'     => [
                'nullable', 'file', 'max:25600',
                'mimes:pdf,doc,docx,ppt,pptx,mp4,webm,mov,m4v,mp3,m4a,wav,jpg,jpeg,png,webp',
                'required_without:url',
            ],
        ]);

        // HIGH-24 · cross-validation type↔archivo: evitar que se suba un
        // PDF etiquetado como "video" (el reproductor de la vista
        // asume el mime correcto). Sólo aplica si vino archivo.
        if ($request->hasFile('archivo')) {
            $mime = $request->file('archivo')->getMimeType() ?? '';
            $tipoDetectado = match (true) {
                str_starts_with($mime, 'video/')                        => ContentItem::TYPE_VIDEO,
                str_starts_with($mime, 'audio/')                        => ContentItem::TYPE_AUDIO,
                str_starts_with($mime, 'image/')                        => ContentItem::TYPE_DOCUMENT, // imágenes se etiquetan como document
                str_contains($mime, 'pdf'),
                str_contains($mime, 'msword'),
                str_contains($mime, 'officedocument')                   => ContentItem::TYPE_DOCUMENT,
                default => null,
            };
            if ($tipoDetectado && $tipoDetectado !== $data['type']) {
                abort(422, "El archivo subido es de tipo '{$tipoDetectado}' pero seleccionaste '{$data['type']}'. Cambia el tipo o sube el archivo correcto.");
            }
        }

        return $data;
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
