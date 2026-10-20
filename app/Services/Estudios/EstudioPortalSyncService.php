<?php

namespace App\Services\Estudios;

use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EstudioPortalSyncService extends BaseService
{
    /**
     * Servicio de estudios principal.
     */
    protected EstudioService $estudioService;

    /**
     * Constructor del servicio.
     *
     * @param  EstudioService  $estudioService  Servicio de gestión de estudios
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-20
     */
    public function __construct(EstudioService $estudioService)
    {
        $this->estudioService = $estudioService;
    }

    /**
     * Valida los pre-requisitos de configuración del entorno antes de iniciar la sincronización.
     *
     * Flujo del proceso:
     * 1. Verifica la existencia de URL_API_REPORTES en la configuración.
     * 2. Verifica la existencia de API_HMAC_SECRET en la configuración.
     * 3. Verifica la existencia del ejecutable portable de LibreOffice para la generación de PDFs.
     * 4. Retorna el diagnóstico con éxito o error descriptivo.
     *
     * @return array{success: bool, message?: string}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-20
     */
    public function validarPrerrequisitos(): array
    {
        $urlApi = config('app.URL_API_REPORTES');
        if (empty($urlApi)) {
            return [
                'success' => false,
                'message' => 'La variable URL_API_REPORTES no está configurada en el archivo .env.',
            ];
        }

        $secret = config('app.API_HMAC_SECRET');
        if (empty($secret)) {
            return [
                'success' => false,
                'message' => 'La variable API_HMAC_SECRET no está configurada en el archivo .env.',
            ];
        }

        $soffice = base_path('storage/app_lidiag/LibreOfficePortable/App/libreoffice/program/soffice.com');
        if (! file_exists($soffice)) {
            return [
                'success' => false,
                'message' => "El ejecutable de LibreOffice no fue encontrado en: {$soffice}",
            ];
        }

        return ['success' => true];
    }

    /**
     * Construye la consulta base para obtener estudios transcritos listos para sincronizar.
     *
     * Flujo del proceso:
     * 1. Relaciona estudios_productos con estudios, pacientes, sedes y médicos.
     * 2. Filtra únicamente registros transcritos, con lectura y activos.
     * 3. Aplica filtros opcionales de rango de fecha de transcripción, sede o ID puntual.
     * 4. Aplica límite opcional y orden cronológico ascendente.
     *
     * @param  array{
     *     desde?: ?string,
     *     hasta?: ?string,
     *     sede_id?: ?int,
     *     id?: ?int,
     *     limite?: ?int
     * }  $filtros
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-20
     */
    public function construirConsulta(array $filtros = []): Builder
    {
        $query = DB::table('estudios_productos')
            ->join('estudios', 'estudios_productos.estudio_id', '=', 'estudios.id')
            ->join('pacientes', 'estudios.paciente_id', '=', 'pacientes.id')
            ->join('config_sedes', 'estudios.sede_id', '=', 'config_sedes.id')
            ->leftJoin('users as medico', 'estudios.medico_id', '=', 'medico.id')
            ->whereNotNull('estudios_productos.transcriptor_id')
            ->whereNotNull('estudios_productos.fechor_trascrito')
            ->whereNotNull('estudios_productos.lectura')
            ->where('estudios_productos.lectura', '!=', '')
            ->where('estudios_productos.activo', '=', 1)
            ->select([
                'estudios.id as estudio_id',
                'estudios.study_iuid',
                'estudios.atencion',
                'estudios.fec_estudio',
                'estudios.eps',
                'estudios.contrato',
                'estudios.sede_id',
                'pacientes.num_docu',
                'pacientes.nombres as nom_pacien',
                'pacientes.direccion',
                'pacientes.email',
                'pacientes.tel',
                'pacientes.sexo',
                'pacientes.fec_naci',
                'estudios_productos.id as estudio_producto_id',
                'estudios_productos.lectura',
                'estudios_productos.cod_cups',
                'estudios_productos.nom_produc',
                'estudios_productos.fechor_lectura',
                'estudios_productos.fechor_trascrito',
                'medico.num_docu as num_docu_medico',
                'medico.name as nombres_medico',
                'medico.reg_med as reg_medico',
                'medico.firma as firma_medico',
                'config_sedes.nom_sede',
                'config_sedes.url_oviyam',
                'config_sedes.tap_oviyam',
                'config_sedes.plantilla_rx',
            ]);

        if (! empty($filtros['id'])) {
            $query->where('estudios_productos.id', '=', (int) $filtros['id']);
        }

        if (! empty($filtros['sede_id'])) {
            $query->where('estudios.sede_id', '=', (int) $filtros['sede_id']);
        }

        if (! empty($filtros['desde'])) {
            $inicio = Carbon::parse($filtros['desde'])->startOfDay();
            $query->where('estudios_productos.fechor_trascrito', '>=', $inicio);
        }

        if (! empty($filtros['hasta'])) {
            $fin = Carbon::parse($filtros['hasta'])->endOfDay();
            $query->where('estudios_productos.fechor_trascrito', '<=', $fin);
        }

        $query->orderBy('estudios_productos.fechor_trascrito', 'asc');

        if (! empty($filtros['limite']) && (int) $filtros['limite'] > 0) {
            $query->limit((int) $filtros['limite']);
        }

        return $query;
    }

    /**
     * Transforma un registro de la base de datos al formato requerido para generar el PDF y enviar al portal.
     *
     * Flujo del proceso:
     * 1. Calcula la edad dinámica si la fecha de nacimiento está disponible.
     * 2. Resuelve fechas deterministas para estudio, lectura y transcripción.
     * 3. Establece valores seguros por defecto para metadatos requeridos por el portal.
     * 4. Retorna el payload compatible con PlantillaEstudioService y EstudioNotificacionService.
     *
     * @param  object  $item  Registro proveniente de la consulta SQL
     * @return array<string, mixed> Payload normalizado
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-20
     */
    public function prepararPayload(object $item): array
    {
        $edad = '';
        if (! empty($item->fec_naci)) {
            try {
                $edad = (string) Carbon::parse($item->fec_naci)->age;
            } catch (\Throwable $e) {
                $edad = '';
            }
        }

        $fechorLectura = ! empty($item->fechor_lectura)
            ? Carbon::parse($item->fechor_lectura)->format('Y-m-d H:i:s')
            : ($item->fechor_trascrito ?: ($item->fec_estudio ?: Carbon::now()->format('Y-m-d H:i:s')));

        return [
            'sede_id' => (int) $item->sede_id,
            'id_estudio' => (int) $item->estudio_id,
            'estudio_producto_id' => (int) $item->estudio_producto_id,
            'num_docu' => (string) $item->num_docu,
            'nom_pacien' => (string) $item->nom_pacien,
            'name' => (string) $item->nom_pacien,
            'sexo' => (string) ($item->sexo ?? ''),
            'edad' => $edad,
            'fec_naci' => ! empty($item->fec_naci) ? Carbon::parse($item->fec_naci)->format('Y-m-d') : null,
            'eps' => (string) ($item->eps ?? ''),
            'contrato' => (string) ($item->contrato ?? ''),
            'fec_estudio' => (string) $item->fec_estudio,
            'fechor_lectura' => $fechorLectura,
            'fechor_trascrito' => (string) $item->fechor_trascrito,
            'atencion' => (string) ($item->atencion ?? ''),
            'lectura' => (string) $item->lectura,
            'nom_sede' => (string) $item->nom_sede,
            'cod_cups' => (string) ($item->cod_cups ?? ''),
            'nom_produc' => (string) $item->nom_produc,
            'nombres_medico' => (string) ($item->nombres_medico ?? 'N/A'),
            'reg_medico' => (string) ($item->reg_medico ?? ''),
            'num_docu_medico' => (string) ($item->num_docu_medico ?? ''),
            'firma_medico' => (string) ($item->firma_medico ?? ''),
            'url_oviyam' => (string) ($item->url_oviyam ?: 'N/A'),
            'study_iuid' => (string) ($item->study_iuid ?: 'N/A'),
            'tap_oviyam' => (string) ($item->tap_oviyam ?: 'N/A'),
        ];
    }

    /**
     * Ejecuta el proceso de sincronización por lotes hacia el portal de pacientes.
     *
     * Flujo del proceso:
     * 1. Consulta los registros coincidentes con los filtros especificados.
     * 2. Itera cada estudio e invoca la callback de progreso.
     * 3. Si está en modo dry-run, valida integridad y simula el envío sin invocar LibreOffice ni red.
     * 4. En modo real, invoca EstudioService::enviarPortalPacientes() para generar PDF, firmar HMAC y transferir.
     * 5. Totaliza éxitos, fallos y simulaciones, registrando errores en logs.
     *
     * @param  array{
     *     desde?: ?string,
     *     hasta?: ?string,
     *     sede_id?: ?int,
     *     id?: ?int,
     *     limite?: ?int,
     *     dry_run?: bool
     * }  $filtros
     * @param  callable|null  $callback  Fn(string $tipo, string $mensaje, object $item, ?array $resultado)
     * @return array{
     *     total: int,
     *     enviados: int,
     *     fallidos: int,
     *     simulados: int,
     *     detalles: array<int, array{id: int, num_docu: string, paciente: string, estudio: string, estado: string, mensaje: string}>
     * }
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-20
     */
    public function sincronizar(array $filtros = [], ?callable $callback = null): array
    {
        $isDryRun = ! empty($filtros['dry_run']);
        $query = $this->construirConsulta($filtros);
        $registros = $query->get();

        $resumen = [
            'total' => $registros->count(),
            'enviados' => 0,
            'fallidos' => 0,
            'simulados' => 0,
            'detalles' => [],
        ];

        foreach ($registros as $item) {
            $payload = $this->prepararPayload($item);
            $id = (int) $item->estudio_producto_id;
            $doc = (string) $item->num_docu;
            $paciente = (string) $item->nom_pacien;
            $produc = (string) $item->nom_produc;

            // Validación de plantilla configurada para la sede
            if (empty($item->plantilla_rx)) {
                $mensaje = "La sede '{$item->nom_sede}' no tiene plantilla_rx configurada.";
                $resumen['fallidos']++;
                $resumen['detalles'][] = [
                    'id' => $id,
                    'num_docu' => $doc,
                    'paciente' => $paciente,
                    'estudio' => $produc,
                    'estado' => 'ERROR',
                    'mensaje' => $mensaje,
                ];

                if ($callback) {
                    $callback('error', $mensaje, $item, null);
                }

                continue;
            }

            $plantillaPath = storage_path('app/plantillas_lecturas/'.$item->plantilla_rx);
            if (! file_exists($plantillaPath)) {
                $mensaje = "Plantilla no encontrada en disco: {$item->plantilla_rx}";
                $resumen['fallidos']++;
                $resumen['detalles'][] = [
                    'id' => $id,
                    'num_docu' => $doc,
                    'paciente' => $paciente,
                    'estudio' => $produc,
                    'estado' => 'ERROR',
                    'mensaje' => $mensaje,
                ];

                if ($callback) {
                    $callback('error', $mensaje, $item, null);
                }

                continue;
            }

            if ($isDryRun) {
                $mensaje = 'Validación exitosa (simulación).';
                $resumen['simulados']++;
                $resumen['detalles'][] = [
                    'id' => $id,
                    'num_docu' => $doc,
                    'paciente' => $paciente,
                    'estudio' => $produc,
                    'estado' => 'SIMULADO',
                    'mensaje' => $mensaje,
                ];

                if ($callback) {
                    $callback('simulado', $mensaje, $item, null);
                }

                continue;
            }

            try {
                $resultado = $this->estudioService->enviarPortalPacientes($payload);

                if (! empty($resultado['success'])) {
                    $resumen['enviados']++;
                    $mensaje = $resultado['message'] ?? 'Transferido exitosamente.';
                    $resumen['detalles'][] = [
                        'id' => $id,
                        'num_docu' => $doc,
                        'paciente' => $paciente,
                        'estudio' => $produc,
                        'estado' => 'OK',
                        'mensaje' => $mensaje,
                    ];

                    if ($callback) {
                        $callback('success', $mensaje, $item, $resultado);
                    }
                } else {
                    $resumen['fallidos']++;
                    $mensaje = $resultado['message'] ?? 'Error desconocido al transferir estudio.';
                    $resumen['detalles'][] = [
                        'id' => $id,
                        'num_docu' => $doc,
                        'paciente' => $paciente,
                        'estudio' => $produc,
                        'estado' => 'ERROR',
                        'mensaje' => $mensaje,
                    ];

                    Log::warning("Fallo al transferir estudio #{$id} a portal: {$mensaje}", [
                        'estudio_producto_id' => $id,
                        'num_docu' => $doc,
                        'resultado' => $resultado,
                    ]);

                    if ($callback) {
                        $callback('error', $mensaje, $item, $resultado);
                    }
                }
            } catch (\Throwable $e) {
                $resumen['fallidos']++;
                $mensaje = 'Excepción: '.$e->getMessage();
                $resumen['detalles'][] = [
                    'id' => $id,
                    'num_docu' => $doc,
                    'paciente' => $paciente,
                    'estudio' => $produc,
                    'estado' => 'ERROR',
                    'mensaje' => $mensaje,
                ];

                Log::error("Excepción al transferir estudio #{$id} a portal: ".$e->getMessage(), [
                    'estudio_producto_id' => $id,
                    'num_docu' => $doc,
                    'trace' => $e->getTraceAsString(),
                ]);

                if ($callback) {
                    $callback('error', $mensaje, $item, null);
                }
            }
        }

        return $resumen;
    }
}
