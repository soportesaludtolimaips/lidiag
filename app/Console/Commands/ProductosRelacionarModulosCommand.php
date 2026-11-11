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

        // 2. Definición de reglas clínicas por modalidad
        $cupsEcografias = [
            '881332', '881305', '881302', '881301', '881340', '881390', '881312', '881431',
            '881432', '881435', '881436', '881437', '881438', '881440', '881501', '881601',
            '881602', '881610', '881620', '881621', '881112', '881118', '881121', '881201',
        ];

        $keywordsMamografia = [
            'MAMOGRAF', 'XEROMAMOGRAFIA', 'BIOPSIA DE MAMA', 'AGUJA FINA DE MAMA',
            'TRUCUT', 'ESTEREOTAXIA', 'SENO', 'MAMARIA', 'MAMARIO',
        ];

        $keywordsEcografias = [
            'ECOGRAF', 'ULTRASON', 'DOPPLER', 'ECOCARDIOGRAMA', 'ECOCARDIOGRAFIA',
            'ECOENDOSCOPIA', 'TIROIDES', 'TORACENTESIS', 'PARACENTESIS', 'BIOPSIA DE PLEURA',
            'DRENAJE DE COLECCI', 'PUNCION O ASPIRACION',
        ];

        $keywordsTomografia = [
            'TOMOGRAF', 'TAC', 'UROTAG', 'ANGIOTAC', 'ESCANOGRAFIA',
            'IOVERSOL', 'IOPAMIDOL', 'IOBITRIDOL', 'XENETIX', 'OPTIRAY',
        ];

        $keywordsResonancia = [
            'RESONANC', 'RMN', 'COLANGIORESONANCIA', 'ANGIORESONANCIA',
            'GADOTERICO', 'DOTAREM', 'GADOLINIO',
        ];

        $keywordsRx = [
            'RADIOGRAF', 'RAYOS', 'FLUOROSCOPIA', 'TELERADIOGRAFIA', 'ORTOPANTOMOGRAFIA',
            'PORTATILES', 'GASTROVIEW', 'DIATRIZOATO', ' RX ', ' RX', 'RX ',
        ];

        $keywordsOftalmologia = [
            'OFTALMO', 'CAMPIMETRIA', 'OCT', 'RETINA', 'PAQUIMETRIA', 'BIOMETRIA OCULAR',
            'FONDO DE OJO', 'ANGIOGRAFIA FLUORESCEINICA',
        ];

        // 3. Consultar productos
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
            $nom = mb_strtoupper($prod->nom_produc ?? '');
            $cups = trim((string) ($prod->cod_cups ?? ''));
            $moduloDestino = null;
            $criterio = null;

            // Prioridad 1: CUPS específicos de ecografías y procedimientos
            if (in_array($cups, $cupsEcografias, true)) {
                $moduloDestino = 'Ecografias';
                $criterio = "CUPS exacto ({$cups})";
            }

            // Prioridad 2: Mamografía
            if (! $moduloDestino) {
                foreach ($keywordsMamografia as $kw) {
                    if (str_contains($nom, $kw) || str_contains($cups, $kw)) {
                        $moduloDestino = 'Mamografia';
                        $criterio = "Palabra clave '{$kw}'";
                        break;
                    }
                }
            }

            // Prioridad 3: Ecografías
            if (! $moduloDestino) {
                foreach ($keywordsEcografias as $kw) {
                    if (str_contains($nom, $kw) || str_contains($cups, $kw)) {
                        $moduloDestino = 'Ecografias';
                        $criterio = "Palabra clave '{$kw}'";
                        break;
                    }
                }
            }

            // Prioridad 4: Tomografía
            if (! $moduloDestino) {
                foreach ($keywordsTomografia as $kw) {
                    if (str_contains($nom, $kw) || str_contains($cups, $kw)) {
                        $moduloDestino = 'Tomografia';
                        $criterio = "Palabra clave '{$kw}'";
                        break;
                    }
                }
            }

            // Prioridad 5: Resonancia
            if (! $moduloDestino) {
                foreach ($keywordsResonancia as $kw) {
                    if (str_contains($nom, $kw) || str_contains($cups, $kw)) {
                        $moduloDestino = 'Resonancia';
                        $criterio = "Palabra clave '{$kw}'";
                        break;
                    }
                }
            }

            // Prioridad 6: Rayos X
            if (! $moduloDestino) {
                foreach ($keywordsRx as $kw) {
                    if (str_contains($nom, $kw) || str_contains($cups, $kw)) {
                        $moduloDestino = 'Rx';
                        $criterio = "Palabra clave '{$kw}'";
                        break;
                    }
                }
            }

            // Prioridad 7: Oftalmología
            if (! $moduloDestino) {
                foreach ($keywordsOftalmologia as $kw) {
                    if (str_contains($nom, $kw) || str_contains($cups, $kw)) {
                        $moduloDestino = 'Oftalmologia';
                        $criterio = "Palabra clave '{$kw}'";
                        break;
                    }
                }
            }

            // Aplicar asignación si se resolvió el módulo
            if ($moduloDestino !== null) {
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
                    'cups' => $cups,
                    'nombre' => mb_substr($nom, 0, 60),
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
}
