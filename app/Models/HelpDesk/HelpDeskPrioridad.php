<?php

namespace App\Models\HelpDesk;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpDeskPrioridad extends Model
{
    use HasFactory;

    protected $table = 'help_desk_prioridades';

    protected $fillable = ['nombre', 'orden', 'activo'];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];
}
