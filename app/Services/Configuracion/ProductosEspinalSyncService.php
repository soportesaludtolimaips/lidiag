<?php

namespace App\Services\Configuracion;

use App\Models\Configuracion\ConfigProducto;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Servicio para sincronización, resolución de CUPS y enriquecimiento financiero de productos Espinal desde SAHI.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-10
 */
class ProductosEspinalSyncService extends BaseService
{
    /**
     * Mapeo canónico predeterminado para productos con descripciones específicas que carecen de CUPS en el Excel.
     */
    private const CUPS_CANONICOS = [
        'RADIOGRAFIAS COMPARATIVAS DE PIES CON APOYO ( AP Y LATERAL )' => '873303',
        'RESONANCIA NUCLEAR MAGNETICA CON ANGIOGRAFIA' => '883909',
        'ECOGRAFIA DE VIAS URINARIAS RIÑONES VEJIGA Y PROSTATA TRANSABDOMINAL' => '881332',
        'ST RADIOGRAFIA DE COLUMNA LUMBOSACRA' => '871040',
        'RESONANCIA NUCLEAR MAGNETICA DE ORBITAS' => '883103',
        'RESONANCIA NUCLEAR MAGNETICA DE PLEJO BRAQUIAL' => '883560',
        'RADIOGRAFIA DE TORAX  PA O AP  LATERAL DECUBITO LATERAL  OBLICUAS O LATERAL CON BARIO' => '871121',
        'RADIOGRAFIA DE TORAX P A  O A P  LATERAL DECUBITO LATERAL  OBLICUAS O LATERAL CON BARIO' => '871121',
        'RESONANCIA NUCLEAR MAGNETICA DE ARTICULACION TEMPOROMANDIBULAR' => '883105',
        'RESONANCIA MAGNETICA DE COLUMNA CERVICAL CON CONTRASTE' => '883211',
        'RESONANCIA MAGNETICA DE OIDOS' => '883109',
        'RADIOGRAFIA DE FARINGE [FARINGOGRAFIA' => '870603',
        'RADIOGRAFIA DE FARINGE [FARINGOGRAFIA]' => '870603',
        'TOMOGRAFIA AXIAL COMPUTADA DE MIEMBROS INFERIORES  ANTEVERSION FEMORAL O TORSION TIBIAL' => '879522',
        'ST RADIOGRAFIA DE FEMUR (AP LATERAL)' => '873312',
        'RESONANCIA MAGNETICA DE SITIO NO ESPECIFICADO' => '883902',
        'RADIOGRAFIA DE TORAX  PA O   AP Y  LATERAL DE CUBITO LATERAL  OBLICUAS O LATERAL' => '871121',
        'ST RADIOGRAFIA DE PIERNA (AP LATERAL)' => '873313',
        'RESONANCIA MAGNETICA DE COLUMNA SACROILIACA SIMPLE' => '883232',
        'RESONANCIA NUCLEAR MAGNETICA DE ARTICULACIONES COMPARATIVA' => '883545',
        'RESONANCIA MAGNETICA DE SENOS PARANASALES O CARA' => '883110',
        'ECOGRAFIA DE ABDOMEN PILORO' => '881313',
        'RESONANCIA MAGNETICA DE CUELLO' => '883111',
        'RESONANCIA NUCLEAR MAGNETICA DE VIAS BILIARES' => '883430',
        'RESONANCIA MAGNETICA CON ANGIOGRAFIA' => '883909',
        'RADIOGRAFIA TANGENCIAL O AXIAL DE ROTULA' => '873423',
        'ST RADIOGRAFIA DE PIE (AP LATERAL Y OBLICUA)' => '873333',
        'ST RADIOGRAFIA DE CLAVICULA' => '873112',
        'ST  RADIOGRAFIA DE TORAX (PA O AP Y LATERAL DECUBITO LATERAL OBLICUAS O LATERAL)' => '871121',
        'RESONANCIA MAGNETICA DE PLACENTA' => '883443',
        'TOMOGRAFIA COMPUTADA DE VIAS URINARIAS [UROTAC]' => '879430',
    ];

