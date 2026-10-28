<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Seguridad</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Seguridad</a>
                    </li>
                    <li class="breadcrumb-item active">Gestión de Roles</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary waves-effect waves-light m-r-5"
                    :disabled="loadingListar"
                    title="Actualizar listado de roles"
                    @click="ListarDatos()"
                >
                    <i class="fa fa-refresh mr-1" :class="{ 'fa-spin': loadingListar }"></i>
                    Refrescar
                </button>
                <button
                    type="button"
                    class="btn btn-info waves-effect waves-light btnDesplegarRigthSidebar"
                    title="Crear nuevo rol"
                    @click="
                        actualizar = false;
                        mostrarRegistro();
                    "
                >
                    <i class="fa fa-plus-circle m-r-5"></i> Nuevo registro
                </button>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Start Page Content -->
        <!-- ============================================================== -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Listado de roles</h4>
                        <div v-if="loadingListar" class="text-center p-3">
                            <i class="fa fa-spinner fa-spin"></i> Cargando...
                        </div>
                        <div class="table-responsive m-t-40">
                            <table
                                id="example23"
                                ref="dt"
                                class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th>Rol</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="!listarRoles.length">
                                        <td colspan="2" class="text-center text-muted">
                                            No se encontraron roles registrados.
                                        </td>
                                    </tr>
                                    <tr v-for="item in listarRoles" :key="item.id">
                                        <td>{{ item.name }}</td>
                                        <td class="text-nowrap">
                                            <!-- <button class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm m-r-5">
                                                <i class="fa fa-eye"></i>
                                            </button> -->

                                            <button
                                                type="button"
                                                title="Editar rol"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                :disabled="loadingCargarPermisos"
                                                @click="
                                                    actualizar = true;
                                                    mostrarRegistro(item);
                                                "
                                            >
                                                <i class="fa fa-pencil"></i>
                                            </button>

                                            <button
                                                type="button"
                                                title="Desactivar rol"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-success btn-sm"
                                                @click="cambiarEstado(1, item)"
                                                v-if="item.estado == 1"
                                            >
                                                <i class="fa fa-unlock"></i>
                                            </button>

                                            <button
                                                type="button"
                                                title="Activar rol"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm"
                                                @click="cambiarEstado(0, item)"
                                                v-if="item.estado == 0"
                                            >
                                                <i class="fa fa-lock"></i>
                                            </button>

                                            <button
                                                type="button"
                                                title="Eliminar rol"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm m-l-5"
                                                @click="eliminarRol(item)"
                                            >
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End PAge Content -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Right sidebar -->
        <!-- ============================================================== -->
        <div v-if="sidebarOpen" class="sidebar-backdrop" @click="btnCerrarModalForm()"></div>
        <!-- .right-sidebar -->
        <div
            class="right-sidebar"
            :class="{ 'shw-rside': sidebarOpen }"
            style="overflow-y: auto; height: 100%"
        >
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <button
                        type="button"
                        class="right-side-toggle text-white border-0 bg-transparent"
                        title="Cerrar panel (Esc)"
                        aria-label="Cerrar panel"
                        @click="btnCerrarModalForm()"
                    >
                        <i class="ti-close"></i>
                    </button>
                </div>
                <div class="r-panel-body">
                    <!-- ============================================================== -->
                    <!-- Aqui va el contenido de los formularios -->
                    <!-- ============================================================== -->
                    <form action="#">
                        <div class="form-body">
                            <div class="row p-t-20">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Nombre del Rol</label>
                                        <input
                                            type="text"
                                            id="name"
                                            name="name"
                                            v-model="registro.name"
                                            class="form-control"
                                            placeholder="Ingrese aqui el nombre del rol"
                                        />
                                        <span class="text-danger" v-if="errores">{{
                                            errores
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <p class="text-info"><i class="fa fa-unlock"></i> Permisos</p>
                            <div v-if="loadingCargarPermisos" class="text-muted mb-2">
                                <i class="fa fa-spinner fa-spin"></i> Cargando permisos del rol...
                            </div>
                            <span class="text-danger" v-if="erroresRpermisosElegidos">{{
                                erroresRpermisosElegidos
                            }}</span>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="controls">
                                            <div
                                                v-for="(permisosGrupo, modulo) in permisosAgrupados"
                                                :key="modulo"
                                                class="mb-3"
                                            >
                                                <div
                                                    class="d-flex align-items-center justify-content-between p-2 rounded"
                                                    style="background: #f0f2f5; cursor: pointer"
                                                    @click="toggleGrupo(modulo)"
                                                >
                                                    <div class="d-flex align-items-center">
                                                        <i
                                                            class="fa m-r-5"
                                                            :class="
                                                                gruposAbiertos[modulo]
                                                                    ? 'fa-chevron-down'
                                                                    : 'fa-chevron-right'
                                                            "
                                                        ></i>
                                                        <strong>{{
                                                            modulosTraducidos[modulo] || modulo
                                                        }}</strong>
                                                        <span class="badge badge-secondary ml-2">{{
                                                            permisosGrupo.length
                                                        }}</span>
                                                    </div>
                                                    <div @click.stop>
                                                        <small
                                                            class="text-info mr-2"
                                                            role="button"
                                                            @click="seleccionarGrupo(modulo, true)"
                                                            >Todos</small
                                                        >
                                                        <small
                                                            class="text-muted"
                                                            role="button"
                                                            @click="seleccionarGrupo(modulo, false)"
                                                            >Ninguno</small
                                                        >
                                                    </div>
                                                </div>
                                                <div v-show="gruposAbiertos[modulo]" class="pt-2">
                                                    <div class="row">
                                                        <div
                                                            v-for="itemPermiso in permisosGrupo"
                                                            :key="itemPermiso.id"
                                                            class="col-md-6 col-lg-4 mb-1"
                                                        >
                                                            <div
                                                                class="custom-control custom-checkbox"
                                                            >
                                                                <input
                                                                    type="checkbox"
                                                                    class="custom-control-input"
                                                                    :id="'perm_' + itemPermiso.id"
                                                                    v-model="itemPermiso.checked"
                                                                    :disabled="
                                                                        loadingCargarPermisos
                                                                    "
                                                                />
                                                                <label
                                                                    class="custom-control-label"
                                                                    :for="'perm_' + itemPermiso.id"
                                                                >
                                                                    {{ itemPermiso.name }}
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions mt-3">
                                <button
                                    type="button"
                                    class="btn btn-success waves-effect waves-light m-r-5"
                                    @click="guardarRegistro()"
                                    v-if="!actualizar"
                                    :disabled="loadingGuardar"
                                    title="Guardar rol"
                                >
                                    <i v-if="loadingGuardar" class="fa fa-spinner fa-spin mr-1"></i>
                                    <i v-else class="fa fa-check mr-1"></i> Guardar
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-warning waves-effect waves-light m-r-5"
                                    v-if="actualizar"
                                    @click="guardarRegistro()"
                                    :disabled="loadingGuardar"
                                    title="Actualizar rol"
                                >
                                    <i v-if="loadingGuardar" class="fa fa-spinner fa-spin mr-1"></i>
                                    <i v-else class="fa fa-pencil mr-1"></i> Actualizar
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-inverse waves-effect waves-light"
                                    title="Cancelar"
                                    @click="btnCerrarModalForm()"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Right sidebar -->
        <!-- ============================================================== -->
    </div>
</template>
<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";

const dt = ref(null);

import { confirmAction, toastMessage } from "@/helpers/alerts";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts.js";
import axios from "axios";

const id = ref(0);
const listarRoles = ref([]);
const permisos = ref([]);
const registro = ref({ name: "", permisosRol: [] });
const tituloModal = ref("Nuevo registro");
const actualizar = ref(false);
const errores = ref("");
const erroresRpermisosElegidos = ref("");
const loadingListar = ref(false);
const loadingGuardar = ref(false);
const gruposAbiertos = ref({});
const sidebarOpen = ref(false);
const loadingCargarPermisos = ref(false);

/**
 * Resuelve el módulo al que pertenece un permiso a partir de su nombre.
 * Los permisos siguen el patrón "modulo.recurso.accion" (por ejemplo
 * "seguridad.roles.index"); los que no tienen prefijo van a "Otros".
 *
 * @param {Object} permiso Permiso con la propiedad `name`.
 *
 * @returns {string} Clave del módulo al que pertenece el permiso.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function moduloDePermiso(permiso) {
    const partes = String(permiso?.name ?? "").split(".");
    return partes.length > 1 ? partes[0] : "Otros";
}

/**
 * Agrupa los permisos del formulario por módulo para pintarlos en acordeones.
 * Se apoya en el mismo criterio que `moduloDePermiso`, por lo que el computed
 * y `seleccionarGrupo` nunca pueden discrepar.
 *
 * @returns {Object} Diccionario `{ modulo: [permisos...] }` con la selección actual.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
const permisosAgrupados = computed(() => {
    const grupos = {};
    registro.value.permisosRol.forEach((permiso) => {
        const modulo = moduloDePermiso(permiso);
        if (!grupos[modulo]) {
            grupos[modulo] = [];
        }
        grupos[modulo].push(permiso);
    });
    return grupos;
});

const modulosTraducidos = {
    seguridad: "Seguridad",
    config: "Configuración",
    estudio: "Estudios",
    estudios: "Estudios",
    reportes: "Reportes",
    agenda: "Agenda",
    agendamiento: "Agendamiento",
    ecografias: "Ecografías",
    consentimientos: "Consentimientos",
    citas: "Citas",
    helpdesk: "Help Desk",
    tele: "Tele-Radiología",
    gestion: "Gestión",
    Otros: "Otros",
};

/**
 * Expande o contrae el acordeón de un módulo de permisos.
 *
 * @param {string} modulo Clave del módulo (por ejemplo "seguridad").
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function toggleGrupo(modulo) {
    gruposAbiertos.value[modulo] = !gruposAbiertos.value[modulo];
}

/**
 * Devuelve los permisos del formulario que pertenecen a un módulo.
 * Trabaja sobre `registro.permisosRol` (la fuente real de la selección) y no
 * sobre el computed, para no escribir a través de un valor derivado.
 *
 * @param {string} modulo Clave del módulo (por ejemplo "seguridad").
 *
 * @returns {Array} Permisos del módulo indicado.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function permisosDelModulo(modulo) {
    return registro.value.permisosRol.filter((permiso) => moduloDePermiso(permiso) === modulo);
}

/**
 * Marca o desmarca en bloque todos los permisos de un módulo ("Todos"/"Ninguno").
 *
 * @param {string} modulo    Clave del módulo (por ejemplo "seguridad").
 * @param {boolean} seleccionar true para marcar, false para desmarcar.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function seleccionarGrupo(modulo, seleccionar) {
    permisosDelModulo(modulo).forEach((permiso) => {
        permiso.checked = seleccionar;
    });
}

/**
 * Calcula qué módulos quedan expandidos. Al editar se abre únicamente donde
 * el rol ya tiene permisos asignados, para que el usuario vea de inmediato
 * qué tiene el rol; al crear se abren todos para poder explorarlos.
 *
 * @param {Object}  [opciones]                    Opciones de apertura.
 * @param {boolean} [opciones.soloConSeleccion]   true abre solo módulos con permisos marcados.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function abrirGrupos({ soloConSeleccion = false } = {}) {
    gruposAbiertos.value = {};
    Object.entries(permisosAgrupados.value).forEach(([modulo, permisosDelGrupo]) => {
        gruposAbiertos.value[modulo] = soloConSeleccion
            ? permisosDelGrupo.some((permiso) => permiso.checked)
            : true;
    });
}

/**
 * Inicializa (o reinicializa) la tabla de roles con DataTables.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function initDataTable() {
    safeInitDataTable(dt.value || "#example23", {
        dom: "Bfrtip",
        buttons: ["copy", "csv", "excel", "pdf", "print"],
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 100],
        order: [[0, "asc"]],
        language: {
            lengthMenu: "Mostrar _MENU_ registros por página",
            zeroRecords: "No se encontraron roles",
            info: "Mostrando _START_ a _END_ de _TOTAL_ roles",
            infoEmpty: "No hay roles disponibles",
            infoFiltered: "(filtrado de _MAX_ registros totales)",
            search: "Buscar:",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior",
            },
        },
    });
}
/**
 * Destruye la instancia de DataTables antes de recargar o al desmontar el
 * componente, evitando la fuga de listeners sobre el nodo de la tabla.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function destroyDataTable() {
    safeDestroyDataTable(dt.value || "#example23");
}

/**
 * Carga roles y el catálogo completo de permisos, reconstruye la lista de
 * checkboxes del formulario y refresca la tabla.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
async function ListarDatos() {
    loadingListar.value = true;
    try {
        destroyDataTable();

        const res = await axios.get("/api/seguridad-roles");

        listarRoles.value = res.data.data.roles;
        permisos.value = res.data.data.permissions;

        registro.value.permisosRol = [];
        listarPermisosRol();

        await nextTick();
        initDataTable();
    } catch (_error) {
        if (_error?.response?.status === 401) {
            window.location.href = "/login";
        }
    } finally {
        loadingListar.value = false;
    }
}
/**
 * Valida y guarda el rol (nuevo o existente) con la selección de permisos.
 * Exige al menos un permiso marcado para no dejar un rol sin acceso por
 * descuido: el backend también lo rechaza.
 *
 * @returns {Promise<boolean|undefined>} false si no hay permisos marcados.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
async function guardarRegistro() {
    if (loadingCargarPermisos.value) return;

    const totalPermisosElegido = registro.value.permisosRol.filter(
        (permiso) => permiso.checked == true
    ).length;

    if (totalPermisosElegido == 0) {
        erroresRpermisosElegidos.value = "Debes elegir al menos un permiso para el rol";
        return false;
    }

    erroresRpermisosElegidos.value = "";

    if (loadingGuardar.value) return;
    loadingGuardar.value = true;
    try {
        if (actualizar.value === false) {
            const res = await axios.post("/api/seguridad-roles", registro.value);
            toastMessage({ heading: res.data.data.message, icon: "success" });
            ListarDatos();
            limpiarFormulario();
            btnCerrarModalForm();
        } else {
            const res = await axios.put("/api/seguridad-roles/" + id.value, {
                registro: registro.value,
            });
            toastMessage({ heading: res.data.data.message, icon: "success" });
            ListarDatos();
            limpiarFormulario();
            btnCerrarModalForm();
        }
    } catch (_error) {
        errores.value = _error?.response?.data?.message || "";
        toastMessage({
            heading: "Error",
            text: _error?.response?.data?.message || "No se pudo guardar el rol.",
            icon: "error",
        });
    } finally {
        loadingGuardar.value = false;
    }
}
/**
 * Construye la lista de checkboxes del formulario a partir del catálogo de
 * permisos, todos inicialmente desmarcados. Se hace con `push` sobre el arreglo
 * existente para conservar las referencias de objetos ya enlazadas al template.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function listarPermisosRol() {
    permisos.value.forEach(function (x) {
        registro.value.permisosRol.push({
            id: x.id,
            name: x.name,
            checked: false,
        });
    });
}

/**
 * Prepara el panel lateral para crear o editar un rol.
 *
 * Al editar, consulta el rol y marca sus permisos sobre el arreglo ya montado.
 * La selección se modifica EN SITIO (mutando `checked`) y nunca se reasigna el
 * arreglo: los checkboxes del template están enlazados a esos mismos objetos, y
 * vaciarlo dejaba el árbol de permisos sin renderizar y con un TypeError que el
 * catch silenciaba. Mientras dura la petición los checkboxes quedan
 * deshabilitados para que nada se sobrescriba bajo los dedos del usuario.
 *
 * @param {Object} [data]      Rol de la fila editada.
 * @param {number} [data.id]   Id del rol.
 * @param {string} [data.name] Nombre del rol.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
async function mostrarRegistro(data = {}) {
    if (actualizar.value == true) {
        tituloModal.value = "Actualizar el registro: " + data.name;
        id.value = data.id;
        registro.value.name = data.name;
        errores.value = "";
        erroresRpermisosElegidos.value = "";

        loadingCargarPermisos.value = true;
        sidebarOpen.value = true;
        nextTick(() => {
            const el = document.getElementById("name");
            if (el) el.focus();
        });

        try {
            const res = await axios.get("/api/seguridad-roles/" + id.value);
            const permisosDelRol = Object.keys(res?.data?.data?.permisosRol ?? {});

            registro.value.permisosRol.forEach((permiso) => {
                permiso.checked = false;
            });

            permisosDelRol.forEach((permisoDelRol) => {
                const indice = registro.value.permisosRol.findIndex(
                    (permiso) => String(permiso.id) === String(permisoDelRol)
                );

                if (indice !== -1) {
                    registro.value.permisosRol[indice].checked = true;
                }
            });

            abrirGrupos({ soloConSeleccion: true });
        } catch (_error) {
            if (_error?.response?.status === 401) {
                window.location.href = "/login";

                return;
            }

            toastMessage({
                heading: "No fue posible cargar los permisos del rol.",
                text: _error?.response?.data?.message || "Intente nuevamente.",
                icon: "error",
            });
        } finally {
            loadingCargarPermisos.value = false;
        }

        return;
    }

    actualizar.value = false;
    tituloModal.value = "Nuevo registro";
    id.value = 0;
    registro.value.name = "";
    errores.value = "";
    erroresRpermisosElegidos.value = "";
    registro.value.permisosRol = [];
    listarPermisosRol();
    abrirGrupos();
    sidebarOpen.value = true;
    nextTick(() => {
        const el = document.getElementById("name");
        if (el) el.focus();
    });
}
/**
 * Activa o desactiva un rol previa confirmación del usuario.
 *
 * @param {number} estado       Estado destino: 0 activo, 1 inactivo.
 * @param {Object} [data]       Rol afectado.
 * @param {number} [data.id]    Id del rol.
 * @param {string} [data.name]  Nombre del rol.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
async function cambiarEstado(estado, data = {}) {
    const ok = await confirmAction({
        title: "Confirmación",
        text:
            "¿Estás seguro de que deseas " +
            (estado == 0 ? "activar" : "desactivar") +
            " el rol " +
            data.name +
            "?",
        icon: "warning",
    });
    if (!ok) return;
    try {
        await axios.put("/api/seguridad-roles/estado/" + data.id, {
            id: data.id,
            estado: estado == 0 ? 1 : 0,
        });
        toastMessage({
            heading:
                "El rol " +
                data.name +
                " se " +
                (estado == 0 ? "activó" : "desactivó") +
                " correctamente",
            icon: "success",
        });
        ListarDatos();
    } catch (_error) {
        const msg = _error?.response?.data?.message || "Error al cambiar estado del rol";
        toastMessage({ heading: msg, icon: "error" });
    }
}
/**
 * Elimina un rol previa confirmación. El backend impide el borrado si el rol
 * todavía está asignado a algún usuario.
 *
 * @param {Object} [data]       Rol a eliminar.
 * @param {number} [data.id]    Id del rol.
 * @param {string} [data.name]  Nombre del rol.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
async function eliminarRol(data = {}) {
    const ok = await confirmAction({
        title: "Confirmación",
        text: "¿Estás seguro de que deseas eliminar el rol " + data.name + "?",
        icon: "warning",
    });
    if (!ok) return;
    try {
        const res = await axios.delete("/api/seguridad-roles/" + data.id);
        toastMessage({ heading: res.data.data.message, icon: "success" });
        ListarDatos();
    } catch (_error) {
        const msg = _error?.response?.data?.message || "Error al eliminar el rol";
        toastMessage({ heading: msg, icon: "error" });
    }
}
/**
 * Abre o cierra el panel lateral. Se usa únicamente para cerrar (botón X y
 * Cancelar); para abrir se asigna `sidebarOpen` directamente, de modo que
 * pulsar editar dos veces no llegue a cerrar el panel.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function btnCerrarModalForm() {
    sidebarOpen.value = !sidebarOpen.value;
}

/**
 * Deja el formulario en blanco tras guardar o cancelar. Desmarca los permisos
 * recorriendo el arreglo, sin indexar por posición (un índice fuera de rango
 * lanzaba un TypeError y terminaba mostrando un toast de error falso).
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function limpiarFormulario() {
    actualizar.value = false;
    tituloModal.value = "Nuevo registro";
    id.value = 0;
    registro.value.name = "";
    errores.value = "";
    erroresRpermisosElegidos.value = "";

    registro.value.permisosRol.forEach((permiso) => {
        permiso.checked = false;
    });
}

onMounted(() => {
    ListarDatos();
});

onBeforeUnmount(() => {
    destroyDataTable();
});

useKeyboardShortcuts({
    onSearch: () => {
        if (!sidebarOpen.value && !loadingListar.value) {
            ListarDatos();
        }
    },
    onSave: () => {
        if (sidebarOpen.value && !loadingGuardar.value) {
            guardarRegistro();
        }
    },
    onEscape: () => {
        if (sidebarOpen.value) {
            btnCerrarModalForm();
        }
    },
});
</script>
<style scoped>
.right-sidebar {
    position: fixed;
    right: -50%;
    width: 50%;
    visibility: hidden;
    pointer-events: none;
    z-index: 1100;
    background: #ffffff;
    top: 0px;
    padding-bottom: 20px;
    height: 100%;
    box-shadow: -5px 0 25px rgba(0, 0, 0, 0.15);
    transition:
        right 0.35s cubic-bezier(0.16, 1, 0.3, 1),
        visibility 0.35s;
}

.shw-rside {
    right: 0px;
    visibility: visible;
    pointer-events: auto;
}

.sidebar-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1050;
    transition: opacity 0.25s ease;
}
</style>
