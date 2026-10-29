<?php

namespace App\Helpers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

/**
 * Helper central para el registro de auditoría de acciones de usuarios y del sistema.
 * Registra eventos en la tabla audit_logs con metadatos de IP, User-Agent y deltas.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-13
 */
class AuditLogger
{
    /**
     * Registra un evento de auditoría en la tabla audit_log.
     *
     * @param  string  $evento  Tipo de evento (crear, actualizar, eliminar u otro).
     * @param  Model|null  $model  Modelo asociado al evento.
     * @param  string|null  $descripcion  Descripción legible del evento.
     * @param  array|null  $datosViejos  Valores previos al cambio.
     * @param  array|null  $datosNuevos  Valores posteriores al cambio.
     * @param  int|null  $userId  ID del usuario responsable (usa el autenticado si es nulo).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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

    /**
     * Registra la creación de un modelo en audit_log.
     *
     * @param  Model  $model  Modelo recién creado.
     * @param  string|null  $descripcion  Descripción opcional del evento.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public static function crear(Model $model, ?string $descripcion = null): AuditLog
    {
        return self::log('crear', $model, $descripcion, null, $model->toArray());
    }

    /**
     * Registra la actualización de un modelo en audit_log.
     *
     * @param  Model  $model  Modelo actualizado.
     * @param  array  $datosViejos  Valores del modelo antes del cambio.
     * @param  string|null  $descripcion  Descripción opcional del evento.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public static function actualizar(Model $model, array $datosViejos, ?string $descripcion = null): AuditLog
    {
        return self::log('actualizar', $model, $descripcion, $datosViejos, $model->toArray());
    }

    /**
     * Registra la eliminación de un modelo en audit_log.
     *
     * @param  Model  $model  Modelo eliminado.
     * @param  string|null  $descripcion  Descripción opcional del evento.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public static function eliminar(Model $model, ?string $descripcion = null): AuditLog
    {
        return self::log('eliminar', $model, $descripcion, $model->toArray(), null);
    }

    /**
     * Registra un evento de auditoría genérico en audit_log.
     *
     * @param  string  $evento  Tipo de evento a registrar.
     * @param  Model|null  $model  Modelo asociado al evento.
     * @param  string|null  $descripcion  Descripción opcional del evento.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public static function evento(string $evento, ?Model $model = null, ?string $descripcion = null): AuditLog
    {
        return self::log($evento, $model, $descripcion);
    }
}
