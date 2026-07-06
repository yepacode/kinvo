<?php

namespace App\Console\Commands;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Console\Command;

class QaCheck extends Command
{
    protected $signature = 'qa:users';

    protected $description = 'Lista cuentas representativas por rol';

    public function handle(): int
    {
        $muestra = [
            'hola@gokinvoo.com',
            'qa.pro0@kinvoo.test',
            'qa.completo@kinvoo.test',
            'qa.vacio@kinvoo.test',
            'qa.pendiente@kinvoo.test',
            'qa.suspendido@kinvoo.test',
            'qa.gym0@kinvoo.test',
            'qa.gympend1@kinvoo.test',
        ];

        foreach ($muestra as $email) {
            $u = User::where('email', $email)->first();
            if (! $u) {
                $this->line($email.' → (no existe)');
                continue;
            }
            $this->line(str_pad($email, 30).' | '.str_pad($u->nivel->label(), 13).' | '.$u->estado->label());
        }

        $this->newLine();
        $this->line('Totales → Profesionales activos: '.User::where('nivel', RolUsuario::Professional->value)->count()
            .' | Contratantes: '.User::where('nivel', RolUsuario::Contractor->value)->count()
            .' | Admin: '.User::where('nivel', RolUsuario::Admin->value)->count());

        return self::SUCCESS;
    }
}
