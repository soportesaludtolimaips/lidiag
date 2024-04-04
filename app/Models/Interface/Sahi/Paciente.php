<?php

namespace App\Models\Interface\Sahi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';

    protected $table = 'admCliente';

    public function atenciones()
    {
        return $this->belongsTo(Atencion::class, 'IdCliente', 'IdCliente');
    }
}
