<?php

namespace App\Helpers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

/**
 * @deprecated Usar App\Providers\AppServiceProvider::registerGlobalActivityLog()
 *             que registra automáticamente created/updated/deleted via Model eventos
 *             en la tabla activity_log. Este helper escribe en audit_log (legacy).
 */
class AuditLogger
{
    public static function log(
        string $evento,
        ?Model $model = null,
        ?string $descripcion = null,
        ?array $datosViejos = null,
        ?array $datosNuevos = null,
        ?int $userId = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId ?? auth()->id(),
            'evento' => $evento,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model ? $model->getKey() : null,
            'descripcion' => $descripcion,
            'datos_viejos' => $datosViejos,
            'datos_nuevos' => $datosNuevos,
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public static function crear(Model $model, ?string $descripcion = null): AuditLog
    {
        return self::log('crear', $model, $descripcion, null, $model->toArray());
    }

    public static function actualizar(Model $model, array $datosViejos, ?string $descripcion = null): AuditLog
    {
        return self::log('actualizar', $model, $descripcion, $datosViejos, $model->toArray());
    }

    public static function eliminar(Model $model, ?string $descripcion = null): AuditLog
    {
        return self::log('eliminar', $model, $descripcion, $model->toArray(), null);
    }

    public static function evento(string $evento, ?Model $model = null, ?string $descripcion = null): AuditLog
    {
        return self::log($evento, $model, $descripcion);
    }
}
