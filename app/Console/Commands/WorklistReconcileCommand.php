<?php

namespace App\Console\Commands;

use App\Services\Dcm4chee\WorklistService;
use Illuminate\Console\Command;

class WorklistReconcileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'worklist:reconcile
        {--sede= : ID opcional de sede hospitalaria a reconciliar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcilia las órdenes de Modality Worklist con los estudios recibidos en el PACS Dcm4chee';

    /**
     * Constructor del comando.
     *
     * @param  WorklistService  $worklistService  Servicio de Worklist
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function __construct(
        protected WorklistService $worklistService
    ) {
        parent::__construct();
    }

    /**
     * Ejecuta el comando de reconciliación de órdenes Worklist vs PACS Dcm4chee.
     *
     * Flujo del proceso:
     * 1. Lee el parámetro opcional de sede.
     * 2. Invoca WorklistService::reconciliarOrdenes().
     * 3. Despliega tabla de métricas y detalles de órdenes reconciliadas.
     *
     * @return int Código de salida del comando (0: éxito, 1: fallo)
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function handle(): int
    {
        $sedeId = $this->option('sede') ? (int) $this->option('sede') : null;

        $this->info('=========================================================');
        $this->info('   RECONCILIACION MODALITY WORKLIST vs PACS DCM4CHEE     ');
        $this->info('=========================================================');
        if ($sedeId) {
            $this->line(" Filtrando por Sede ID: {$sedeId}");
        } else {
            $this->line(' Evaluando todas las sedes con PACS configurado.');
        }

        $resultado = $this->worklistService->reconciliarOrdenes($sedeId);

        if ($resultado['status'] >= 400) {
            $this->error(' [ERROR] '.$resultado['message']);

            return 1;
        }

        $datos = $resultado['data'];

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Órdenes Evaluadas', $datos['total_evaluadas']],
                ['Imágenes Detectadas en PACS (ADQUIRIDAS)', $datos['adquiridas']],
                ['Estudios Asignados en LIDiag (COMPLETADAS)', $datos['completadas']],
            ]
        );

        if (! empty($datos['detalles'])) {
            $this->newLine();
            $this->info('Detalle de órdenes actualizadas:');
            $filas = [];
            foreach ($datos['detalles'] as $det) {
                $filas[] = [
                    $det['orden_id'],
                    $det['accession_no'],
                    $det['sede'],
                    $det['nuevo_estado'],
                    $det['motivo'],
                ];
            }

            $this->table(
                ['ID Orden', 'Accession No.', 'Sede', 'Nuevo Estado', 'Motivo'],
                $filas
            );
        }

        $this->info('Reconciliación finalizada con éxito.');

        return 0;
    }
}
