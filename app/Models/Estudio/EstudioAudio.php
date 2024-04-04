<?php

namespace App\Models\Estudio;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstudioAudio extends Model
{
    use HasFactory;

    protected $table = 'estudios_audios';

    protected $fillable = ['audio'];
}
