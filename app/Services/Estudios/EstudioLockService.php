<?php

namespace App\Services\Estudios;

use App\Models\Configuracion\ConfigSede;
use App\Models\Estudio\EstudioLock;
use App\Models\Estudio\EstudioProducto;
use App\Services\BaseService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Reservas (locks) de lectura y asignación de estudios.
 *
 * Impide que dos usuarios abran el mismo registro a la vez: al abrir se
 * toma la reserva por 2 minutos (o `config_sedes.lock_min` si está
 * configurado), se renueva con cada heartbeat y se libera al guardar,
 * cerrar o expirar. Cualquier escritura posterior valida la reserva.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-08
 */
class EstudioLockService extends BaseService
{
    /**
     * Timeout por defecto en minutos cuando la sede no define lock_min.
     */
    public const MINUTOS_DEFAULT = 2;

    /**
     * Toma (o renueva) la reserva de un estudio para el usuario actual.
     *
     * @param  string  $tipo  'lectura' o 'asignacion'
     * @param  array  $datos  estudio_producto_id (lectura) o study_iuid/study_pk/sede_id (asignación)
     *
     * @returns array Respuesta con la clave y la expiración, o 409 si otro usuario la tiene
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function tomar(string $tipo, array $datos): array
    {
        $clave = $this->resolverClave($tipo, $datos);
        if ($clave === null) {
            return $this->validationError(['clave' => ['No se pudo identificar el registro a reservar.']]);
        }

        $minutos = $this->minutosDisponibles($tipo, $datos);
        $ahora = Carbon::now();

        // Limpieza oportunista de reservas expiradas de esta clave.
        EstudioLock::where('tipo', $tipo)
            ->where('clave', $clave)
            ->where('hasta', '<=', $ahora)
            ->delete();

        $lock = EstudioLock::where('tipo', $tipo)
            ->where('clave', $clave)
            ->first();

        if ($lock !== null) {
            if ((int) $lock->user_id === (int) Auth::id()) {
                return $this->renovar($lock, $minutos, $ahora);
            }

            return $this->conflicto($lock);
        }

        try {
            $lock = EstudioLock::create([
                'tipo' => $tipo,
                'clave' => $clave,
                'user_id' => Auth::id(),
                'hasta' => $ahora->copy()->addMinutes($minutos),
            ]);

            return $this->reservaExitosa($lock, 'Reserva adquirida.');
        } catch (UniqueConstraintViolationException) {
            // Carrera entre dos usuarios: gana el insert del otro.
            $lock = EstudioLock::where('tipo', $tipo)->where('clave', $clave)->first();
            if ($lock !== null && (int) $lock->user_id === (int) Auth::id()) {
                return $this->renovar($lock, $minutos, $ahora);
            }

            return $lock !== null ? $this->conflicto($lock) : $this->error('No fue posible reservar el registro.', 500);
        }
    }

    /**
     * Libera la reserva de la clave si pertenece al usuario actual (idempotente).
     *
     * @param  string  $tipo  'lectura' o 'asignacion'
     * @param  array  $datos  Mismos datos usados en tomar()
     *
     * @returns array Respuesta de confirmación
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function liberar(string $tipo, array $datos): array
    {
        $clave = $this->resolverClave($tipo, $datos);
        if ($clave === null) {
            return $this->validationError(['clave' => ['No se pudo identificar el registro a liberar.']]);
        }

        $this->liberarPorClave($tipo, $clave);

        return $this->success(null, 'Reserva liberada.');
    }

    /**
     * Elimina la reserva de la clave solo si es del usuario actual.
     *
     * @param  string  $tipo  'lectura' o 'asignacion'
     * @param  string  $clave  Clave canónica de la reserva
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function liberarPorClave(string $tipo, string $clave): void
    {
        EstudioLock::where('tipo', $tipo)
            ->where('clave', $clave)
            ->where('user_id', Auth::id())
            ->delete();
    }

    /**
     * Valida si la clave está reservada por OTRO usuario (no expirada).
     *
     * @param  string  $tipo  'lectura' o 'asignacion'
     * @param  string  $clave  Clave canónica de la reserva
     *
     * @returns array|null null si está libre/es propia; array con status 409 si otro la tiene
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function verificar(string $tipo, string $clave): ?array
    {
        $lock = EstudioLock::where('tipo', $tipo)
            ->where('clave', $clave)
            ->where('hasta', '>', Carbon::now())
            ->with('user:id,name')
            ->first();

        if ($lock === null || (int) $lock->user_id === (int) Auth::id()) {
            return null;
        }

        return $this->conflicto($lock);
    }

    /**
     * Construye la clave canónica de una reserva.
     *
     * @param  string  $tipo  'lectura' o 'asignacion'
     * @param  array  $datos  Parámetros del registro
     *
     * @returns string|null Clave canónica o null si no hay identificador
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function resolverClave(string $tipo, array $datos): ?string
    {
        if ($tipo === 'lectura') {
            $id = $datos['estudio_producto_id'] ?? null;

            return $id !== null && $id !== '' ? 'ep:'.(int) $id : null;
        }

        $iuid = trim((string) ($datos['study_iuid'] ?? ''));
        if ($iuid !== '') {
            return 'iuid:'.$iuid;
        }

        $pk = $datos['study_pk'] ?? null;

        return $pk !== null && $pk !== '' && (int) $pk > 0 ? 'pk:'.(int) $pk : null;
    }

    /**
     * Clave canónica de lectura para un producto de estudio.
     *
     * @param  int  $estudioProductoId  ID del producto de estudio
     *
     * @returns string Clave canónica
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function claveLectura(int $estudioProductoId): string
    {
        return 'ep:'.$estudioProductoId;
    }

    /**
     * Determina los minutos de reserva a aplicar (sede o default).
     *
     * @param  string  $tipo  'lectura' o 'asignacion'
     * @param  array  $datos  Parámetros (sede_id directo o study para lectura)
     *
     * @returns int Minutos de reserva
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    private function minutosDisponibles(string $tipo, array $datos): int
    {
        $sedeId = $datos['sede_id'] ?? null;

        if ($tipo === 'lectura' && empty($sedeId) && ! empty($datos['estudio_producto_id'])) {
            $sedeId = EstudioProducto::query()
                ->join('estudios', 'estudios.id', '=', 'estudios_productos.estudio_id')
                ->where('estudios_productos.id', (int) $datos['estudio_producto_id'])
                ->value('estudios.sede_id');
        }

        if (! empty($sedeId)) {
            $lockMin = ConfigSede::where('id', (int) $sedeId)->value('lock_min');
            if (! empty($lockMin) && (int) $lockMin > 0) {
                return (int) $lockMin;
            }
        }

        return self::MINUTOS_DEFAULT;
    }

    /**
     * Renueva la reserva propia extendiendo la expiración.
     *
     * @param  EstudioLock  $lock  Reserva existente del usuario actual
     * @param  int  $minutos  Minutos a extender
     * @param  Carbon  $ahora  Fecha/hora actual
     *
     * @returns array Respuesta con la nueva expiración
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    private function renovar(EstudioLock $lock, int $minutos, Carbon $ahora): array
    {
        $lock->hasta = $ahora->copy()->addMinutes($minutos);
        $lock->save();

        return $this->reservaExitosa($lock, 'Reserva renovada.');
    }

    /**
     * Arma la respuesta exitosa de una reserva.
     *
     * @param  EstudioLock  $lock  Reserva creada o renovada
     * @param  string  $mensaje  Mensaje de confirmación
     *
     * @returns array Respuesta con clave y expiración
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    private function reservaExitosa(EstudioLock $lock, string $mensaje): array
    {
        return $this->success([
            'clave' => $lock->clave,
            'hasta' => $lock->hasta?->format('Y-m-d H:i:s'),
        ], $mensaje);
    }

    /**
     * Arma la respuesta 409 de un conflicto de reserva.
     *
     * @param  EstudioLock  $lock  Reserva ajena activa
     *
     * @returns array Respuesta con status 409 y datos del titular
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    private function conflicto(EstudioLock $lock): array
    {
        $lock->loadMissing('user:id,name');
        $titular = $lock->user?->name ?? 'otro usuario';
        $hasta = $lock->hasta?->format('H:i') ?? '';

        return $this->error(
            "Este registro está siendo atendido por {$titular} hasta las {$hasta}. Intente de nuevo en unos segundos.",
            409,
            ['titular' => $titular, 'hasta' => $lock->hasta?->format('Y-m-d H:i:s')]
        );
    }
}
