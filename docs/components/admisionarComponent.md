# Documentación: `admisionarComponent.vue`

Componente Vue 3 (Options/Composition API mixto) encargado de **admisión de pacientes para ecografías**.
Permite buscar un paciente, cargar sus datos desde el RIS, seleccionar médico/contrato/productos y
generar una admisión (orden de atención) que luego se envía a **SAHI** (sistema de facturación HIS).

**Ruta:** `resources/js/components/modulos/ecografias/admisionarComponent.vue`
**Usado en:** `views/ecografias/bandeja.blade.php` (módulo Ecografías → "Admisión de Pacientes")

---

## Dependencias / Librerías

| Librería | Uso |
|----------|-----|
| `axios` | Llamadas HTTP a endpoints `/api/...` |
| `vue-select` (`vueSelect`) | Selectores de médico, tipo de documento y producto |
| `jquery` (`$`) | Animación `.right-sidebar` y toast `$.toast` |
| `sweetalert2` (`Swal`) | Confirmaciones y alertas de error/éxito |
| `toastMessage` | Helper interno de toast (`heading, text, icon`) |
| `emitter` (mitt) | Evento global `sedeSeleccionada` |
| `Loading` | Componente de spinner de carga |
| `sessionStorage` | `ST-sede`, `ST-modulo`, `Auth` (datos de usuario) |

---

## Props / Emits

No define `props` ni `emits`. Se comunica vía:
- `emitter.on("sedeSeleccionada", ...)` → recibe sede activa.
- `sessionStorage.getItem("ST-sede")` / `sessionStorage.getItem("ST-modulo")` → contexto.
- `$(".right-sidebar")` (jQuery DOM) → panel lateral de admisión.

---

## Estado Reactivo Principal (`ref`/`reactive`)

| Variable | Tipo | Descripción |
|----------|------|-------------|
| `registro` | `reactive` | Objeto con todos los campos del paciente/admición (paciente_id, num_docu, nombres, pri_ape, seg_ape, sexo, fec_naci, direccion, tel, cel, medico_id, tipo_documento_id, productosAgenda[], sede_id, contrato, plan, admin_salud, quien_ordeno, atencion_id). |
| `medicos` | `ref([])` | Lista de médicos (usuarios rol Médico). |
| `tipoDocumentos` | `ref([])` | Tipos de documento activos. |
| `productos` | `ref([])` | Catálogo de productos (cups). |
| `productoSelecciondo` | `ref("")` | Producto elegido en el `<vue-select>` temporal. |
| `tituloModal` | `ref("")` | Título del panel lateral. |
| `isSearching` | `ref(false)` | Bandera de búsqueda en curso. |
| `isSaving` | `ref(false)` | Bandera de guardado en curso. |
| `mapProcesando` | `ref({})` | Mapa `id → bool` de envíos SAHI en proceso (proxy `setMapValue`). |
| `patientNotFound` | `ref(false)` | True si la búsqueda no halló paciente. |
| `showConfirmacion` | `ref(false)` | Muestra modal de confirmación de envío SAHI. |
| `confirmacion` | `reactive` | Datos del paciente a confirmar (paciente_id, num_docu, nom_comple, sede_id, medico_id, contrato, plan, admin_salud). |
| `searchValue` | `ref("")` | Input de búsqueda de paciente. |
| `searchResults` | `ref([])` | Resultados de búsqueda en RIS. |

### Variables computadas (`computed`)
| Nombre | Retorna |
|--------|---------|
| `tipoDocumentoOptions` | `tipoDocumentos` mapeado a `{ id, texto: DesTipoDoc, codigo: CodTipoDoc }`. |
| `medicoOptions` | `medicos` mapeado a `{ id, texto: CONCAT(nombres apellidos) }`. |
| `productoOptions` | `productos` mapeado a `{ id, texto: nom_produc }`. |

---

## Funciones Principales

### Búsqueda y carga de paciente
- **`buscarPaciente()`** (línea ~127): valida `searchValue`, llama `POST /api/contratacion.buscarOrdenes` con `num_docu`, `sede_id`, `modulo=2` (Ecografías). Si no hay resultados → `patientNotFound=true`. Si hay → `searchResults` y muestra lista.
- **`selectPaciente(item)`** (~221): llama `POST /api/contratacion.ordenesPorConfirmar`/`buscarOrdenes` para traer la orden completa, luego `mostrarRegistro(data)`.
- **`cargarPacienteDesdeRIS(idAtencion)`** (~266): `POST /api/admision.buscarPaciente/{sede}` con `IdAtencion` → puebla `registro`.
- **`buscarEnRIS()`** (~293): recorre `searchResults` para localizar la atención seleccionada y abrir el panel.

### Reenvío a SAHI
- **`reenviarSahi(id)`** (~929): set `mapProcesando[id]=true`, `POST /api/sahi.reenviar/{id}`, estado `ENVIADO`, toast éxito. `catch` → estado `ERROR` + toast warning. `finally` limpia flag.

