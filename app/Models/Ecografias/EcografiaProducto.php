<?php

namespace App\Models\Ecografias;

use App\Models\Configuracion\ConfigProducto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcografiaProducto extends Model
{
    use HasFactory;

    protected $table = 'ecografias_productos';

    protected $fillable = [
        'ecografia_id',
        'medico_id',
        'producto_id',
        'lectura',
        'opinion',
        'fechor_lectura',
        'estado',
    ];

    /**
     * Relaciones
     */
    public function ecografia()
    {
        return $this->belongsTo(Ecografia::class, 'ecografia_id');
    }

    public function medico()
    {
        return $this->belongsTo(User::class, 'medico_id');
    }

    public function producto()
    {
        return $this->belongsTo(ConfigProducto::class, 'producto_id');
    }
}
