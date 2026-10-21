<?php

namespace App\Models\Interface\Sahi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $IdDetalleValor
 * @property int $IdEsquemaEncabezado
 * @property int $ValNroRegistro
 * @property string $ValVarChar1
 * @property string $ValVarChar2
 * @property string $ValVarChar3
 * @property string $ValVarChar4
 * @property string $ValVarChar5
 * @property string $ValVarChar6
 * @property string $ValVarChar7
 * @property string $ValVarChar8
 * @property string $ValVarChar9
 * @property string $ValVarChar10
 * @property string $ValVarChar11
 * @property string $ValVarChar12
 * @property string $ValVarChar13
 * @property string $ValVarChar14
 * @property string $ValVarChar15
 * @property string $ValVarChar16
 * @property string $ValVarChar17
 * @property string $ValVarChar18
 * @property string $ValVarChar19
 * @property string $ValVarChar20
 * @property int $IdUsuarioR
 * @property string $FecRegistro
 */
class hceEsquemaDetProc extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';

    protected $table = 'hceEsquemaDetProc';

    protected $fillable = [
        'IdDetalleValor', 'IdEsquemaEncabezado', 'ValNroRegistro', 'ValVarChar1', 'ValVarChar2', 'ValVarChar3',
        'ValVarChar4', 'ValVarChar5', 'ValVarChar6', 'ValVarChar7', 'ValVarChar8', 'ValVarChar9', 'ValVarChar10', 'ValVarChar11',
        'ValVarChar12', 'ValVarChar13', 'ValVarChar14', 'ValVarChar15', 'ValVarChar16', 'ValVarChar17', 'ValVarChar18', 'ValVarChar19',
        'ValVarChar20', 'IdUsuarioR', 'FecRegistro',
    ];

    public $timestamps = false;
}
