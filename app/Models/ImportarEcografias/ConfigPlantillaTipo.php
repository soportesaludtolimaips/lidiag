<?php

namespace App\Models\ImportarEcografias;

use Illuminate\Database\Eloquent\Model;

class ConfigPlantillaTipo extends Model
{
    protected $connection = 'mysql_importar_dicom';
    protected $table = 'config_plantilla_tipo';
    protected $primaryKey = 'id_tipo';
    public $timestamps = false;

    protected $fillable = ['nom_tipo'];

    public function plantillaDetalle()
    {
        return $this->hasMany(ConfigPlantillaDetalle::class, 'tipo_id_tipo', 'id_tipo');
    }
}
