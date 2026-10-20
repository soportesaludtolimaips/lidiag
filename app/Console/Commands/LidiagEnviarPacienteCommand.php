<?php

namespace App\Console\Commands;

use App\Services\Estudios\EstudioPortalSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class LidiagEnviarPacienteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lidiag:enviar-paciente
        {--desde= : Fecha de transcripcion inicial (YYYY-MM-DD)}
        {--hasta= : Fecha de transcripcion final (YYYY-MM-DD)}
        {--sede= : Filtrar por ID de sede}
        {--limite= : Limite maximo de estudios a procesar}
        {--id= : ID especifico de estudio_producto a enviar}
        {--dry-run : Simular la seleccion y validacion sin generar PDFs ni enviar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia los estudios transcritos al portal de pacientes (lidiag-reportes)';

    /**
     * Servicio de sincronización al portal.
     */
    protected EstudioPortalSyncService $syncService;

    /**
     * Constructor del comando.
     *
     * @param  EstudioPortalSyncService  $syncService  Servicio de sincronización al portal de pacientes
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-20
     */
    public function __construct(EstudioPortalSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    /**
     * Ejecuta el comando para enviar estudios transcritos al portal de pacientes.
     *
     * Flujo del proceso:
     * 1. Despliega encabezado informativo y valida parámetros de entrada (fechas, sede, id).
     * 2. Verifica pre-requisitos de configuración (URL del portal, clave HMAC y LibreOffice).
     * 3. Cuenta los estudios coincidentes con los filtros especificados.
     * 4. Despliega la barra de progreso y ejecuta la sincronización estudio por estudio.
     * 5. Muestra la tabla de resumen estadístico y detalles de errores si existieron.
     *
     * @return int Código de salida (0: éxito, 1: fallo o errores detectados)
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-20
     */
    public function handle(): int
    {
        @ini_set('max_execution_time', '0');
        @set_time_limit(0);

        $this->info('=========================================================');
        $this->info('    SINCRONIZACION DE ESTUDIOS AL PORTAL DE PACIENTES    ');
        $this->info('=========================================================');

        // Validación de prerrequisitos
        $prereqs = $this->syncService->validarPrerrequisitos();
        if (! $prereqs['success']) {
            $this->error(' [ERROR] '.$prereqs['message']);

            return 1;
        }

        $filtros = [];

        if ($this->option('desde')) {
            $desde = $this->option('desde');
            try {
                Carbon::createFromFormat('Y-m-d', $desde);
                $filtros['desde'] = $desde;
            } catch (\Throwable $e) {
                $this->error("Formato inválido para --desde (esperado YYYY-MM-DD): {$desde}");

                return 1;
            }
        }

        if ($this->option('hasta')) {
            $hasta = $this->option('hasta');
            try {
                Carbon::createFromFormat('Y-m-d', $hasta);
                $filtros['hasta'] = $hasta;
            } catch (\Throwable $e) {
                $this->error("Formato inválido para --hasta (esperado YYYY-MM-DD): {$hasta}");

                return 1;
            }
        }

        if ($this->option('sede')) {
            $filtros['sede_id'] = (int) $this->option('sede');
        }

        if ($this->option('id')) {
            $filtros['id'] = (int) $this->option('id');
        }

        if ($this->option('limite')) {
            $filtros['limite'] = (int) $this->option('limite');
        }

        $isDryRun = (bool) $this->option('dry-run');
        $filtros['dry_run'] = $isDryRun;

        // Conteo previo de estudios coincidentes
        $totalCoincidentes = $this->syncService->construirConsulta($filtros)->count();
        $totalAProcesar = ! empty($filtros['limite'])
            ? min($totalCoincidentes, (int) $filtros['limite'])
            : $totalCoincidentes;

        $this->line(' Modo: '.($isDryRun ? '<comment>SIMULACION (DRY-RUN)</comment>' : '<info>ENVIO REAL</info>'));
        $this->line(' Destino: '.config('app.URL_API_REPORTES').'/api/almacenarLectura');
        if (! empty($filtros['desde'])) {
            $this->line(" Fecha transcripción desde: {$filtros['desde']}");
        }
        if (! empty($filtros['hasta'])) {
            $this->line(" Fecha transcripción hasta: {$filtros['hasta']}");
        }
        if (! empty($filtros['sede_id'])) {
            $this->line(" Sede ID: {$filtros['sede_id']}");
        }
        if (! empty($filtros['id'])) {
            $this->line(" Estudio Producto ID: {$filtros['id']}");
        }
        if (! empty($filtros['limite'])) {
            $this->line(" Límite máximo: {$filtros['limite']}");
        }
        $this->line(" Total estudios coincidentes: <comment>{$totalCoincidentes}</comment> (a procesar: <comment>{$totalAProcesar}</comment>)");
        $this->newLine();

        if ($totalAProcesar === 0) {
            $this->warn('No se encontraron estudios transcritos con los filtros indicados.');

            return 0;
        }

        $progressBar = $this->output->createProgressBar($totalAProcesar);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% -- %message%');
        $progressBar->setMessage('Iniciando...');
        $progressBar->start();

        $callback = function (string $tipo, string $mensaje, object $item) use ($progressBar) {
            $nombreCorto = mb_substr($item->nom_pacien, 0, 20);
            $progressBar->setMessage("#{$item->estudio_producto_id} - {$nombreCorto}: {$mensaje}");
            $progressBar->advance();
        };

        $resultado = $this->syncService->sincronizar($filtros, $callback);

        $progressBar->finish();
        $this->newLine(2);

        // Tabla de resumen estadístico
        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Total evaluados', $resultado['total']],
                ['Enviados con éxito', $resultado['enviados']],
                ['Simulados (Dry-Run)', $resultado['simulados']],
                ['Fallidos / Errores', $resultado['fallidos']],
            ]
        );

        // Mostrar errores si los hubo
        if ($resultado['fallidos'] > 0) {
            $this->newLine();
            $this->error("Se detectaron {$resultado['fallidos']} fallo(s) durante el procesamiento:");

            $filasErrores = [];
            foreach ($resultado['detalles'] as $detalle) {
                if ($detalle['estado'] === 'ERROR') {
                    $filasErrores[] = [
                        $detalle['id'],
                        $detalle['num_docu'],
                        mb_substr($detalle['paciente'], 0, 25),
                        mb_substr($detalle['estudio'], 0, 30),
                        $detalle['mensaje'],
                    ];
                }
            }

            $this->table(
                ['ID Prod', 'Doc. Paciente', 'Paciente', 'Estudio', 'Error'],
                array_slice($filasErrores, 0, 25)
            );

            if (count($filasErrores) > 25) {
                $restantes = count($filasErrores) - 25;
                $this->comment("... y {$restantes} error(es) adicional(es). Consulte los logs en storage/logs/laravel.log.");
            }

            return 1;
        }

        $this->info($isDryRun ? 'Simulación completada con éxito.' : 'Todos los estudios fueron sincronizados correctamente.');

        return 0;
    }
}
