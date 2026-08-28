# Documentación del componente `admisionarComponent.vue` (Ecografías)

> Documentación generada a partir de la lectura real del código fuente (frontend Vue 3 + backend Laravel).
> Autor de referencia: **Jhon Javer Lozano Arce**

---

## 1. Encabezado

- **Ruta del componente:** `resources/js/components/modulos/ecografias/admisionarComponent.vue`
- **Dónde se usa:** Módulo de **Ecografías -> Admisionar Paciente**. Se carga dentro del layout de ecografías y forma parte del flujo de admisión/agendamiento de pacientes para estudios ecográficos.
- **Propósito:** Permite buscar atenciones ya facturadas en SAHI, visualizarlas en un DataTable, seleccionar una para "admisionar" (cargar datos del paciente y productos), asignar médico y productos, guardar la ecografía en LIDiag, y gestionar el envío/consulta/reenvío del registro hacia la interfaz SAHI, así como la captura de consentimiento informado.

> **Nota importante (verificación de endpoints):** Los endpoints sugeridos en el enunciado (`/api/contratacion.buscarOrdenes`, `/api/admision.buscarPaciente/{sede}`, `/api/sahi.enviar`, `/api/sahi.reenviar/{id}`) **NO están presentes en este componente**. El componente invoca realmente las rutas `interface-sahi-ecografias/*` y `ecografias*`. Se documenta el backend real que soporta el componente.

---

## 2. Dependencias / Librerías (frontend)

- **Vue 3** (`<script setup>`): `computed`, `nextTick`, `onMounted`, `ref`, `getCurrentInstance`.
- **axios**: cliente HTTP para los endpoints API.
- **@/helpers/permissions.js** -> `usePermissions(props.permisos)` -> `can(permiso)`.
- **@/helpers/funciones.js** -> `formatoMoneda(valor)`.
- **@/helpers/alerts.js** -> `alertError`, `alertSuccess`, `confirmAction`, `toastMessage`.
- **@/helpers/sahi.js** -> `setMapValue(vm, mapName, key, value)` (compatibilidad Vue 2/3 para isConsultandoSahi/isEnviandoSahi).
- **@/composables/useSede.js** -> `useSede()` -> `sedeActual`, `sedeInterface`, `establecerSede`.
- **@/composables/useSahiInterface.js** -> `useSahiInterface()` -> `consultarEstado`, `enviarEcografiaProducto`.
- **jQuery (`window.$`)**: usado directamente para el sidebar (`.slideDown`, `.toggleClass`) y para **DataTables** (`window.$.fn.DataTable`).
- **Select2** (componente importado localmente): `medicoOptions`, `tipoDocumentoOptions`, `productoOptions`.
- Event bus `emitter` (inyectado vía `proxy.emitter`) para el evento `sedeSeleccionada`.

---

## 3. Estado reactivo y computadas

### Props
- `usuarioactual` (`Object`): usuario logueado; se usan `usuarioactual.id` y `usuarioactual.name`.
- `permisos` (`Array`): listado de permisos para `usePermissions`.

### Refs / reactive
- `dt` (`ref`): referencia al `<table id="example23">` para DataTables.
- `actualizar` (`ref<boolean>`): flag para distinguir edición/creación al abrir el modal de anular.
- `tituloModal` (`ref<string>`): título del panel lateral de admisión.
- `busqueda` (`ref<Object>`): `{ sede_id, medico_id, bus_nom_num_docu, bus_fech_ini, bus_fecha_fin }` (valor por defecto de búsqueda "6934193").
- `registros` (`ref<Array>`): filas del DataTable (atenciones SAHI).
- `registro` (`ref<Object>`): formulario de admisión del paciente/asignación.
- `registroAnular` (`ref<Object>`): `{ ecografia_id, atencion_id, quien_anulo_id, motivo_anula }`.
- `errores` (`ref<Object>`): errores de validación del formulario de guardado.
- `erroresBusqueda` (`ref<Object>`): errores de la búsqueda de atenciones.
- `medicos`, `tipoDocumentos`, `productos` (`ref<Array>`).
- `medicoActual` (`ref`): id de médico persistido en localStorage/sessionStorage.
- `productoSelecciondo` (`ref`): valor del Select2 de productos.
- `isBuscandoAtenciones`, `isGuardandoEcografia`, `isAnulandoEcografia` (`ref<boolean>`): loaders.
- `isConsultandoSahi` / `isEnviandoSahi` (`ref<Object>`): mapas reactivos por `ecografia_producto_id` (con `setMapValue`).
- `consentimientoItem`, `consentimientoFirmanteTipo`, `acompanante`, `isGenerandoLinkFirma`, `consentimientoUrl` (`ref`): estado del modal de consentimiento informado.

