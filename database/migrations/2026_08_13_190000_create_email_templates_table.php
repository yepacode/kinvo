<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * M7 · Editor de correos (petición cliente): permite a Marian editar el
 * contenido de cada correo del sistema desde el panel admin sin tocar código.
 *
 * Cada Notification/Mailable lee `EmailTemplate::forKey('xxx')`. Si existe y
 * está activa, usa esos textos. Si no, cae al hard-coded como fallback.
 * Los `{{placeholders}}` se reemplazan con el contexto que pase la clase.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('description'); // qué correo es (para el admin)
            $table->string('subject', 200);
            $table->string('greeting', 200)->nullable();
            $table->text('body');
            $table->string('action_label', 60)->nullable();
            $table->string('action_url_hint', 200)->nullable(); // texto que aparece como URL en el mail
            $table->text('outro')->nullable();
            $table->json('placeholders_hint')->nullable(); // ["coach", "estudio", "url"] — solo hint UI
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
