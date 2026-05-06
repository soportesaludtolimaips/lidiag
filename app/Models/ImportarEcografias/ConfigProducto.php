<?php

namespace App\Models\ImportarEcografias;

use Illuminate\Database\Eloquent\Model;

class ConfigProducto extends Model
{
    protected $connection = 'mysql_importar_dicom';

    protected $table = 'config_productos';

    protected $primaryKey = 'id_produc';

    public $timestamps = false;

    protected $fillable = [
        'cod_produc',
        'nom_produc',
        'val_soat',
        'acti',
    ];

    public function lecturas()
    {
        return $this->belongsToMany(
            Lectura::class,
            'lecturas_productos',
            'id_producto',    // FK en lecturas_productos hacia config_productos
            'id_lectura'      // FK en lecturas_productos hacia lecturas
        )->withPivot(['val_produc']);
    }
}
