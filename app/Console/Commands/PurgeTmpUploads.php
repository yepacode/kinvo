<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * B1 · GC de tmp uploads huérfanos (docblock del trait PersistsUploadedFile
 * prometía este job diario — antes no existía). Borra cualquier archivo
 * bajo `storage/app/tmp-uploads/` con mtime > 24h. Sin listado recursivo
 * de sesiones (no las conocemos): se limpia por edad del archivo.
 *
 * Uso:
 *   php artisan uploads:purge-tmp            → borra > 24h
 *   php artisan uploads:purge-tmp --hours=72 → umbral personalizado
 *   php artisan uploads:purge-tmp --dry-run  → lista qué borraría
 *
 * Cron sugerido en app/Console/Kernel.php (schedule):
 *   $schedule->command('uploads:purge-tmp')->daily();
 */
class PurgeTmpUploads extends Command
{
    protected $signature = 'uploads:purge-tmp
        {--hours=24 : Edad mínima en horas para borrar}
        {--dry-run : Solo listar, no borrar}';

    protected $description = 'Borra tmp uploads huérfanos del trait PersistsUploadedFile';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $horas = (int) $this->option('hours');
        $dry = (bool) $this->option('dry-run');
        $limite = now()->subHours($horas)->getTimestamp();

        if (! $disk->exists('tmp-uploads')) {
            $this->info('No hay tmp-uploads/. Nada que limpiar.');
            return self::SUCCESS;
        }

        $borrados = 0;
        $bytes = 0;
        foreach ($disk->allFiles('tmp-uploads') as $rel) {
            $mtime = $disk->lastModified($rel);
            if ($mtime >= $limite) {
                continue;
            }
            $size = $disk->size($rel);
            if ($dry) {
                $this->line(sprintf('[DRY] %s · %d bytes · %s',
                    $rel, $size, date('Y-m-d H:i', $mtime)));
            } else {
                $disk->delete($rel);
            }
            $borrados++;
            $bytes += $size;
        }

        $this->info(sprintf('%s %d archivo(s) · %s MB',
            $dry ? 'Encontrados' : 'Borrados',
            $borrados,
            number_format($bytes / 1024 / 1024, 2)
        ));
        return self::SUCCESS;
    }
}
