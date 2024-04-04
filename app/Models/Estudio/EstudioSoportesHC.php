<?php

namespace App\Models\Estudio;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstudioSoportesHC extends Model
{
    use HasFactory;
    protected $table = 'estudios_soportes_hc';

    protected $fillable = ['estudio_id', 'archivo_original', 'archivo_encrip'];
}
