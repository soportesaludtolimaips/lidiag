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

    /**
     * Tipo de documento del personal.
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

    /**
     * Departamento del personal.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function departamento()
    {
        return $this->belongsTo(ConfigDepar::class, 'id_depar', 'id_depar');
    }

    /**
     * Municipio del personal.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function municipio()
    {
        return $this->belongsTo(ConfigMuni::class, 'id_muni', 'id_muni');
    }

    /**
     * Lecturas asociadas al personal.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasMany
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function lecturas()
    {
        return $this->hasMany(Lectura::class, 'id_medico', 'id_personal');
    }

    /**
     * Usuario del personal.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasOne
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function usuario()
    {
        return $this->hasOne(SeguUsuario::class, 'id_personal', 'id_personal');
    }
}
