<?php

namespace Database\Seeders;

use App\Enums\EstadoContacto;
use App\Enums\EstadoUsuario;
use App\Enums\ModalidadTrabajo;
use App\Enums\RolUsuario;
use App\Models\Discipline;
use App\Models\Location;
use App\Models\ProfessionalProfile;
use App\Models\User;
use App\Notifications\CuentaAprobadaNotification;
use App\Notifications\NuevoContactoNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Datos de QA: dataset amplio + casos borde para probar todo a fondo.
 * Correr con: php artisan db:seed --class=QaSeeder
 */
class QaSeeder extends Seeder
{
    private array $slugsDisciplinas;
    private array $ciudades;
    private array $modalidades;

    public function run(): void
    {
        $this->call(TaxonomiaSeeder::class);

        $this->slugsDisciplinas = Discipline::pluck('slug')->all();
        $this->ciudades = Location::pluck('ciudad')->all();
        $this->modalidades = array_column(ModalidadTrabajo::cases(), 'value');

        $f = fake('es_ES');

        // ---- 30 profesionales "normales": activos, publicados, verificación y foto variadas ----
        for ($i = 0; $i < 30; $i++) {
            $u = $this->usuario('qa.pro'.$i.'@kinvoo.test', $f->name(), RolUsuario::Professional, EstadoUsuario::Activo);
            $this->perfilProfesional($u, [
                'headline' => $f->randomElement(['Coach de fuerza', 'Instructor de yoga', 'Entrenador personal', 'Coach de CrossFit', 'Nutriólogo deportivo', 'Instructor de spinning', 'Preparador físico']),
                'bio' => $f->paragraph(rand(1, 4)),
                'years_experience' => rand(0, 20),
                'modalidad' => $f->randomElement($this->modalidades),
                'is_published' => true,
                'is_verified' => (bool) rand(0, 1),
                'con_foto' => (bool) rand(0, 1),
                'discs' => $f->randomElements($this->slugsDisciplinas, rand(1, 4)),
            ]);
        }

        // ---- Casos borde ----

        // 1) Suspendido con perfil PUBLICADO → NO debe verse en público (A1)
        $sus = $this->usuario('qa.suspendido@kinvoo.test', 'Sergio Suspendido', RolUsuario::Professional, EstadoUsuario::Suspendido);
        $this->perfilProfesional($sus, ['headline' => 'Coach suspendido', 'bio' => 'No debería aparecer.', 'is_published' => true, 'is_verified' => true, 'discs' => ['yoga']]);

        // 2) Pendiente con perfil publicado → tampoco visible (dueño no activo)
        $pen = $this->usuario('qa.pendiente@kinvoo.test', 'Paola Pendiente', RolUsuario::Professional, EstadoUsuario::Pendiente);
        $this->perfilProfesional($pen, ['headline' => 'Coach pendiente', 'is_published' => true, 'discs' => ['crossfit']]);

        // 3) Colisión de slug: dos "Juan Pérez"
        $j1 = $this->usuario('qa.juan1@kinvoo.test', 'Juan Pérez', RolUsuario::Professional, EstadoUsuario::Activo);
        $j2 = $this->usuario('qa.juan2@kinvoo.test', 'Juan Pérez', RolUsuario::Professional, EstadoUsuario::Activo);
        $this->perfilProfesional($j1, ['headline' => 'Coach A', 'is_published' => true, 'discs' => ['boxeo']]);
        $this->perfilProfesional($j2, ['headline' => 'Coach B', 'is_published' => true, 'discs' => ['boxeo']]);

        // 4) Nombre con intento de XSS → debe verse escapado, nunca ejecutarse
        $x = $this->usuario('qa.xss@kinvoo.test', '</script><script>alert(1)</script>', RolUsuario::Professional, EstadoUsuario::Activo);
        $this->perfilProfesional($x, ['headline' => 'Prueba <b>XSS</b>', 'bio' => 'Bio con <img src=x onerror=alert(1)>', 'is_published' => true, 'discs' => ['hiit']]);

        // 5) Perfil vacío (0% completo), sin publicar
        $this->usuario('qa.vacio@kinvoo.test', 'Vera Vacía', RolUsuario::Professional, EstadoUsuario::Activo)
            ->professionalProfile()->firstOrCreate([]);

        // 6) Perfil completo al 100% + verificado + publicado
        $full = $this->usuario('qa.completo@kinvoo.test', 'Carla Completa', RolUsuario::Professional, EstadoUsuario::Activo);
        $this->perfilProfesional($full, [
            'headline' => 'Coach integral certificada',
            'bio' => 'Perfil completísimo para probar el 100%.',
            'years_experience' => 12,
            'modalidad' => 'hibrido',
            'is_published' => true,
            'is_verified' => true,
            'con_foto' => true,
            'socials' => ['web' => 'https://carla.example.com', 'instagram' => '@carla', 'tiktok' => '@carla'],
            'discs' => ['yoga', 'pilates'],
            'completo' => true,
        ]);

        // ---- Contratantes ----
        $contratantes = [];
        for ($i = 0; $i < 8; $i++) {
            $c = $this->usuario('qa.gym'.$i.'@kinvoo.test', $f->company(), RolUsuario::Contractor, EstadoUsuario::Activo);
            $c->companyProfile()->firstOrCreate([], [
                'company_name' => $c->name,
                'sector' => $f->randomElement(['Gimnasio', 'Estudio de yoga', 'Box de CrossFit', 'Marca deportiva', 'Estudio boutique']),
                'location_id' => Location::where('ciudad', $f->randomElement($this->ciudades))->value('id'),
            ]);
            $contratantes[] = $c;
        }
        // 2 contratantes pendientes
        $this->usuario('qa.gympend1@kinvoo.test', 'Gimnasio Pendiente', RolUsuario::Contractor, EstadoUsuario::Pendiente);
        $this->usuario('qa.gympend2@kinvoo.test', 'Marca Pendiente', RolUsuario::Contractor, EstadoUsuario::Pendiente);

        // ---- Interacciones: contactos, vistas, favoritos, notificaciones ----
        $publicados = ProfessionalProfile::visiblePublicamente()->get();

        foreach ($contratantes as $c) {
            // cada contratante contacta 1-3 profesionales y guarda 2-5
            foreach ($publicados->random(min(3, $publicados->count())) as $p) {
                $contacto = $p->contacts()->create([
                    'contractor_user_id' => $c->id,
                    'contact_name' => $c->name,
                    'contact_email' => $c->email,
                    'contact_phone' => $f->phoneNumber(),
                    'message' => $f->sentence(rand(8, 20)),
                    'estado' => $f->randomElement([EstadoContacto::NoLeido, EstadoContacto::Leido]),
                ]);
                $p->user->notify(new NuevoContactoNotification($contacto));
            }
            foreach ($publicados->random(min(rand(2, 5), $publicados->count())) as $p) {
                $c->saves()->firstOrCreate([
                    'saveable_type' => $p->getMorphClass(),
                    'saveable_id' => $p->id,
                ]);
            }
        }

        // Vistas de perfil (logueadas y anónimas)
        $viewers = User::where('nivel', RolUsuario::Contractor->value)->pluck('id')->all();
        foreach ($publicados as $p) {
            for ($v = 0; $v < rand(0, 25); $v++) {
                $p->views()->create([
                    'viewer_user_id' => rand(0, 3) === 0 ? null : $f->randomElement($viewers),
                ]);
            }
        }

        // Notificación de cuenta aprobada de ejemplo
        $full->notify(new CuentaAprobadaNotification());

        $this->resumen();
    }

