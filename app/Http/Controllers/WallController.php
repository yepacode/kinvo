<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\WallPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * H4 · Wall "Comparte un momento" (petición cliente, docx PRUEBA KINVOO).
 *
 * Rutas:
 *  - GET  /comunidad              → feed público (usuarios activos)
 *  - GET  /mis-momentos           → estudio ve/gestiona los suyos
 *  - POST /mis-momentos           → subir foto/video + frase
 *  - DELETE /mis-momentos/{post}  → estudio archiva el suyo
 */
class WallController extends Controller
{
    /** Feed público: solo posts aprobados por admin, más nuevos primero.
     *  Matriz: coach y estudio SIN membresía NO ven Momentos — redirigir a planes. */
    public function comunidad(\Illuminate\Http\Request $request)
    {
        $user = $request->user();
        if ($user && ! $user->hasBenefit('comunidad_ver')) {
            return redirect()->route('membresias.index')
                ->with('status', 'plan-necesario-comunidad');
        }

        $posts = WallPost::query()
            ->where('status', WallPost::STATUS_APPROVED)
            ->with('author.companyProfile')
            ->latest()
            ->paginate(12);

        return view('wall.comunidad', ['posts' => $posts]);
    }

    /** El estudio ve sus propios posts + estado de moderación.
     *  MED-I11/D · Un coach que llegue por link viejo recibía 403 seco.
     *  Ahora redirect claro al dashboard con status. */
    public function misMomentos(Request $request)
    {
        $user = $request->user();
        if (! $user->esContratante()) {
            return redirect()->route('dashboard')
                ->with('status', 'mis-momentos-solo-estudios');
        }
        // Sólo el estudio CON membresía puede publicar → si free llega aquí,
        // upsell claro en vez de mostrar la UI con botón que rebota.
        if (! $user->hasBenefit('comunidad_publicar')) {
            return redirect()->route('membresias.index')
                ->with('status', 'plan-necesario-momentos');
        }

        $posts = WallPost::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(20);

        return view('wall.mis-momentos', ['posts' => $posts]);
    }

    /** Estudio sube un momento (queda pending hasta que admin apruebe).
     *  Matriz: sólo estudio CON membresía puede publicar. */
    public function guardar(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esContratante(), 403);
        if (! $user->hasBenefit('comunidad_publicar')) {
            return redirect()->route('membresias.index')
                ->with('status', 'plan-necesario-momentos');
        }

        $data = $request->validate([
            'caption'    => ['required', 'string', 'max:280'],
            // Peso permisivo (video corto): 25 MB. Mismos formatos que el
            // media_file del perfil profesional para consistencia.
            // HIGH-27 · dimensions se aplica sólo a IMÁGENES (Laravel ignora
            // la regla para videos). Tope superior evita fotos gigantes que
            // matan la memoria del móvil y sirve de proxy para videos ultra-4K.
            'media_file' => ['required', 'file',
                'mimes:mp4,webm,mov,m4v,jpg,jpeg,png,webp',
                'max:25600',
                'dimensions:min_width=200,min_height=200,max_width=4096,max_height=4096',
            ],
        ], [
            'caption.required'    => 'Escribe una frase — no necesitas más.',
            'media_file.required' => 'Sube una foto o un video corto para compartir el momento.',
            'media_file.max'      => 'El archivo pesa más de 25 MB. Prueba con un video más corto.',
            'media_file.dimensions' => 'La imagen debe medir entre 200 y 4096 px de ancho y alto.',
        ]);

        $file = $request->file('media_file');
        $path = $file->store('wall', 'public');
        $tipo = str_starts_with($file->getMimeType() ?? '', 'video/')
            ? WallPost::TYPE_VIDEO
            : WallPost::TYPE_IMAGE;

        $post = WallPost::create([
            'user_id'    => $user->id,
            'caption'    => $data['caption'],
            'media_path' => $path,
            'media_type' => $tipo,
            'status'     => WallPost::STATUS_PENDING,
        ]);
        AuditLog::record($user, $post, 'wall_post_created');

        return redirect()->route('wall.mis-momentos')->with('status', 'momento-enviado');
    }

    /** Estudio archiva su propio momento. Admin usa Filament para moderar.
     *  HIGH-26 · Al archivar borramos el archivo del disco público. Antes
     *  quedaba accesible por URL directa aunque el post ya no apareciera
     *  en el feed — data expuesta sin control. Guardamos AuditLog primero
     *  para conservar el path original en la bitácora. */
    public function archivar(Request $request, WallPost $post): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->esContratante() && $post->user_id === $user->id, 403);

        $mediaOriginal = $post->media_path;
        AuditLog::record($user, $post, 'wall_post_archived', old: [
            'media_path' => $mediaOriginal,
            'status_previo' => $post->status,
        ]);
        $post->update([
            'status'     => WallPost::STATUS_ARCHIVED,
            'media_path' => null,
        ]);
        if ($mediaOriginal) {
            Storage::disk('public')->delete($mediaOriginal);
        }

        return back()->with('status', 'momento-archivado');
    }
}
