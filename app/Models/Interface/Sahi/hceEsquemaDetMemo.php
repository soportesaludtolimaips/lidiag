<?php

namespace App\Models\Interface\Sahi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class hceEsquemaDetMemo extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';

    protected $table = 'hceEsquemaDetMemo';

    public $timestamps = false;

    protected $fillable = ['IdMemo', 'IdEsquemaEncabezado', 'DesMemo'];
}
