<?php

namespace App\Models\Configuracion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigProducto extends Model
{
    use HasFactory;

    protected $fillable = ['cod_cups',  'nom_produc', 'porcen', 'val_produc', 'requi_lectura', 'tipo_oral', 'tipo_venoso', 'factura', 'estado'];
}
