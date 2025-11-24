<?php

namespace App\Models\ImportarEcografias;

use Illuminate\Database\Eloquent\Model;

class ConfigEps extends Model
{
    protected $connection = 'mysql_importar_dicom';
    protected $table = 'config_eps';
    protected $primaryKey = 'id_eps';
    public $timestamps = false;

    protected $fillable = ['cod_eps', 'nom_eps'];

    public function lecturas()
    {
        return $this->hasMany(Lectura::class, 'id_eps', 'id_eps');
    }
}