### Computadas
- `medicoOptions`: mapea `medicos` -> `{ id, name }` para Select2.
- `tipoDocumentoOptions`: mapea `tipoDocumentos` -> `{ id, codigo, tipo_documento }` (clave para homologación de tipo de documento).
- `productoOptions`: mapea `productos` -> `{ id, cod_cups, nom_produc }`.
---

## 4. Documentación de funciones FRONTEND

### buscarStudy
/**
 * Alias/disparador de búsqueda: delega en listarAtenciones cuando el usuario
 * presiona Enter en el input de búsqueda (num_docu / nombres / # atención).
 *
 * @returns {Promise<void>} Resultado de listarAtenciones.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */

### btnLimpiar
/**
 * Limpia los criterios de búsqueda (se mantienen sede y médico del contexto),
 * destruye el DataTable, vacía los registros y limpia el formulario de admisión.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */

### safeDestroyDataTable
/**
 * Destruye de forma segura la instancia de DataTables asociada al selector dado,
 * evitando errores si jQuery/DataTables no están disponibles o la tabla no existe.
 *
 * @param {string} selector - Selector jQuery de la tabla (por defecto "#example23").
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */

### safeInitDataTable
/**
 * Inicializa DataTables (botones copy/csv/excel/pdf/print y lenguaje en español)
 * sobre el selector indicado, dentro de nextTick para asegurar el render del DOM.
 *
 * @param {string} selector - Selector jQuery de la tabla (por defecto "#example23").
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */

### listarAtenciones
/**
 * Consulta el backend por POST y carga en el DataTable las atenciones/productos
 * de SAHI para la sede y criterios de búsqueda actuales. Destruye e inicializa
 * el DataTable con los nuevos datos.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */

### abrirModalConsentimiento
/**
 * Prepara el estado del modal de consentimiento informado para un registro:
 * guarda el item, reinicia el tipo de firmante a PACIENTE, limpia los datos
 * del acompañante y el link generado.
 *
 * @param {Object} item - Fila de registro (debe tener ecografia_producto_id).
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */

### generarLinkFirma
/**
 * Solicita al backend la creación de un token (10 min) y la URL de firma del
 * consentimiento para el producto de ecografía indicado; soporta firmante
 * PACIENTE o ACOMPANANTE. Refresca luego el estado del consentimiento en el listado.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */

### abrirLinkFirma
/**
 * Abre en una nueva pestaña la URL de firma de consentimiento generada.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */

### refrescarConsentimientoItem
/**
 * Vuelve a consultar el consentimiento vigente para el ecografia_producto_id
 * actual y actualiza las propiedades consentimiento_id / consentimiento_estado /
 * consentimiento_bloquea del item en el listado.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */
### guardarRegistro
/**
 * Envía por POST el objeto `registro` (datos del paciente + productos agendados)
 * para crear la ecografía en LIDiag. Ante 201 recarga la lista, limpia el
 * formulario y cierra el panel; ante error muestra los errores de validación.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */

### registroParaAnular
/**
 * Carga en `registroAnular` el id de ecografía y el id de atención del registro
 * seleccionado antes de abrir el modal de anulación.
 *
 * @param {Object} data - Fila del registro (propiedades id, atencion_id/IdAtencion).
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */

