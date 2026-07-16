# Plan de Pruebas — Módulos Refactorizados

> Progreso de pruebas funcionales desde navegador (UI/UX + funcionalidad).

---

## PRIORIDAD 1 — Configuración

- [ ] **ConfigAdminSalud** — CRUD, listado, búsqueda
- [ ] **ConfigDiagnóstico** — CRUD, listado, búsqueda
- [ ] **ConfigMódulos** — CRUD, listado, activar/desactivar
- [ ] **ConfigPlantilla** — CRUD, listado, contenido HTML
- [ ] **ConfigPlantillaTipo** — CRUD, listado
- [ ] **ConfigPrioridad** — CRUD, listado, orden
- [ ] **ConfigProducto** — CRUD, listado, filtros
- [ ] **ConfigRecomendaciónProducto** — CRUD, listado, asignación
- [ ] **ConfigSede** — CRUD, listado, WhatsApp number
- [ ] **ConfigSedeWhatsappNumber** — CRUD, listado
- [ ] **ConfigServicioCita** — CRUD, listado
- [ ] **ConfigTipoDocumento** — CRUD, listado
- [ ] **ConfigWhatsapp** — CRUD, listado, configuración
- [ ] **ConsentimientoInformado** — CRUD, listado, previsualización
- [ ] **ServicioProducto** — CRUD, listado

## PRIORIDAD 2 — Estudios

- [ ] **Estudio** — listar, asignar, leer, descargar PDF
- [ ] **EstudioAudio** — subir, reproducir, stream
- [ ] **EstudioDiagnóstico** — CRUD diagnósticos
- [ ] **EstudioNotificación** — listar, notificar, canales
- [ ] **EstudioProducto** — CRUD productos asociados
- [ ] **EstudioSoporteHC** — subir/descargar soportes
- [ ] **PlantillaEstudio** — CRUD plantillas
- [ ] **TranscripciónEstudio** — transcribir, guardar
- [ ] **LeerEstudio** — flujo completo de lectura

## PRIORIDAD 3 — Seguridad

- [ ] **Usuario** — CRUD, perfil, cambio contraseña
- [ ] **Rol** — CRUD, asignación de permisos
- [ ] **Auditoría** — listado, filtros, detalle

## PRIORIDAD 4 — Reportes

- [ ] **ReporteProducción** — generación, filtros, descarga Excel

---

## Módulos Secundarios

### Auth

- [ ] **Login** — formulario, validación, redirección
- [ ] **ResetPassword** — solicitud, token, cambio
- [ ] **Verification** — verificación de email
- [ ] **PasswordChange** — cambio forzado, validación

### Agenda / Citas

- [ ] **AgendaDisponibilidad** — CRUD, bloqueo horario
- [ ] **AgendaCita** — crear, reprogramar, cancelar
- [ ] **CitasRevisión** — listado, aprobar/rechazar
- [ ] **CitasNotificación** — badge, listado
- [ ] **RecomendaciónProducto** — CRUD en cita
- [ ] **GestionCitas** — vista general

### Ecografías

- [ ] **Admisionar** — formulario, búsqueda paciente
- [ ] **Admisionadas** — listado, filtros
- [ ] **Lectura** — flujo de lectura, diagnóstico
- [ ] **Consentimientos** — firma, generación ZIP, anular
- [ ] **ConsentimientosPlantillas** — CRUD plantillas
- [ ] **FirmaConsentimiento** — flujo público (token)
- [ ] **EcografiaProducto** — CRUD productos

### Help Desk

- [ ] **Tickets** — listar, crear, detalle, cambiar estado, reasignar
- [ ] **Adjuntos** — subir/descargar
- [ ] **Categorías** — CRUD
- [ ] **Estados** — CRUD
- [ ] **Notificaciones** — listado, marcar leídas
- [ ] **Prioridades** — CRUD
- [ ] **Reportes** — generación, filtros, descarga
- [ ] **Usuarios** — CRUD

### Interfaces (SAHI)

- [ ] **SAHI** — consulta, validación
- [ ] **EcografíasEnviar** — envío a SAHI
- [ ] **Transcibir** — recepción
- [ ] **TeleRadiología** — envío, estado

### Otros

- [ ] **Dcm4chee/Study** — listar estudios DICOM
- [ ] **OHIF Visor** — visualización
- [ ] **ImportarDicom** — importación manual
- [ ] **WhatsApp Flow** — flujo de mensajes, historial
- [ ] **FileAccess** — acceso a archivos protegidos
- [ ] **Dashboard** — cards, resumen, datos
- [ ] **OtrasPlataformas** — asignar estudio externo
- [ ] **ReporteLectura** — reporte de lecturas

---

## Módulos Varios

- [ ] **Sidebar** — navegación, items dinámicos
- [ ] **Selector de sede** — cambio de sede funcional
- [ ] **Componentes compartidos** — Select2, EmojiPicker, FormattedTextEditor, LoadingSpinner
- [ ] **Toast** — notificaciones toast en todos los módulos
