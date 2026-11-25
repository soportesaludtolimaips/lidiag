<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Normalizador y unificador de Modalidades DICOM / PACS y familias de diagnóstico.
 *
 * Flujo del proceso:
 * 1. Resuelve la familia canónica (RX, CT, MR, MG, US, etc.) a partir de modalidades DICOM compuestas (ej. CT\SR, CR\DX, PR\MR).
 * 2. Provee cláusulas de consulta SQL para filtrar por familia clínica independientemente de cómo el PACS rotule el estudio.
 * 3. Provee etiquetas legibles e iconos para la interfaz de reportes.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-25
 */
class DicomModalityHelper
{
    /**
     * Mapeo de familia a códigos DICOM individuales o patrones.
     */
    public const FAMILIA_RX = ['CR', 'DX', 'DR', 'RG', 'X-RAY'];

    public const FAMILIA_CT = ['CT'];

    public const FAMILIA_MR = ['MR'];

    public const FAMILIA_MG = ['MG'];

    public const FAMILIA_US = ['US'];

    /**
     * Normaliza un string de modalidad DICOM (que puede contener slash compuestos como CT\SR, CR\DX)
     * a su clave canónica de familia: RX, CT, MR, MG, US, u OTRO.
     */
    public static function normalizarFamilia(?string $mods): string
    {
        $raw = strtoupper(trim((string) $mods));
        if ($raw === '' || $raw === 'OTRO') {
            return 'OTRO';
        }

        // Si ya viene como familia declarada
        if ($raw === 'RX') {
            return 'RX';
        }

        // Búsqueda de tokens divididos por backslash o espacios
        $tokens = preg_split('/[\\\\\/,\s]+/', $raw);

        // Si contiene tomografía
        if (in_array('CT', $tokens, true) || str_contains($raw, 'CT')) {
            return 'CT';
        }

        // Si contiene resonancia
        if (in_array('MR', $tokens, true) || str_contains($raw, 'MR')) {
            return 'MR';
        }

        // Si contiene mamografía
        if (in_array('MG', $tokens, true)) {
            return 'MG';
        }

        // Si contiene ecografía
        if (in_array('US', $tokens, true)) {
            return 'US';
        }

        // Si contiene radiología convencional
        foreach (self::FAMILIA_RX as $rxCode) {
            if (in_array($rxCode, $tokens, true) || str_contains($raw, $rxCode)) {
                return 'RX';
            }
        }

        return $tokens[0] ?? $raw;
    }

    /**
     * Aplica la condición WHERE correspondiente a una familia o código de modalidad
     * sobre un QueryBuilder o EloquentBuilder.
     *
     * @param  QueryBuilder|EloquentBuilder  $query
     * @param  string  $columna  Nombre completo de la columna (ej. "estudios.mods_in_study" o "study.mods_in_study")
     * @param  string|null  $modalidadFiltro  Código o familia ingresada por el usuario (ej. "RX", "CT", "CR", etc.)
     * @return QueryBuilder|EloquentBuilder
     */
    public static function aplicarFiltroModalidad($query, string $columna, ?string $modalidadFiltro)
    {
        $mod = strtoupper(trim((string) $modalidadFiltro));
        if ($mod === '' || $mod === 'TODAS' || $mod === 'ALL') {
            return $query;
        }

        return $query->where(function ($q) use ($columna, $mod) {
            if ($mod === 'RX') {
                $q->where(function ($sub) use ($columna) {
                    $sub->where("{$columna}", 'LIKE', '%CR%')
                        ->orWhere("{$columna}", 'LIKE', '%DX%')
                        ->orWhere("{$columna}", 'LIKE', '%DR%')
                        ->orWhere("{$columna}", 'LIKE', '%RG%')
                        ->orWhere("{$columna}", 'LIKE', '%RX%');
                });
            } elseif ($mod === 'CT' || $mod === 'TAC') {
                $q->where("{$columna}", 'LIKE', '%CT%');
            } elseif ($mod === 'MR' || $mod === 'RNM') {
                $q->where("{$columna}", 'LIKE', '%MR%');
            } elseif ($mod === 'MG') {
                $q->where("{$columna}", 'LIKE', '%MG%');
            } elseif ($mod === 'US') {
                $q->where("{$columna}", 'LIKE', '%US%');
            } else {
                $q->where("{$columna}", 'LIKE', "%{$mod}%");
            }
        });
    }

    /**
     * Retorna etiqueta legible en español para reportes y gráficos.
     */
    public static function etiquetaFamilia(?string $mods): string
    {
        $familia = self::normalizarFamilia($mods);

        return match ($familia) {
            'RX' => 'Rayos X (Convencional)',
            'CT' => 'Tomografía (TAC)',
            'MR' => 'Resonancia Magnética',
            'MG' => 'Mamografía',
            'US' => 'Ecografía',
            default => $familia,
        };
    }
}