### anularEcografia
/**
 * Valida el motivo de anulación, pide confirmación y envía por POST la anulación
 * de la ecografía al backend. Cierra el modal y recarga el listado.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */

### initSede
/**
 * Inicializa la sede en el formulario/búsqueda leyendo ST-sede de sessionStorage,
 * invoca el composable useSede y vacía los registros.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */

### establecerMedico
/**
 * Establece la sede y recupera de localStorage/sessionStorage el médico actual
 * (ST-ecografia-medico / ST-medico), sincroniza con el composable de sede y limpia registros.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */

### consultarEstadoSahi
/**
 * Consulta el estado de envío a SAHI de un producto de ecografía vía el composable
 * useSahiInterface y actualiza las propiedades de estado del item en el listado.
 * No hace nada si no hay interfaz SAHI o falta ecografia_producto_id.
 *
 * @param {Object} item - Fila del registro con ecografia_producto_id.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */

### reenviarASahi
/**
 * Reenvía (encola) un producto de ecografía a SAHI mediante el composable
 * useSahiInterface. Marca estado ENVIANDO durante el envío y maneja errores
 * mostrando el mensaje devuelto por el backend.
 *
 * @param {Object} item - Fila del registro con ecografia_producto_id.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */

### listarMedicos
/**
 * Obtiene por GET el listado de usuarios con rol Medico para el módulo de
 * ecografías (hardcoded módulo 2) y los almacena en `medicos`.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */

### listarTipoDocumentos
/**
 * Obtiene por GET los tipos de documento activos y los almacena en `tipoDocumentos`.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */

### listarProductos
/**
 * Obtiene por GET el catálogo de productos de configuración y los almacena en `productos`.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */

### onMedicoSelect
/**
 * Maneja el evento @input del Select2 de médicos y normaliza el valor (puede
 * venir como array, objeto o escalar) asignándolo a registro.medico_id.
 *
 * @param {any} value - Valor emitido por Select2 (array/objeto/scalar).
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */
### onTipoDocumentoSelect
/**
 * Maneja el evento @input del Select2 de tipo de documento, normaliza el valor
 * y lo asigna a registro.tipo_documento_id.
 *
 * @param {any} value - Valor emitido por Select2 (array/objeto/scalar).
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */

### onProductoSelect
/**
 * Maneja la selección de un producto en el Select2: busca el producto en el
 * catálogo, lo agrega a registro.productosAgenda (si no está repetido) con
 * cantidad 1 y valor, y limpia la selección.
 *
 * @param {any} value - Id del producto seleccionado (o null).
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */

### btnCerralModalForm
/**
 * Controla la visibilidad del panel lateral (right-sidebar) de admisión usando
 * jQuery (.slideDown / .toggleClass "shw-rside").
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */

### mostrarRegistro
/**
 * Carga en el formulario de admisión los datos del paciente, contrato, plan,
 * administradora y productos de la atención seleccionada; homologa el tipo de
 * documento usando el código SAHI contra tipoDocumentoOptions y abre el panel lateral.
 *
 * @param {Object} data - Fila de la atención SAHI (PrimerNombre, NumDocumento, etc.).
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */

### limpiarFormulario
/**
 * Reinicializa todos los campos del objeto `registro` (datos de paciente y
 * asignación) y vacía productosAgenda.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */

### quitarProductoAgenda
/**
 * Elimina de registro.productosAgenda el producto cuyo CodLegal coincida.
 *
 * @param {string|number} codProduc - Código legal (CodLegal) del producto a quitar.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */

### onMounted
/**
 * Hook de montaje: valida que exista ST-sede; si no, muestra toast y retorna.
 * De lo contrario carga médicos, sede, tipos de documento y productos, y suscribe
 * el evento `sedeSeleccionada` del emitter para reaccionar al cambio de sede.
 *
 * @param {Function} callback - Función de montaje (arrow) ejecutada por Vue.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */

