<?php

namespace App\Models\HelpDesk;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpDeskFolioCounter extends Model
{
    use HasFactory;

    protected $table = 'help_desk_folio_counters';

    protected $fillable = ['anio', 'last_number'];

    protected $casts = [
        'anio' => 'integer',
        'last_number' => 'integer',
    ];
}
