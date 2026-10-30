# AGENTS.md — LIDiag

Laravel 3 (PHP >= 8.4) + MySQL + Vue 3 + Vite. Spatie Permissions ^6, Sanctum, Maatwebsite Excel. Windows + Laragon + PowerShell 5.1. Vite dev on `https://lidiag.test` (Laragon vhost).

## Integraciones y Servicios Críticos
- **Procesamiento de Audio:** FFmpeg comprime obligatoriamente a MP3 (64 kbps) en el disco `audios` asignado a `estudio_producto_id`.
- **Transcripción STT:** FastAPI + Whisper (modelo base) corriendo localmente en `http://localhost:8001` (`storage/transcriptor/`).
- **Interoperabilidad:** HIS/SAHI (SQL Server), PACS Dcm4chee (Visor Oviyam) y Twilio (WhatsApp webhooks).

## Memory & State Persistence
- **Read First:** Every session MUST start by reading `MEMORY.md` to understand the current state of features, active tasks, and recent hotfixes.
- **Update on Success:** After successfully implementing a feature, fixing a bug, or changing an integration status, the agent MUST update `MEMORY.md` under section `4. Próximos Pasos Técnicos e Historial Inmediato` before asking for commit approval.

## Shell (Windows)

- PowerShell 5.1: no `&&`. Chain with `;` and `if ($?)`.
- Pint: use `vendor\bin\pint.bat` — the bare `vendor\bin\pint` produces no output.
- Line endings: `core.autocrlf=true` checks out CRLF, but Pint (laravel preset) demands LF. If `pint --test` fails ONLY with `line_ending`, normalize the file to LF. (`unary_operator_spaces` + `not_operator_with_successor_space` fight to net-zero bytes — ignore them if a byte-exact probe shows no change.)
- Never write PHP with `Set-Content -Encoding utf8` (emits BOM → PHP fatal). Write UTF-8 no-BOM, LF.
- `php artisan tinker --execute` breaks on `\`. Use temp `.php` files (bootstrap `vendor/autoload.php` + `bootstrap/app.php`) run with `php`.
- Temp scripts go in `C:\Users\Jhon\AppData\Local\Temp\opencode\`, never in the repo.

## Verify (no automated tests)

- PHP: `vendor\bin\pint.bat --test <files>` (pint.json sets `single_quote: false` → double quotes). PHPStan via Larastan, config in `phpstan.neon`.
- JS/Vue: `npx eslint <file>` (`eslint.config.js`, flat), `npm run build` after any `.vue` change (`public/build/` is gitignored).
- PHP checks run against the real `lidiag` DB (`mysql` connection): writes ONLY inside `DB::beginTransaction()` / `DB::rollBack()`. NEVER `migrate` / `migrate:rollback`.
- Commits run lint-staged (eslint --fix, prettier --write, `php vendor/bin/pint`). Verify style BEFORE asking to commit.

## Git

- NEVER commit without explicit user approval ("si", "dale", "commitea").
- `git add` explicit paths only — the tree routinely contains others' uncommitted work (seeders, xlsx). Never `git add -A`.
- Work on `main`.

## Conventions (repo-specific, verified)

- PHPDoc (`docs/CODING_STANDARDS.md`): description + numbered "flujo del proceso" + `@param`/`@returns` + `@author Jhon Javer Lozano Arce` + `@date`. Same author/date in Vue JSDoc.
- `BaseService::error(string $message, int $status = 400)` — that order. Pre-write validation inside a transaction uses `return $this->error(...)`, not `throw`.
- `permission:a|b|c` route strings are project convention, NOT a vulnerability: the controller constructor's `->only([...])` governs (AND semantics).
- New permissions must be seeded (`database/seeders/Permision*Seeder.php`: idempotent + `syncRoles`) AND applied with `php artisan db:seed --class=...`, or every check 403s.
- `$request->validate()` returns ONLY ruled fields — flags like `retirar_faltantes` need `'sometimes|boolean'` or they never reach the service.
- New module wiring = 5 places: `routes/api_X.php` (+ registration in the `bootstrap/app.php` list, without it the file never loads) + `Route::view` in `routes/web.php` + blade mounting `<x-component>` + `app.component()` in `resources/js/app.js` + `@can` entry in `resources/views/comunes/sidebar.blade.php`.
- Vue is Composition API (`<script setup>`). Comunicación entre componentes vía bus de eventos global `mitt` (`app.config.globalProperties.emitter`). No uses Pinia/Vuex.
- Badges are `badge badge-*`, `confirmAction` hardcodes button labels (put the meaning in `text`).
- Module docs live in `docs/CONTROLLERS.md` (one section per module).
- Useful: `permissions:audit`, `sync:missing-permissions`, `permissions:user-check`, `sync-usuarios`, `citas:notificar`. Dev login: `administrador@administrador.com` / `123456`.