### (Composable) useSahiInterface.enviarEcografiaProducto
/**
 * Función del composable useSahiInterface que envía por POST un producto de
 * ecografía a SAHI y devuelve la respuesta axios.
 *
 * @param {number} ecografiaProductoId - Id del producto de ecografía a enviar.
 *
 * @returns {Promise<any>} Respuesta axios de /api/interface-sahi-ecografias/enviar/ecografia-producto.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-26
 */

### (Composable) useSahiInterface.consultarEstado
/**
 * Función del composable useSahiInterface que consulta por GET el estado de
 * envío a SAHI de un producto de ecografía.
 *
 * @param {number} ecografiaProductoId - Id del producto de ecografía.
 *
 * @returns {Promise<Object>} Objeto con estado_envio_sahi, fechor_envio_sahi, etc.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-26
 */

### (Helper) setMapValue
/**
 * Asigna un valor a una propiedad de un objeto reactivo en la instancia Vue,
 * usando $set cuando existe (compatibilidad Vue 2) o asignación directa en Vue 3.
 *
 * @param {object} vm - Instancia del componente Vue (proxy).
 * @param {string} mapName - Nombre de la propiedad/mapa reactivo (ej. "isConsultandoSahi").
 * @param {*} key - Clave dentro del mapa reactivo.
 * @param {*} value - Valor a asignar.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-28
 */

---

## 5. Endpoints API usados

| Método | Endpoint | Controlador@método | Service@método | Descripción |
|--------|----------|--------------------|----------------|-------------|
| POST | /api/interface-sahi-ecografias/listar/productos/atencion | SAHIEcografiasController@listarProductosAtencion | SAHIEcografiasService@listarProductosAtencion | Lista atenciones/productos SAHI para la sede (listarAtenciones). |
| POST | /api/ecografias | EcografiaController@store | EcografiaService@create | Crea la ecografía + productos (guardarRegistro). |
| POST | /api/ecografia-anular/{id} | EcografiaController@anularEcografia | EcografiaService@anular | Anula una ecografía (anularEcografia). |
| POST | /api/interface-sahi-ecografias/enviar/ecografia-producto | SAHIEcografiasEnviarController@enviarPorEcografiaProducto | SAHIEcografiasEnviarService@enviarPorEcografiaProducto | Envía/reenvía producto a SAHI (reenviarASahi). |
| GET | /api/interface-sahi-ecografias/estado/ecografia-producto/{ecografia_producto_id} | SAHIEcografiasEnviarController@estadoPorEcografiaProducto | SAHIEcografiasEnviarService@estadoPorEcografiaProducto | Consulta estado SAHI (consultarEstadoSahi). |
| POST | /api/ecografias-consentimientos/token | EcografiaConsentimientosController@crearToken | EcografiaConsentimientosService@crearToken | Genera token/URL de firma (generarLinkFirma). |
| GET | /api/ecografias-consentimientos/vigente/{ecografia_producto_id} | EcografiaConsentimientosController@vigentePorEcografiaProducto | EcografiaConsentimientosService@vigentePorEcografiaProducto | Estado vigente del consentimiento (refrescarConsentimientoItem). |
| GET | /api/user.listarUsuarios/Medico/2 | UserController@listarUsuarios | UserService@listByRoleAndModule | Lista médicos del módulo ecografías (listarMedicos). |
| GET | /api/config-tipos-documentos/listar/activos | ConfigTipoDocumentoController@listarActivos | ConfigTipoDocumentoService@listarActivos | Tipos de documento activos (listarTipoDocumentos). |
| GET | /api/config-productos | ConfigProductoController@index | ConfigProductoService@all | Catálogo de productos (listarProductos). |

Permisos asociados (middleware): ecografias.admision|ecografias.index, ecografias.anular, ecografias.consentimientos.capturar_firma, etc. (ver routes/api_ecografias.php).
---

## 6. Documentación de métodos BACKEND

### 6.1 SAHIEcografiasController

