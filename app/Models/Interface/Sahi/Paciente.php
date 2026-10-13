<?php

namespace App\Models\Interface\Sahi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';

    protected $table = 'admCliente';

    /**
     * Atención relacionada con el paciente.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function atenciones()
    {
        return $this->belongsTo(Atencion::class, 'IdCliente', 'IdCliente');
    }
}
