<?php

namespace App\Models\HelpDesk;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpDeskEstado extends Model
{
    use HasFactory;

    protected $table = 'help_desk_estados';

    protected $fillable = ['nombre', 'orden', 'activo', 'es_base'];

    protected $casts = [
        'activo' => 'boolean',
        'es_base' => 'boolean',
        'orden' => 'integer',
    ];
}
