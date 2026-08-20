<?php

namespace App\Models\Estudio;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EstudioAudio extends Model
{
    use HasFactory;

    protected $table = 'estudios_audios';

    protected $fillable = [
        'audio',
        'estudio_id',
        'estudio_producto_id',
        'tipo',
        'nombre_original',
        'formato',
        'duracion',
        'tamano',
        'usuario_id',
        'transcripcion',
    ];

    protected $guarded = ['*'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['url'];

    public function estudioProducto()
    {
        return $this->belongsTo(EstudioProducto::class, 'estudio_producto_id');
    }

    public function estudio()
    {
        return $this->belongsTo(Estudio::class, 'estudio_id');
    }

    public function getUrlAttribute()
    {
        $path = $this->audio;
        if (str_starts_with($path, 'audios/')) {
            $path = substr($path, 7);
        }

        return Storage::disk('audios')->url($path);
    }

    public function getFullPathAttribute()
    {
        return Storage::disk('audios')->path($this->audio);
    }
}
