<?php

namespace App\Console\Commands;

use App\Models\ProfessionalProfile;
use App\Models\User;
use Illuminate\Console\Command;

class QaCheck extends Command
{
    protected $signature = 'qa:check';

    protected $description = 'Verifica escenarios borde del QA seeder';

    public function handle(): int
    {
        $sus = User::where('email', 'qa.suspendido@kinvoo.test')->first()?->professionalProfile;
        $this->line('Suspendido visible públicamente: '.($sus?->esVisiblePublicamente() ? 'SÍ (BUG)' : 'NO (ok)'));

        $slugs = ProfessionalProfile::whereHas('user', fn ($q) => $q->whereIn('email', ['qa.juan1@kinvoo.test', 'qa.juan2@kinvoo.test']))
            ->pluck('slug')->all();
        $this->line('Slugs colisión Juan: '.implode(', ', $slugs));

        $xss = User::where('email', 'qa.xss@kinvoo.test')->first()?->professionalProfile;
        $this->line('Slug XSS: '.$xss?->slug);

        $vacio = User::where('email', 'qa.vacio@kinvoo.test')->first()?->professionalProfile;
        $this->line('Perfil vacío %: '.$vacio?->porcentajeCompleto().' | publicado: '.($vacio?->is_published ? 'sí' : 'no'));

        $full = User::where('email', 'qa.completo@kinvoo.test')->first()?->professionalProfile;
        $this->line('Perfil completo %: '.$full?->porcentajeCompleto());

        $this->line('Visibles en buscador: '.ProfessionalProfile::visiblePublicamente()->count());

        return self::SUCCESS;
    }
}
