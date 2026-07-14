<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class BaseService
{
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

    protected function success(mixed $data = null, string $message = 'Operación exitosa', int $status = 200): array
    {
        return ['success' => true, 'data' => $data, 'message' => $message, 'status' => $status];
    }

    protected function created(mixed $data = null, string $message = 'Registro creado correctamente'): array
    {
        return $this->success($data, $message, 201);
    }

    protected function updated(mixed $data = null, string $message = 'Registro actualizado correctamente'): array
    {
        return $this->success($data, $message, 200);
    }

    protected function deleted(string $message = 'Registro eliminado correctamente'): array
    {
        return $this->success(null, $message, 200);
    }

    protected function error(string $message, int $status = 400, mixed $errors = null): array
    {
        $result = ['success' => false, 'data' => null, 'message' => $message, 'status' => $status];
        if ($errors !== null) {
            $result['errors'] = $errors;
        }

        return $result;
    }

    protected function notFound(string $message = 'Registro no encontrado'): array
    {
        return $this->error($message, 404);
    }

    protected function unauthorized(string $message = 'No autorizado'): array
    {
        return $this->error($message, 403);
    }

    protected function validationError(mixed $errors, string $message = 'Error de validación'): array
    {
        return $this->error($message, 422, $errors);
    }

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
