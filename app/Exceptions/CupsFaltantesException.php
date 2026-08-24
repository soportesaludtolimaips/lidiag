<?php

namespace App\Exceptions;

use Exception;

/**
 * Excepción CupsFaltantesException: se lanza cuando el Excel de importación
 * contiene códigos CUPS que no existen en config_productos.
 *
 * Transporta la lista de productos faltantes para que el controller responda
 * al frontend con `requiere_confirmacion` y el usuario decida si crearlos
 * automáticamente o cancelar la importación.
 *
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-24
 */
class CupsFaltantesException extends Exception
{
    /**
     * Lista de productos faltantes: [{codigo: string, actividad: string}].
     *
     * @var array<int, array{codigo: string, actividad: string}>
     */
    public array $faltantes;

    /**
     * Constructor de la excepción.
     *
     * @param  array<int, array{codigo: string, actividad: string}>  $faltantes  Productos CUPS no encontrados en el catálogo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    public function __construct(array $faltantes)
    {
        $this->faltantes = $faltantes;

        parent::__construct('Códigos CUPS no encontrados en el catálogo de productos.');
    }
}
