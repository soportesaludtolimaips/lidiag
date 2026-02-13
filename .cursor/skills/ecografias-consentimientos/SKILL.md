---
name: ecografias-consentimientos
description: Trabaja el módulo de consentimientos informados de Ecografías (tokens, firmas, plantillas, PDF/ZIP) en LIDiag. Usar al agregar endpoints, corregir generación/descarga de PDF/ZIP, o ajustar pantallas Vue de consentimientos.
---

# Ecografías: consentimientos informados (LIDiag)

## Piezas principales

- **Rutas**: `routes/api_ecografias.php` (sección “Consentimientos (Ecografías)” y “Plantillas de consentimiento”)
- **Controladores**:
  - `App\Http\Controllers\Ecografias\EcografiaConsentimientosController`
  - `App\Http\Controllers\Consentimientos\ConsentimientoPlantillasController`
- **Discos/archivos** (symlinks públicos):
  - `files_consentimientos`
  - `firmas_consntimientos`
  - (ver `config/filesystems.php` + `php artisan storage:link`)
- **Frontend (Vue)**:
  - `resources/js/components/modulos/ecografias/ecografiaFirmaConsentimientoComponent.vue`
  - `resources/js/components/modulos/ecografias/ecografiasConsentimientosComponent.vue`
  - `resources/js/components/modulos/ecografias/ecografiasConsentimientosPlantillasComponent.vue`

## Flujo funcional (resumen)

- **Plantillas**: crear/editar → marcar vigente → previsualizar PDF.
- **Consentimiento**: crear token → capturar/firmar (médico/paciente según flujo) → generar PDF → (opcional) ZIP.

## Cuando agregues/modifiques endpoints

- Mantenerlos bajo `auth:sanctum`.
- Proteger con permisos `ecografias.consentimientos.*` usando `permission:...,web`.
- Si el endpoint entrega PDF/ZIP:
  - API debe responder binario/stream correctamente
  - Frontend usar `responseType: 'blob'`

## Checklist de “archivos”

- Si se guardan archivos/firmas:
  - disk correcto (`files_consentimientos` o `firmas_consntimientos`)
  - symlink existe en `public/` (`php artisan storage:link`)
  - permisos de escritura/lectura en `storage/app/*`

## Debug típico

- 401 → seguir skill `debug-sanctum-401-spa`
- 403 → seguir skill `spatie-permissions-workflow`
- Archivos no visibles en `public/` → revisar `storage:link` y `config/filesystems.php`
