<?php

namespace App\Models\HelpDesk;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpDeskCategoria extends Model
{
    use HasFactory;

    protected $table = 'help_desk_categorias';

    protected $fillable = ['nombre', 'activo'];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
