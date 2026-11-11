<?php

namespace App\Models\Importar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent para importación de diagnósticos de agenda desde sistema externo (DICOM).
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
class AgendaDiagnostico extends Model
{
    use HasFactory;

    protected $connection = 'mysql_importar_dicom';

    protected $table = 'agenda_diagnosticos';
}
