<?php

namespace App\Models\Interface\Sahi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $IdEsquemadeAtencion
 * @property int $IdEsquema
 * @property int $IdAtencion
 * @property int $IdUbicacion
 * @property int $idTraslado
 * @property int $IdMedico
 * @property string $FecEsquema
 * @property int $IndHabilitado
 * @property int $IndActivado
 * @property string $FecCerrado
 * @property int $IdUsuarioR
 */
class hceEsquemasdeAte extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';

    protected $table = 'hceEsquemasdeAte';

    protected $fillable = [
        'IdEsquemadeAtencion', 'IdEsquema', 'IdAtencion', 'IdUbicacion',  'idTraslado', 'IdMedico',
        'FecEsquema', 'IndHabilitado', 'IndActivado', 'FecCerrado', 'IdUsuarioR',
    ];

    public $timestamps = false;
}
