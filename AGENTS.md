# AGENTS.md — LIDiag

## Quick Start

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
npm install
npm run dev
```

Vite runs on `https://lidiag.test` — ensure this vhost exists in Laragon.

## Dev Commands

```bash
npm run dev         # Dev server with HMR
npm run build       # Production build
npm run lint        # ESLint (check only)
npm run lint:fix    # ESLint --fix + Prettier write
npm run format     # Prettier write
npm run format:check

composer run lint     # pint --test + phpstan (level 5)
composer run lint:fix # pint only
```

## Architecture

- **Backend**: Laravel 10 (PHP >= 8.0.2), MySQL
- **Frontend**: Vue 3 + Vite (Laravel Vite Plugin), Bootstrap 5 + Vuetify
- **Auth**: Sanctum (stateful SPA) + Spatie permissions
- **API routes**: `routes/api_*.php` modules under `auth:sanctum`

## Vue Components (Options API)

This project uses **Options API only** (no Composition API, no TypeScript):
- `data()`, `methods`, `mounted()`, `computed`, `watch`
- No arrow functions in methods/lifecycle hooks (`this` binding)
- Run `npm run prepare` after `npm install` to enable pre-commit lint hooks

### Key Components

- `resources/js/components/modulos/estudios/leerEstudioComponent.vue` (2200+ lines) — main study reader with speech dictation
- `resources/js/components/modulos/estudios/transcribirEstudioComponent.vue` — study transcriber
- `resources/js/composables/useVoiceDictation.js` — voice commands for dictation

## Audio System

- **Table**: `estudios_audios` — stores audio files linked to `estudio_producto_id`
- **Storage**: `storage/app/audios/` (disk: `audios`)
- **Controller**: `App\Http\Controllers\Estudios\EstudioAudioController`
- **Route**: `/api/listar-audios-estudio/{estudio_producto_id}`

## Important Commands

```bash
# Permission auditing
php artisan permissions:audit
php artisan permissions:audit --fail    # CI: fails if perms in code not in DB
php artisan sync:missing-permissions # Sync permissions that exist in code but not DB

# Sync from external DBs
php artisan sync-usuarios              # SAHI users + ecografía templates
```

## Integrations (verify `.env`)

- **FFmpeg**: `FFMPEG_BINARIES`, `FFPROBE_BINARIES` (Windows paths with `/`)
- **Twilio**: `TWILIO_*` for WhatsApp (legacy)
- **Meta WhatsApp Cloud API**: `META_WHATSAPP_TOKEN`, `META_WHATSAPP_PHONE_ID`, `META_WHATSAPP_APP_SECRET`, `META_WHATSAPP_BUSINESS_ID`
- **SAHI (SQL Server)**: `DB_*_SQLSRV` for HIS integration
- **Whisper.php**: AI transcription (`codewithkyrian/whisper.php`)
- **Google Cloud Speech**: `GOOGLE_APPLICATION_CREDENTIALS` for voice-to-text
- **PDF/Word**: DomPDF, PhpWord, Maatwebsite Excel
- **Multiple MySQL connections**: `DB_*_2`, `DB_IMPORTAR_*` configs

## Citas + WhatsApp Bot

The bot flow is: `documento → sede → servicio → dirección → teléfono1 → teléfono2 → fotos cédula → orden → autorización → confirmar`

### Endpoints
- `/api/citas` - Full CRUD
- `/api/citas/servicios` - List servicios
- `/api/citas/webhook` - Meta WhatsApp webhook
- `/api/citas/test/enviar` - Test endpoint

### Commands
```bash
php artisan citas:notificar  # Daily at 8am - sends reminders
```

### Tables
- `citas` - Appointment records
- `config_servicio_citas` - Services list
- `config_servicio_cita_producto` - Service-Product pivot
- `whatsapp_conversations` - Bot conversation state

### WhatsApp Flow States
`inicio → pedir_documento → seleccionar_sede → seleccionar_servicio → pedir_dirección → confirmar_dirección → pedir_telefono1 → pedir_telefono2 → pedir_foto_cedula_frente → pedir_foto_cedula_dorso → pedir_orden → pedir_autorización → confirmar_solicitud`

### Gotchas

1. **Vite SSL**: `vite.config.js` hardcoded to `lidiag.test` with Laragon certs (`C:/laragon/etc/ssl/`)
2. **Sanctum 401**: Ensure `APP_URL` matches host and domain is in `SANCTUM_STATEFUL_DOMAINS`
3. **Audio conversion**: FFmpeg needs write permission to `storage/app/audios/tmp/`
4. **Multiple DBs**: Check `config/database.php` for secondary connections
5. **Dictation**: Press F2 key to start/stop dictation in leerEstudioComponent
6. **Browser cache**: Hard refresh (Ctrl+Shift+R) after build to clear stale JS
7. **Vuetify custom elements**: `<md-linedivider>` needs explicit registration in vite.config.js compilerOptions
8. **Meta WhatsApp locked**: If error 131031 "Business Account locked", unlock in Meta Business Manager
9. **ConfigSede plantilla**: Field was renamed from `plantilla` to `plantilla_rx`

## Skills

Load skill with `skill load <name>` when applicable:
- **LIDiag specific** (`.agents/skills/`, `.claude/skills/`)
- **Reference**: laravel-best-practices, vue-options-api-best-practices

## Storage

`php artisan storage:link` creates symlinks for multiple disks:
- `audios`, `firmas_medicos`, `soportes_hc`, `reporte_lecturas`, `files_help_desk`, `plantillas_lecturas`

## Code Style

- **PHP**: Pint (`preset: laravel`) + PHPStan (level 5)
- **JS/Vue**: ESLint + Prettier (see `.eslintrc.cjs`, `.prettierrc.json`)