---
name: helpdesk-workflow
description: Implementa o modifica el módulo interno HelpDesk (tickets, catálogos, adjuntos, historial, notificaciones) en LIDiag. Usar al tocar rutas API helpdesk, migraciones/seeders, almacenamiento de adjuntos o pantallas Vue help-desk.
---

# HelpDesk (LIDiag)

## Componentes del módulo

- **Rutas**: `routes/api_helpdesk.php` (API para SPA)
- **Modelos**: `app/Models/HelpDesk/*`
- **Adjuntos**: disk `files_help_desk` + symlink público (ver `config/filesystems.php`)
- **Frontend (Vue)**: `resources/js/components/modulos/help-desk/*`
- **Configuración (catálogos)**: componentes en `resources/js/components/modulos/configuracion/*helpdesk*`

## Cuando agregues un catálogo / campo nuevo

1. Migración (tabla catálogo o FK en tickets).
2. Modelo y relaciones.
3. Endpoints en `routes/api_helpdesk.php` (Sanctum + permission).
4. Seeder (si aplica) y permisos.
5. Pantallas Vue (listado/crear/editar).

## Checklist de adjuntos

- Subida:
  - Validar `files_help_desk` en `config/filesystems.php`
  - Confirmar que el directorio exista/sea escribible (`storage/app/files_help_desk`)
- Descarga desde Vue:
  - Usar `responseType: 'blob'` cuando aplique
  - Construir URL con `/api/...` (no rutas web si ya existe API)

## Debug rápido

- Adjuntos no descargan: validar symlink (`storage:link`) y permisos de filesystem.
- Estados no cambian: revisar historial y transiciones en backend.
