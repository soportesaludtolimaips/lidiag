<?php

namespace App\Models\Dcm4chee;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Dcm4chee\Patient;
use App\Models\Estudio\Estudio;

class Study extends Model
{
    use HasFactory;

    protected $connection = 'mysql_sucursal';

    protected $table = 'study';
    protected $primaryKey = 'pk'; // Asumiendo que 'pk' es la clave primaria de la tabla study
    public $timestamps = false; // Si no usas created_at y updated_at

    //protected $fillable = ['pk', 'patient_fk', 'accno_issuer_fk', 'study_iuid'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function series()
    {
        return $this->hasMany(Series::class, 'study_fk', 'pk');
    }
}
