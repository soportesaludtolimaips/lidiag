<?php

namespace App\Models\Interface\Sahi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ASI_USUA extends Model
{
    use HasFactory;


    protected $connection = 'sqlsrv';

    protected $table = 'ASI_USUA';
}
