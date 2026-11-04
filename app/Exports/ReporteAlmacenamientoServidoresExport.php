<?php

namespace App\Exports;

use App\Exports\Sheets\ReporteAlmacenamientoDetalleSheet;
use App\Exports\Sheets\ReporteAlmacenamientoEstadisticasSheet;
use App\Models\Configuracion\ConfigSede;
use App\Services\Seguridad\TokenSedeService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Exportador institucional de Excel para el Monitor de Almacenamiento, Discos y Servidores PACS.
 *
 * Flujo del proceso:
 * 1. Mide el almacenamiento del Servidor Central (total, libre, usado, porcentaje de disco).
 * 2. Consulta la telemetría en vivo de las sedes (respaldos de bases de datos, sync de imágenes y dosimetría).
 * 3. Mapea la salud integral de la infraestructura tecnológica de la red hospitalaria.
 * 4. Genera un libro Excel con 2 hojas: Estadísticas Ejecutivas y Detalle Completo de Servidores.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteAlmacenamientoServidoresExport implements WithMultipleSheets
{
    protected ?int $sedeId;

    /**
     * Cache de datos consolidados.
     */
    protected ?Collection $cachedData = null;

    /**
     * Constructor del reporte de almacenamiento y servidores.
     *
     * @param  int|null  $sedeId  Filtro opcional de sede.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function __construct(?int $sedeId = null)
    {
        $this->sedeId = $sedeId;
    }

    /**
     * Genera las hojas del libro de cálculo institucional.
     *
     * @return array<int, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function sheets(): array
    {
        $data = $this->getData();

        return [
            new ReporteAlmacenamientoEstadisticasSheet($data),
            new ReporteAlmacenamientoDetalleSheet($data),
        ];
    }

    /**
     * Consolida los datos de almacenamiento y telemetría de servidores.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function getData(): Collection
    {
        if ($this->cachedData !== null) {
            return $this->cachedData;
        }

        $filas = [];

        // 1. Fila del Servidor Central LIDiag Cloud
        $pathApp = base_path();
        $totalBytes = @disk_total_space($pathApp);
        $freeBytes = @disk_free_space($pathApp);

        $totalGb = $totalBytes ? round($totalBytes / (1024 ** 3), 1) : 0;
        $freeGb = $freeBytes ? round($freeBytes / (1024 ** 3), 1) : 0;
        $usedGb = ($totalBytes && $freeBytes) ? round(($totalBytes - $freeBytes) / (1024 ** 3), 1) : 0;
        $freePct = $totalBytes ? round(($freeBytes / $totalBytes) * 100, 1) : 0;

        $estadoCentral = "ÓPTIMO";
        if ($freePct <= 5.0 || $freeGb < 20.0) {
            $estadoCentral = "CRÍTICO (<5% o <20GB)";
        } elseif ($freePct <= 15.0 || $freeGb < 50.0) {
            $estadoCentral = "ADVERTENCIA (<15% o <50GB)";
        }

        $filas[] = (object) [
            "tipo" => "Servidor Central",
            "nombre" => "LIDiag Cloud / Central Web",
            "ip" => "127.0.0.1 (Localhost / vHost)",
            "almacenamiento" => "Capacidad: {$totalGb} GB | Libre: {$freeGb} GB ({$freePct}%) | Usado: {$usedGb} GB",
            "estado_disco" => $estadoCentral,
            "backup_estado" => "N/A (Host)",
            "backup_tiempo" => "Al día",
            "sync_estado" => "Receptor Activo",
            "dosimetria_estado" => "Receptor API",
            "observacion" => "Unidad principal del servidor de aplicaciones",
        ];

        // 2. Filas de Sedes Periféricas y Agentes Edge
        try {
            /** @var TokenSedeService $tokenService */
            $tokenService = app(TokenSedeService::class);
            $res = $tokenService->listarSedesConTokens();
            $sedes = $res["data"]["sedes"] ?? [];

            foreach ($sedes as $s) {
                if ($this->sedeId && (int) $s["id"] !== $this->sedeId) {
                    continue;
                }

                $bkEstado = $s["agente_backup_db"]["estado"] ?? "SIN_DATOS";
                $bkTiempo = $s["agente_backup_db"]["tiempo_humano"] ?? "Nunca";
                $bkFecha = $s["agente_backup_db"]["fecha_legible"] ?? "Sin registros";
                $syncEstado = $s["agente_sync_imagenes"]["estado"] ?? "SIN_DATOS";
                $dosiEstado = $s["agente_dosimetria"]["estado"] ?? "SIN_DATOS";

                $filas[] = (object) [
                    "tipo" => "Sede Hospitalaria",
                    "nombre" => $s["nom_sede"],
                    "ip" => $s["ip_dcm"] ?: "Sin IP DCM",
                    "almacenamiento" => "Telemetría Edge Activa",
                    "estado_disco" => $s["tiene_token"] ? "Token Activo" : "Sin Token",
                    "backup_estado" => $bkEstado,
                    "backup_tiempo" => "{$bkTiempo} ({$bkFecha})",
                    "sync_estado" => $syncEstado,
                    "dosimetria_estado" => $dosiEstado,
                    "observacion" => $s["tiene_token"] ? "Sede sincronizada" : "Pendiente generación de token",
                ];
            }
        } catch (\Throwable) {
            // Fallback con ConfigSede si el servicio no responde
            $sedes = ConfigSede::where("estado", true)->get();
            foreach ($sedes as $s) {
                if ($this->sedeId && (int) $s->id !== $this->sedeId) {
                    continue;
                }
                $filas[] = (object) [
                    "tipo" => "Sede Hospitalaria",
                    "nombre" => $s->nom_sede,
                    "ip" => $s->ip_dcm ?: "Sin IP DCM",
                    "almacenamiento" => "Agente Periférico",
                    "estado_disco" => "Activo",
                    "backup_estado" => "Verificando",
                    "backup_tiempo" => "N/D",
                    "sync_estado" => "N/D",
                    "dosimetria_estado" => "N/D",
                    "observacion" => "Telemetría estándar",
                ];
            }
        }

        $this->cachedData = collect($filas);

        return $this->cachedData;
    }

    /**
     * Compatibilidad con colecciones directas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function collection(): Collection
    {
        return $this->getData();
    }
}
