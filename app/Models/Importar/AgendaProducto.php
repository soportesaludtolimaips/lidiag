<?php

namespace App\Models\Importar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent para importación de productos de agenda desde sistema externo (DICOM).
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
class AgendaProducto extends Model
{
    use HasFactory;

    protected $connection = 'mysql_importar_dicom';

    protected $table = 'agenda_productos';
}
