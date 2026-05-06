<?php

namespace App\Models\Dcm4chee;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Study extends Model
{
    use HasFactory;

    protected $connection = 'mysql_sucursal';

    protected $table = 'study';

    protected $primaryKey = 'pk'; // Asumiendo que 'pk' es la clave primaria de la tabla study

    public $timestamps = false; // Si no usas created_at y updated_at

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_fk', 'pk');
    }

    public function series()
    {
        return $this->hasMany(Series::class, 'study_fk', 'pk');
    }
}
