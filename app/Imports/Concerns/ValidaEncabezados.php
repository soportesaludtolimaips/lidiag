<?php

namespace App\Imports\Concerns;

use Illuminate\Support\Collection;

/**
 * Trait ValidaEncabezados: validación estricta del formato de plantillas Excel.
 *
 * Verifica que la fila de encabezados contenga todas las columnas requeridas
 * y sugiere al usuario cuando subió una plantilla distinta a la esperada
 * (ej: plantilla de productos general en el import de médicos-producto).
 *
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-24
 */
trait ValidaEncabezados
{
    /**
     * Pistas para detectar plantillas equivocadas comunes del módulo.
     * Si el archivo contiene una de estas columnas pero falta una requerida,
     * se agrega una sugerencia específica al mensaje de error.
     *
     * @var array<string, string>
     */
    protected array $pistasPlantillaEquivocada = [
        'tarifa_actividad' => 'El archivo parece ser la PLANTILLA GENERAL DE PRODUCTOS (CODIGO | ACTIVIDAD | Tarifa Actividad).',
        'valor_urgencias' => 'El archivo parece ser la plantilla de DETALLE DE MÉDICO (CODIGO | ACTIVIDAD | Valor Contrato | Valor Consulta | Valor Urgencias).',
    ];

    /**
     * Valida que la fila de encabezados contenga todas las columnas requeridas.
     * Lanza excepción con mensaje claro indicando las columnas faltantes,
     * las encontradas y una pista si detecta una plantilla equivocada conocida.
     *
     * @param  Collection  $primeraFila  Primera fila de datos; sus keys son los encabezados normalizados.
     * @param  array<int, string>  $requeridas  Columnas obligatorias (formato snake_case de Maatwebsite).
     * @param  string  $formatoEsperado  Descripción legible del formato correcto para el usuario.
     *
     * @throws \Exception Si falta alguna columna requerida.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    protected function validarEncabezados(Collection $primeraFila, array $requeridas, string $formatoEsperado): void
    {
        $encontradas = $primeraFila->keys()->all();
        $faltantes = array_diff($requeridas, $encontradas);

        if (empty($faltantes)) {
            return;
        }

        $mensaje = 'El archivo no corresponde a la plantilla esperada.'
            .' Formato correcto: '.$formatoEsperado.'.'
            .' Columnas faltantes: '.implode(', ', $faltantes).'.';

        // Pista: ¿subieron otra plantilla del módulo?
        foreach ($this->pistasPlantillaEquivocada as $columna => $pista) {
            if (! in_array($columna, $requeridas, true) && in_array($columna, $encontradas, true)) {
                $mensaje .= ' '.$pista;
                break;
            }
        }

        throw new \Exception($mensaje);
    }
}
