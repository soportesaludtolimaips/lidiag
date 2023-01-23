<?php

namespace App\Models\Estudio;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class EstudioProducto extends Model
{
    use HasFactory;

    protected $table = 'estudios_productos';
    
    protected $fillable = ['estudio_id', 'producto_id', 'transcriptor_id', 'cod_cups', 'nom_produc', 'fechor_lectura', 'lectura', 'fechor_trascrito', 'visible'];

    public function estudio()
    {
        return $this->belongsTo(Estudio::class);
    }

    public function transcriptor()
    {
        return $this->belongsTo(User::class);
    }
}
