<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    /**
     * Registra los escuchadores de eventos del modelo en el arranque del trait.
     *
     * Asocia los eventos created, updated y deleted con la escritura del
     * registro de actividad.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    protected static function bootLogsActivity(): void
    {
        $events = ['created', 'updated', 'deleted'];
        foreach ($events as $event) {
            static::registerModelEvent($event, function ($model) use ($event) {
                $model->logActivity($event);
            });
        }
    }

    /**
     * Guarda en activity_log los valores anterior y nuevo del evento.
     *
     * @param  string  $event  Evento ocurrido (created, updated o deleted).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function logActivity(string $event): void
    {
        if (! Auth::check()) {
            return;
        }

        $old = match ($event) {
            'updated' => $this->getOriginal(),
            'deleted' => $this->getAttributes(),
            default => null,
        };

        $new = match ($event) {
            'created' => $this->getAttributes(),
            'updated' => $this->getChanges(),
            default => null,
        };

        ActivityLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => get_class($this),
            'loggable_id' => $this->getKey(),
            'event' => $event,
            'old_values' => $old ? array_diff_key($old, ['password' => 1, 'remember_token' => 1, 'firma' => 1, 'token_hash' => 1]) : null,
            'new_values' => $new ? array_diff_key($new, ['password' => 1, 'remember_token' => 1, 'firma' => 1, 'token_hash' => 1]) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
