<?php

namespace App\Models\Interface\Sahi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sisTabla extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';

    protected $table = 'sisTabla';

    protected $fillable = ['Consecutivo'];
}
