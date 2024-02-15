<?php

namespace App\Models\Importar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudyDicom extends Model
{
    use HasFactory;

    protected $connection = 'mysql_importar_dicom';

    protected $table = 'study';

    protected $fillable = ['accession_no'];
}
