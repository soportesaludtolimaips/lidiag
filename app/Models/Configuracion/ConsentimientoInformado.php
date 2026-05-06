<?php

namespace App\Models\Configuracion;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsentimientoInformado extends Model
{
    use HasFactory;

    protected $fillable = [
        'producto_id',
        'sede_id',
        'usuario_crea_id',
        'nombre',
        'archivo',
        'estado',
    ];

    public function producto()
    {
        return $this->belongsTo(ConfigProducto::class, 'producto_id');
    }

    public function sede()
    {
        return $this->belongsTo(ConfigSede::class, 'sede_id');
    }

    public function usuarioCrea()
    {
        return $this->belongsTo(User::class, 'usuario_crea_id');
    }
}
