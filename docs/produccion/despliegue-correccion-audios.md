# Despliegue en Producción — Corrección de Audios

## Archivos modificados

| Archivo | Cambio |
|---------|--------|
| `app/Models/Estudio/EstudioAudio.php` | `getUrlAttribute()` — limpia prefijo `audios/` |
| `app/Http/Controllers/Estudios/EstudioAudioController.php` | `stream()` — regex acepta `.wav` y `cargado_` |
| `resources/js/components/modulos/estudios/notificarEstudioComponent.vue` | DataTable destroy + email en mostrarRegistro |
| `resources/js/components/modulos/estudios/estudioAsignadosComponent.vue` | `this.id` → `this.idRow` en listarAudios, wysihtml5 sync, DataTable destroy orden |
| `app/Http/Controllers/Estudios/EstudioController.php` | DATE() → columna directa, activo=1, DB::transaction |

## Pasos para producción

```bash
# 1. Respaldar base de datos
mysqldump -u root -proot lidiag > respaldo_$(date +%Y%m%d_%H%M%S).sql

# 2. Corregir paths de audios legacy (prefijo "audios/" en columna audio)
mysql -u root -proot lidiag -e "
UPDATE estudios_audios
SET audio = REPLACE(audio, 'audios/', '')
WHERE audio LIKE 'audios/%';
"
echo "Filas afectadas: $(mysql -u root -proot lidiag -e \"SELECT ROW_COUNT() as affected;\")"

# 3. Verificar que los paths corregidos apunten a archivos existentes
mysql -u root -proot lidiag -e "
SELECT COUNT(*) AS archivos_inexistentes
FROM estudios_audios ea
LEFT JOIN (
    SELECT CONCAT('audios/', audio) AS ruta FROM estudios_audios
) chk ON chk.ruta = CONCAT('audios/', ea.audio)
WHERE ea.audio != '' 
  AND NOT EXISTS (
    SELECT 1 FROM (SELECT 1) dummy
    WHERE (SELECT COUNT(*) FROM information_schema.FILES 
           WHERE CONCAT('/ruta/completa/storage/app/audios/', ea.audio) LIKE '%') > 0
  );
"

# 4. Desplegar código actualizado
git pull origin main

# 5. Compilar assets frontend
npm ci
npm run build

# 6. Limpiar cachés de Laravel
php artisan optimize:clear

# 7. Verificar sintaxis PHP
php -l app/Models/Estudio/EstudioAudio.php
php -l app/Http/Controllers/Estudios/EstudioAudioController.php
php -l app/Http/Controllers/Estudios/EstudioController.php

# 8. Verificar que los endpoints responden
curl -s -o /dev/null -w "%{http_code}" "http://localhost/api/listar-audios-estudio/213823"
# Debe responder 200 o 401 (por auth), nunca 500

# 9. Verificar streaming de audio (nuevo formato .mp3)
curl -s -o /dev/null -w "%{http_code}" "http://localhost/audios/audio_01KFYQPD870DGK3646HQANQZH1.mp3"
# Debe responder 200

# 10. Verificar streaming de audio (legacy .wav con path corregido)
curl -s -o /dev/null -w "%{http_code}" "http://localhost/audios/audio_1715292524.wav"
# Debe responder 200
```

## Resumen de cambios aplicados en desarrollo

### Frontend
- `estudioAsignadosComponent.vue`: audios ahora envían `this.idRow` (estudio_producto_id) en vez de `this.id` (estudio_id)
- `notificarEstudioComponent.vue`: DataTable destruido ANTES de limpiar registros; email del paciente cargado en sidebar

### Backend
- `EstudioAudio::getUrlAttribute()`: limpia prefijo `audios/` legacy antes de generar URL
- `EstudioAudioController::stream()`: regex relajado para aceptar `.wav` y prefijo `cargado_`
- `EstudioController`: DATE() reemplazado por columna directa + horas, `activo=1`, transacción en guardarTranscripcion

## Rollback

Si algo sale mal:

```bash
# Revertir código
git revert HEAD --no-edit
git push origin main

# Revertir datos (necesita respaldo)
mysql -u root -proot lidiag < respaldo_*.sql

# Recompilar
npm run build
php artisan optimize:clear
```
