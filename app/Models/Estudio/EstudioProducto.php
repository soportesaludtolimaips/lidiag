<?php

namespace App\Models\Estudio;

use App\Models\Configuracion\ConfigProducto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstudioProducto extends Model
{
    use HasFactory;

    protected $table = 'estudios_productos';

    protected $fillable = ['estudio_id', 'producto_id', 'transcriptor_id', 'cod_cups', 'nom_produc', 'fechor_lectura', 'lectura', 'fechor_trascrito', 'medico_ordena', 'visible'];

    public function estudio()
    {
        return $this->belongsTo(Estudio::class, 'estudio_id');
    }

    public function transcriptor()
    {
        return $this->belongsTo(User::class);
    }

    public function adendums()
    {
        return $this->hasMany(EstudioProductoAdendum::class);
    }

    public function producto()
    {
        return $this->belongsTo(ConfigProducto::class, 'producto_id');
    }
}
