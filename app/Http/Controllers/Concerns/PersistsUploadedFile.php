<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Fase 2 · Fix B1 (petición cliente Marian, 2026-08-06):
 * "No se guardó la foto del logo en el primer intento".
 *
 * Causa raíz: input type=file NO persiste su valor al re-renderizar el
 * form tras un error de validación (limitación estándar de HTML). Marian
 * subía logo + otros datos, otro campo fallaba, reintentaba, y el logo
 * "desaparecía" del input → guardaba sin logo.
 *
 * Solución: cuando llega un file al request:
 *  1. Se guarda en storage/app/tmp-uploads/{user_id}/{campo}.{ext}
 *  2. El path se referencia en sesión (tmp_upload_{campo}).
 *  3. Si validación pasa → el controller usa el file del request O el tmp
 *     y lo mueve al destino definitivo.
 *  4. Si validación falla → el tmp queda en sesión; en el próximo submit,
 *     si NO viene file nuevo pero SÍ hay tmp, se re-inyecta transparentemente.
 *  5. Al save exitoso → tmp se borra.
 *
 * Vida útil del tmp: 24h. Como se guarda por session key, si el user
 * cierra sesión el tmp queda huérfano — un job diario limpia tmps > 24h.
 */
trait PersistsUploadedFile
{
    /**
     * Antes de validar: si el request trae file, lo guarda como tmp y lo
     * deja también en el request. Si NO trae file pero HAY tmp de sesión
     * (reintento), inyecta el tmp file al request.
     *
     * @param  string  $campo  nombre del input (ej. 'logo', 'photo').
     */
    protected function restaurarArchivoTemporal(Request $request, string $campo): void
    {
        $userId = $request->user()?->id ?? 'anon';
        $sessionKey = "tmp_upload_{$campo}";

        // Caso A: el request TRAE file nuevo → snapshot en tmp y sigue.
        if ($request->hasFile($campo)) {
            $file = $request->file($campo);
            // SEGURIDAD (auditoría ago-2026): la extensión del CLIENTE no es
            // confiable (un `foo.php` guardado en disco es RCE si algún path
            // se sirve). Derivamos la extensión del MIME real (guessExtension)
            // y whitelisteamos a formatos de imagen/pdf esperados.
            $ext = strtolower((string) $file->guessExtension() ?: 'bin');
            $extPermitidas = ['jpg','jpeg','png','webp','gif','pdf','svg'];
            if (! in_array($ext, $extPermitidas, true)) {
                $ext = 'bin';
            }
            $tmpPath = "tmp-uploads/{$userId}/{$campo}.{$ext}";
            Storage::disk('local')->putFileAs(
                "tmp-uploads/{$userId}",
                $file,
                "{$campo}.{$ext}"
            );
            $request->session()->put($sessionKey, [
                'path' => $tmpPath,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'saved_at' => now()->toIso8601String(),
            ]);
            return;
        }

        // Caso B: no viene file, pero hay tmp de sesión → inyectar al request.
        $tmp = $request->session()->get($sessionKey);
        if (! is_array($tmp) || empty($tmp['path'])) {
            return;
        }
        // Defense-in-depth: rechazar cualquier path que no sea del prefijo
        // canónico del user actual. Blinda contra corrupción/leak de sesión.
        $prefijoOk = str_starts_with((string) $tmp['path'], "tmp-uploads/{$userId}/");
        if (! $prefijoOk) {
            $request->session()->forget($sessionKey);
            return;
        }
        $fullPath = Storage::disk('local')->path($tmp['path']);
        if (! is_file($fullPath)) {
            $request->session()->forget($sessionKey);
            return;
        }
        // Ojo: UploadedFile con $test=true evita chequeo isValid() que
        // solo pasa para archivos subidos vía HTTP dentro del mismo request.
        $request->files->set($campo, new UploadedFile(
            $fullPath,
            $tmp['original_name'] ?? basename($fullPath),
            $tmp['mime'] ?? null,
            null,
            true, // $test — marca el archivo como "válido" (no vino del HTTP actual)
        ));

        // Fix crítico: `$request->file()` cachea `$convertedFiles` la primera
        // vez que se lee. Cualquier `hasFile()`/`file()` posterior devuelve
        // el snapshot cacheado (sin nuestro tmp) — el bug B1 seguía vivo.
        // Invalidamos el caché con reflexión para que la próxima lectura
        // vea el file inyectado.
        try {
            $ref = new \ReflectionObject($request);
            if ($ref->hasProperty('convertedFiles')) {
                $prop = $ref->getProperty('convertedFiles');
                $prop->setAccessible(true);
                $prop->setValue($request, null);
            }
        } catch (\Throwable $e) {
            // Peor caso: hasFile devuelve false y la validación required falla.
            // El usuario ve el mensaje de error y re-sube el logo (comportamiento
            // pre-fix, mismo escenario que si no tuviéramos trait).
            report($e);
        }
    }

    /** Limpia el tmp file del campo tras save exitoso. */
    protected function limpiarArchivoTemporal(Request $request, string $campo): void
    {
        $sessionKey = "tmp_upload_{$campo}";
        $tmp = $request->session()->pull($sessionKey);
        if (is_array($tmp) && ! empty($tmp['path'])) {
            Storage::disk('local')->delete($tmp['path']);
        }
    }
}
