<?php

namespace App\Models\Interface\Sahi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class hceEsquemaEncabeza extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';

    protected $table = 'hceEsquemaEncabeza';

    protected $fillable = [
        'IdEsquemaEncabezado', 'IdEsquema',  'idEncabezadoPadre', 'ValEsquemaConsecutivo', 'valDetConsecutivo', 'IdUbicacion', 'idAtencion',  'ValUltimoNroRegistro',
        'IndActivado', 'IndHabilitado',  'IdUsuarioR', 'FecRegistro', 'IdProductoR', 'NomProducto', 'CodProducto', 'IdEstudio'
    ];
}
