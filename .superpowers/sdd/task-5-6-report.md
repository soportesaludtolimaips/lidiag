# Task 5-6 Report

## Completed

### Task 5: ActivityLog Model + Migration
- **Migration**: `database/migrations/2026_06_24_000001_create_activity_log_table.php` — creates `activity_log` table with `id`, `user_id` (FK nullable), `loggable_type`, `loggable_id`, `event`, `old_values` (json), `new_values` (json), `ip_address`, `user_agent`, timestamps, and 3 indexes
- **Model**: `app/Models/ActivityLog.php` — `HasFactory`, `$table='activity_log'`, `$fillable`, `$casts` (array for json columns), `user()` BelongsTo, `loggable()` MorphTo
- **Factory**: `database/factories/ActivityLogFactory.php` — `Database\Factories` namespace, `$model=ActivityLog::class`, definition with User factory defaults
- **Migration applied**: ✅ Ran `php artisan migrate` — 98.47ms

### Task 6: LogsActivity Trait
- **Trait**: `app/Traits/LogsActivity.php` — `bootLogsActivity()` registers `created`/`updated`/`deleted` events; `logActivity()` records auth user, model type/id, old/new values (filtered for sensitive keys), IP, user agent
- **Modified**:
  - `app/Models/Ecografias/Ecografia.php` — added `use App\Traits\LogsActivity;` + `use LogsActivity;`
  - `app/Models/Ecografias/EcografiaProducto.php` — same
  - `app/Models/Consentimientos/EcografiaConsentimiento.php` — same

### Verification
- `php -l` — no syntax errors on all 7 files
- `php artisan migrate` — successful
- Git commit: `90f7fc6e` — `feat(audit): activity_log model and LogsActivity trait`
