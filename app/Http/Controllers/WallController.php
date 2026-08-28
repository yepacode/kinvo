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

        // Feedback Karla 27-ago: "Mis momentos" y "Comunidad" se unifican en
        // esta vista. Si el user es estudio con benefit `comunidad_publicar`,
        // le pasamos sus propios posts + el flag para pintar el form.
        $puedePublicar = $user && $user->esContratante() && $user->hasBenefit('comunidad_publicar');
        // Últimos 20 propios (con estado de moderación). Antes /mis-momentos
        // paginaba 20 — mantenemos ese tope para no perder acceso a viejos.
        $misPosts = $puedePublicar
            ? WallPost::where('user_id', $user->id)->latest()->limit(20)->get()
            : collect();

        return view('wall.comunidad', [
            'posts'        => $posts,
            'puedePublicar' => $puedePublicar,
            'misPosts'     => $misPosts,
        ]);
    }

    /** Feedback Karla 27-ago: /mis-momentos ahora redirige a /comunidad (vista
     *  unificada). Se conserva la ruta y su nombre para no romper links viejos
     *  ni notificaciones enviadas antes del cambio. */
    public function misMomentos(Request $request)
    {
        return redirect()->route('wall.comunidad');
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
            // Feedback Karla 27-ago: subimos el peso máximo a 100 MB para
            // aceptar clips de clases más largas. Alineado con el `.user.ini`
            // publicado en public/ (upload_max_filesize=128M / post=130M).
            'media_file' => ['required', 'file',
                'mimes:mp4,webm,mov,m4v,jpg,jpeg,png,webp',
                'max:102400',
                'dimensions:min_width=200,min_height=200,max_width=4096,max_height=4096',
            ],
        ], [
            'caption.required'    => 'Escribe una frase — no necesitas más.',
            'media_file.required' => 'Sube una foto o un video corto para compartir el momento.',
            'media_file.max'      => 'El archivo pesa más de 100 MB. Prueba con un video más corto.',
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

        // wall.mis-momentos ya redirige a wall.comunidad tras el rediseño
        // 27-ago; apuntamos directo para evitar el hop de 302 extra.
        return redirect()->route('wall.comunidad')->with('status', 'momento-enviado');
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
