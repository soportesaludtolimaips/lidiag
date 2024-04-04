<?php

namespace App\Models\Interface\Sahi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Atencion extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';

    protected $table = 'admAtencion';

    public function paciente()
    {
        return $this->hasOne(Paciente::class, 'IdCliente', 'IdCliente');
    }
}
