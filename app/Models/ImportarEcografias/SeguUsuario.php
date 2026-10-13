<?php

namespace App\Models\ImportarEcografias;

use Illuminate\Database\Eloquent\Model;

class SeguUsuario extends Model
{
    protected $connection = 'mysql_importar_dicom';

    protected $table = 'segu_usuarios';

    protected $primaryKey = 'id_usua';

    public $timestamps = false;

    protected $fillable = [
        'id_personal',
        'nom_usua',
        'ape_usu',
        'login',
        'contra',
        'tipo_usua',
        'acti',
        'id_sahi',
    ];

    /**
     * Personal al que pertenece el usuario.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function personal()
    {
        return $this->belongsTo(GenePersonal::class, 'id_personal', 'id_personal');
    }
}
