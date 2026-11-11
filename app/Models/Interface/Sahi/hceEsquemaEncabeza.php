<?php

namespace App\Models\Interface\Sahi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $IdEsquemaEncabezado
 * @property string $IdEsquema
 * @property int $idEncabezadoPadre
 * @property string $ValEsquemaConsecutivo
 * @property string $valDetConsecutivo
 * @property int $IdUbicacion
 * @property int $idAtencion
 * @property string $ValUltimoNroRegistro
 * @property string $IndActivado
 * @property string $IndHabilitado
 * @property int $IdUsuarioR
 * @property string $FecRegistro
 * @property string $IdProductoR
 * @property string $NomProducto
 * @property string $CodProducto
 * @property string $IdEstudio
 */
class hceEsquemaEncabeza extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';

    protected $table = 'hceEsquemaEncabeza';

    protected $fillable = [
        'IdEsquemaEncabezado', 'IdEsquema',  'idEncabezadoPadre', 'ValEsquemaConsecutivo', 'valDetConsecutivo', 'IdUbicacion', 'idAtencion',  'ValUltimoNroRegistro',
        'IndActivado', 'IndHabilitado',  'IdUsuarioR', 'FecRegistro', 'IdProductoR', 'NomProducto', 'CodProducto', 'IdEstudio',
    ];

    public $timestamps = false;
}
