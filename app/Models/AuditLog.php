<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id', 'evento', 'model_type', 'model_id',
        'descripcion', 'datos_viejos', 'datos_nuevos',
        'ip', 'user_agent',
    ];

    protected $casts = [
        'datos_viejos' => 'array',
        'datos_nuevos' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