#### listarProductosAtencion
/**
 * Controlador que recibe la petición de listado de atenciones/productos SAHI y
 * delega en el service. Devuelve JSON con el status obtenido del service.
 *
 * @param \Illuminate\Http\Request $request - Request con bus_nom_num_docu (número de atención/documento/nombres).
 *
 * @returns \Illuminate\Http\JsonResponse
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-20
 */

### 6.2 SAHIEcografiasEnviarController

#### estadoPorEcografiaProducto
/**
 * Devuelve el estado de envío a SAHI (estado_envio_sahi, fechor_envio_sahi,
 * error_envio_sahi, esquemas 149/150) de un producto de ecografía.
 *
 * @param int $ecografia_producto_id - Id del producto de ecografía.
 *
 * @returns \Illuminate\Http\JsonResponse
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-20
 */

#### enviarPorEcografiaProducto
/**
 * Valida la request y delega el envío/reenvío de un producto de ecografía a SAHI.
 *
 * @param \App\Http\Requests\Interfaces\EnviarEcografiaRequest $request - Request validado con ecografia_producto_id.
 *
 * @returns \Illuminate\Http\JsonResponse
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-20
 */

### 6.3 SAHIEcografiasService

#### listarProductosAtencion
/**
 * Consulta en la base SAHI (sqlsrv) las atenciones facturadas y en movimiento
 * para el número de atención indicado, filtrando por grupos permitidos
 * (44,195,156,190,204,191). Enriquece cada fila con la info local de LIDiag
 * (estado de ecografía, envío SAHI, consentimiento, plantillas vigentes) y
 * enmascara datos sensibles del paciente (documento, dirección, tel, email, dob).
 *
 * @param int $atencionId - Número de atención SAHI (recibido como bus_nom_num_docu).
 *
 * @returns array - Estructura estándar del BaseService (status 200 + data).
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-14
 */

### 6.4 SAHIEcografiasEnviarService

#### estadoPorEcografiaProducto
/**
 * Recupera el producto de ecografía LIDiag y retorna su estado de envío SAHI;
 * retorna notFound si no existe.
 *
 * @param int $ecografiaProductoId - Id del producto de ecografía.
 *
 * @returns array
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-15
 */

#### enviarPorEcografiaProducto
/**
 * Orquesta el envío a SAHI: valida que el producto exista y no esté ENVIADO/ENVIANDO,
 * delega la inserción en SAHI y actualiza el estado local de los productos.
 *
 * @param int $ecografiaProductoId - Id del producto de ecografía a enviar.
 *
 * @returns array
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-15
 */

#### sendToSahiByEcografiaProductoId (private)
/**
 * Ejecuta la transacción de envío: bloquea el registro, valida prerrequisitos,
 * inserta los esquemas 149/150 en SAHI y actualiza estado local. Maneja errores
 * marcando los productos con estado ERROR.
 *
 * @param int $ecografiaProductoId - Id del producto de ecografía.
 *
 * @returns array
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-15
 */

#### validateSendPrerequisites (private)
/**
 * Valida que existan productos, que la ecografía tenga atencion_id y que el
 * médico tenga interface_id (SAHI). Marca los productos como ENVIANDO y construye
 * el memo de lectura/opinión normalizado a texto plano.
 *
 * @param \App\Models\Ecografias\EcografiaProducto $ecoProd - Producto raíz.
 * @param \Illuminate\Support\Collection $ecoProds - Todos los productos de la ecografía.
 *
 * @returns array
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-15
 */
#### executeSahiInsert (private)
/**
 * Inserta en SAHI los esquemas 149 (encabezado + esquema de atención + memo +
 * detalle médico) y 150 (detalle de productos), utilizando sp_getapplock para
 * evitar envíos concurrentes y verificando registros previos (idempotencia).
 *
 * @param object $medico - Modelo médico con interface_id.
 * @param int $atencionId - Id de atención SAHI.
 * @param int $ecografiaId - Id de ecografía LIDiag.
 * @param array $productoIds - Ids de productos a enviar.
 * @param string $fecRegistro - Fecha/hora de registro.
 * @param string $memo - Texto de lectura/opinión.
 *
 * @returns array
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-15
 */

