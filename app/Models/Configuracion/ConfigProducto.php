<?php

namespace App\Models\Configuracion;

use App\Models\Estudio\EstudioProducto;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigProducto extends Model
{
    use HasFactory;

    protected $fillable = ['cod_cups', 'nom_produc', 'porcen', 'val_produc', 'requi_lectura', 'tipo_oral', 'tipo_venoso', 'factura', 'estado', 'servicio_id'];

    protected $casts = [
        'requi_lectura' => 'boolean',
        'tipo_oral' => 'boolean',
        'tipo_venoso' => 'boolean',
        'factura' => 'boolean',
        'estado' => 'boolean',
    ];

    public function consentimientoInformado()
    {
        return $this->hasMany(ConsentimientoInformado::class);
    }

    public function estudioProductos()
    {
        return $this->hasMany(EstudioProducto::class, 'producto_id');
    }

    public function recomendacionProducto()
    {
        return $this->hasOne(ConfigRecomendacionProducto::class, 'producto_id');
    }
}
