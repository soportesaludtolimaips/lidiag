# Plan de Pruebas — Lidiag
> Última actualización: 2026-08-05

## Leyenda
- 🔴 Sin iniciar
- 🟡 Iniciado
- 🟠 Pendiente
- 🟢 Completado

---

## 1. SEGURIDAD
| # | Prueba | Estado | Observaciones |
|---|--------|--------|---------------|
| 1.1 | Login con credenciales válidas | 🟢 | POST /login → 200, redirect a dashboard |
| 1.2 | Login con credenciales inválidas | 🟢 | 401 "Credenciales inválidas". Lockout 5 intentos/15 min funciona |
| 1.3 | Logout | 🟢 | POST /logout → 204, sesión invalidada |
| 1.4 | Cambio de contraseña | 🟢 | Valida contraseña actual, min 8 chars, confirmed. password_changed_at se actualiza |
| 1.5 | Control de permisos por rol (7 roles) | 🟢 | 8 roles (extra: Reportes). Admin: 134 permisos. Medico: 9. Admin no tiene permisos de seguridad bloqueados |
| 1.6 | Sesiones concurrentes | 🟢 | Tabla sessions OK. detectPreviousSession registra sesiones anteriores |

## 2. CONFIGURACIÓN
| # | Prueba | Estado | Observaciones |
|---|--------|--------|---------------|
| 2.1 | CRUD Admin Salud | 🟢 | Listar, crear, editar, eliminar OK |
| 2.2 | CRUD Diagnósticos | 🟢 | Listar, crear, editar, eliminar OK |
| 2.3 | CRUD Prioridades | 🟢 | Listar, crear, editar, eliminar OK |
| 2.4 | CRUD Productos | 🟢 | Listar, crear, editar, eliminar OK |
| 2.5 | CRUD Sedes | 🟢 | Listar, crear, editar, eliminar OK |
| 2.6 | CRUD Servicios de Citas | 🟢 | Faltaban permisos en BD, creados y asignados. CRUD OK |
| 2.7 | CRUD Productos por Servicio | 🟢 | Incluido en config WhatsApp (pestaña Productos). Fix: faltaba método misServicios |
| 2.8 | CRUD Tipos de Plantilla | 🟢 | Fix: permisos FormRequest 'configuracion.' → 'config.', columna producto_id nullable, status check 200→2xx |
| 2.9 | CRUD Plantillas (WYSIWYG) | 🟢 | Fix: permisos FormRequest 'configuracion.' → 'config.', delete permission name, status 201 check. Implementado wysihtml5 en textarea |
| 2.10 | CRUD Tipos de Documento | ⚪ | No construido |
| 2.11 | CRUD Consentimiento Informado | ⚪ | No construido |
| 2.12 | CRUD WhatsApp Bot | ⚪ | No construido |
| 2.13 | CRUD Motivos de Rechazo | ⚪ | No construido |

## 3. ESTUDIOS (Rx)
| # | Prueba | Estado | Observaciones |
|---|--------|--------|---------------|
| 3.1 | Asignar estudio a médico/transcriptor | 🟢 | OK |
| 3.2 | Listar estudios asignados | 🟢 | OK |
| 3.3 | Leer estudio — sidebar derecho | 🟢 | OK con usuario médico |
| 3.4 | Grabación de audio por micrófono | 🟢 | Fix: `stopRecording` no estaba destructurado de `dictationFlow` |
| 3.5 | Grabación continua al cambiar de pestaña | 🟢 | Fix MediaRecorder auto-resume |
| 3.6 | F2 para iniciar/detener grabación | 🟢 | Fix stopAll en F2 |
| 3.7 | Carga de archivos de audio | 🟢 | Upload mp3/wav/m4a/webm/ogg/flac, max 100MB, conversión FFmpeg OK |
| 3.8 | Transcripción automática (Whisper) | 🟢 | Whisper service localhost:8001, transcripción aparece en textarea |
| 3.9 | Auto-inserción de transcripción en textarea | 🟢 | Fix res.data.data + callback |
| 3.10 | Guardar lectura | 🟢 | Fix guardarRegistro flow |
| 3.11 | Transcribir estudio | 🟢 | Listar pendientes, abrir estudio, editar transcripción, guardar, generar PDF |
| 3.12 | Notificar estudio | 🟢 | Imprimir PDF, notificar por correo, enviar al portal de pacientes |
| 3.13 | Eliminar audio cargado (SweetAlert) | 🟢 | Fix confirm → SweetAlert |
| 3.14 | Stream de audio (reproducción) | 🟢 | Fix regex cargado_ prefix |