#### updateProductsAfterSahiSend (private)
/**
 * Marca los productos de ecografía como ENVIADO y guarda los ids de esquemas
 * SAHI 149/150 y la fecha de envío.
 *
 * @param array $ecoProdIds - Ids de productos de ecografía.
 * @param \Illuminate\Support\Collection $ecoProds - Colección de productos.
 * @param array $result - Resultado de executeSahiInsert.
 *
 * @returns void
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-15
 */

#### markEnvioErrorMany (private)
/**
 * Marca múltiples productos de ecografía con estado ERROR y guarda el mensaje,
 * siempre que no estén ya ENVIADO.
 *
 * @param array $ecografiaProductoIds - Ids de productos.
 * @param string $error - Mensaje de error.
 *
 * @returns void
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-15
 */

#### incrementSisTablaConsecutivo (private)
/**
 * Incrementa y persiste el consecutivo de la tabla sisTabla para la entidad dada.
 *
 * @param string $nomTabla - Nombre de tabla (ej. "hceEsquemaEncabeza").
 *
 * @returns int - Nuevo consecutivo.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-15
 */

#### incrementHceEsquemaConsecutivo (private)
/**
 * Incrementa el consecutivo del esquema HceEsquema para el IdEsquema indicado.
 *
 * @param int $idEsquema - Id del esquema (149 o 150).
 *
 * @returns void
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-15
 */

#### insertEncabezadoEsquema (private)
/**
 * Inserta un registro en hceEsquemaEncabeza (esquema 149 o 150).
 *
 * @param array $data - Datos del encabezado (IdEsquemaEncabezado, IdEsquema, idAtencion, etc.).
 *
 * @returns \App\Models\Interface\Sahi\hceEsquemaEncabeza
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-15
 */

#### insertEsquemaAtencion (private)
/**
 * Inserta un registro en hceEsquemasdeAte (relación esquema-atención-medico).
 *
 * @param array $data - Datos del esquema de atención.
 *
 * @returns \App\Models\Interface\Sahi\hceEsquemasdeAte
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-15
 */

#### insertMemo (private)
/**
 * Inserta el memo (lectura/opinión) en hceEsquemaDetMemo.
 *
 * @param array $data - Datos del memo (IdMemo, IdEsquemaEncabezado, DesMemo).
 *
 * @returns \App\Models\Interface\Sahi\hceEsquemaDetMemo
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-15
 */

#### insertDetalleMedico (private)
/**
 * Inserta el detalle de médico (hceEsquemaDetProc) con los ValVarChar del esquema 149.
 *
 * @param array $data - Datos del detalle médico.
 *
 * @returns \App\Models\Interface\Sahi\hceEsquemaDetProc
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-15
 */

#### insertDetalleProducto (private)
/**
 * Inserta el detalle de producto (hceEsquemaDetProc) para el esquema 150.
 *
 * @param array $data - Datos del detalle producto (IdProducto, NomProducto, CodLegal, etc.).
 *
 * @returns \App\Models\Interface\Sahi\hceEsquemaDetProc
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-15
 */

#### findExistingEsquema150ForAtencionProducto (private)
/**
 * Busca si ya existe un esquema 150 en SAHI para la atención/producto dado
 * (idempotencia del envío).
 *
 * @param int $atencionId - Id de atención SAHI.
 * @param int $productoId - Id de producto SAHI.
 *
 * @returns array|null
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-15
 */

#### normalizeHtmlToText (private)
/**
 * Convierte HTML (saltos <br>, <div>, <hN>) a texto plano para el memo SAHI.
 *
 * @param string $value - Texto HTML.
 *
 * @returns string
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-15
 */
### 6.5 EcografiaController

#### store
/**
 * Crea una nueva ecografía validando la request (EcografiaStoreRequest) y
 * delegando en EcografiaService@create.
 *
 * @param \App\Http\Requests\Ecografias\EcografiaStoreRequest $request - Datos de la admisión.
 *
 * @returns \Illuminate\Http\JsonResponse
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-19
 */

