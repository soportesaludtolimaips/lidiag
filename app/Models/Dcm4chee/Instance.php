<?php

namespace App\Models\Dcm4chee;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instance extends Model
{
    use HasFactory;

    protected $connection = 'mysql_sucursal';

    protected $table = 'instance';

    protected $primaryKey = 'pk'; // Asumiendo que 'pk' es la clave primaria de la tabla instance

    public $timestamps = false;

    /**
     * Serie a la que pertenece la instancia.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function series()
    {
        return $this->belongsTo(Series::class, 'series_fk', 'pk');
    }
}