    /**
     * Resuelve de forma determinística y difusa el código CUPS para un procedimiento.
     *
     * Flujo del proceso:
     * 1. Si el registro ya posee un código CUPS válido, lo retorna normalizado.
     * 2. Si no posee CUPS, consulta el diccionario canónico de equivalencias.
     * 3. Si no hay coincidencia exacta, normaliza el texto (eliminando prefijo 'ST ', corchetes y paréntesis) y busca en catálogo canónico.
     * 4. Si aún no se halla, consulta el catálogo proProducto en SAHI (SQL Server).
     *
     * @param  string  $nomProduc  Nombre del procedimiento
     * @param  string|null  $cupsActual  Código CUPS actual si ya existiere
     * @return string|null Código CUPS resuelto o null
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-10
     */
    public function resolverCups(string $nomProduc, ?string $cupsActual = null): ?string
    {
        $cupsTrim = trim((string) $cupsActual);
        if ($cupsTrim !== '') {
            return $cupsTrim;
        }

        $nomTrim = trim($nomProduc);
        if (isset(self::CUPS_CANONICOS[$nomTrim])) {
            return self::CUPS_CANONICOS[$nomTrim];
        }

        $cleanNom = trim(preg_replace('/^ST\s+/i', '', $nomTrim));
        $cleanNom = trim(preg_replace('/[\[\]\(\)]/', '', $cleanNom));

        foreach (self::CUPS_CANONICOS as $key => $code) {
            $keyClean = trim(preg_replace('/^ST\s+/i', '', $key));
            $keyClean = trim(preg_replace('/[\[\]\(\)]/', '', $keyClean));
            if (strcasecmp($cleanNom, $keyClean) === 0) {
                return $code;
            }
        }

        // Búsqueda en SQL Server SAHI
        try {
            $sahiMatch = DB::connection('sqlsrv')->selectOne("
                SELECT TOP 1 CodProducto
                FROM proProducto
                WHERE NomProducto LIKE ? OR NomProducto LIKE ?
                ORDER BY CASE WHEN CodProducto NOT LIKE '%0000' THEN 0 ELSE 1 END, IdProducto DESC
            ", ["%{$cleanNom}%", "%{$nomTrim}%"]);

            if ($sahiMatch && ! empty($sahiMatch->CodProducto)) {
                return (string) $sahiMatch->CodProducto;
            }
        } catch (\Throwable $e) {
            Log::warning("Error al buscar CUPS en SAHI para '{$nomProduc}': ".$e->getMessage());
        }

        return null;
    }

    /**
     * Consulta masiva y eficiente de datos financieros en SAHI para una lista de códigos CUPS.
     *
     * Flujo del proceso:
     * 1. Consulta en proProducto los IdProducto correspondientes a los códigos CUPS.
     * 2. Si se especifica un contratoId (por defecto 5082 - Salud Total HSR COE-22-GIR0022), extrae el valor unitario
     *    real de la factura más reciente para ese contrato puntual.
     * 3. Para productos no facturados aún en dicho contrato, consulta el tarifario del contrato en conTarifaDetalle
     *    (UVB 2026 IdTarifa 452 con -25% de descuento contractual).
     * 4. Si aún restan productos, consulta la última factura facturada en 2026 de cualquier contrato del hospital.
     * 5. Retorna arreglo asociativo indexado por CodProducto con el valor financiero unitario y metadatos del origen.
     *
     * @param  array  $cupsList  Lista de códigos CUPS a cotizar
     * @param  int  $year  Año base de facturación (predeterminado 2026)
     * @param  int|null  $contratoId  ID de contrato en SAHI (predeterminado 5082 para Salud Total HSR)
     * @return array<string, array{valor: float, origen: string, facturas: int}>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-10
     */
    public function consultarTarifasSahi(array $cupsList, int $year = 2026, ?int $contratoId = 5082): array
    {
        $uniqueCups = array_values(array_unique(array_filter(array_map('trim', $cupsList))));
        if (empty($uniqueCups)) {
            return [];
        }

        $tarifasPorCups = [];

        try {
            // 1. Obtener mapeo CodProducto -> IdProducto
            $prods = DB::connection('sqlsrv')->select("
                SELECT IdProducto, CodProducto, NomProducto
                FROM proProducto
                WHERE CodProducto IN ('".implode("','", $uniqueCups)."')
            ");

            $idToCups = [];
            foreach ($prods as $p) {
                $idToCups[$p->IdProducto] = (string) $p->CodProducto;
            }

            $ids = array_keys($idToCups);
            if (empty($ids)) {
                return [];
            }

            // 2. Si se especifica contrato (ej. 5082 - Salud Total HSR), extraer la última factura facturada en ese contrato
            if ($contratoId) {
                $facturasContrato = DB::connection('sqlsrv')->select("
                    SELECT 
                        det.IdProducto,
                        ROUND(det.ValTotal / NULLIF(det.Cantidad, 0), 0) as precio,
                        fac.NumFactura,
                        fac.FecFactura,
                        ROW_NUMBER() OVER(PARTITION BY det.IdProducto ORDER BY fac.FecFactura DESC) as rn
                    FROM facFactura fac
                    INNER JOIN facFacturaDet det ON fac.IdFactura = det.IdFactura
                    WHERE fac.IdContrato = {$contratoId}
                      AND det.IdProducto IN (".implode(',', $ids).")
                      AND det.ValTotal > 0
                ");

                foreach ($facturasContrato as $fc) {
                    if ((int) $fc->rn === 1) {
                        $cups = $idToCups[$fc->IdProducto] ?? null;
                        if ($cups) {
                            $tarifasPorCups[$cups] = [
                                'valor' => (float) $fc->precio,
                                'origen' => "Factura SAHI {$fc->NumFactura} (Contrato {$contratoId})",
                                'facturas' => 1,
                            ];
                        }
                    }
                }

                // 2.1 Para los productos de ese contrato que no tienen factura aún, consultar tarifario UVB 2026 con -25% (IdTarifa 452)
                $idsSinFacturaContrato = array_diff($ids, array_map(fn ($c) => array_search($c, $idToCups), array_keys($tarifasPorCups)));
                if (! empty($idsSinFacturaContrato)) {
                    $tarifasContratoBase = DB::connection('sqlsrv')->select("
                        SELECT td.IdProducto, ROUND(td.Valor * 0.75, 0) as valor_con_descuento, t.DesTarifa
                        FROM conTarifaDetalle td
                        INNER JOIN conTarifa t ON td.IdTarifa = t.IdTarifa
                        WHERE td.IdTarifa = 452
                          AND td.IdProducto IN (".implode(',', $idsSinFacturaContrato).")
                          AND td.Valor > 0
                    ");

                    foreach ($tarifasContratoBase as $tcb) {
                        $cups = $idToCups[$tcb->IdProducto] ?? null;
                        if ($cups && ! isset($tarifasPorCups[$cups])) {
                            $tarifasPorCups[$cups] = [
                                'valor' => (float) $tcb->valor_con_descuento,
                                'origen' => "Tarifario SAHI (UVB 2026 -25% / Contrato {$contratoId})",
                                'facturas' => 0,
                            ];
                        }
                    }
                }
            }

            // 3. Fallback: Facturación general más reciente del año 2026 para productos restantes
            $idsSinTarifa = array_diff($ids, array_map(fn ($c) => array_search($c, $idToCups), array_keys($tarifasPorCups)));
            if (! empty($idsSinTarifa)) {
                $facturasGen = DB::connection('sqlsrv')->select("
                    SELECT 
                        det.IdProducto,
                        ROUND(det.ValTotal / NULLIF(det.Cantidad, 0), 0) as precio,
                        fac.NumFactura,
                        fac.IdContrato,
                        ROW_NUMBER() OVER(PARTITION BY det.IdProducto ORDER BY fac.FecFactura DESC) as rn
                    FROM facFactura fac
                    INNER JOIN facFacturaDet det ON fac.IdFactura = det.IdFactura
                    WHERE fac.FecFactura >= '{$year}-01-01 00:00:00'
                      AND det.IdProducto IN (".implode(',', $idsSinTarifa).")
                      AND det.ValTotal > 0
                ");

                foreach ($facturasGen as $fg) {
                    if ((int) $fg->rn === 1) {
                        $cups = $idToCups[$fg->IdProducto] ?? null;
                        if ($cups && ! isset($tarifasPorCups[$cups])) {
                            $tarifasPorCups[$cups] = [
                                'valor' => (float) $fg->precio,
                                'origen' => "Última Factura General SAHI {$fg->NumFactura} (Contrato {$fg->IdContrato})",
                                'facturas' => 1,
                            ];
                        }
                    }
                }
            }

            // 4. Fallback: Tarifario general 2026 si aún restan
            $idsAunSinValor = array_diff($ids, array_map(fn ($c) => array_search($c, $idToCups), array_keys($tarifasPorCups)));
            if (! empty($idsAunSinValor)) {
                $tarifasBase = DB::connection('sqlsrv')->select("
                    SELECT td.IdProducto, td.Valor, t.DesTarifa
                    FROM conTarifaDetalle td
                    INNER JOIN conTarifa t ON td.IdTarifa = t.IdTarifa
                    WHERE td.IdProducto IN (".implode(',', $idsAunSinValor).")
                      AND td.Valor > 0
                      AND (t.DesTarifa LIKE '%SOAT 2026%' OR t.DesTarifa LIKE '%UVB 2026%' OR t.DesTarifa LIKE '%UVT 2026%')
                    ORDER BY td.IdProducto, td.Valor DESC
                ");

                foreach ($tarifasBase as $tb) {
                    $cups = $idToCups[$tb->IdProducto] ?? null;
                    if ($cups && ! isset($tarifasPorCups[$cups])) {
                        $tarifasPorCups[$cups] = [
                            'valor' => (float) $tb->Valor,
                            'origen' => "Tarifario SAHI ({$tb->DesTarifa})",
                            'facturas' => 0,
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('Error consultando tarifas en SAHI: '.$e->getMessage());
        }

        return $tarifasPorCups;
    }

    /**
     * Procesa integralmente el archivo Excel de productos Espinal, actualiza CUPS, enriquece precios y sincroniza con BD.
     *
     * Flujo del proceso:
     * 1. Valida la existencia física del archivo Excel.
     * 2. Carga la hoja 'Hoja1' mediante PhpSpreadsheet.
     * 3. Itera las filas resolviendo códigos CUPS faltantes mediante el catálogo determinístico y difuso.
     * 4. Consulta en lote a SAHI las tarifas financieras históricas y del año especificado.
     * 5. Escribe en el archivo Excel los códigos CUPS en columna A y los valores en columna C con formato numérico.
     * 6. Si se habilita sincronización de BD, actualiza en config_productos de MySQL de forma transaccional.
     * 7. Guarda el archivo Excel manteniendo estrictamente estilos, cabeceras y estructura.
     *
     * @param  string  $filePath  Ruta al archivo Excel
     * @param  bool  $actualizarExcel  Indica si se deben guardar los cambios en el archivo físico
     * @param  bool  $actualizarDb  Indica si se debe sincronizar la tabla config_productos en MySQL
     * @param  int  $year  Año base para cruce de facturación (predeterminado 2026)
     * @return array Resultado estructurado con métricas de la sincronización
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-10
     */
    public function procesarArchivoExcel(
        string $filePath,
        bool $actualizarExcel = true,
        bool $actualizarDb = true,
        int $year = 2026,
        ?int $contratoId = 5082
    ): array {
        if (! file_exists($filePath)) {
            return $this->error("El archivo especificado no existe: {$filePath}", 404);
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getSheetByName('Hoja1') ?? $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            $filasProcesadas = [];
            $cupsParaCotizar = [];
            $cupsCompletados = 0;

            // Paso 1: Lectura y resolución de CUPS
            for ($r = 2; $r <= $highestRow; $r++) {
                $cupsOriginal = trim((string) $sheet->getCell('A'.$r)->getValue());
                $nomProduc = trim((string) $sheet->getCell('B'.$r)->getValue());
                $valOriginal = $sheet->getCell('C'.$r)->getValue();

                if ($nomProduc === '') {
                    continue;
                }

                $cupsResuelto = $this->resolverCups($nomProduc, $cupsOriginal);

                if (empty($cupsOriginal) && ! empty($cupsResuelto)) {
                    $cupsCompletados++;
                }

                if (! empty($cupsResuelto)) {
                    $cupsParaCotizar[] = $cupsResuelto;
                }

                $filasProcesadas[] = [
                    'row' => $r,
                    'nom_produc' => $nomProduc,
                    'cups_original' => $cupsOriginal,
                    'cups' => $cupsResuelto,
                    'val_original' => $valOriginal,
                ];
            }

            // Paso 2: Consulta financiera en SAHI
            $tarifasSahi = $this->consultarTarifasSahi($cupsParaCotizar, $year, $contratoId);

            $valoresActualizados = 0;
            $dbActualizados = 0;

            // Paso 3: Aplicación de cambios en Excel
            foreach ($filasProcesadas as &$fila) {
                $r = $fila['row'];
                $cups = $fila['cups'];
                $tarifaInfo = $cups ? ($tarifasSahi[$cups] ?? null) : null;
                $valorFinal = $tarifaInfo['valor'] ?? null;

                $fila['valor_sahi'] = $valorFinal;
                $fila['origen_tarifa'] = $tarifaInfo['origen'] ?? 'Sin tarifa encontrada';

                if ($actualizarExcel) {
                    if (! empty($cups)) {
                        $sheet->setCellValue('A'.$r, $cups);
                    }
                    if ($valorFinal !== null && $valorFinal > 0) {
                        $sheet->setCellValue('C'.$r, $valorFinal);
                        $sheet->getStyle('C'.$r)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
                        $valoresActualizados++;
                    }
                }
            }
            unset($fila);

            // Guardar cambios en el archivo físico
            if ($actualizarExcel) {
                $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
                $writer->save($filePath);
            }

            // Paso 4: Sincronización transaccional en MySQL config_productos
            if ($actualizarDb) {
                DB::transaction(function () use ($filasProcesadas, &$dbActualizados) {
                    foreach ($filasProcesadas as $fila) {
                        $cups = $fila['cups'];
                        $nom = $fila['nom_produc'];
                        $val = $fila['valor_sahi'] ?? 0;

                        if (empty($cups)) {
                            continue;
                        }

                        // Normalización de nombre para cruce
                        $cleanNom = trim(preg_replace('/^ST\s+/i', '', $nom));
                        $cleanNom = trim(preg_replace('/[\[\]\(\)]/', '', $cleanNom));

                        $productoLocal = ConfigProducto::where(function ($q) use ($nom, $cleanNom, $cups) {
                            $q->where('cod_cups', $cups)
                                ->orWhere('nom_produc', $nom)
                                ->orWhere('nom_produc', $cleanNom);
                        })->first();

                        if ($productoLocal) {
                            $updates = [];
                            if (empty($productoLocal->cod_cups)) {
                                $updates['cod_cups'] = $cups;
                            }
                            if ($val > 0 && (float) $productoLocal->val_produc != (float) $val) {
                                $updates['val_produc'] = $val;
                            }

                            if (! empty($updates)) {
                                $productoLocal->update($updates);
                                $dbActualizados++;
                            }
                        }
                    }
                });
            }

            return $this->success([
                'total_filas' => count($filasProcesadas),
                'cups_completados' => $cupsCompletados,
                'valores_actualizados_excel' => $valoresActualizados,
                'productos_bd_actualizados' => $dbActualizados,
                'archivo' => $filePath,
                'detalle' => $filasProcesadas,
            ], 'Proceso de sincronización y enriquecimiento completado con éxito.');
        } catch (\Throwable $e) {
            Log::error('Fallo en sincronización de productos Espinal: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return $this->error("Error al procesar el archivo Excel: {$e->getMessage()}", 500);
        }
    }
}
