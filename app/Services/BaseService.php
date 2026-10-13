<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class BaseService
{
    /**
     * Ejecuta una operación dentro de una transacción de base de datos con reintentos.
     *
     * @param  callable  $callback  Operación a ejecutar dentro de la transacción
     * @param  int  $attempts  Número máximo de intentos ante fallos transitorios
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    protected function transaction(callable $callback, int $attempts = 3): array
    {
        try {
            return DB::transaction($callback, $attempts);
        } catch (\Throwable $e) {
            Log::error($e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error('Error interno del servidor', 500);
        }
    }

    /**
     * Construye una respuesta estándar de éxito para las operaciones de los servicios.
     *
     * @param  mixed  $data  Datos a devolver en la respuesta
     * @param  string  $message  Mensaje descriptivo del resultado
     * @param  int  $status  Código HTTP asociado a la respuesta
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    protected function success(mixed $data = null, string $message = 'Operación exitosa', int $status = 200): array
    {
        return ['success' => true, 'data' => $data, 'message' => $message, 'status' => $status];
    }

    /**
     * Construye una respuesta estándar de éxito con código HTTP 201 para recursos creados.
     *
     * @param  mixed  $data  Datos del recurso creado
     * @param  string  $message  Mensaje descriptivo del resultado
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    protected function created(mixed $data = null, string $message = 'Registro creado correctamente'): array
    {
        return $this->success($data, $message, 201);
    }

    /**
     * Construye una respuesta estándar de éxito para recursos actualizados.
     *
     * @param  mixed  $data  Datos del recurso actualizado
     * @param  string  $message  Mensaje descriptivo del resultado
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    protected function updated(mixed $data = null, string $message = 'Registro actualizado correctamente'): array
    {
        return $this->success($data, $message, 200);
    }

    /**
     * Construye una respuesta estándar de éxito para recursos eliminados.
     *
     * @param  string  $message  Mensaje descriptivo del resultado
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    protected function deleted(string $message = 'Registro eliminado correctamente'): array
    {
        return $this->success(null, $message, 200);
    }

    /**
     * Construye una respuesta estándar de error para las operaciones de los servicios.
     *
     * @param  string  $message  Mensaje descriptivo del error
     * @param  int  $status  Código HTTP asociado al error
     * @param  mixed  $errors  Detalles adicionales del error, si existen
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    protected function error(string $message, int $status = 400, mixed $errors = null): array
    {
        $result = ['success' => false, 'data' => null, 'message' => $message, 'status' => $status];
        if ($errors !== null) {
            $result['errors'] = $errors;
        }

        return $result;
    }

    /**
     * Construye una respuesta de error con código HTTP 404 para recursos no encontrados.
     *
     * @param  string  $message  Mensaje descriptivo del recurso no encontrado
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    protected function notFound(string $message = 'Registro no encontrado'): array
    {
        return $this->error($message, 404);
    }

    /**
     * Construye una respuesta de error con código HTTP 403 para accesos no autorizados.
     *
     * @param  string  $message  Mensaje descriptivo de la denegación
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    protected function unauthorized(string $message = 'No autorizado'): array
    {
        return $this->error($message, 403);
    }

    /**
     * Construye una respuesta de error con código HTTP 422 para fallos de validación.
     *
     * @param  mixed  $errors  Detalles de los campos que fallaron la validación
     * @param  string  $message  Mensaje descriptivo del error de validación
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    protected function validationError(mixed $errors, string $message = 'Error de validación'): array
    {
        return $this->error($message, 422, $errors);
    }

    /**
     * Extrae los identificadores de los elementos marcados como seleccionados en un arreglo.
     *
     * @param  array  $data  Arreglo de datos que contiene la lista de elementos
     * @param  string  $key  Clave dentro del arreglo que contiene la lista de elementos
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    protected function extractCheckedIds(array $data, string $key): array
    {
        if (! isset($data[$key]) || ! is_array($data[$key])) {
            return [];
        }

        return collect($data[$key])
            ->where('checked', true)
            ->pluck('id')
            ->values()
            ->toArray();
    }
}
