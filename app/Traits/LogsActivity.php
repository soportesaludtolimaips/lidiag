<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    protected static function bootLogsActivity(): void
    {
        $events = ['created', 'updated', 'deleted'];
        foreach ($events as $event) {
            static::registerModelEvent($event, function ($model) use ($event) {
                $model->logActivity($event);
            });
        }
    }

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
