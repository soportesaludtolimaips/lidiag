<?php

namespace App\Models\Estudio;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EstudioAudio extends Model
{
    use HasFactory;

    protected $table = 'estudios_audios';

    protected $fillable = ['audio'];

    protected $appends = ['url'];

    /**
     * Obtiene la URL pública del audio
     */
    public function getUrlAttribute()
    {
        return Storage::disk('audios')->url($this->audio);
    }

    /**
     * Obtiene la ruta completa del archivo en storage
     */
    public function getFullPathAttribute()
    {
        return Storage::disk('audios')->path($this->audio);
    }
}
