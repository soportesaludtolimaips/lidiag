<?php

namespace App\Models\General;

use App\Models\Configuracion\ConfigTipoDocumento;
use App\Models\Ecografias\Ecografia;
use App\Models\Estudio\Estudio;
use App\Models\Worklist\WorklistOrden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    use HasFactory;

    protected $table = 'pacientes';

    protected $fillable = ['tipo_documento_id', 'num_docu', 'nombres', 'seg_nom', 'pri_ape', 'seg_ape', 'direccion', 'sexo', 'fec_naci', 'tel', 'cel', 'email'];

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
        return $this->belongsTo(ConfigTipoDocumento::class, 'tipo_documento_id');
    }

    /**
     * Estudios del paciente.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasMany
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function estudios()
    {
        return $this->hasMany(Estudio::class);
    }

    /**
     * Ecografías del paciente.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasMany
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function ecografias()
    {
        return $this->hasMany(Ecografia::class);
    }

    /**
     * Órdenes de Worklist del paciente.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function worklistOrdenes()
    {
        return $this->hasMany(WorklistOrden::class, 'paciente_id');
    }
}
