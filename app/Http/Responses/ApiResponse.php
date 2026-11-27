<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Clase estándar de respuesta HTTP JSON alineada con el contrato de BaseService en LIDiag.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-27
 */
class ApiResponse implements Responsable
{
    /**
     * Inicializa la respuesta HTTP estandarizada.
     *
     * Flujo del proceso:
     * 1. Recibe el arreglo de resultado emitido por la capa de servicio o parámetros explícitos.
     * 2. Almacena estado de éxito, payload, mensaje, código HTTP y errores de validación opcionales.
     *
     * @param  bool  $success  Indicador booleano de operación exitosa.
     * @param  mixed  $data  Carga útil de datos o recurso transformado.
     * @param  string  $message  Mensaje descriptivo para el cliente.
     * @param  int  $status  Código de estado HTTP.
     * @param  mixed  $errors  Detalle de errores de validación si existen.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function __construct(
        private readonly bool $success,
        private readonly mixed $data = null,
        private readonly string $message = "Operación exitosa",
        private readonly int $status = 200,
        private readonly mixed $errors = null,
    ) {}

    /**
     * Construye una instancia de ApiResponse a partir del arreglo estándar retornado por BaseService.
     *
     * Flujo del proceso:
     * 1. Extrae las claves success, data, message, status y errors del arreglo del servicio.
     * 2. Retorna una instancia tipada lista para ser entregada por el controlador.
     *
     * @param  array<string, mixed>  $serviceResult  Respuesta generada por un método de BaseService.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public static function fromService(array $serviceResult): self
    {
        $status = (int) ($serviceResult["status"] ?? 200);
        $success = (bool) ($serviceResult["success"] ?? ($status >= 200 && $status < 300));

        return new self(
            success: $success,
            data: $serviceResult["data"] ?? null,
            message: (string) ($serviceResult["message"] ?? ($success ? "Operación exitosa" : "Error en la solicitud")),
            status: $status,
            errors: $serviceResult["errors"] ?? null,
        );
    }

    /**
     * Transforma el objeto en una respuesta JsonResponse nativa de Laravel con sustitución segura UTF-8.
     *
     * Flujo del proceso:
     * 1. Ensambla el sobre JSON estándar { success, data, message, status }.
     * 2. Adjunta la propiedad errors únicamente cuando está presente.
     * 3. Emite JsonResponse con bandera JSON_INVALID_UTF8_SUBSTITUTE.
     *
     * @param  Request  $request  Solicitud HTTP entrante.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function toResponse($request): JsonResponse
    {
        $payload = [
            "success" => $this->success,
            "data" => $this->data,
            "message" => $this->message,
            "status" => $this->status,
        ];

        if ($this->errors !== null) {
            $payload["errors"] = $this->errors;
        }

        return response()->json($payload, $this->status, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