## 4. ECOGRAFÍAS
| # | Prueba | Estado | Observaciones |
|---|--------|--------|---------------|
| 4.1 | Admisión de ecografía | 🟢 | Fix: columna `medico_id` faltante en tabla ecografias (migración), columna `estado` era tinyint en vez de enum. CRUD funciona correctamente |
| 4.2 | Listado de ecografías admitidas | 🟢 | Filtros por nombre/doc, fechas. Retorna relaciones (paciente, medico, productos) |
| 4.3 | Lectura de ecografía | 🟢 | IMPRIMIR_LECTURA y TERMINAR_LECTURA funcionan. Estado cambia a LEIDO |
| 4.4 | Reporte de ecografía | 🟡 | Código funciona (valida, retorna error 402 si no hay plantilla). Requiere plantilla Word configurada en sede (no hay ninguna configurada actualmente) |

## 5. CONSENTIMIENTOS
| # | Prueba | Estado | Observaciones |
|---|--------|--------|---------------|
| 5.1 | Listado de consentimientos | 🔴 | |
| 5.2 | CRUD Plantillas de consentimiento | 🔴 | |
| 5.3 | Vista previa PDF | 🔴 | |
| 5.4 | Captura de firma del paciente | 🔴 | |
| 5.5 | Firma del médico | 🔴 | |
| 5.6 | Anulación de consentimiento | 🔴 | |
| 5.7 | Generación de PDF | 🔴 | |
| 5.8 | Generación de ZIP | 🔴 | |

## 6. AGENDA
| # | Prueba | Estado | Observaciones |
|---|--------|--------|---------------|
| 6.1 | Disponibilidad de médicos | 🔴 | |
| 6.2 | CRUD de horarios | 🔴 | |

## 7. CITAS
| # | Prueba | Estado | Observaciones |
|---|--------|--------|---------------|
| 7.1 | Listado de citas | 🔴 | |
| 7.2 | Asignar cita | 🔴 | |
| 7.3 | Confirmar cita | 🔴 | |
| 7.4 | Cancelar cita | 🔴 | |
| 7.5 | Cumplir cita | 🔴 | |
| 7.6 | No asistió | 🔴 | |
| 7.7 | Rechazar cita | 🔴 | |
| 7.8 | Notificaciones de citas | 🔴 | |
| 7.9 | Webhook WhatsApp | 🔴 | |

## 8. TELE-RADIOLOGÍA
| # | Prueba | Estado | Observaciones |
|---|--------|--------|---------------|
| 8.1 | Enviar estudio a plataforma externa | 🔴 | |
| 8.2 | Recibir estudio de plataforma externa | 🔴 | |
| 8.3 | Transcribir estudio recibido | 🔴 | |

## 9. OTRAS PLATAFORMAS
| # | Prueba | Estado | Observaciones |
|---|--------|--------|---------------|
| 9.1 | Enviar estudio a otras plataformas | 🔴 | |

## 10. HELP DESK
| # | Prueba | Estado | Observaciones |
|---|--------|--------|---------------|
| 10.1 | Crear ticket | 🔴 | |
| 10.2 | Listar tickets | 🔴 | |
| 10.3 | Ver detalle de ticket | 🔴 | |
| 10.4 | Comentarios en ticket | 🔴 | |
| 10.5 | Cambio de estado | 🔴 | |
| 10.6 | Reasignación | 🔴 | |
| 10.7 | Subir adjuntos | 🔴 | |
| 10.8 | Descargar adjuntos | 🔴 | |
| 10.9 | Notificaciones helpdesk (campana) | 🔴 | |
| 10.10 | Reporte indicadores | 🔴 | |
| 10.11 | Reporte detallado | 🔴 | |
| 10.12 | Exportar Excel/PDF | 🔴 | |
| 10.13 | Config categorías | 🔴 | |
| 10.14 | Config prioridades | 🔴 | |
| 10.15 | Config estados | 🔴 | |

## 11. REPORTES
| # | Prueba | Estado | Observaciones |
|---|--------|--------|---------------|
| 11.1 | Reporte de producción detallado | 🔴 | |
| 11.2 | Reporte de indicadores | 🔴 | |

## 12. DASHBOARD
| # | Prueba | Estado | Observaciones |
|---|--------|--------|---------------|
| 12.1 | KPIs visibles | 🔴 | |
| 12.2 | Estudios pendientes | 🔴 | |
| 12.3 | Aging | 🔴 | |

## 13. COMPONENTES COMUNES
| # | Prueba | Estado | Observaciones |
|---|--------|--------|---------------|
| 13.1 | Selector de sede | 🟢 | Fix 403 buscarPorId |
| 13.2 | Selector de médico | 🔴 | |
| 13.3 | Sidebar / navegación | 🔴 | |
| 13.4 | Perfil de usuario | 🔴 | |
| 13.5 | Notificaciones helpdesk | 🔴 | |

## 14. INTEGRACIONES EXTERNAS
| # | Prueba | Estado | Observaciones |
|---|--------|--------|---------------|
| 14.1 | SAHI (atenciones, pacientes) | 🔴 | |
| 14.2 | DICOM/PACS (listado de estudios) | 🔴 | |
| 14.3 | OHIF Viewer | 🔴 | |
| 14.4 | WhatsApp | 🔴 | |

---
**Resumen:** 93 pruebas | 🟢 15 completados | 🟡 1 parcial | 🔴 77 sin iniciar
