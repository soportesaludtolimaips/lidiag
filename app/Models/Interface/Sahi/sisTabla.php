<?php

namespace App\Models\Interface\Sahi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $NomTabla
 * @property int $IdTabla
 * @property int $Consecutivo
 */
class sisTabla extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';

    protected $table = 'sisTabla';

    protected $fillable = ['Consecutivo'];

    protected $primaryKey = 'NomTabla';

    public $timestamps = false;
}