#### anularEcografia
/**
 * Anula una ecografía (requiere permiso ecografias.anular) delegando el motivo
 * al service.
 *
 * @param \App\Http\Requests\Ecografias\EcografiaAnularRequest $request - Request con motivo_anula.
 * @param int $id - Id de la ecografía a anular.
 *
 * @returns \Illuminate\Http\JsonResponse
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-19
 */

### 6.6 EcografiaService

#### create
/**
 * Crea la ecografía en una transacción: upsert del paciente, firstOrCreate de la
 * administradora de salud, crea la ecografía (estado EN PROCESO) y crea un
 * EcografiaProducto por cada productoAgendado.
 *
 * @param array $data - Datos de admisión (num_docu, nombres, productosAgenda, etc.).
 *
 * @returns array
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-07
 */

#### anular
/**
 * Marca una ecografía como ANULADO, registrando motivo, usuario y fecha de anulación.
 *
 * @param int $id - Id de la ecografía.
 * @param string $motivo - Motivo de anulación.
 *
 * @returns array
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-07
 */

### 6.7 EcografiaConsentimientosController

#### crearToken
/**
 * Crea un token (10 min) de firma de consentimiento informado para un producto de
 * ecografía, validando la request y delegando en el service con usuario e IP.
 *
 * @param \App\Http\Requests\Ecografias\EcografiaConsentimientoCrearTokenRequest $request
 * @param \Illuminate\Http\Request $request - Request HTTP (usuario/IP).
 *
 * @returns \Illuminate\Http\JsonResponse
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-11
 */

#### vigentePorEcografiaProducto
/**
 * Retorna el consentimiento vigente asociado a un producto de ecografía.
 *
 * @param \Illuminate\Http\Request $request
 * @param int $ecografia_producto_id - Id del producto de ecografía.
 *
 * @returns \Illuminate\Http\JsonResponse
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-11
 */

### 6.8 EcografiaConsentimientosService

#### crearToken
/**
 * Genera el consentimiento informado: valida que exista plantilla vigente para el
 * producto/módulo 2, crea el registro con token (hash sha256, expira en 10 min),
 * y retorna la URL de firma. Anula consentimientos vigentes previos del mismo producto.
 *
 * @param array $data - { ecografia_producto_id, sede_id, firmante_tipo, acompanante? }.
 * @param object $user - Usuario autenticado (transcriptor).
 * @param string|null $ip - IP del cliente.
 *
 * @returns array
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-07
 */

#### vigentePorEcografiaProducto
/**
 * Consulta el consentimiento vigente (vigente=true) para un producto de ecografía.
 *
 * @param int $ecografiaProductoId - Id del producto de ecografía.
 *
 * @returns array
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-07
 */
### 6.9 UserController / UserService

#### UserController::listarUsuarios
/**
 * Lista usuarios filtrados por tipo de rol y módulo; delega en UserService.
 *
 * @param string $tipoUser - Tipo de usuario/rol (ej. "Medico").
 * @param int $modulo - Id del módulo (2 = ecografías).
 *
 * @returns \Illuminate\Http\JsonResponse
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-15
 */

#### UserService::listByRoleAndModule
/**
 * Retorna los usuarios que tienen el rol indicado Y pertenecen al módulo indicado.
 *
 * @param string $roleName - Nombre del rol (ej. "Medico").
 * @param int $moduleId - Id del módulo.
 *
 * @returns array
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-14
 */

### 6.10 ConfigTipoDocumentoController / Service

#### ConfigTipoDocumentoController::listarActivos
/**
 * Retorna los tipos de documento activos (estado=true).
 *
 * @returns \Illuminate\Http\JsonResponse
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-16
 */

#### ConfigTipoDocumentoService::listarActivos
/**
 * Consulta y retorna todos los tipos de documento con estado activo.
 *
 * @returns array
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-14
 */

### 6.11 ConfigProductoController / Service

#### ConfigProductoController::index
/**
 * Retorna el catálogo completo de productos de configuración ordenado por nombre.
 *
 * @returns \Illuminate\Http\JsonResponse
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-16
 */

