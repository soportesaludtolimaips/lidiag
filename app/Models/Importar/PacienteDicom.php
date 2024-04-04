<?php

namespace App\Models\Importar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PacienteDicom extends Model
{
    use HasFactory;

    protected $connection = 'mysql_importar_dicom';

    protected $table = 'pacientes';
}
