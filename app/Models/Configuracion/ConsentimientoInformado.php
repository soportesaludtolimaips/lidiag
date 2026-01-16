<?php

namespace App\Models\Configuracion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsentimientoInformado extends Model
{
    use HasFactory;

    protected $fillable = [
        'producto_id',
        'sede_id',
        'nombre',
        'plantilla',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }
}
