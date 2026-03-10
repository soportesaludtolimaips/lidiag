<?php

namespace App\Models\Estudio;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstudioProductoAdendum extends Model
{
    use HasFactory;

    protected $table = 'estudios_productos_adendums';

    protected $fillable = ['estudio_producto_id', 'transcriptor_id', 'fechor_lectura', 'lectura', 'fechor_trascrito', 'activo'];

    public function estudioProducto()
    {
        return $this->belongsTo(EstudioProducto::class, 'estudio_producto_id');
    }
}
