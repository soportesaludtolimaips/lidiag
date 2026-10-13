<?php

namespace App\Models\Configuracion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigTipoDocumento extends Model
{
    use HasFactory;

    protected $table = 'config_tipos_documentos';

    protected $fillable = [
        'codigo',
        'tipo_documento',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    /**
     * Pacientes con este tipo de documento.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasMany
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function pacientes()
    {
        return $this->hasMany(Paciente::class);
    }
}
