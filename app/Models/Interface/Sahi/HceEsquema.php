<?php

namespace App\Models\Interface\Sahi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HceEsquema extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';

    protected $table = 'HceEsquema';

    protected $fillable = ['IdConsecutivoEsq'];
}
