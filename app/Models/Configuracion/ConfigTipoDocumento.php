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
        'estado'
    ];

    public function pacientes()
    {
        return $this->hasMany(Paciente::class);
    }
}
