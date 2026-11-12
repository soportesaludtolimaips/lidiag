<?php

namespace App\Console\Commands;

use App\Models\Configuracion\ConfigModulo;
use App\Models\Configuracion\ConfigProducto;
use Illuminate\Console\Command;

class ProductosRelacionarModulosCommand extends Command
{
    /**
     * Nombre y firma del comando de consola.
     *
     * @var string
     */
    protected $signature = 'productos:relacionar-modulos
        {--force : Sobrescribir relaciones existentes incluso si el producto ya tiene modulo_id asignado}
        {--dry-run : Ejecutar en modo simulación sin guardar cambios en la base de datos}';

    /**
     * Descripción del comando de consola.
     *
     * @var string
     */
    protected $description = 'Relaciona los productos asistenciales de config_productos con sus respectivos módulos (Rx, Ecografías, Tomografía, Resonancia, Mamografía, Oftalmología) según códigos CUPS, modalidad y patrones clínicos.';

    /**
     * Ejecuta el comando de consola.
     *
     * Flujo del proceso:
     * 1. Asegura la existencia de los módulos asistenciales base en config_modulos.
     * 2. Consulta los productos registrados en config_productos aplicando el filtro de asignación (--force o solo sin módulo).
     * 3. Evalúa cada producto contra reglas jerárquicas: códigos CUPS exactos, prefijos CUPS y palabras clave clínicas.
     * 4. Actualiza el campo modulo_id de cada producto asignado (omitido en modo --dry-run).
     * 5. Muestra tablas y resumen estadístico en consola con el balance de asignaciones por módulo.
     *
     * @return int Código de salida del comando.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-11
     */
    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');

        $this->info('Iniciando proceso de asignación de módulos a productos...');
        if ($dryRun) {
            $this->warn('Modo DRY-RUN activado: No se guardarán cambios en la base de datos.');
        }

        // 1. Asegurar módulos base
        $modulosBase = [
            'Rx',
            'Ecografias',
            'Tomografia',
            'Resonancia',
            'Mamografia',
            'Oftalmologia',
        ];

        $modulosMap = [];
        foreach ($modulosBase as $nom) {
            $mod = ConfigModulo::firstOrCreate(['modulo' => $nom]);
            $modulosMap[$nom] = $mod->id;
        }

        // 2. Consultar productos
        $query = ConfigProducto::query();
        if (! $force) {
            $query->whereNull('modulo_id');
        }

        $productos = $query->get();
        $total = $productos->count();

        if ($total === 0) {
            $this->info('No hay productos pendientes por asignar. Use --force si desea reevaluar todos los productos.');

            return Command::SUCCESS;
        }

        $this->info("Procesando {$total} productos...");

        $asignados = [
            'Rx' => 0,
            'Ecografias' => 0,
            'Tomografia' => 0,
            'Resonancia' => 0,
            'Mamografia' => 0,
            'Oftalmologia' => 0,
        ];
        $sinClasificar = [];
        $actualizados = 0;

        foreach ($productos as $prod) {
            $clasificacion = $this->clasificarProducto($prod);

            if ($clasificacion !== null) {
                $moduloDestino = $clasificacion['modulo'];
                $moduloId = $modulosMap[$moduloDestino];
                if (! $dryRun) {
                    $prod->modulo_id = $moduloId;
                    $prod->save();
                }
                $asignados[$moduloDestino]++;
                $actualizados++;
            } else {
                $sinClasificar[] = [
                    'id' => $prod->id,
                    'cups' => $prod->cod_cups,
                    'nombre' => mb_substr($prod->nom_produc ?? '', 0, 60),
                ];
            }
        }

        // 4. Mostrar resumen estadístico
        $tablaResumen = [];
        foreach ($asignados as $modulo => $cant) {
            $tablaResumen[] = [
                'Módulo' => $modulo,
                'Productos Asignados' => $cant,
            ];
        }

        $this->newLine();
        $this->table(['Módulo Asistencial', 'Productos Asignados'], $tablaResumen);

        if (! empty($sinClasificar)) {
            $this->newLine();
            $this->warn('Productos sin clasificar (insumos, suministros o no mapeados):');
            $this->table(['ID', 'CUPS', 'Nombre Producto'], array_slice($sinClasificar, 0, 15));
            if (count($sinClasificar) > 15) {
                $this->line('... y '.(count($sinClasificar) - 15).' productos más sin clasificar.');
            }
        }

        $this->newLine();
        $verbo = $dryRun ? 'simularon' : 'asignaron';
        $this->info("Proceso finalizado con éxito: se {$verbo} {$actualizados} de {$total} productos evaluados.");

