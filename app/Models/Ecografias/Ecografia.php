<?php

namespace App\Models\Ecografias;

use App\Models\Configuracion\ConfigAdminSalud;
use App\Models\General\Paciente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ecografia extends Model
{
    use HasFactory;

    protected $fillable = [
        'medico_id',
        'paciente_id',
        'usua_regis_id',
        'admin_salud_id',
        'fec_cita_sahi',
        'fec_regis_cita_sahi',
        'contrato',
        'plan',
        'quien_ordena',
        'estado',
    ];

    /**
     * Relaciones
     */
    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function usuarioRegistrador()
    {
        return $this->belongsTo(User::class, 'usua_regis_id');
    }

    public function adminSalud()
    {
        return $this->belongsTo(ConfigAdminSalud::class, 'admin_salud_id');
    }

    public function medico()
    {
        return $this->belongsTo(User::class, 'medico_id');
    }

    public function productosEcografias()
    {
        return $this->hasMany(EcografiaProducto::class, 'ecografia_id');
    }
}
