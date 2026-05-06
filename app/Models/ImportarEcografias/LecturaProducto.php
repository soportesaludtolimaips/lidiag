<?php

namespace App\Models\ImportarEcografias;

use Illuminate\Database\Eloquent\Model;

class LecturaProducto extends Model
{
    protected $connection = 'mysql_importar_dicom';

    protected $table = 'lecturas_productos';

    public $timestamps = false;

    protected $fillable = [
        'id_lectura',
        'id_producto',
        'val_produc',
    ];
}
