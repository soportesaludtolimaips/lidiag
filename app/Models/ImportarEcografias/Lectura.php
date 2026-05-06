<?php

namespace App\Models\ImportarEcografias;

use Illuminate\Database\Eloquent\Model;

class Lectura extends Model
{
    protected $connection = 'mysql_importar_dicom';

    protected $table = 'lecturas';

    protected $primaryKey = 'id_lectura';

    public $timestamps = false;

    protected $fillable = [
        'id_plantilla',
        'id_medico',
        'id_atencion',
        'id_factura',
        'id_pacien',
        'id_eps',
        'id_usua_regis',
        'lectura',
        'opinion',
        'fechor_lectura',
        'acti',
        'envio_sahi',
    ];

    public function paciente()
    {
        return $this->belongsTo(GenePaciente::class, 'id_pacien', 'id_pacien');
    }

    public function medico()
    {
        return $this->belongsTo(GenePersonal::class, 'id_medico', 'id_personal');
    }

    public function eps()
    {
        return $this->belongsTo(ConfigEps::class, 'id_eps', 'id_eps');
    }

    public function usuarioRegistrador()
    {
        return $this->belongsTo(SeguUsuario::class, 'id_usua_regis', 'id_usua');
    }

    public function plantilla()
    {
        return $this->belongsTo(ConfigPlantillaDetalle::class, 'id_plantilla', 'id_plantilla');
    }

    public function productos()
    {
        return $this->belongsToMany(
            ConfigProducto::class,
            'lecturas_productos',
            'id_lectura',
            'id_producto'
        )->withPivot(['val_produc']);
    }
}
