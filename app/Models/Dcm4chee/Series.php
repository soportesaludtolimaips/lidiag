<?php

namespace App\Models\Dcm4chee;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Series extends Model
{
    use HasFactory;

    protected $connection = 'mysql_sucursal';

    protected $table = 'series';

    protected $primaryKey = 'pk'; // Asumiendo que 'pk' es la clave primaria de la tabla series

    public $timestamps = false;

    public function study()
    {
        return $this->belongsTo(Study::class, 'study_fk', 'pk');
    }

    public function instances()
    {
        return $this->hasMany(Instance::class, 'series_fk', 'pk');
    }
}
