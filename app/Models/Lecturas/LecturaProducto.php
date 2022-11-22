<?php

namespace App\Models\Lecturas;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LecturaProducto extends Model
{
    use HasFactory;

    protected $fillable = ['lectua_id', 'lectua_id',  'producto_id', 'producto_id', 'transcriptor_id', 'transcriptor_id', 'cod_cups', 'nom_produc', 'fechor_lectura', 'lectura', 'fechor_trascrito', 'visible'];
}
