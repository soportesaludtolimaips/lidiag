<?php

namespace App\Models\Interface\Sahi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GenTipoDoc extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';

    protected $table = 'genTipoDoc';

    public function atenciones()
    {
        return $this->belongsTo(Atencion::class, 'IdCliente', 'IdCliente');
    }
}
