<?php

namespace App\Models\Interface\Sahi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $IdAtencion
 * @property string|null $estado_ecografia
 */
class facFacturaDet extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';

    protected $table = 'facFacturaDet';
}
