<?php

namespace App\Models\ImportarEcografias;

use Illuminate\Database\Eloquent\Model;

class ConfigPlantillaDetalle extends Model
{
    protected $connection = 'mysql_importar_dicom';

    protected $table = 'config_plantilla_detalle';

    protected $primaryKey = 'id_plantilla';

    public $timestamps = false;

    protected $fillable = ['tipo_id_tipo', 'id_personal', 'nom_plantilla', 'detalle', 'opinion', 'acti'];

    public function plantillaTipo()
    {
        return $this->belongsTo(ConfigPlantillaTipo::class, 'tipo_id_tipo', 'id_tipo');
    }
}
