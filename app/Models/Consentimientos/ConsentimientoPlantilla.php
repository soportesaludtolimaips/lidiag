<?php

namespace App\Models\Consentimientos;

use App\Models\Configuracion\ConfigModulo;
use App\Models\Configuracion\ConfigProducto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsentimientoPlantilla extends Model
{
    use HasFactory;

    protected $table = 'consentimiento_plantillas';

    protected $fillable = [
        'config_modulo_id',
        'producto_id',
        'usuario_crea_id',
        'nombre',
        'codigo',
        'version_formato',
        'anio_formato',
        'html',
        'version',
        'vigente',
        'vigente_key',
    ];

    public function modulo()
    {
        return $this->belongsTo(ConfigModulo::class, 'config_modulo_id');
    }

    public function producto()
    {
        return $this->belongsTo(ConfigProducto::class, 'producto_id');
    }

    public function usuarioCrea()
    {
        return $this->belongsTo(User::class, 'usuario_crea_id');
    }

    public function versiones()
    {
        return $this->hasMany(ConsentimientoPlantillaVersion::class, 'plantilla_id');
    }

    public function imagenes()
    {
        return $this->hasMany(ConsentimientoPlantillaImagen::class, 'plantilla_id');
    }

    public function productosAsignados()
    {
        return $this->hasMany(ConsentimientoPlantillaProducto::class, 'plantilla_id');
    }
}
