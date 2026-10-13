<?php

namespace App\Models\Interface\Sahi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Atencion extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';

    protected $table = 'admAtencion';

    /**
     * Paciente de la atención.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasOne
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function paciente()
    {
        return $this->hasOne(Paciente::class, 'IdCliente', 'IdCliente');
    }
}
