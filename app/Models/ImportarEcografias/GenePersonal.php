<?php

namespace App\Models\ImportarEcografias;

use Illuminate\Database\Eloquent\Model;

class GenePersonal extends Model
{
    protected $connection = 'mysql_importar_dicom';

    protected $table = 'gene_personal';

    protected $primaryKey = 'id_personal';

    public $timestamps = false;

    protected $fillable = [
        'id_tipo_docu',
        'id_depar',
        'id_muni',
        'num_docu',
        'num_regis',
        'pri_nom',
        'seg_nom',
        'pri_ape',
        'seg_ape',
        'dir',
        'tel',
        'cel',
        'e_mail',
        'id_sahi',
        'tipo_personal',
        'acti',
    ];

    public function tipoDocumento()
    {
        return $this->belongsTo(ConfigTipoDocumento::class, 'id_tipo_docu', 'id_tipo');
    }

    public function departamento()
    {
        return $this->belongsTo(ConfigDepar::class, 'id_depar', 'id_depar');
    }

    public function municipio()
    {
        return $this->belongsTo(ConfigMuni::class, 'id_muni', 'id_muni');
    }

    public function lecturas()
    {
        return $this->hasMany(Lectura::class, 'id_medico', 'id_personal');
    }

    public function usuario()
    {
        return $this->hasOne(SeguUsuario::class, 'id_personal', 'id_personal');
    }
}
