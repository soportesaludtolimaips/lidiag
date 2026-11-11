<?php

namespace App\Models\Interface\Sahi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $IdProducto
 * @property string $NomProducto
 * @property string $CodLegal
 * @property string $CodProducto
 * @property int $IdGrupo
 */
class ProProducto extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';

    protected $table = 'proProducto';
}
