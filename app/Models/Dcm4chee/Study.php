<?php

namespace App\Models\Dcm4chee;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Study extends Model
{
    use HasFactory;

    protected $connection = 'mysql_sucursal';

    protected $table = 'study';
}
