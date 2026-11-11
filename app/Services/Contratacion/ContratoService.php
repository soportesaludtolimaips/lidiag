<?php

namespace App\Services\Contratacion;

use App\Exceptions\CupsFaltantesException;
use App\Imports\ContratoMedicoImport;
use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigSede;
use App\Models\Contratacion\Contrato;
use App\Models\Contratacion\ContratoMedico;
use App\Models\Contratacion\ContratoProducto;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Service ContratoService: lógica de negocio del módulo de contratación.
 *
 * Gestiona el CRUD de contratos y la sincronización de sus relaciones:
 * productos contratados (`contratos_productos`) y médicos vinculados con
 * sus valores por lectura (`contratos_medicos`).
 *
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-20
 */
class ContratoService extends BaseService
{
    /**
     * Lista contratos con filtros opcionales y paginación.
     * Incluye relaciones: sede, productos.producto, medicos.medico y medicos.producto.
     *
     * @param  array  $filters  Filtros: sede_id, estado, q (búsqueda), per_page, page.
     *
     * @returns array Respuesta con items paginados y metadata.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function list(array $filters): array
    {
        $q = Contrato::query()
            ->with([
                'sede:id,cod_sede,nom_sede',
                'productos.producto:id,cod_cups,nom_produc',
                'medicos.medico:id,name,reg_med',
                'medicos.contratoProducto.producto:id,cod_cups,nom_produc',
            ]);

        if (! empty($filters['sede_id'])) {
            $q->where('sede_id', (int) $filters['sede_id']);
        }

        if (! empty($filters['estado'])) {
            $q->where('estado', $filters['estado'] === '1');
        }

        if (! empty($filters['q'])) {
            $term = trim((string) $filters['q']);
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $term);
            $q->where(function ($qq) use ($escaped) {
                $qq->where('observaciones', 'like', "%{$escaped}%")
                    ->orWhereHas('sede', function ($sq) use ($escaped) {
                        $sq->where('nom_sede', 'like', "%{$escaped}%");
                    });
            });
        }

        $q->orderByDesc('created_at');

        $perPage = max(1, min(200, (int) ($filters['per_page'] ?? 50)));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $paginator = $q->paginate(perPage: $perPage, page: $page);

        return $this->success([
            'data' => $paginator->items(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $perPage,
        ]);
    }

    /**
     * Busca un contrato por ID con todas sus relaciones.
     *
     * @param  int  $id  Identificador del contrato.
     *
     * @returns array Respuesta con el contrato encontrado o error 404.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function find(int $id): array
    {
        $contrato = Contrato::with([
            'sede:id,cod_sede,nom_sede',
            'productos.producto:id,cod_cups,nom_produc',
            'medicos.medico:id,name,reg_med',
            'medicos.contratoProducto.producto:id,cod_cups,nom_produc',
        ])->find($id);

        if (! $contrato) {
            return $this->notFound('Contrato no encontrado');
        }

        return $this->success($contrato);
    }

    /**
     * Busca el contrato de la sede cuyo rango de fechas se cruza con el indicado.
     *
     * Se usa dentro de la misma transacción que inserta o actualiza, con el
     * bloqueo de fila ya tomado, para que dos cargas simultáneas no puedan
     * insertar contratos solapados. La regla es solo por fechas: un contrato
     * cerrado también ocupa su rango.
     *
     * Flujo del proceso:
     * 1. Filtra los contratos de la sede cuyas fechas se cruzan con el rango indicado.
     * 2. Excluye el contrato que se está editando, si se recibe su identificador.
     * 3. Bloquea las filas y devuelve el primer contrato encontrado, o null si no hay cruce.
     *
     * @param  int  $sedeId  Sede del contrato a validar.
     * @param  string  $fechaInicio  Fecha de inicio en formato Y-m-d.
     * @param  string  $fechaFin  Fecha de fin en formato Y-m-d.
     * @param  int|null  $ignorarContratoId  Contrato a excluir (el que se está editando).
     * @return Contrato|null Contrato en conflicto, o null si no existe solapamiento.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    private function buscarSolapamiento(
        int $sedeId,
        string $fechaInicio,
        string $fechaFin,
        ?int $ignorarContratoId = null
    ): ?Contrato {
        $query = Contrato::where('sede_id', $sedeId)
            ->whereDate('fecha_inicio', '<=', $fechaFin)
            ->whereDate('fecha_fin', '>=', $fechaInicio);

        if ($ignorarContratoId !== null) {
            $query->where('id', '!=', $ignorarContratoId);
        }

        return $query->lockForUpdate()->first();
    }

    /**
     * Crea un nuevo contrato dentro de una transacción.
     *
     * Flujo del proceso:
     * 1. Bloquea la fila de la sede para serializar cargas concurrentes.
     * 2. Verifica que no exista otro contrato de esa sede con fechas cruzadas.
     * 3. Inserta el contrato y devuelve la sede ya cargada.
     *
     * @param  array  $data  Datos del contrato: sede_id, fecha_inicio, fecha_fin, observaciones, estado.
     *
     * @returns array Contrato creado, o error 422 si las fechas se solapan.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function create(array $data): array
    {
        return $this->transaction(function () use ($data) {
            ConfigSede::where('id', $data['sede_id'])->lockForUpdate()->first();

            $inicio = Carbon::parse($data['fecha_inicio'])->format('Y-m-d');
            $fin = Carbon::parse($data['fecha_fin'])->format('Y-m-d');

            $conflicto = $this->buscarSolapamiento($data['sede_id'], $inicio, $fin);

            if ($conflicto !== null) {
                $rango = $conflicto->fecha_inicio->format('Y-m-d').' a '.$conflicto->fecha_fin->format('Y-m-d');

                return $this->error(
                    'La sede ya tiene el contrato #'.$conflicto->id
                    .' (vigencia '.$rango.') ocupando ese rango de fechas. '
                    .'Elija un rango de fechas diferente.',
                    422
                );
            }

            $contrato = Contrato::create([
                'sede_id' => $data['sede_id'],
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'observaciones' => $data['observaciones'] ?? null,
                'estado' => $data['estado'] ?? 1,
            ]);

            return $this->created($contrato->load('sede:id,cod_sede,nom_sede'));
        });
    }

    /**
     * Actualiza un contrato existente dentro de una transacción.
     *
     * @param  int  $id  Identificador del contrato a actualizar.
     * @param  array  $data  Datos a actualizar (campos opcionales).
     *
     * @returns array Respuesta con el contrato actualizado o error 404.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function update(int $id, array $data): array
    {
        $contrato = Contrato::find($id);

        if (! $contrato) {
            return $this->notFound('Contrato no encontrado');
        }

        return $this->transaction(function () use ($contrato, $data) {
            $sedeId = (int) ($data['sede_id'] ?? $contrato->sede_id);

            ConfigSede::where('id', $sedeId)->lockForUpdate()->first();

            $inicio = Carbon::parse($data['fecha_inicio'] ?? $contrato->fecha_inicio)->format('Y-m-d');
            $fin = Carbon::parse($data['fecha_fin'] ?? $contrato->fecha_fin)->format('Y-m-d');

            $conflicto = $this->buscarSolapamiento($sedeId, $inicio, $fin, $contrato->id);

            if ($conflicto !== null) {
                $rango = $conflicto->fecha_inicio->format('Y-m-d').' a '.$conflicto->fecha_fin->format('Y-m-d');

                return $this->error(
                    'La sede ya tiene el contrato #'.$conflicto->id
                    .' (vigencia '.$rango.') ocupando ese rango de fechas. '
                    .'Elija un rango de fechas diferente.',
                    422
                );
            }

            $contrato->update([
                'sede_id' => $sedeId,
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'observaciones' => $data['observaciones'] ?? $contrato->observaciones,
                'estado' => $data['estado'] ?? $contrato->estado,
            ]);

            return $this->updated($contrato->load('sede:id,cod_sede,nom_sede'));
        });
    }

    /**
     * Elimina un contrato junto con sus médicos vinculados y productos contratados.
     * Operación atómica dentro de una transacción.
     *
     * @param  int  $id  Identificador del contrato a eliminar.
     *
     * @returns array Respuesta de confirmación o error 404.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function delete(int $id): array
    {
        $contrato = Contrato::find($id);

        if (! $contrato) {
            return $this->notFound('Contrato no encontrado');
        }

        return $this->transaction(function () use ($contrato) {
            $contrato->medicos()->delete();
            $contrato->productos()->delete();
            $contrato->delete();

            return $this->deleted('Contrato eliminado correctamente');
        });
    }

    /**
     * Cambia el estado (activo/inactivo) de un contrato.
     *
     * @param  int  $id  Identificador del contrato.
     *
     * @returns array Respuesta con el contrato y estado actualizado o error 404.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function toggleEstado(int $id): array
    {
        $contrato = Contrato::find($id);

        if (! $contrato) {
            return $this->notFound('Contrato no encontrado');
        }

        return $this->transaction(function () use ($contrato) {
            $contrato->update(['estado' => ! $contrato->estado]);

            return $this->updated($contrato);
        });
    }

    /**
     * Calcula la diferencia entre el detalle recibido y el detalle guardado.
     *
     * Flujo del proceso:
     * 1. Traduce cada producto_id a su contratos_productos_id dentro del contrato.
     * 2. Clasifica cada item recibido como alta, cambio o error. Re-incluir un
     *    registro retirado cuenta como cambio y lo reactiva al aplicar.
     * 3. Detecta los registros existentes que no vienen en el payload: quedan
     *    en "ausentes" y solo se retiran si se piden explícitamente.
     * 4. No escribe nada en la base de datos.
     *
     * @param  int  $contratoId  Contrato al que pertenece el detalle.
     * @param  array  $items  Items con medico_id, producto_id, valor_consulta,
     *                        valor_urgencias y fecha_ingreso opcional.
     * @param  bool  $retirarFaltantes  Si true, los ausentes se marcan como retiros.
     *
     * @returns array Estructura con las claves altas, cambios, retiros, ausentes y errores.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function calcularDiffMedicos(int $contratoId, array $items, bool $retirarFaltantes = false): array
    {
        $mapaProductos = ContratoProducto::where('contrato_id', $contratoId)
            ->pluck('id', 'producto_id');

        $existentes = ContratoMedico::where('contrato_id', $contratoId)
            ->with(['medico:id,name', 'contratoProducto.producto:id,cod_cups,nom_produc'])
            ->get()
            ->keyBy(fn ($m) => $m->medico_id.'-'.($m->contratoProducto->producto_id ?? 0));

        $altas = [];
        $cambios = [];
        $errores = [];
        $vistos = [];

        foreach ($items as $item) {
            $medicoId = (int) $item['medico_id'];
            $productoId = (int) $item['producto_id'];
            $clave = $medicoId.'-'.$productoId;

            if (isset($vistos[$clave])) {
                $errores[] = "El médico {$medicoId} tiene el producto {$productoId} duplicado en el archivo.";

                continue;
            }

            $vistos[$clave] = true;

            if (! isset($mapaProductos[$productoId])) {
                $errores[] = "El producto {$productoId} no está configurado en este contrato.";

                continue;
            }

            $valores = [
                'medico_id' => $medicoId,
                'producto_id' => $productoId,
                'valor_consulta' => $item['valor_consulta'] ?? 0,
                'valor_urgencias' => $item['valor_urgencias'] ?? 0,
                'fecha_ingreso' => $item['fecha_ingreso'] ?? null,
            ];

            $registro = $existentes->get($clave);

            if (! $registro) {
                $altas[] = $valores;

                continue;
            }

            $fechaExistente = $registro->fecha_ingreso?->format('Y-m-d');
            $fechaNueva = $valores['fecha_ingreso'] ? Carbon::parse($valores['fecha_ingreso'])->format('Y-m-d') : null;

            $cambio = $registro->valor_consulta != $valores['valor_consulta']
                || $registro->valor_urgencias != $valores['valor_urgencias']
                || $fechaExistente != $fechaNueva
                || (int) $registro->estado === 0;

            if ($cambio) {
                $valores['contratos_medicos_id'] = $registro->id;
                $valores['reactiva'] = (int) $registro->estado === 0;
                $cambios[] = $valores;
            }
        }

        $ausentes = [];
        $retiros = [];

        foreach ($existentes as $clave => $registro) {
            if (isset($vistos[$clave])) {
                continue;
            }

            $resumen = [
                'contratos_medicos_id' => $registro->id,
                'medico_id' => $registro->medico_id,
                'producto_id' => $registro->contratoProducto->producto_id ?? null,
                'medico_name' => $registro->medico->name ?? '',
                'nom_produc' => $registro->contratoProducto->producto->nom_produc ?? '',
            ];

            $ausentes[] = $resumen;

            if ($retirarFaltantes) {
                $retiros[] = $resumen;
            }
        }

        return compact('altas', 'cambios', 'retiros', 'ausentes', 'errores');
    }

    /**
     * Devuelve el diff del detalle de médicos sin aplicarlo, para que el
     * frontend muestre al usuario exactamente qué va a cambiar antes de confirmar.
     *
     * @param  int  $contratoId  Contrato a previsualizar.
     * @param  array  $items  Items del payload de sincronización.
     *
     * @returns array Contrato con relaciones más las claves del diff.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function previewMedicos(int $contratoId, array $items): array
    {
        $contrato = Contrato::find($contratoId);

        if (! $contrato) {
            return $this->notFound('Contrato no encontrado');
        }

        $diff = $this->calcularDiffMedicos($contratoId, $items);

        return $this->success(array_merge(
            ['contrato' => $contrato],
            $diff
        ));
    }

    /**
     * Aplica el diff del detalle de médicos. Nunca borra registros: los items
     * nuevos se insertan, los existentes con valores distintos se actualizan y
     * los que no vienen en el payload solo se retiran si el cliente lo pide
     * explícitamente con retirar_faltantes.
     *
     * Flujo del proceso:
     * 1. Calcula el diff contra el estado guardado.
     * 2. Si hay errores de validación, devuelve 422 y no escribe nada.
     * 3. Inserta las altas con fecha_ingreso en null (aplica desde el inicio
     *    del contrato) salvo que el item traiga una fecha.
     * 4. Actualiza solo los registros que cambiaron de valor; re-incluir un
     *    retirado lo reactiva (estado = 1, fecha_retiro en null).
     * 5. Marca como retirados los ausentes cuando retirar_faltantes es true.
     *
     * @param  int  $contratoId  Contrato a sincronizar.
     * @param  array  $data  Payload con medicos y retirar_faltantes opcional.
     *
     * @returns array Contrato actualizado con relaciones, o error 422.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function syncMedicos(int $contratoId, array $data): array
    {
        $contrato = Contrato::find($contratoId);

        if (! $contrato) {
            return $this->notFound('Contrato no encontrado');
        }

        $items = $data['medicos'];
        $retirarFaltantes = (bool) ($data['retirar_faltantes'] ?? false);
        $diff = $this->calcularDiffMedicos($contratoId, $items, $retirarFaltantes);

        if (! empty($diff['errores'])) {
            return $this->error(implode(' ', $diff['errores']), 422);
        }

        return $this->transaction(function () use ($contrato, $diff) {
            $mapaProductos = ContratoProducto::where('contrato_id', $contrato->id)
                ->pluck('id', 'producto_id');

            foreach ($diff['altas'] as $alta) {
                ContratoMedico::create([
                    'contrato_id' => $contrato->id,
                    'medico_id' => $alta['medico_id'],
                    'contratos_productos_id' => $mapaProductos[$alta['producto_id']],
                    'valor_contrato' => 0,
                    'valor_consulta' => $alta['valor_consulta'],
                    'valor_urgencias' => $alta['valor_urgencias'],
                    'fecha_ingreso' => $alta['fecha_ingreso'],
                    'estado' => 1,
                ]);
            }

            foreach ($diff['cambios'] as $cambio) {
                $datos = [
                    'valor_consulta' => $cambio['valor_consulta'],
                    'valor_urgencias' => $cambio['valor_urgencias'],
                    'fecha_ingreso' => $cambio['fecha_ingreso'],
                ];

                if (! empty($cambio['reactiva'])) {
                    $datos['estado'] = 1;
                    $datos['fecha_retiro'] = null;
                }

                ContratoMedico::where('id', $cambio['contratos_medicos_id'])->update($datos);
            }

            foreach ($diff['retiros'] as $retiro) {
                ContratoMedico::where('id', $retiro['contratos_medicos_id'])->update([
                    'estado' => 0,
                    'fecha_retiro' => now()->toDateString(),
                ]);
            }

            return $this->updated(
                $contrato->load([
                    'sede:id,cod_sede,nom_sede',
                    'medicos.medico:id,name,reg_med',
                    'medicos.contratoProducto.producto:id,cod_cups,nom_produc',
                ]),
                'Médicos del contrato actualizados correctamente'
            );
        });
    }

    /**
     * Indica qué productos del catálogo exigibles no tienen tarifa en el contrato.
     *
     * Flujo del proceso:
     * 1. Obtiene el mapa producto_id → contratos_productos.id del contrato.
     * 2. Compara contra el catálogo de productos que requieren lectura y están activos.
     * 3. Devuelve el total, cuántos están tarifados y el detalle de los que faltan.
     *
     * Un producto está tarifado si existe al menos un ContratoMedico activo que
     * lo referencia: si solo está cargado el producto sin médicos, hay lecturas
     * que se harían sin pagar.
     *
     * @param  int  $contratoId  Contrato a verificar.
     *
     * @returns array Contrato, totales y lista de productos faltantes con su código.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function cobertura(int $contratoId): array
    {
        $contrato = Contrato::find($contratoId);

        if (! $contrato) {
            return $this->notFound('Contrato no encontrado');
        }

        $catalogo = ConfigProducto::where('requi_lectura', 1)
            ->where('estado', 1)
            ->orderBy('cod_cups')
            ->get(['id', 'cod_cups', 'nom_produc']);

        $contratosProducto = ContratoProducto::where('contrato_id', $contratoId)
            ->pluck('id', 'producto_id')
            ->toArray();

        $tarifados = ContratoMedico::whereIn('contratos_productos_id', $contratosProducto)
            ->where('estado', 1)
            ->distinct()
            ->pluck('contratos_productos_id')
            ->toArray();

        $faltantes = [];

        foreach ($catalogo as $producto) {
            $contratoProductoId = $contratosProducto[$producto->id] ?? null;

            if ($contratoProductoId === null || ! in_array($contratoProductoId, $tarifados, true)) {
                $faltantes[] = [
                    'id' => $producto->id,
                    'cod_cups' => $producto->cod_cups,
                    'nom_produc' => $producto->nom_produc,
                ];
            }
        }

        return $this->success([
            'contrato' => $contrato->load('sede:id,cod_sede,nom_sede'),
            'total' => $catalogo->count(),
            'tarifados' => $catalogo->count() - count($faltantes),
            'faltantes' => $faltantes,
        ]);
    }

    /**
     * Importa médicos-producto a un contrato desde un archivo Excel.
     *
     * Flujo del proceso:
     * 1. Valida el ID del contrato en la celda C2 contra el contrato recibido.
     * 2. Si no coincide o no existe, devuelve error 422.
     * 3. Ejecuta la importación con ContratoMedicoImport vía Maatwebsite Excel.
     * 4. Si surgen CUPS faltantes (CupsFaltantesException) y no se envió crear_faltantes,
     *    devuelve respuesta con status 200 y requiere_confirmacion = true.
     * 5. Retorna la cantidad de registros importados exitosamente.
     *
     * @param  int  $contratoId  Identificador del contrato.
     * @param  UploadedFile  $archivo  Archivo Excel cargado.
     * @param  int  $medicoId  Identificador del médico.
     * @param  bool  $crearFaltantes  Si true, crea los productos no encontrados.
     *
     * @returns array Resultado estructurado para la respuesta API.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function importarMedicos(int $contratoId, UploadedFile $archivo, int $medicoId, bool $crearFaltantes = false): array
    {
        try {
            $excel = IOFactory::load($archivo->getPathname());
            $c2Value = $excel->getActiveSheet()->getCell('C2')->getValue();

            if (! $c2Value || ! preg_match('/(\d+)/', (string) $c2Value, $matches)) {
                return $this->error('No se encontró el ID del contrato en el archivo Excel (celda C2).', 422);
            }

            $excelContratoId = (int) $matches[1];

            if ($excelContratoId !== $contratoId) {
                return $this->error("El archivo no corresponde a este contrato. Se esperaba ID {$contratoId}, el archivo tiene ID {$excelContratoId}.", 422);
            }

            $import = new ContratoMedicoImport($contratoId, $medicoId, $crearFaltantes);
            Excel::import($import, $archivo);

            return $this->success(
                ['importados' => $import->importados],
                "Detalle importado correctamente ({$import->importados} registros)."
            );
        } catch (CupsFaltantesException $e) {
            return [
                'success' => false,
                'requiere_confirmacion' => true,
                'message' => $e->getMessage(),
                'data' => ['faltantes' => $e->faltantes],
                'status' => 200,
            ];
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Normaliza un valor monetario de celda Excel a número flotante.
     *
     * @param  mixed  $valor  Contenido de la celda.
     * @return float Valor numérico positivo o cero.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-10
     */
    private function parseMoneyHelper(mixed $valor): float
    {
        if (is_numeric($valor)) {
            return max(0.0, (float) $valor);
        }
        if (empty($valor)) {
            return 0.0;
        }
        $s = trim(preg_replace('/[^\d.,]/', '', (string) $valor));
        if (empty($s)) {
            return 0.0;
        }
        if (preg_match('/[.,]\d{3}$/', $s)) {
            $s = str_replace([',', '.'], '', $s);
        } else {
            $s = str_replace(',', '.', $s);
        }

        return max(0.0, (float) $s);
    }

