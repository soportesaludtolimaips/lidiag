<?php

namespace App\Models\Dcm4chee;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instance extends Model
{
    use HasFactory;

    protected $connection = 'mysql_sucursal';
    protected $table = 'instance';
    protected $primaryKey = 'pk'; // Asumiendo que 'pk' es la clave primaria de la tabla instance
    public $timestamps = false;

    public function series()
    {
        return $this->belongsTo(Series::class, 'series_fk', 'pk');
    }
}
