<?php

namespace App\Models\Interface\Sahi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $IdEsquema
 * @property int $IdConsecutivoEsq
 */
class HceEsquema extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';

    protected $table = 'HceEsquema';

    protected $primaryKey = 'IdEsquema';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['IdConsecutivoEsq'];
}