    /**
     * Genera la vista previa (diff no destructivo y reporte de cobertura) para la Plantilla 1
     * de tarifas de entidades/hospitales. No persiste cambios en base de datos.
     *
     * Flujo del proceso:
     * 1. Valida existencia del contrato y lee la celda A2 del archivo.
     * 2. Compara el ID del contrato de A2 contra el contrato destino (error 422 si discrepa).
     * 3. Itera las filas a partir de la fila 6/7 extrayendo CUPS, Producto y Valor.
     * 4. Cruza contra contratos_productos para clasificar en altas, cambios, retiros y sin cambios.
     * 5. Evalúa la cobertura contra el catálogo de productos que requieren lectura.
     *
     * @param  int  $contratoId  Identificador del contrato destino.
     * @param  UploadedFile  $archivo  Archivo Excel cargado.
     * @return array Reporte estructurado con resumen, altas, cambios, retiros y cobertura.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-10
     */
    public function previewImportarEntidad(int $contratoId, UploadedFile $archivo): array
    {
        $contrato = Contrato::with('sede')->find($contratoId);

        if (! $contrato) {
            return $this->notFound('Contrato no encontrado');
        }

        try {
            $spreadsheet = IOFactory::load($archivo->getPathname());
            $sheet = $spreadsheet->getActiveSheet();

            // Validación estricta de ID en celda A2 (con fallback a C2 por retrocompatibilidad)
            $a2 = (string) $sheet->getCell('A2')->getValue();
            $c2 = (string) $sheet->getCell('C2')->getValue();
            $rawId = preg_match('/(\d+)/', $a2, $m) ? (int) $m[1] : (preg_match('/(\d+)/', $c2, $m2) ? (int) $m2[1] : null);

            if ($rawId === null) {
                return $this->error('No se encontró el ID del contrato en el archivo Excel (celda A2).', 422);
            }

            if ($rawId !== $contratoId) {
                return $this->error("El archivo no corresponde a este contrato. Se esperaba ID {$contratoId}, pero el archivo indica ID {$rawId} en la celda A2.", 422);
            }

            // Identificar fila inicial de datos (fila 6 si no hay encabezados textuales, o fila 7 si fila 6 es encabezado)
            $startRow = 7;
            $valA6 = trim((string) $sheet->getCell('A6')->getValue());
            if (is_numeric($valA6)) {
                $startRow = 6;
            }

            $highestRow = $sheet->getHighestRow();
            $filasArchivo = [];
            $cupsVistos = [];

            for ($row = $startRow; $row <= $highestRow; $row++) {
                $cups = trim((string) $sheet->getCell("A{$row}")->getValue());
                $nombre = trim((string) $sheet->getCell("B{$row}")->getValue());
                $rawValor = $sheet->getCell("C{$row}")->getValue();

                if (empty($cups) && empty($nombre)) {
                    continue;
                }

                // Evitar filas con encabezados repetidos
                if (strcasecmp($cups, 'CUPS') === 0 || strcasecmp($cups, 'CODIGO') === 0) {
                    continue;
                }

                $valor = $this->parseMoneyHelper($rawValor);

                if (isset($cupsVistos[$cups])) {
                    continue;
                }
                $cupsVistos[$cups] = true;

                $filasArchivo[$cups] = [
                    'cups' => $cups,
                    'nombre_archivo' => $nombre,
                    'valor' => $valor,
                ];
            }

            // Catálogo institucional activo
            $catalogo = ConfigProducto::where('requi_lectura', 1)
                ->where('estado', 1)
                ->get()
                ->keyBy('cod_cups');

            // Tarifas existentes en contratos_productos
            $existentes = ContratoProducto::where('contrato_id', $contratoId)
                ->with('producto:id,cod_cups,nom_produc')
                ->get()
                ->keyBy(fn ($cp) => (string) ($cp->producto->cod_cups ?? $cp->producto_id));

            $altas = [];
            $cambios = [];
            $sinCambios = [];
            $retiros = [];
            $noEncontrados = [];

            foreach ($filasArchivo as $cups => $fila) {
                $producto = $catalogo->get($cups);

                if (! $producto) {
                    $producto = ConfigProducto::where('cod_cups', $cups)->first();
                }

                if (! $producto) {
                    $noEncontrados[] = [
                        'cups' => $cups,
                        'nombre' => $fila['nombre_archivo'],
                        'valor' => $fila['valor'],
                    ];

                    continue;
                }

                $cpExistente = $existentes->get($cups);

                if (! $cpExistente) {
                    if ($fila['valor'] > 0) {
                        $altas[] = [
                            'producto_id' => $producto->id,
                            'cod_cups' => $producto->cod_cups,
                            'nom_produc' => $producto->nom_produc,
                            'valor_anterior' => 0.0,
                            'valor_contrato' => $fila['valor'],
                        ];
                    }
                } else {
                    $valorActual = (float) $cpExistente->valor_contrato;
                    $estadoActual = (int) $cpExistente->estado;

                    if ($fila['valor'] <= 0) {
                        if ($estadoActual === 1) {
                            $retiros[] = [
                                'id' => $cpExistente->id,
                                'producto_id' => $producto->id,
                                'cod_cups' => $producto->cod_cups,
                                'nom_produc' => $producto->nom_produc,
                                'valor_contrato' => $valorActual,
                                'motivo' => 'Tarifa en cero o excluida en el archivo',
                            ];
                        }
                    } elseif ($valorActual != $fila['valor'] || $estadoActual === 0) {
                        $cambios[] = [
                            'id' => $cpExistente->id,
                            'producto_id' => $producto->id,
                            'cod_cups' => $producto->cod_cups,
                            'nom_produc' => $producto->nom_produc,
                            'valor_anterior' => $valorActual,
                            'valor_contrato' => $fila['valor'],
                            'reactiva' => $estadoActual === 0,
                        ];
                    } else {
                        $sinCambios[] = [
                            'id' => $cpExistente->id,
                            'producto_id' => $producto->id,
                            'cod_cups' => $producto->cod_cups,
                            'nom_produc' => $producto->nom_produc,
                            'valor_contrato' => $valorActual,
                        ];
                    }
                }
            }

            // Productos que estaban activos en el contrato pero NO vinieron en el archivo
            foreach ($existentes as $cups => $cp) {
                if ((int) $cp->estado === 1 && ! isset($filasArchivo[$cups])) {
                    $retiros[] = [
                        'id' => $cp->id,
                        'producto_id' => $cp->producto_id,
                        'cod_cups' => $cp->producto->cod_cups ?? $cups,
                        'nom_produc' => $cp->producto->nom_produc ?? '',
                        'valor_contrato' => (float) $cp->valor_contrato,
                        'motivo' => 'No viene en la plantilla importada',
                    ];
                }
            }

            // Evaluación de cobertura interactiva contra el catálogo
            $tarifadosFuturos = collect($altas)->pluck('producto_id')
                ->merge(collect($cambios)->pluck('producto_id'))
                ->merge(collect($sinCambios)->pluck('producto_id'))
                ->diff(collect($retiros)->pluck('producto_id'))
                ->unique()
                ->flip();

            $faltantesCobertura = [];
            foreach ($catalogo as $prod) {
                if (! $tarifadosFuturos->has($prod->id)) {
                    $faltantesCobertura[] = [
                        'id' => $prod->id,
                        'cod_cups' => $prod->cod_cups,
                        'nom_produc' => $prod->nom_produc,
                    ];
                }
            }

            return $this->success([
                'tipo' => 'entidad',
                'contrato' => [
                    'id' => $contrato->id,
                    'sede_id' => $contrato->sede_id,
                    'nom_sede' => $contrato->sede->nom_sede ?? '',
                    'fecha_inicio' => $contrato->fecha_inicio->format('Y-m-d'),
                    'fecha_fin' => $contrato->fecha_fin->format('Y-m-d'),
                ],
                'resumen' => [
                    'total_archivo' => count($filasArchivo),
                    'altas' => count($altas),
                    'cambios' => count($cambios),
                    'retiros' => count($retiros),
                    'sin_cambios' => count($sinCambios),
                    'no_encontrados' => count($noEncontrados),
                ],
                'altas' => $altas,
                'cambios' => $cambios,
                'retiros' => $retiros,
                'no_encontrados' => $noEncontrados,
                'cobertura' => [
                    'total_catalogo' => $catalogo->count(),
                    'tarifados' => $catalogo->count() - count($faltantesCobertura),
                    'faltantes' => $faltantesCobertura,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->error('Error al procesar la plantilla de entidad: '.$e->getMessage(), 422);
        }
    }

    /**
     * Persiste atómicamente el resultado confirmado del diff para tarifas de entidad.
     * Aplica altas, actualizaciones y bajas lógicas (estado = 0). NUNCA elimina con DELETE.
     *
     * @param  int  $contratoId  Identificador del contrato.
     * @param  array  $data  Datos con altas, cambios y retiros.
     * @return array Resultado de la persistencia.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-10
     */
    public function confirmarImportarEntidad(int $contratoId, array $data): array
    {
        $contrato = Contrato::find($contratoId);

        if (! $contrato) {
            return $this->notFound('Contrato no encontrado');
        }

        return $this->transaction(function () use ($contratoId, $data) {
            $altas = $data['altas'] ?? [];
            $cambios = $data['cambios'] ?? [];
            $retiros = $data['retiros'] ?? [];

            // 1. Altas
            foreach ($altas as $item) {
                ContratoProducto::updateOrCreate(
                    [
                        'contrato_id' => $contratoId,
                        'producto_id' => (int) $item['producto_id'],
                    ],
                    [
                        'valor_contrato' => (float) $item['valor_contrato'],
                        'estado' => 1,
                    ]
                );
            }

            // 2. Cambios
            foreach ($cambios as $item) {
                ContratoProducto::where('id', (int) $item['id'])
                    ->where('contrato_id', $contratoId)
                    ->update([
                        'valor_contrato' => (float) $item['valor_contrato'],
                        'estado' => 1,
                    ]);
            }

            // 3. Retiros con BAJA LÓGICA (estado = 0, nunca delete)
            foreach ($retiros as $item) {
                ContratoProducto::where('id', (int) $item['id'])
                    ->where('contrato_id', $contratoId)
                    ->update([
                        'estado' => 0,
                    ]);
            }

            $totalAfectados = count($altas) + count($cambios) + count($retiros);

            return $this->success([
                'altas' => count($altas),
                'cambios' => count($cambios),
                'retiros' => count($retiros),
                'total' => $totalAfectados,
            ], "Tarifas de entidad actualizadas exitosamente ({$totalAfectados} registros procesados).");
        });
    }

    /**
     * Genera la vista previa (diff no destructivo y reporte de cobertura) para la Plantilla 2
     * de tarifas de médicos lectores. Realiza validación estricta de celda A4 (médico) y A2 (contrato).
     *
     * Flujo del proceso:
     * 1. Valida existencia de contrato y médico.
     * 2. Lee celda A2 del Excel y valida estricta coincidencia con contrato_id.
     * 3. Lee celda A4 del Excel y valida estricta coincidencia con medico_id (422 si discrepa).
     * 4. Itera filas a partir de fila 7/8 leyendo CUPS, Consulta Externa y Urgencias/Hosp.
     * 5. Cruza contra contratos_medicos de ese médico para clasificar altas, cambios, retiros y sin cambios.
     * 6. Evalúa cobertura interactiva de productos exigibles sin tarifa para el médico.
     *
     * @param  int  $contratoId  Identificador del contrato.
     * @param  UploadedFile  $archivo  Archivo Excel cargado.
     * @param  int  $medicoId  Identificador del médico lector.
     * @return array Reporte estructurado para la vista previa.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-10
     */
    public function previewImportarMedicos(int $contratoId, UploadedFile $archivo, int $medicoId): array
    {
        $contrato = Contrato::with('sede')->find($contratoId);

        if (! $contrato) {
            return $this->notFound('Contrato no encontrado');
        }

        $medico = User::find($medicoId);

        if (! $medico) {
            return $this->error('El médico seleccionado no existe.', 422);
        }

        try {
            $spreadsheet = IOFactory::load($archivo->getPathname());
            $sheet = $spreadsheet->getActiveSheet();

            // 1. Validación estricta de ID Contrato en celda A2 (fallback C2)
            $a2 = (string) $sheet->getCell('A2')->getValue();
            $c2 = (string) $sheet->getCell('C2')->getValue();
            $rawContratoId = preg_match('/(\d+)/', $a2, $m) ? (int) $m[1] : (preg_match('/(\d+)/', $c2, $m2) ? (int) $m2[1] : null);

            if ($rawContratoId === null) {
                return $this->error('No se encontró el ID del contrato en el archivo Excel (celda A2).', 422);
            }

            if ($rawContratoId !== $contratoId) {
                return $this->error("El archivo no corresponde a este contrato. Se esperaba ID {$contratoId}, pero el archivo indica ID {$rawContratoId} en la celda A2.", 422);
            }

            // 2. Validación estricta de ID Médico en celda A4 (REQUERIMIENTO 3)
            $a4 = (string) $sheet->getCell('A4')->getValue();
            $rawMedicoId = preg_match('/(\d+)/', $a4, $mM) ? (int) $mM[1] : null;

            if ($rawMedicoId === null) {
                return $this->error('No se encontró el ID del médico en el archivo Excel (celda A4 obligatoria).', 422);
            }

            if ($rawMedicoId !== $medicoId) {
                return $this->error("Discrepancia estricta en la plantilla: El archivo Excel contiene en la celda A4 el médico ID {$rawMedicoId} y no coincide con el médico seleccionado ID {$medicoId} ({$medico->name}).", 422);
            }

            // 3. Identificar fila inicial de datos (fila 8 si fila 7 es encabezado, o fila 7)
            $startRow = 8;
            $valA7 = trim((string) $sheet->getCell('A7')->getValue());
            if (is_numeric($valA7)) {
                $startRow = 7;
            }

            $highestRow = $sheet->getHighestRow();
            $filasArchivo = [];
            $cupsVistos = [];

            for ($row = $startRow; $row <= $highestRow; $row++) {
                $cups = trim((string) $sheet->getCell("A{$row}")->getValue());
                $nombre = trim((string) $sheet->getCell("B{$row}")->getValue());
                $rawConsulta = $sheet->getCell("C{$row}")->getValue();
                $rawUrgencias = $sheet->getCell("D{$row}")->getValue();

                if (empty($cups) && empty($nombre)) {
                    continue;
                }

                if (strcasecmp($cups, 'CUPS') === 0 || strcasecmp($cups, 'CODIGO') === 0) {
                    continue;
                }

                $valorConsulta = $this->parseMoneyHelper($rawConsulta);
                $valorUrgencias = $this->parseMoneyHelper($rawUrgencias);

                if (isset($cupsVistos[$cups])) {
                    continue;
                }
                $cupsVistos[$cups] = true;

                $filasArchivo[$cups] = [
                    'cups' => $cups,
                    'nombre_archivo' => $nombre,
                    'valor_consulta' => $valorConsulta,
                    'valor_urgencias' => $valorUrgencias,
                ];
            }

            $catalogo = ConfigProducto::where('requi_lectura', 1)
                ->where('estado', 1)
                ->get()
                ->keyBy('cod_cups');

            $existentes = ContratoMedico::where('contrato_id', $contratoId)
                ->where('medico_id', $medicoId)
                ->with(['contratoProducto.producto'])
                ->get()
                ->keyBy(fn ($cm) => (string) ($cm->contratoProducto->producto->cod_cups ?? $cm->contratoProducto->producto_id ?? $cm->id));

            $altas = [];
            $cambios = [];
            $sinCambios = [];
            $retiros = [];
            $noEncontrados = [];

            foreach ($filasArchivo as $cups => $fila) {
                $producto = $catalogo->get($cups);

                if (! $producto) {
                    $producto = ConfigProducto::where('cod_cups', $cups)->first();
                }

                if (! $producto) {
                    $noEncontrados[] = [
                        'cups' => $cups,
                        'nombre' => $fila['nombre_archivo'],
                        'valor_consulta' => $fila['valor_consulta'],
                        'valor_urgencias' => $fila['valor_urgencias'],
                    ];

                    continue;
                }

                $cmExistente = $existentes->get($cups);

                if (! $cmExistente) {
                    if ($fila['valor_consulta'] > 0 || $fila['valor_urgencias'] > 0) {
                        $altas[] = [
                            'producto_id' => $producto->id,
                            'cod_cups' => $producto->cod_cups,
                            'nom_produc' => $producto->nom_produc,
                            'valor_consulta' => $fila['valor_consulta'],
                            'valor_urgencias' => $fila['valor_urgencias'],
                        ];
                    }
                } else {
                    $cActual = (float) $cmExistente->valor_consulta;
                    $uActual = (float) $cmExistente->valor_urgencias;
                    $estadoActual = (int) $cmExistente->estado;

                    if ($fila['valor_consulta'] <= 0 && $fila['valor_urgencias'] <= 0) {
                        if ($estadoActual === 1) {
                            $retiros[] = [
                                'id' => $cmExistente->id,
                                'producto_id' => $producto->id,
                                'cod_cups' => $producto->cod_cups,
                                'nom_produc' => $producto->nom_produc,
                                'valor_consulta' => $cActual,
                                'valor_urgencias' => $uActual,
                                'motivo' => 'Valores en cero o retirados en la plantilla',
                            ];
                        }
                    } elseif ($cActual != $fila['valor_consulta'] || $uActual != $fila['valor_urgencias'] || $estadoActual === 0) {
                        $cambios[] = [
                            'id' => $cmExistente->id,
                            'producto_id' => $producto->id,
                            'cod_cups' => $producto->cod_cups,
                            'nom_produc' => $producto->nom_produc,
                            'valor_consulta_anterior' => $cActual,
                            'valor_consulta' => $fila['valor_consulta'],
                            'valor_urgencias_anterior' => $uActual,
                            'valor_urgencias' => $fila['valor_urgencias'],
                            'reactiva' => $estadoActual === 0,
                        ];
                    } else {
                        $sinCambios[] = [
                            'id' => $cmExistente->id,
                            'producto_id' => $producto->id,
                            'cod_cups' => $producto->cod_cups,
                            'nom_produc' => $producto->nom_produc,
                            'valor_consulta' => $cActual,
                            'valor_urgencias' => $uActual,
                        ];
                    }
                }
            }

            // Registros de este médico activos que no vinieron en el archivo
            foreach ($existentes as $cups => $cm) {
                if ((int) $cm->estado === 1 && ! isset($filasArchivo[$cups])) {
                    $retiros[] = [
                        'id' => $cm->id,
                        'producto_id' => $cm->contratoProducto->producto_id ?? null,
                        'cod_cups' => $cm->contratoProducto->producto->cod_cups ?? $cups,
                        'nom_produc' => $cm->contratoProducto->producto->nom_produc ?? '',
                        'valor_consulta' => (float) $cm->valor_consulta,
                        'valor_urgencias' => (float) $cm->valor_urgencias,
                        'motivo' => 'No viene en la plantilla importada',
                    ];
                }
            }

            // Cobertura interactiva para este médico
            $tarifadosFuturos = collect($altas)->pluck('producto_id')
                ->merge(collect($cambios)->pluck('producto_id'))
                ->merge(collect($sinCambios)->pluck('producto_id'))
                ->diff(collect($retiros)->pluck('producto_id'))
                ->unique()
                ->flip();

            $faltantesCobertura = [];
            foreach ($catalogo as $prod) {
                if (! $tarifadosFuturos->has($prod->id)) {
                    $faltantesCobertura[] = [
                        'id' => $prod->id,
                        'cod_cups' => $prod->cod_cups,
                        'nom_produc' => $prod->nom_produc,
                    ];
                }
            }

            return $this->success([
                'tipo' => 'medico',
                'contrato' => [
                    'id' => $contrato->id,
                    'sede_id' => $contrato->sede_id,
                    'nom_sede' => $contrato->sede->nom_sede ?? '',
                ],
                'medico' => [
                    'id' => $medico->id,
                    'name' => $medico->name,
                    'reg_med' => $medico->reg_med,
                ],
                'resumen' => [
                    'total_archivo' => count($filasArchivo),
                    'altas' => count($altas),
                    'cambios' => count($cambios),
                    'retiros' => count($retiros),
                    'sin_cambios' => count($sinCambios),
                    'no_encontrados' => count($noEncontrados),
                ],
                'altas' => $altas,
                'cambios' => $cambios,
                'retiros' => $retiros,
                'no_encontrados' => $noEncontrados,
                'cobertura' => [
                    'total_catalogo' => $catalogo->count(),
                    'tarifados' => $catalogo->count() - count($faltantesCobertura),
                    'faltantes' => $faltantesCobertura,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->error('Error al procesar la plantilla de médicos: '.$e->getMessage(), 422);
        }
    }

    /**
     * Persiste atómicamente el resultado confirmado del diff para tarifas de médico lector.
     * Aplica altas, actualizaciones y bajas lógicas (estado = 0, fecha_retiro = now()). NUNCA elimina con DELETE.
     *
     * @param  int  $contratoId  Identificador del contrato.
     * @param  array  $data  Datos con medico_id, altas, cambios y retiros.
     * @return array Resultado de la persistencia.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-10
     */
    public function confirmarImportarMedicos(int $contratoId, array $data): array
    {
        $contrato = Contrato::find($contratoId);

        if (! $contrato) {
            return $this->notFound('Contrato no encontrado');
        }

        $medicoId = (int) $data['medico_id'];

        return $this->transaction(function () use ($contratoId, $medicoId, $data) {
            $altas = $data['altas'] ?? [];
            $cambios = $data['cambios'] ?? [];
            $retiros = $data['retiros'] ?? [];

            // 1. Altas
            foreach ($altas as $item) {
                $cp = ContratoProducto::firstOrCreate(
                    [
                        'contrato_id' => $contratoId,
                        'producto_id' => (int) $item['producto_id'],
                    ],
                    [
                        'valor_contrato' => 0.0,
                        'estado' => 1,
                    ]
                );

                ContratoMedico::updateOrCreate(
                    [
                        'contrato_id' => $contratoId,
                        'medico_id' => $medicoId,
                        'contratos_productos_id' => $cp->id,
                    ],
                    [
                        'valor_contrato' => 0.0,
                        'valor_consulta' => (float) $item['valor_consulta'],
                        'valor_urgencias' => (float) $item['valor_urgencias'],
                        'fecha_ingreso' => now()->toDateString(),
                        'fecha_retiro' => null,
                        'estado' => 1,
                    ]
                );
            }

            // 2. Cambios
            foreach ($cambios as $item) {
                ContratoMedico::where('id', (int) $item['id'])
                    ->where('contrato_id', $contratoId)
                    ->where('medico_id', $medicoId)
                    ->update([
                        'valor_consulta' => (float) $item['valor_consulta'],
                        'valor_urgencias' => (float) $item['valor_urgencias'],
                        'fecha_retiro' => null,
                        'estado' => 1,
                    ]);
            }

            // 3. Retiros con BAJA LÓGICA (estado = 0, fecha_retiro = now(), nunca delete)
            foreach ($retiros as $item) {
                ContratoMedico::where('id', (int) $item['id'])
                    ->where('contrato_id', $contratoId)
                    ->where('medico_id', $medicoId)
                    ->update([
                        'estado' => 0,
                        'fecha_retiro' => now()->toDateString(),
                    ]);
            }

            $totalAfectados = count($altas) + count($cambios) + count($retiros);

            return $this->success([
                'altas' => count($altas),
                'cambios' => count($cambios),
                'retiros' => count($retiros),
                'total' => $totalAfectados,
            ], "Tarifas de médico actualizadas exitosamente ({$totalAfectados} registros procesados).");
        });
    }

    /**
     * Obtiene el listado de médicos vinculados y productos/tarifas del contrato para el drawer,
     * permitiendo filtrado condicional reactivo por médico lector.
     *
     * Flujo del proceso:
     * 1. Valida la existencia del contrato con su sede asociada.
     * 2. Consulta los médicos únicos activos relacionados al contrato en contratos_medicos.
     * 3. Si se especifica un médico ($medicoId), consulta los productos y tarifas vinculados a ese médico
     *    con su valor contratado institucional (contratos_productos) y valores de consulta y urgencias.
     * 4. Si no se especifica médico (o el contrato no tiene médicos vinculados aún), lista todos los productos
     *    activos del contrato con su valor contratado institucional y valores médicos en null.
     * 5. Retorna la estructura estandarizada con contrato, médicos únicos y productos detallados.
     *
     * @param  int  $contratoId  Identificador del contrato.
     * @param  int|null  $medicoId  Identificador del médico lector (opcional).
     * @return array Estructura de respuesta con contrato, médicos relacionados y productos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-10
     */
    public function listarMedicosProductos(int $contratoId, ?int $medicoId = null): array
    {
        $contrato = Contrato::with('sede:id,cod_sede,nom_sede')->find($contratoId);

        if (! $contrato) {
            return $this->notFound('Contrato no encontrado');
        }

        // 1. Médicos únicos relacionados al contrato (activos)
        $medicosRelacionados = User::query()
            ->join('contratos_medicos', 'contratos_medicos.medico_id', '=', 'users.id')
            ->where('contratos_medicos.contrato_id', $contratoId)
            ->where('contratos_medicos.estado', 1)
            ->select('users.id', 'users.name', 'users.reg_med')
            ->distinct()
            ->orderBy('users.name')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'reg_med' => $u->reg_med,
            ])
            ->values()
            ->all();

        // 2. Consulta condicional de productos
        if (! empty($medicoId)) {
            $productos = DB::table('contratos_medicos')
                ->where('contratos_medicos.contrato_id', $contratoId)
                ->where('contratos_medicos.medico_id', $medicoId)
                ->where('contratos_medicos.estado', 1)
                ->join('contratos_productos', 'contratos_productos.id', '=', 'contratos_medicos.contratos_productos_id')
                ->join('config_productos', 'config_productos.id', '=', 'contratos_productos.producto_id')
                ->select([
                    'contratos_medicos.id as contrato_medico_id',
                    'contratos_productos.id as contrato_producto_id',
                    'config_productos.id as producto_id',
                    'config_productos.cod_cups',
                    'config_productos.nom_produc',
                    'contratos_productos.valor_contrato',
                    'contratos_medicos.valor_consulta',
                    'contratos_medicos.valor_urgencias',
                    'contratos_medicos.medico_id',
                ])
                ->orderBy('config_productos.nom_produc')
                ->get()
                ->map(fn (object $row): array => [
                    'contrato_medico_id' => (int) $row->contrato_medico_id,
                    'contrato_producto_id' => (int) $row->contrato_producto_id,
                    'producto_id' => (int) $row->producto_id,
                    'cod_cups' => (string) $row->cod_cups,
                    'nom_produc' => (string) $row->nom_produc,
                    'valor_contrato' => (float) $row->valor_contrato,
                    'valor_consulta' => (float) $row->valor_consulta,
                    'valor_urgencias' => (float) $row->valor_urgencias,
                    'medico_id' => (int) $row->medico_id,
                ])
                ->values()
                ->all();
        } else {
            $productos = DB::table('contratos_productos')
                ->where('contratos_productos.contrato_id', $contratoId)
                ->where('contratos_productos.estado', 1)
                ->join('config_productos', 'config_productos.id', '=', 'contratos_productos.producto_id')
                ->select([
                    'contratos_productos.id as contrato_producto_id',
                    'config_productos.id as producto_id',
                    'config_productos.cod_cups',
                    'config_productos.nom_produc',
                    'contratos_productos.valor_contrato',
                ])
                ->orderBy('config_productos.nom_produc')
                ->get()
                ->map(fn (object $cp): array => [
                    'contrato_medico_id' => null,
                    'contrato_producto_id' => (int) $cp->contrato_producto_id,
                    'producto_id' => (int) $cp->producto_id,
                    'cod_cups' => (string) $cp->cod_cups,
                    'nom_produc' => (string) $cp->nom_produc,
                    'valor_contrato' => (float) $cp->valor_contrato,
                    'valor_consulta' => null,
                    'valor_urgencias' => null,
                    'medico_id' => null,
                ])
                ->values()
                ->all();
        }

        $totalProductosContratados = ContratoProducto::where('contrato_id', $contratoId)
            ->where('estado', 1)
            ->count();

        $nomSede = $contrato->sede instanceof ConfigSede ? $contrato->sede->nom_sede : 'No disponible';
        $fechaInicio = $contrato->fecha_inicio instanceof Carbon ? $contrato->fecha_inicio->format('Y-m-d') : (string) $contrato->fecha_inicio;
        $fechaFin = $contrato->fecha_fin instanceof Carbon ? $contrato->fecha_fin->format('Y-m-d') : (string) $contrato->fecha_fin;

        return $this->success([
            'contrato' => [
                'id' => $contrato->id,
                'sede_id' => $contrato->sede_id,
                'nom_sede' => $nomSede,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'estado' => (bool) $contrato->estado,
            ],
            'medicos' => $medicosRelacionados,
            'medico_seleccionado_id' => $medicoId,
            'total_medicos' => count($medicosRelacionados),
            'total_productos_contratados' => $totalProductosContratados,
            'total_productos' => count($productos),
            'productos' => $productos,
        ]);
    }
}
