<?php

namespace App\Models\Dcm4chee;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Dcm4chee\Study;

class Patient extends Model
{
    use HasFactory;

    protected $connection = 'mysql_sucursal';

    protected $table = 'patient';

    public function Study()
    {
        return $this->hasMany(Study::class);
    }
}