    private function usuario(string $email, string $name, RolUsuario $rol, EstadoUsuario $estado): User
    {
        $u = User::updateOrCreate(['email' => $email], ['name' => $name, 'password' => Hash::make('password')]);
        $u->forceFill(['nivel' => $rol, 'estado' => $estado, 'email_verified_at' => now()])->save();

        return $u;
    }

    private function perfilProfesional(User $u, array $data): void
    {
        $completo = $data['completo'] ?? false;
        $p = $u->professionalProfile()->firstOrCreate([]);
        $p->update([
            'headline' => $data['headline'] ?? null,
            'bio' => $data['bio'] ?? null,
            // Campos nuevos (Lotes B): si el perfil es "completo" o publicado, se llenan
            // para que el % de completitud sea real y las secciones nuevas tengan contenido.
            'birthdate' => ($completo || ($data['is_published'] ?? false)) ? '1992-04-15' : null,
            'availability' => ($completo || ($data['is_published'] ?? false)) ? ['lun_am', 'mie_pm', 'vie_am', 'fds_am'] : null,
            'languages' => ($completo || ($data['is_published'] ?? false)) ? ['es', 'en'] : null,
            'certifications_text' => ($completo || ($data['is_published'] ?? false)) ? 'NASM-CPT, Instructor certificado, Primeros auxilios' : null,
            'media_url' => $completo ? 'https://youtube.com/watch?v=qa-demo' : null,
            'years_experience' => $data['years_experience'] ?? null,
            'modalidad' => $data['modalidad'] ?? null,
            'location_id' => Location::where('ciudad', fake('es_ES')->randomElement($this->ciudades))->value('id'),
            'socials' => $data['socials'] ?? [],
            'is_published' => $data['is_published'] ?? false,
            'is_verified' => $data['is_verified'] ?? false,
            'verified_at' => ($data['is_verified'] ?? false) ? now() : null,
            'photo_path' => ($data['con_foto'] ?? false || $completo) ? 'perfiles/placeholder.jpg' : null,
        ]);
        if (! empty($data['discs'])) {
            $p->disciplines()->sync(Discipline::whereIn('slug', $data['discs'])->pluck('id'));
        }
    }

    private function resumen(): void
    {
        $c = fn ($m) => $m::count();
        $this->command->info('=== QA data sembrada ===');
        $this->command->info('Usuarios: '.$c(User::class).' | Perfiles: '.$c(ProfessionalProfile::class)
            .' | Publicados+activos visibles: '.ProfessionalProfile::visiblePublicamente()->count());
        $this->command->info('Contactos: '.$c(\App\Models\Contact::class).' | Vistas: '.$c(\App\Models\ProfileView::class)
            .' | Guardados: '.$c(\App\Models\Save::class));
        $this->command->info('Casos borde: qa.suspendido / qa.pendiente / qa.juan1&2 (slug) / qa.xss / qa.vacio / qa.completo (todos @kinvoo.test, pass "password")');
    }
}
