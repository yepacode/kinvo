<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Fase 2 · Trait que registra automáticamente cambios en el modelo al
 * AuditLog polimórfico.
 *
 * Configuración por modelo — sobreescribe `auditableAttributes()` para
 * limitar qué atributos se rastrean (por default rastrea todos los dirty).
 *
 * Uso:
 *   class Subscription extends Model {
 *       use \App\Models\Concerns\Auditable;
 *       protected function auditableAttributes(): array {
 *           return ['status', 'canceled_at']; // solo estos disparan bitácora
 *       }
 *   }
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::updated(function (Model $model) {
            $watched = method_exists($model, 'auditableAttributes')
                ? $model->auditableAttributes()
                : array_keys($model->getDirty());

            $old = [];
            $new = [];
            foreach ($watched as $attr) {
                if (array_key_exists($attr, $model->getChanges())) {
                    $old[$attr] = $model->getOriginal($attr);
                    $new[$attr] = $model->getAttribute($attr);
                }
            }

            if (! empty($new)) {
                AuditLog::record(auth()->user(), $model, 'updated', $old, $new);
            }
        });

        static::created(function (Model $model) {
            AuditLog::record(auth()->user(), $model, 'created');
        });

        static::deleted(function (Model $model) {
            AuditLog::record(auth()->user(), $model, 'deleted');
        });
    }
}
