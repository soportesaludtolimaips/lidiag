<?php

namespace App\Models\Configuracion;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigPlantillaDetalle extends Model
{
    use HasFactory;

    protected $table = 'config_plantillas_detalles';

    protected $fillable = [
        'plantilla_tipo_id',
        'medico_id',
        'nom_plantilla',
        'planitalla',
        'opinion',
        'estado',
    ];

    public function plantillaTipo()
    {
        return $this->belongsTo(ConfigPlantillaTipo::class, 'plantilla_tipo_id');
    }

    public function medico()
    {
        return $this->belongsTo(User::class, 'medico_id');
    }
}
