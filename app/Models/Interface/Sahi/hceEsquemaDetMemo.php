<?php

namespace App\Models\Interface\Sahi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $IdMemo
 * @property int $IdEsquemaEncabezado
 * @property string $DesMemo
 */
class hceEsquemaDetMemo extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';

    protected $table = 'hceEsquemaDetMemo';

    public $timestamps = false;

    protected $fillable = ['IdMemo', 'IdEsquemaEncabezado', 'DesMemo'];
}
