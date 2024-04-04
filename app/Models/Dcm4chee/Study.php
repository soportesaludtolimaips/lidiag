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

    //protected $fillable = ['pk', 'patient_fk', 'accno_issuer_fk', 'study_iuid'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
