# Reestructuración Arquitectónica Lidiag — Design Doc

## Objetivo

Refactorizar la arquitectura monolítica de Lidiag aplicando SOLID, buenas prácticas Laravel+Vue 3, y controles ISO 27001. Estrategia: **backend completo primero, frontend después**.

## Decisiones Arquitectónicas

| Decisión | Opción Elegida |
|----------|----------------|
| Integración Vue-Laravel | MPA híbrido (Blade + Vue components) — mantener |
| Vue API | Migración completa de Options API a Composition API (`<script setup>`) |
| Método migración Vue | Manual, componente por componente |
| Sistema auditoría | ActivityLog (trait LogsActivity automático) — unificar desde AuditLog |
| Backend refactorización | Backend completo primero: Controllers → Services → Models |

## Capas Target

```
Blade → Vue 3 (<script setup>) → Axios → Laravel Router
                                                ↓
                                        Controllers (thin)
                                                ↓
                                        Services (business logic + transactions)
                                                ↓
                                        Models + FileService + ActivityLog
```

## Componentes Nuevos

### Backend Infrastructure
- `app/Services/BaseService.php` — CRUD genérico con transacciones + ActivityLog
- `app/Services/FileService.php` — SHA-256 hashing, store, verify, serve
- `app/Http/Resources/BaseResource.php` — Transform con metadata estándar
- `app/Http/Requests/BaseRequest.php` — Autorización base + español

### File Integrity (ISO 27001 A.10.1.1)
- Cada archivo subido genera hash SHA-256 del contenido binario
- Hash almacenado en `activity_log` como metadato del evento `archivo_subido`
- En descarga, se recalcula y compara (detección de manipulación)
- Soportado por `FileService` + trait `LogsActivity` ampliado

### Unified Audit (ISO 27001 A.12.4.1)
- `LogsActivity` trait se expande a TODOS los modelos (hoy solo Ecografia)
- Eventos: `created`, `updated`, `deleted`, `restored`, `archivo_subido`, `archivo_descargado`
- Tabla `activity_log` es la canónica; `audit_logs` se depreca
- UI de consulta (AuditLogController) migra a leer de `activity_log`

## Orden de Refactorización Backend

| Fase | Módulo | Controllers | Prioridad |
|------|--------|-------------|-----------|
| 0 | Infraestructura (Services, FileService, ActivityLog, Requests, Resources) | — | Alta |
| 1 | Seguridad | 3 | Alta |
| 2 | Configuración | 15 | Alta |
| 3 | Estudios | 7 | Alta |
| 4 | Ecografías | 5 | Alta |
| 5 | Help Desk | 8 | Media |
| 6 | Citas / Agenda | 5 | Media |
| 7 | Interfaces (SAHI, TeleRadio) | 4 | Baja |
| 8 | Reportes | 2 | Baja |

## Controles ISO 27001

- A.9.4.2: Rate limiting en login (throttle:20,1)
- A.12.4.1: ActivityLog unificado en todos los modelos
- A.12.6.1: CSP enforcing (quitar Report-Only), reducir unsafe-inline/eval
- A.13.2.3: Limpiar .env.example de secrets
- A.16.1.5: Deshabilitar /api/debug* en producción
- OWASP: Validación MIME real en uploads, límites por disco

## Fuera de Alcance (Fase Post-Refactorización)

- 2FA (tabla existe, implementación pendiente)
- Vue Router SPA
- Inertia.js migración
- Pruebas E2E
