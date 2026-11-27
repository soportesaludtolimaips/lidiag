<?php

namespace App\Models\Admisiones;

use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigSede;
use App\Models\Estudio\Estudio;
use App\Models\General\Paciente;
use App\Models\User;
use App\Models\Worklist\WorklistOrden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Modelo Eloquent para la trazabilidad integral de admisiones clínicas y su flujo hacia Worklist/PACS/Lectura.
 *
 * @property int $id
 * @property int $paciente_id
 * @property int $sede_id
 * @property int|null $worklist_orden_id
 * @property int|null $estudio_id
 * @property int|null $producto_id
 * @property int|null $usuario_admision_id
 * @property int|null $usuario_asignacion_id
 * @property string|null $accession_no
 * @property string|null $study_iuid
 * @property string|null $study_id
 * @property int|null $study_pk
 * @property string|null $modality
 * @property string|null $study_desc
 * @property Carbon $fecha_admision
 * @property Carbon|null $fecha_toma
 * @property Carbon|null $fecha_asignacion
 * @property string $estado
 * @property string|null $atencion
 * @property string|null $eps
 * @property string|null $contrato
 * @property string|null $plan
 * @property string|null $servicio
 * @property string|null $ubicacion
 * @property string|null $cama
 * @property string|null $medico_ordena
 * @property string|null $observaciones
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-27
 */
class AdminAdmision extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const ESTADO_ADMISIONADO = "ADMISIONADO";

    public const ESTADO_TOMANDO_ESTUDIO = "TOMANDO ESTUDIO";

    public const ESTADO_PENDIENTE_LECTURA = "PENDIENTE LECTURA";

    public const ESTADO_ASIGNADO = "ASIGNADO";

    public const ESTADO_CANCELADO = "CANCELADO";

    /**
     * Lista canónica de estados permitidos en el workflow de admisiones.
     *
     * @var array<int, string>
     */
    public const ESTADOS_VALIDOS = [
        self::ESTADO_ADMISIONADO,
        self::ESTADO_TOMANDO_ESTUDIO,
        self::ESTADO_PENDIENTE_LECTURA,
        self::ESTADO_ASIGNADO,
        self::ESTADO_CANCELADO,
    ];

    protected $table = "admin_admisiones";

    protected $fillable = [
        "paciente_id",
        "sede_id",
        "worklist_orden_id",
        "estudio_id",
        "producto_id",
        "usuario_admision_id",
        "usuario_asignacion_id",
        "accession_no",
        "study_iuid",
        "study_id",
        "study_pk",
        "modality",
        "study_desc",
        "fecha_admision",
        "fecha_toma",
        "fecha_asignacion",
        "estado",
        "atencion",
        "eps",
        "contrato",
        "plan",
        "servicio",
        "ubicacion",
        "cama",
        "medico_ordena",
        "observaciones",
    ];

    protected $casts = [
        "paciente_id" => "integer",
        "sede_id" => "integer",
        "worklist_orden_id" => "integer",
        "estudio_id" => "integer",
        "producto_id" => "integer",
        "usuario_admision_id" => "integer",
        "usuario_asignacion_id" => "integer",
        "study_pk" => "integer",
        "fecha_admision" => "datetime",
        "fecha_toma" => "datetime",
        "fecha_asignacion" => "datetime",
    ];

    /**
     * Normaliza alias textuales de estado hacia su constante canónica.
     *
     * Flujo del proceso:
     * 1. Limpia espacios y convierte el texto recibido a mayúsculas.
     * 2. Homologa variantes con guion bajo o capitalización mixta hacia las constantes oficiales.
     *
     * @param  string  $estado  Estado crudo a normalizar.
     * @return string Estado canónico normalizado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public static function normalizarEstado(string $estado): string
    {
        $limpio = strtoupper(trim(str_replace("_", " ", $estado)));

        return match ($limpio) {
            "ADMISIONADO" => self::ESTADO_ADMISIONADO,
            "TOMANDO ESTUDIO" => self::ESTADO_TOMANDO_ESTUDIO,
            "PENDIENTE LECTURA" => self::ESTADO_PENDIENTE_LECTURA,
            "ASIGNADO", "COMPLETADO" => self::ESTADO_ASIGNADO,
            "CANCELADO", "ANULADO" => self::ESTADO_CANCELADO,
            default => $limpio,
        };
    }

    /**
     * Paciente asociado al registro de admisión.
     *
     * Flujo del proceso:
     * 1. Establece relación BelongsTo con la tabla pacientes mediante paciente_id.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, "paciente_id");
    }

    /**
     * Sede hospitalaria donde se registró la admisión.
     *
     * Flujo del proceso:
     * 1. Establece relación BelongsTo con config_sedes mediante sede_id.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function sede(): BelongsTo
    {
        return $this->belongsTo(ConfigSede::class, "sede_id");
    }

    /**
     * Orden de Modality Worklist vinculada a la admisión.
     *
     * Flujo del proceso:
     * 1. Establece relación BelongsTo con worklist_ordenes mediante worklist_orden_id.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function worklistOrden(): BelongsTo
    {
        return $this->belongsTo(WorklistOrden::class, "worklist_orden_id");
    }

    /**
     * Estudio radiológico generado tras enviar la admisión a lectura.
     *
     * Flujo del proceso:
     * 1. Establece relación BelongsTo con estudios mediante estudio_id.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function estudio(): BelongsTo
    {
        return $this->belongsTo(Estudio::class, "estudio_id");
    }

    /**
     * Procedimiento o producto CUPS solicitado en la admisión.
     *
     * Flujo del proceso:
     * 1. Establece relación BelongsTo con config_productos mediante producto_id.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(ConfigProducto::class, "producto_id");
    }

    /**
     * Usuario que realizó la admisión del paciente.
     *
     * Flujo del proceso:
     * 1. Establece relación BelongsTo con users mediante usuario_admision_id.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function usuarioAdmision(): BelongsTo
    {
        return $this->belongsTo(User::class, "usuario_admision_id");
    }

    /**
     * Usuario que asignó el estudio tomado para lectura médica.
     *
     * Flujo del proceso:
     * 1. Establece relación BelongsTo con users mediante usuario_asignacion_id.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function usuarioAsignacion(): BelongsTo
    {
        return $this->belongsTo(User::class, "usuario_asignacion_id");
    }

    /**
     * Filtra las admisiones pertenecientes a una sede específica.
     *
     * Flujo del proceso:
     * 1. Aplica condición where sobre sede_id.
     *
     * @param  Builder  $query  Constructor de consulta Eloquent.
     * @param  int  $sedeId  ID de la sede activa.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function scopePorSede(Builder $query, int $sedeId): Builder
    {
        return $query->where("sede_id", $sedeId);
    }

    /**
     * Filtra las admisiones listas en estado PENDIENTE LECTURA sin estudio asignado aún.
     *
     * Flujo del proceso:
     * 1. Filtra por el estado PENDIENTE LECTURA (incluyendo variante con guion bajo por compatibilidad).
     * 2. Asegura que estudio_id sea nulo para evitar duplicar asignaciones.
     *
     * @param  Builder  $query  Constructor de consulta Eloquent.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function scopePendientesLectura(Builder $query): Builder
    {
        return $query->whereIn("estado", [self::ESTADO_PENDIENTE_LECTURA, "PENDIENTE_LECTURA"])
            ->whereNull("estudio_id");
    }
}
