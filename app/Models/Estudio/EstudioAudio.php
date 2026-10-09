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

    /**
     * Producto del estudio al que pertenece el audio.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function estudioProducto()
    {
        return $this->belongsTo(EstudioProducto::class, 'estudio_producto_id');
    }

    /**
     * Estudio al que pertenece el audio.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function estudio()
    {
        return $this->belongsTo(Estudio::class, 'estudio_id');
    }

    /**
     * URL pública de descarga del audio (normaliza el prefijo "audios/").
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function getUrlAttribute()
    {
        $path = $this->audio;
        if (str_starts_with($path, 'audios/')) {
            $path = substr($path, 7);
        }

        return Storage::disk('audios')->url($path);
    }

    /**
     * Ruta absoluta del archivo de audio en el disco local.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function getFullPathAttribute()
    {
        return Storage::disk('audios')->path($this->audio);
    }
}
