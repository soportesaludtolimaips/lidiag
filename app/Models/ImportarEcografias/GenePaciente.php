<?php

namespace App\Models\ImportarEcografias;

use Illuminate\Database\Eloquent\Model;

class GenePaciente extends Model
{
    protected $connection = 'mysql_importar_dicom';

    protected $table = 'gene_pacientes';

    protected $primaryKey = 'id_pacien';

    public $timestamps = false;

    protected $fillable = [
        'id_tipo_docu',
        'id_ocupa',
        'id_depar',
        'id_muni',
        'num_docu',
        'pri_nom',
        'seg_nom',
        'pri_ape',
        'seg_ape',
        'fec_naci',
        'sexo',
        'dir',
        'tel',
        'cel',
        'e_mail',
    ];

    // --- Relaciones ---
    /**
     * Tipo de documento del paciente.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function tipoDocumento()
    {
        return $this->belongsTo(ConfigTipoDocumento::class, 'id_tipo_docu', 'id_tipo');
    }
}
