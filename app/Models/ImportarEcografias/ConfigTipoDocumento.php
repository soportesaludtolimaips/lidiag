<?php

namespace App\Models\ImportarEcografias;

use Illuminate\Database\Eloquent\Model;

class ConfigTipoDocumento extends Model
{
    protected $connection = 'mysql_importar_dicom';

    protected $table = 'config_tipo_documento';

    protected $primaryKey = 'id_tipo';

    public $timestamps = false;

    protected $fillable = [
        'cod_tipo',
        'nom_tipo',
    ];

    // Relación inversa con pacientes
    public function pacientes()
    {
        return $this->hasMany(GenePaciente::class, 'id_tipo_docu', 'id_tipo');
    }
}