### Guardado / Admisión
- **`guardarAdmision()`** (~?): valida campos, `POST` para crear la orden/admición, almacena `atencion_id`, abre `showConfirmacion=true`. (Ver flujo: tras guardar se confirma envío a SAHI).
- **`confirmarEnvioSahi()`**: `POST /api/sahi.enviar` con `confirmacion` → `atencion_id`, cierra modal, toast info "Envío a SAHI encolado".
- **`cerrarConfirmacion()`**: oculta modal de confirmación.

### Selectores
- **`onMedicoSelect(value)`** (~1005): extrae id (array u objeto) → `registro.medico_id`.
- **`onTipoDocumentoSelect(value)`** (~1017): id → `registro.tipo_documento_id`.
- **`onProductoSelect(value)`** (~1029): busca producto, lo agrega a `registro.productosAgenda` (si no existe) con `{IdProducto, CodLegal, NomProducto, Cantidad, ValProduc}`, luego limpia selección.

### Panel lateral
- **`mostrarRegistro(data)`** (~1056): setea todos los campos de `registro` desde `data`, homologa `tipo_documento_id` por `CodTipoDoc`, asigna `productosAgenda=[data]`, llama `btnCerralModalForm()`.
- **`limpiarFormulario()`** (~1087): reinicia todos los campos de `registro` y `productosAgenda`.
- **`btnCerralModalForm()`** (~1052): anima `.right-sidebar` (jQuery slideDown + toggle `shw-rside`).
- **`quitarProductoAgenda(codProduc)`** (~1109): filtra `productosAgenda` por `CodLegal`.

### Listados (onMounted)
- **`listarMedicos()`** (~978): `GET /api/user.listarUsuarios/Medico/2`.
- **`listarTipoDocumentos()`** (~989): `GET /api/config-tipos-documentos/listar/activos`.
- **`listarProductos()`** (~997): `GET /api/config-productos`.
- **`initSede()`** (~?): lee `sessionStorage.ST-sede` → `registro.sede_id`; si no hay sede → toast warning y retorna.
- **`establecerMedico()`**: asigna médico por defecto desde `Auth` (sessionStorage).

### `onMounted` (~1115)
1. Si `ST-sede` es null → toast "Debes seleccionar una sede..." y `return`.
2. `listarMedicos()`, `initSede()`, `listarTipoDocumentos()`, `listarProductos()`.
3. Suscribe `emitter.on("sedeSeleccionada")` → actualiza `sede_id`, `initSede()`, `establecerMedico()`.

---

## Endpoints API Usados

| Método | Endpoint | Propósito |
|--------|----------|-----------|
| POST | `/api/contratacion.buscarOrdenes` | Buscar órdenes por documento + sede |
| POST | `/api/contratacion.ordenesPorConfirmar` | Traer orden completa |
| POST | `/api/admision.buscarPaciente/{sede}` | Datos paciente desde RIS por IdAtencion |
| GET | `/api/user.listarUsuarios/Medico/2` | Médicos |
| GET | `/api/config-tipos-documentos/listar/activos` | Tipos documento |
| GET | `/api/config-productos` | Productos/cups |
| POST | `/api/sahi.enviar` | Enviar admisión a SAHI |
| POST | `/api/sahi.reenviar/{id}` | Reenviar a SAHI |

---

## Flujo de Usuario (resumen)
1. Seleccionar sede (sidebar) → evento `sedeSeleccionada`.
2. Ingresar nº documento → `buscarPaciente()` → lista de resultados RIS.
3. Elegir paciente → `selectPaciente()` carga datos → se abre panel lateral (`.right-sidebar`).
4. Completar médico, tipo documento, productos (agenda).
5. `guardarAdmision()` → valida y crea orden → modal confirmación SAHI.
6. `confirmarEnvioSahi()` → envía a SAHI (encolado). Reenvío posterior vía `reenviarSahi(id)`.

---

## Notas / Consideraciones
- **Toast**: usa `toastMessage({heading, text, icon})` en la lógica nueva; algunos mensajes legacy aún usan `$.toast({...})` (ej. validación de sede en `onMounted`).
- **SAHI**: el envío es **asíncrono/encolado**; el estado del registro se refleja en `mapProcesando` y `item.estado_envio_sahi` (`ENVIADO`/`ERROR`).
- **Homologación**: `tipo_documento_id` se resuelve comparando `CodTipoDoc` del RIS con el catálogo local (`tipoDocumentoOptions`).
- **Modo**: el módulo es `2` (Ecografías) hardcoded en `buscarPaciente`; `listarMedicos` usa módulo `2` fijo.
- **jQuery + Vue**: el panel lateral se controla con jQuery (`.right-sidebar`, `.shw-rside`); no usar `v-if` para ese panel.
