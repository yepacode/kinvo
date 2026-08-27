<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ContentItem extends Model
{
    use \App\Models\Concerns\HasMediaItems;

    public const TYPE_VIDEO    = 'video';
    public const TYPE_DOCUMENT = 'document';
    public const TYPE_AUDIO    = 'audio';
    public const TYPE_LINK     = 'link';
    public const TYPE_IMAGE    = 'image';
    public const TYPE_BLOG     = 'blog';

    protected $fillable = [
        'slug', 'title', 'description', 'body', 'category', 'type', 'url', 'file_path', 'file_disk',
        'gate_role', 'gate_plan_id', 'is_published', 'published_at',
        'uploader_user_id',
        // H6 · nivel de acceso (1=free, 2/3=premium).
        'access_level',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'views_count' => 'integer',
        'access_level' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (ContentItem $c) {
            if (blank($c->slug)) {
                $base = Str::slug($c->title) ?: 'contenido';
                $slug = $base; $i = 2;
                while (self::where('slug', $slug)->where('id', '!=', $c->id)->exists()) {
                    $slug = $base.'-'.$i; $i++;
                }
                $c->slug = $slug;
            }
        });

        // Al borrar el registro, borramos el archivo físico. Sin esto los archivos
        // quedaban como huérfanos en storage/app/private/contenido/ (leak de disco
        // + posible exposición si algún día se serviera esa carpeta).
        static::deleted(function (ContentItem $c) {
            if (filled($c->file_path)) {
                $disco = $c->file_disk ?: 'public';
                \Illuminate\Support\Facades\Storage::disk($disco)->delete($c->file_path);
            }
        });
    }

    public function gatePlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'gate_plan_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_user_id');
    }

    /** ¿Este ítem lo subió Kinvoo (admin)? */
    public function esOficial(): bool
    {
        return $this->uploader_user_id === null;
    }

    /** ¿Tiene un archivo subido (vs. sólo una URL externa embebida)? */
    public function tieneArchivo(): bool
    {
        return filled($this->file_path);
    }

    /**
     * URL desde la que se sirve el recurso reproducible/descargable.
     * Si hay archivo subido, se sirve por la ruta PRIVADA que valida el plan
     * (el link directo al disco nunca se expone); si no, se usa la URL externa.
     */
    public function archivoUrl(): ?string
    {
        if ($this->tieneArchivo()) {
            return route('contenido.archivo', $this);
        }

        return $this->url;
    }

    public function esBlog(): bool
    {
        return $this->type === self::TYPE_BLOG;
    }

    public function esImagen(): bool
    {
        return $this->type === self::TYPE_IMAGE;
    }

    /**
     * ¿Este user puede ver el ítem? Admin siempre; el rol y el plan de
     * membresía deben coincidir con las restricciones del ítem.
     */
    public function esAccesiblePor(?User $user): bool
    {
        if (! $this->is_published || ! $user) {
            return false;
        }
        if ($user->esAdmin()) {
            return true;
        }
        if ($this->gate_role === 'professional' && ! $user->esProfesional()) {
            return false;
        }
        if ($this->gate_role === 'contractor' && ! $user->esContratante()) {
            return false;
        }
        if ($this->gate_plan_id !== null) {
            return $user->tieneMembresiaActiva() && $user->membership_plan_id === $this->gate_plan_id;
        }
        // Matriz del cliente: estudio sin membresía NO ve contenido (ni siquiera
        // nivel 1). El desarrollo es un servicio que el estudio desbloquea al
        // pagar, sea para consumirlo o para postearlo. El coach free sí ve N1.
        if ($user->esContratante() && ! $user->tieneMembresiaActiva()) {
            return false;
        }
        // H6 · nivel > 1 requiere membresía activa (petición cliente).
        // Nivel 1 (default) es free y visible a cualquier coach/admin.
        if (($this->access_level ?? 1) > 1 && ! $user->tieneMembresiaActiva()) {
            return false;
        }
        return true;
    }
}
