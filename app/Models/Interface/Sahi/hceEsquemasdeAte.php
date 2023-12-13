<?php

namespace App\Models\Interface\Sahi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class hceEsquemasdeAte extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';

    protected $table = 'hceEsquemasdeAte';

    protected $fillable = [
        'IdEsquemadeAtencion', 'IdEsquema', 'IdAtencion', 'IdUbicacion',  'idTraslado', 'IdMedico',
        'FecEsquema', 'IndHabilitado', 'IndActivado', 'FecCerrado', 'IdUsuarioR'
    ];

    public $timestamps = false;
}