#### ConfigProductoService::all
/**
 * Consulta y retorna todos los productos de configuración ordenados por nom_produc.
 *
 * @returns array
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-14
 */

---

## 7. Flujo de usuario

1. **Carga:** `onMounted` valida `ST-sede`; si no existe, avisa y detiene. Si existe, carga médicos, sede, tipos de documento y productos. Suscribe el evento `sedeSeleccionada`.
2. **Búsqueda:** El usuario escribe # atención / # documento / nombres y presiona *Buscar* (`listarAtenciones`). El backend (`SAHIEcografiasService`) consulta SAHI y enriquece con estado local (ecografía, SAHI, consentimiento).
3. **Admisión:** El usuario pulsa el check de una fila -> `mostrarRegistro` carga los datos del paciente en el panel lateral y homologa el tipo de documento. Asigna médico (`onMedicoSelect`), agrega productos (`onProductoSelect` / `quitarProductoAgenda`).
4. **Guardar:** `guardarRegistro` -> POST `/api/ecografias` -> `EcografiaService@create` (upsert paciente + crea ecografía y productos). Recarga la lista y limpia el formulario.
5. **SAHI:** En la columna SAHI, `consultarEstadoSahi` refresca el estado; si está en ERROR, `reenviarASahi` reenvía vía `SAHIEcografiasEnviarService`.
6. **Consentimiento:** Si el producto tiene plantilla, se abre el modal (`abrirModalConsentimiento` -> `generarLinkFirma` -> `refrescarConsentimientoItem`).
7. **Anulación:** El usuario abre el modal de anular, ingresa motivo y confirma (`registroParaAnular` -> `anularEcografia` -> `EcografiaService@anular`).

---

## 8. Notas / consideraciones

- **Endpoints del enunciado vs reales:** El componente NO usa `/api/contratacion.buscarOrdenes`, `/api/admision.buscarPaciente/{sede}`, `/api/sahi.enviar` ni `/api/sahi.reenviar/{id}`. La búsqueda y el envío SAHI se hacen vía `interface-sahi-ecografias/*` (ver tabla del punto 5). Es probable que esas rutas pertenezcan a otros módulos (contratación/admisión general) y no a este componente de ecografías.
- **jQuery + Vue 3:** El componente mezcla jQuery (sidebar con `.slideDown`/`.toggleClass` y DataTables `window.$.fn.DataTable`) con la reactividad de Vue. `safeDestroyDataTable`/`safeInitDataTable` protegen contra la ausencia de jQuery.
- **SAHI encolado / asíncrono:** `reenviarASahi` marca estado `ENVIANDO` y delega en el composable; el estado real se confirma con `consultarEstadoSahi` o con el comando programado `ecografias:sahi-reconcile` (ver routes/console.php).
- **Homologación de tipo de documento:** En `mostrarRegistro` se cruza `CodTipoDoc` (SAHI) contra `tipoDocumentoOptions` (LIDiag) por `codigo` para preseleccionar el tipo de documento local.
- **Módulo 2 hardcoded:** `listarMedicos` fija el módulo `2` (ecografías) en la URL `user.listarUsuarios/Medico/2`; `SAHIEcografiasService` y `EcografiaConsentimientosService` también fijan `config_modulo_id = 2`.
- **Grupos SAHI permitidos:** `SAHIEcografiasService` filtra productos por `IdGrupo` en `[44, 195, 156, 190, 204, 191]`.
- **Enmascaramiento de datos:** Tanto el service de listado como `EcografiaService@all` aplican `maskData` a documento, dirección, teléfono, celular, email y fecha de nacimiento.
- **Permisos:** Las rutas están protegidas con middleware `permission:...` (ver routes/api_ecografias.php); el componente usa `can(...)` para habilitar botones de consentimiento.
- **Consentimiento:** El token de firma expira en 10 minutos (`now()->addMinutes(10)`) y la URL se construye como `/firma/consentimiento-ecografia/{token}`.
