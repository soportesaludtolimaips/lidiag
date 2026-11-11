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
     * URL pública de reproducción del audio a través de la ruta de streaming "/audios/{filename}".
     *
     * @returns string|null
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-30
     */
    public function getUrlAttribute()
    {
        if (! $this->audio) {
            return null;
        }

        $path = $this->audio;
        if (str_starts_with($path, 'audios/')) {
            $path = substr($path, 7);
        }

        return '/audios/'.ltrim($path, '/');
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
