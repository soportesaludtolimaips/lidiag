### Task 5: ActivityLog Model + Migration

**Files:**
- Create: `database/migrations/2026_06_24_000001_create_activity_log_table.php`
- Create: `app/Models/ActivityLog.php`
- Create: `database/factories/ActivityLogFactory.php`

**Migration:**
```php
Schema::create('activity_log', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->string('loggable_type');
    $table->unsignedBigInteger('loggable_id');
    $table->string('event');
    $table->json('old_values')->nullable();
    $table->json('new_values')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->timestamps();
    $table->index(['loggable_type', 'loggable_id'], 'idx_activity_loggable');
    $table->index('event', 'idx_activity_event');
    $table->index('created_at', 'idx_activity_created');
});
```

**Model:**
```php
class ActivityLog extends Model
{
    use HasFactory;
    protected $table = 'activity_log';
    protected $fillable = ['user_id','loggable_type','loggable_id','event','old_values','new_values','ip_address','user_agent'];
    protected $casts = ['old_values' => 'array', 'new_values' => 'array'];
    public function user(): BelongsTo { ... }
    public function loggable(): MorphTo { ... }
}
```

**Factory:**
```php
class ActivityLogFactory extends Factory
{
    public function definition(): array {
        return [
            'user_id' => User::factory(),
            'loggable_type' => User::class,
            'loggable_id' => User::factory(),
            'event' => 'created',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ];
    }
}
```

Run: `php artisan migrate`

### Task 6: LogsActivity Trait

**Files:**
- Create: `app/Traits/LogsActivity.php`
- Modify: `app/Models/Ecografias/Ecografia.php`
- Modify: `app/Models/Ecografias/EcografiaProducto.php`
- Modify: `app/Models/Consentimientos/EcografiaConsentimiento.php`

**Trait code:**
```php
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
        if (!Auth::check()) return;
        $old = match($event) {
            'updated' => $this->getOriginal(),
            'deleted' => $this->getAttributes(),
            default => null,
        };
        $new = match($event) {
            'created' => $this->getAttributes(),
            'updated' => $this->getChanges(),
            default => null,
        };
        ActivityLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => get_class($this),
            'loggable_id' => $this->getKey(),
            'event' => $event,
            'old_values' => $old ? array_diff_key($old, ['password'=>1,'remember_token'=>1,'firma'=>1,'token_hash'=>1]) : null,
            'new_values' => $new ? array_diff_key($new, ['password'=>1,'remember_token'=>1,'firma'=>1,'token_hash'=>1]) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

Add `use App\Traits\LogsActivity;` and `use LogsActivity;` to all 3 models.

Commit both tasks with: `feat(audit): activity_log model and LogsActivity trait`

Work from: C:\laragon\www\lidiag
