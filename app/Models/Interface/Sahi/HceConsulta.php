<?php

namespace App\Models\Interface\Sahi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HceConsulta extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';

    protected $table = 'hceConsulta';
}
