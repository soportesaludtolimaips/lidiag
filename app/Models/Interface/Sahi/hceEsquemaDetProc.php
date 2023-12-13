<?php

namespace App\Models\Interface\Sahi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class hceEsquemaDetProc extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';

    protected $table = 'hceEsquemaDetProc';

    protected $fillabre = [
        'IdDetalleValor', 'IdEsquemaEncabezado', 'ValNroRegistro', 'ValVarChar1', 'ValVarChar2', 'ValVarChar3',
        'ValVarChar4', 'ValVarChar5', 'ValVarChar6', 'ValVarChar7', 'ValVarChar8', 'ValVarChar9', 'ValVarChar10', 'ValVarChar11',
        'ValVarChar12', 'ValVarChar13', 'ValVarChar14', 'ValVarChar15', 'ValVarChar16', 'ValVarChar17', 'ValVarChar18', 'ValVarChar19',
        'ValVarChar20', 'IdUsuarioR', 'FecRegistro'
    ];

    public $timestamps = false;
}