        return Command::SUCCESS;
    }

    /**
     * Evalúa y clasifica un producto en su modalidad asistencial correspondiente.
     *
     * Flujo del proceso:
     * 1. Extrae y normaliza el código CUPS base y la descripción en mayúsculas.
     * 2. Evalúa CUPS clínicos específicos de ecografías y mamografía/biopsias.
     * 3. Evalúa términos inequívocos en el nombre (TOMOGRAFIA, RESONANCIA, MAMOGRAFIA, ECOGRAFIA, RADIOGRAFIA).
     * 4. Evalúa prefijos estándar de codificación CUPS en Colombia (870-873, 876, 879, 881-882, 883).
     * 5. Evalúa casos especiales por patrones clínicos, medios de contraste y procedimientos.
     *
     * @param  ConfigProducto  $prod  Producto a evaluar.
     * @return array{modulo: 'Rx'|'Ecografias'|'Tomografia'|'Resonancia'|'Mamografia'|'Oftalmologia', motivo: string}|null
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-12
     */
    private function clasificarProducto(ConfigProducto $prod): ?array
    {
        $cups = trim((string) ($prod->cod_cups ?? ''));
        $cupsBase = (string) preg_replace('/[^0-9]/', '', substr($cups, 0, 6));
        $nom = mb_strtoupper($prod->nom_produc ?? '');

        // 1. Reglas explícitas por CUPS clínico oficial
        $cupsEcoIntervencion = [
            '061001', '061002', '061302', '345001', '345002', '345401',
            '542801', '542802', '542901', '861101', '881220',
        ];
        if (in_array($cupsBase, $cupsEcoIntervencion, true)) {
            return ['modulo' => 'Ecografias', 'motivo' => "CUPS intervencionismo ecográfico ({$cups})"];
        }

        $cupsMamoBiopsias = ['851101', '851102', '851103', '876801', '876802'];
        if (in_array($cupsBase, $cupsMamoBiopsias, true)) {
            return ['modulo' => 'Mamografia', 'motivo' => "CUPS mamografía/biopsia ({$cups})"];
        }

        // 2. Términos principales inequívocos en el nombre
        if (str_contains($nom, 'TOMOGRAF') || str_contains($nom, 'ESCANOGRAFIA') || str_contains($nom, 'UROTAG') || str_contains($nom, 'ANGIOTAC')) {
            return ['modulo' => 'Tomografia', 'motivo' => 'Nombre contiene TOMOGRAF/TAC/UROTAG'];
        }

        if (str_contains($nom, 'RESONANC') || str_contains($nom, 'COLANGIORESONANCIA') || str_contains($nom, 'ANGIORESONANCIA')) {
            return ['modulo' => 'Resonancia', 'motivo' => 'Nombre contiene RESONANCIA'];
        }

        if (str_contains($nom, 'MAMOGRAF') || str_contains($nom, 'XEROMAMOGRAF') || str_contains($nom, 'CA DE SENO') || str_contains($nom, 'ESTEREOTAXIA')) {
            return ['modulo' => 'Mamografia', 'motivo' => 'Nombre contiene MAMOGRAFIA/CA DE SENO'];
        }

        if (str_contains($nom, 'ECOGRAF') || str_contains($nom, 'ULTRASON') || str_contains($nom, 'DOPPLER') || str_contains($nom, 'DUPLEX SCAN') || str_contains($nom, 'ECOCARDIOG')) {
            return ['modulo' => 'Ecografias', 'motivo' => 'Nombre contiene ECOGRAFIA/DOPPLER'];
        }

        if (str_contains($nom, 'RADIOGRAF') || str_contains($nom, 'TELERADIOGRAF') || str_contains($nom, 'ORTOPANTOMOGRAF') || str_contains($nom, 'FLUOROSCOPIA')) {
            return ['modulo' => 'Rx', 'motivo' => 'Nombre contiene RADIOGRAFIA'];
        }

        // 3. CUPS por prefijo ministerial
        if (str_starts_with($cupsBase, '870') || str_starts_with($cupsBase, '871') || str_starts_with($cupsBase, '872') || str_starts_with($cupsBase, '873')) {
            return ['modulo' => 'Rx', 'motivo' => "Prefijo CUPS Rx ({$cupsBase})"];
        }

        if (str_starts_with($cupsBase, '879')) {
            return ['modulo' => 'Tomografia', 'motivo' => "Prefijo CUPS Tomografía ({$cupsBase})"];
        }

        if (str_starts_with($cupsBase, '881') || str_starts_with($cupsBase, '882')) {
            return ['modulo' => 'Ecografias', 'motivo' => "Prefijo CUPS Ecografía ({$cupsBase})"];
        }

        if (str_starts_with($cupsBase, '883')) {
            return ['modulo' => 'Resonancia', 'motivo' => "Prefijo CUPS Resonancia ({$cupsBase})"];
        }

        // 4. Casos especiales: Portátiles, medios de contraste y oftalmología
        if (str_contains($nom, 'PORTATIL') || str_contains($nom, 'PORTATILES')) {
            return ['modulo' => 'Rx', 'motivo' => 'Estudios portátiles Rx'];
        }

        if (str_contains($nom, 'IOVERSOL') || str_contains($nom, 'IOPAMIDOL') || str_contains($nom, 'IOBITRIDOL') || str_contains($nom, 'XENETIX') || str_contains($nom, 'OPTIRAY')) {
            return ['modulo' => 'Tomografia', 'motivo' => 'Medio de contraste Tomografía'];
        }

        if (str_contains($nom, 'DOTAREM') || str_contains($nom, 'GADOTERICO') || str_contains($nom, 'GADOLINIO')) {
            return ['modulo' => 'Resonancia', 'motivo' => 'Medio de contraste Resonancia'];
        }

        if (str_contains($nom, 'GASTROVIEW') || str_contains($nom, 'DIATRIZOATO')) {
            return ['modulo' => 'Rx', 'motivo' => 'Medio de contraste Rx'];
        }

        if (preg_match('/\b(OFTALMO|CAMPIMETRIA|OCT|RETINA|FONDO DE OJO)\b/i', $nom)) {
            return ['modulo' => 'Oftalmologia', 'motivo' => 'Término de Oftalmología'];
        }

        if (preg_match('/\b(MAMA|MAMARIO|MAMARIA)\b/i', $nom)) {
            return ['modulo' => 'Mamografia', 'motivo' => 'Procedimiento de mama'];
        }

        if (preg_match('/\b(TIROIDES|TORACENTESIS|PARACENTESIS|PLEURA)\b/i', $nom)) {
            return ['modulo' => 'Ecografias', 'motivo' => 'Procedimiento guiado por ecografía'];
        }

        return null;
    }
}
