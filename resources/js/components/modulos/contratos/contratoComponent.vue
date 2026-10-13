<template>
    <div>
        <!-- BREADCRUMB -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Contrataci&oacute;n</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Inicio</a></li>
                    <li class="breadcrumb-item active">Contratos</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-info btnDesplegarRigthSidebar"
                    @click="
                        actualizar = false;
                        mostrarRegistro();
                    "
                >
                    <i class="fa fa-plus-circle m-r-5"></i> Nuevo contrato
                </button>
            </div>
        </div>

        <!-- LISTADO -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Listado de contratos</h4>
                        <h6 class="card-subtitle">
                            Gesti&oacute;n de contrataci&oacute;n por sedes.
                        </h6>
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
                                        <th>Sede</th>
                                        <th>Fecha Inicio</th>
                                        <th>Fecha Fin</th>
                                        <th>Total M&eacute;dicos</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.sede?.nom_sede }}</td>
                                        <td>{{ formatDate(item.fecha_inicio) }}</td>
                                        <td>{{ formatDate(item.fecha_fin) }}</td>
                                        <td>
                                            <span class="label label-info">
                                                {{ contarMedicos(item) }} m&eacute;dicos
                                            </span>
                                        </td>
                                        <td>
                                            <span class="label label-success" v-if="item.estado"
                                                >Activo</span
                                            >
                                            <span class="label label-danger" v-else>Inactivo</span>
                                        </td>
                                        <td class="text-nowrap">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                @click="
                                                    actualizar = true;
                                                    mostrarRegistro(item);
                                                "
                                                title="Editar contrato"
                                            >
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm m-r-5"
                                                @click="abrirDetalle(item)"
                                                title="Configurar detalle"
                                            >
                                                <i class="fa fa-cogs"></i> Detalle
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-success btn-sm m-r-5"
                                                @click="descargarPlantilla(item)"
                                                title="Descargar plantilla de detalle del contrato (m&eacute;dicos-producto)"
                                            >
                                                <i class="fa fa-file-excel-o"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm m-r-5"
                                                @click="abrirModalImportar(item)"
                                                title="Importar m&eacute;dicos-producto desde Excel"
                                            >
                                                <i class="fa fa-upload"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                @click="desactivarRegistro(item)"
                                                :disabled="loadingEliminarId === item.id"
                                                title="Eliminar contrato"
                                            >
                                                <i
                                                    class="fa"
                                                    :class="
                                                        loadingEliminarId === item.id
                                                            ? 'fa-spinner fa-spin'
                                                            : 'fa-trash'
                                                    "
                                                ></i>
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

        <!-- SIDEBAR: CREAR/EDITAR CONTRATO -->
        <div class="right-sidebar" :class="{ 'shw-rside': sidebarVisible }">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <button
                        type="button"
                        class="right-side-toggle"
                        @click="btnCerralModalForm()"
                        aria-label="Cerrar panel"
                    >
                        <i class="ti-close"></i>
                    </button>
                </div>
                <div class="r-panel-body">
                    <form @submit.prevent>
                        <div class="form-body">
                            <div class="row p-t-20">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Sede</label>
                                        <input type="hidden" v-model="registro.sede_id" />
                                        <input
                                            type="text"
                                            class="form-control"
                                            :value="sedeNombreActual"
                                            readonly
                                            disabled
                                        />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Fecha Inicio *</label>
                                        <input
                                            type="date"
                                            v-model="registro.fecha_inicio"
                                            class="form-control"
                                        />
                                        <span class="text-danger" v-if="errores.fecha_inicio">
                                            {{ errores.fecha_inicio[0] }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Fecha Fin *</label>
                                        <input
                                            type="date"
                                            v-model="registro.fecha_fin"
                                            class="form-control"
                                        />
                                        <span class="text-danger" v-if="errores.fecha_fin">
                                            {{ errores.fecha_fin[0] }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Observaciones</label>
                                        <textarea
                                            v-model="registro.observaciones"
                                            class="form-control"
                                            rows="3"
                                            maxlength="500"
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="checkbox checkbox-success">
                                            <input
                                                type="checkbox"
                                                id="estado"
                                                v-model="registro.estado"
                                                :true-value="1"
                                                :false-value="0"
                                            />
                                            <label for="estado"> Activo </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions mt-3">
                                <button
                                    type="button"
                                    class="btn btn-success m-r-5"
                                    @click="guardarRegistro()"
                                    :disabled="loadingGuardar"
                                >
                                    <i v-if="loadingGuardar" class="fa fa-spinner fa-spin"></i>
                                    <i v-else class="fa fa-check"></i>
                                    {{ actualizar ? "Actualizar" : "Guardar" }}
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-inverse"
                                    @click="btnCerralModalForm()"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- SIDEBAR: DETALLE (MÉDICOS + PRODUCTOS) -->
        <div class="right-sidebar sidebar-scroll" :class="{ 'shw-rside': detalleVisible }">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    Detalle: {{ contratoSeleccionado?.sede?.nom_sede }}
                    <button
                        type="button"
                        class="right-side-toggle"
                        @click="cerrarDetalle()"
                        aria-label="Cerrar panel"
                    >
                        <i class="ti-close"></i>
                    </button>
                </div>
                <div class="r-panel-body">
                    <!-- Acción: importar médicos-producto -->
                    <h5 class="card-title text-info">
                        <i class="fa fa-users"></i> M&Eacute;DICOS-PRODUCTO DEL CONTRATO
                    </h5>
                    <hr />
                    <div class="row">
                        <div class="col-md-12">
                            <button
                                type="button"
                                class="btn btn-primary btn-sm"
                                @click="abrirModalImportar(contratoSeleccionado)"
                                :disabled="loadingImportar"
                            >
                                <i v-if="loadingImportar" class="fa fa-spinner fa-spin"></i>
                                <i v-else class="fa fa-upload"></i>
                                Importar m&eacute;dicos (Excel)
                            </button>
                        </div>
                    </div>

                    <!-- Panel de cobertura de productos -->
                    <div class="card mb-3" v-if="cobertura">
                        <div class="card-body py-2">
                            <h6 class="card-subtitle mb-2">
                                Cobertura de productos
                                <span
                                    class="badge"
                                    :class="
                                        cobertura.faltantes.length
                                            ? 'badge-warning'
                                            : 'badge-success'
                                    "
                                >
                                    {{ cobertura.tarifados }} / {{ cobertura.total }}
                                </span>
                            </h6>
                            <p v-if="!cobertura.faltantes.length" class="text-success mb-0">
                                Todos los productos que requieren lectura tienen tarifa.
                            </p>
                            <div v-else style="max-height: 160px; overflow-y: auto">
                                <p class="text-warning mb-1">
                                    Estos productos se pueden leer sin generar pago:
                                </p>
                                <div
                                    v-for="p in cobertura.faltantes"
                                    :key="p.id"
                                    class="small text-muted"
                                >
                                    <strong>{{ p.cod_cups }}</strong> - {{ p.nom_produc }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de médicos-producto -->
                    <div class="col-md-12 table-responsive table-scroll mt-3">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>M&eacute;dico</th>
                                    <th>C&oacute;digo</th>
                                    <th>Producto</th>
                                    <th>Val Consulta</th>
                                    <th>Val Urgencias</th>
                                    <th>Fecha Ingreso</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in detallesLocales" :key="item.id">
                                    <td>{{ item.medico_name }}</td>
                                    <td>{{ item.cod_cups }}</td>
                                    <td>{{ item.nom_produc }}</td>
                                    <td>
                                        <input
                                            type="number"
                                            v-model.number="item.valor_consulta"
                                            class="form-control"
                                            step="0.01"
                                            min="0"
                                        />
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            v-model.number="item.valor_urgencias"
                                            class="form-control"
                                            step="0.01"
                                            min="0"
                                        />
                                    </td>
                                    <td>
                                        <input
                                            type="date"
                                            v-model="item.fecha_ingreso"
                                            class="form-control"
                                        />
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                            @click="quitarProductoDetalle(index)"
                                            title="Quitar producto"
                                        >
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!detallesLocales.length">
                                    <td colspan="7" class="text-center text-muted">
                                        No hay m&eacute;dicos-producto agregados. Use el
                                        bot&oacute;n "Importar m&eacute;dicos" o el bot&oacute;n de
                                        importaci&oacute;n del listado.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Botones -->
                    <div class="form-actions mt-3">
                        <button
                            type="button"
                            class="btn btn-success m-r-5"
                            @click="guardarDetalles()"
                            :disabled="loadingGuardarDetalle"
                        >
                            <i v-if="loadingGuardarDetalle" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-check"></i>
                            Guardar
                        </button>
                        <button type="button" class="btn btn-inverse" @click="cerrarDetalle()">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: IMPORTAR DETALLES DESDE EXCEL -->
        <div
            class="modal fade"
            id="modalImportar"
            tabindex="-1"
            role="dialog"
            aria-labelledby="modalImportarLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="modalImportarLabel">
                            Importar Detalles de Contrato
                        </h4>
                        <button
                            type="button"
                            class="close"
                            @click="cerrarModalImportar()"
                            aria-label="Cerrar"
                            aria-hidden="true"
                        >
                            &times;
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="control-label">Contrato</label>
                            <input
                                type="text"
                                class="form-control"
                                :value="importarForm.contratoLabel"
                                readonly
                                disabled
                            />
                        </div>
                        <div class="form-group">
                            <label class="control-label">
                                M&eacute;dico <span class="text-danger">*</span>
                            </label>
                            <Select2
                                :value="importarForm.medico_id"
                                :options="medicoOptions"
                                :label-fields="['name']"
                                width="100%"
                                :allow-clear="true"
                                dropdown-parent-selector="#modalImportar .modal-body"
                                @input="onMedicoSelectDetalleImport"
                            />
                        </div>
                        <div class="form-group">
                            <label class="control-label">
                                Archivo Excel <span class="text-danger">*</span>
                            </label>
                            <input
                                type="file"
                                ref="archivoImportarRef"
                                accept=".xlsx,.xls"
                                class="form-control"
                            />
                            <small class="text-muted">
                                Formato: C&oacute;digo CUPS | Producto | Valor Consulta | Valor
                                Urgencias (plantilla del contrato)
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-default waves-effect"
                            @click="cerrarModalImportar()"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            class="btn btn-primary waves-effect waves-light"
                            @click="procesarImportacion()"
                            :disabled="loadingImportar"
                        >
                            <i v-if="loadingImportar" class="fa fa-spinner fa-spin"></i>
                            Importar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, computed } from "vue";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { toastMessage, confirmAction } from "@/helpers/alerts.js";
import axios from "axios";
import Select2 from "@/components/shared/Select2.vue";

// ─── REFERENCIAS ──────────────────────────────────────────────────────────────

/**
 * Referencia al elemento DOM de la tabla DataTable
 * @type {import('vue').Ref<HTMLElement|null>}
 */
const dt = ref(null);

/**
 * Listado de contratos cargados desde la API
 * @type {import('vue').Ref<Array>}
 */
const registros = ref([]);

/**
 * Formulario del contrato (crear/editar)
 * @type {import('vue').Ref<Object>}
 */
const registro = ref({
    id: 0,
    sede_id: "",
    fecha_inicio: "",
    fecha_fin: "",
    observaciones: "",
    estado: 1,
});

/**
 * Título dinámico del sidebar de crear/editar contrato
 * @type {import('vue').Ref<string>}
 */
const tituloModal = ref("Nuevo contrato");

/**
 * Flag: true = modo actualizar, false = modo crear
 * @type {import('vue').Ref<boolean>}
 */
const actualizar = ref(false);

/**
 * Errores de validación del formulario principal
 * @type {import('vue').Ref<Object>}
 */
const errores = ref({});

// ─── FLAGS DE CARGA ───────────────────────────────────────────────────────────

/**
 * Flag de carga al listar contratos
 * @type {import('vue').Ref<boolean>}
 */
const loadingListar = ref(false);

/**
 * Flag de carga al guardar contrato
 * @type {import('vue').Ref<boolean>}
 */
const loadingGuardar = ref(false);

/**
 * ID del contrato que se está eliminando (para deshabilitar botón)
 * @type {import('vue').Ref<number|null>}
 */
const loadingEliminarId = ref(null);

// ─── VISIBILIDAD DE SIDEBARS ──────────────────────────────────────────────────

/**
 * Visibilidad del sidebar de crear/editar contrato
 * @type {import('vue').Ref<boolean>}
 */
const sidebarVisible = ref(false);

/**
 * Visibilidad del sidebar de detalle (médicos-producto)
 * @type {import('vue').Ref<boolean>}
 */
const detalleVisible = ref(false);

// ─── SEDE ACTUAL DESDE SESSION STORAGE ────────────────────────────────────────

/**
 * Obtiene la sede actual desde sessionStorage (key: ST-sede).
 * Parsea el JSON almacenado y retorna un objeto con id y nombre.
 *
 * @returns {{ id: number, nom_sede: string } | null}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
function getSedeActual() {
    try {
        const raw = window.sessionStorage.getItem("ST-sede");
        if (!raw) return null;
        const parsed = JSON.parse(raw);
        return {
            id: parsed.id ?? parsed.sede_id ?? null,
            nom_sede: parsed.nom_sede ?? parsed.nombre ?? "Sede",
        };
    } catch {
        return null;
    }
}

/**
 * Sede actual del usuario logueado, obtenida de sessionStorage
 * @type {import('vue').Ref<{ id: number, nom_sede: string }|null>}
 */
const sedeActual = ref(getSedeActual());

/**
 * Nombre legible de la sede actual para mostrar en el formulario (readonly)
 * @type {import('vue').ComputedRef<string>}
 */
const sedeNombreActual = computed(() => sedeActual.value?.nom_sede ?? "No disponible");

// ─── DETALLE ──────────────────────────────────────────────────────────────────

/**
 * Contrato seleccionado para ver/editar su detalle de médicos/productos
 * @type {import('vue').Ref<Object|null>}
 */
const contratoSeleccionado = ref(null);

/**
 * Cobertura de productos del contrato abierto en el detalle.
 * Estructura: { total, tarifados, faltantes: [{ id, cod_cups, nom_produc }] } o null si no se ha cargado.
 * @type {import('vue').Ref<Object|null>}
 */
const cobertura = ref(null);

/**
 * Flag de carga al consultar la cobertura de productos del contrato
 * @type {import('vue').Ref<boolean>}
 */
const loadingCobertura = ref(false);

/**
 * Lista local de médicos-producto del contrato en edición.
 * Cada elemento: { id, medico_id, medico_name, producto_id, cod_cups, nom_produc, valor_consulta, valor_urgencias }
 * @type {import('vue').Ref<Array>}
 */
const detallesLocales = ref([]);

/**
 * Catálogo de usuarios médicos (con reg_med no nulo)
 * @type {import('vue').Ref<Array>}
 */
const medicos = ref([]);

/**
 * Flag de carga al guardar detalles
 * @type {import('vue').Ref<boolean>}
 */
const loadingGuardarDetalle = ref(false);

// ─── COMPUTED: OPTIONS PARA SELECT2 ──────────────────────────────────────────

/**
 * Opciones formateadas para Select2 de médicos.
 * Convierte IDs a String para compatibilidad con Select2.
 *
 * @returns {Array<{id: string, name: string}>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
const medicoOptions = computed(() =>
    medicos.value.map((m) => ({
        id: String(m.id),
        name: m.reg_med ? `${m.reg_med} - ${m.name}` : m.name,
    }))
);

// ─── UTILIDADES ───────────────────────────────────────────────────────────────

/**
 * Formatea una fecha ISO a formato local colombiano (dd/mm/yyyy)
 *
 * @param {string|null} dateStr - Fecha en formato ISO o null
 * @returns {string} Fecha formateada o cadena vacía
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
function formatDate(dateStr) {
    if (!dateStr) return "";
    return new Date(dateStr).toLocaleDateString("es-CO");
}

/**
 * Cuenta los médicos únicos vinculados a un contrato.
 * Como cada registro de contratos_medicos es un par médico+producto,
 * se descuentan duplicados por medico_id para obtener el total real.
 *
 * @param {Object} item - Contrato con su colección medicos cargada
 * @returns {number} Cantidad de médicos distintos del contrato
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-24
 */
function contarMedicos(item) {
    const unicos = new Set((item.medicos || []).map((m) => m.medico_id));
    return unicos.size;
}

// ─── LISTADO PRINCIPAL ────────────────────────────────────────────────────────

/**
 * Carga el listado de contratos desde la API y renderiza la DataTable.
 * Destruye la instancia previa antes de inicializar una nueva.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
async function listar() {
    if (loadingListar.value) return;
    loadingListar.value = true;
    try {
        safeDestroyDataTable(dt.value || "#example23");
        const res = await axios.get("/api/contratos");
        registros.value = res.data.data.data || [];
        await nextTick();
        safeInitDataTable(dt.value || "#example23", {
            dom: "Bfrtip",
            buttons: ["copy", "csv", "excel", "pdf", "print"],
        });
    } catch (error) {
        console.error(error);
        toastMessage({ heading: "Error", text: "Error al cargar los contratos", icon: "error" });
    } finally {
        loadingListar.value = false;
    }
}

// ─── CRUD PRINCIPAL ───────────────────────────────────────────────────────────

/**
 * Abre el sidebar para crear o actualizar un contrato.
 * Si se pasa un item, carga el formulario en modo edición.
 *
 * @param {Object|null} item - Contrato existente para edición, o null para crear nuevo
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
function mostrarRegistro(item = null) {
    errores.value = {};
    const sedeId = sedeActual.value?.id ?? "";
    if (item) {
        tituloModal.value = "Actualizar contrato";
        registro.value = {
            id: item.id,
            sede_id: item.sede_id,
            fecha_inicio: item.fecha_inicio?.split("T")[0] || "",
            fecha_fin: item.fecha_fin?.split("T")[0] || "",
            observaciones: item.observaciones || "",
            estado: item.estado ? 1 : 0,
        };
    } else {
        tituloModal.value = "Nuevo contrato";
        registro.value = {
            id: 0,
            sede_id: sedeId,
            fecha_inicio: "",
            fecha_fin: "",
            observaciones: "",
            estado: 1,
        };
    }
    sidebarVisible.value = true;
}

/**
 * Cierra el sidebar de crear/editar contrato eliminando la clase CSS.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
function btnCerralModalForm() {
    sidebarVisible.value = false;
}

/**
 * Guarda o actualiza el contrato actual según el modo (crear/actualizar).
 * Envía los datos como JSON string en el campo 'registro'.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
async function guardarRegistro() {
    if (loadingGuardar.value) return;
    loadingGuardar.value = true;
    errores.value = {};
    try {
        const payload = {
            registro: JSON.stringify({
                sede_id: registro.value.sede_id,
                fecha_inicio: registro.value.fecha_inicio,
                fecha_fin: registro.value.fecha_fin,
                observaciones: registro.value.observaciones,
                estado: registro.value.estado,
            }),
        };
        if (actualizar.value) {
            await axios.put(`/api/contratos/${registro.value.id}`, payload);
            toastMessage({
                heading: "Ok",
                text: "Contrato actualizado correctamente",
                icon: "success",
            });
        } else {
            await axios.post("/api/contratos", payload);
            toastMessage({ heading: "Ok", text: "Contrato creado correctamente", icon: "success" });
        }
        btnCerralModalForm();
        await listar();
    } catch (error) {
        if (error.response?.status === 422) {
            errores.value = error.response.data.errors || {};
        } else {
            const msg = error.response?.data?.message || "Error al guardar el contrato";
            toastMessage({ heading: "Error", text: msg, icon: "error" });
        }
    } finally {
        loadingGuardar.value = false;
    }
}

/**
 * Elimina un contrato tras confirmación del usuario.
 * Muestra diálogo de confirmación antes de proceder.
 *
 * @param {Object} item - Contrato a eliminar (debe contener id)
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
async function desactivarRegistro(item) {
    const confirmed = await confirmAction({
        title: "Eliminar contrato",
        text: "¿Está seguro de eliminar este contrato? Se eliminarán también sus productos y médicos vinculados.",
        icon: "warning",
    });
    if (!confirmed) return;
    loadingEliminarId.value = item.id;
    try {
        await axios.delete(`/api/contratos/${item.id}`);
        toastMessage({ heading: "Ok", text: "Contrato eliminado correctamente", icon: "success" });
        await listar();
    } catch (error) {
        console.error(error);
        const msg = error.response?.data?.message || "Error al eliminar el contrato";
        toastMessage({ heading: "Error", text: msg, icon: "error" });
    } finally {
        loadingEliminarId.value = null;
    }
}

// ─── CATÁLOGOS ────────────────────────────────────────────────────────────────

/**
 * Carga el catálogo de médicos para los Select2 del módulo.
 * Se ejecuta una vez en onMounted (patrón AsignarEstudio).
 * Médicos: usuarios con reg_med no nulo del módulo actual.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
async function cargarCatalogos() {
    try {
        const moduloId = sessionStorage.getItem("ST-modulo") || "1";
        const medRes = await axios.get(`/api/user.listarUsuarios/Medico/${moduloId}`);
        medicos.value = medRes.data.data || [];
    } catch (error) {
        console.error("Error cargando catálogos:", error);
        const msg = error.response?.data?.message || error.message || "Error desconocido";
        toastMessage({ heading: "Error", text: "Error cargando médicos: " + msg, icon: "error" });
    }
}

// ─── DETALLE: SIDEBAR ─────────────────────────────────────────────────────────

/**
 * Abre el sidebar de detalle de un contrato.
 * Puebla detallesLocales desde los médicos vinculados del contrato y muestra el sidebar.
 * Los catálogos ya se cargaron en onMounted.
 *
 * @param {Object} contrato - Contrato con su sede y médicos vinculados
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
async function abrirDetalle(contrato) {
    contratoSeleccionado.value = contrato;
    cargarCobertura(contrato.id);
    detallesLocales.value = mapearMedicosDetalle(contrato.medicos || []);
    await nextTick();
    detalleVisible.value = true;
}

/**
 * Mapea la colección de contratos_medicos (con relaciones cargadas) a la
 * estructura plana que usa la tabla local del sidebar de detalle.
 *
 * @param {Array} medicosContrato - Registros con medico y contratoProducto.producto incluidos
 * @returns {Array<{id: number, medico_id: number, medico_name: string, producto_id: number|null, cod_cups: string, nom_produc: string, valor_consulta: number, valor_urgencias: number, fecha_ingreso: string}>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
function mapearMedicosDetalle(medicosContrato) {
    return (medicosContrato || []).map((m) => ({
        id: m.id,
        medico_id: m.medico_id,
        medico_name: m.medico?.name || "",
        producto_id: m.contratoProducto?.producto?.id ?? null,
        cod_cups: m.contratoProducto?.producto?.cod_cups || "",
        nom_produc: m.contratoProducto?.producto?.nom_produc || "",
        valor_consulta: m.valor_consulta ?? 0,
        valor_urgencias: m.valor_urgencias ?? 0,
        fecha_ingreso: m.fecha_ingreso || "",
    }));
}

/**
 * Consulta qué productos del catálogo exigibles no tienen tarifa en el contrato.
 *
 * @param {number} contratoId - Identificador del contrato abierto en el detalle.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-30
 */
async function cargarCobertura(contratoId) {
    loadingCobertura.value = true;
    try {
        const res = await axios.get(`/api/contratos/${contratoId}/cobertura`);
        cobertura.value = res.data.data;
    } catch (_error) {
        cobertura.value = null;
    } finally {
        loadingCobertura.value = false;
    }
}

/**
 * Cierra el sidebar de detalle y limpia todo el estado relacionado.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
function cerrarDetalle() {
    contratoSeleccionado.value = null;
    cobertura.value = null;
    detallesLocales.value = [];
    detalleVisible.value = false;
}

/**
 * Elimina un médico-producto de la tabla local de detalles por índice.
 *
 * @param {number} index - Índice del elemento a eliminar en detallesLocales
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
function quitarProductoDetalle(index) {
    detallesLocales.value.splice(index, 1);
}

/**
 * Previsualiza y guarda el detalle de médicos-producto del contrato.
 *
 * Antes de escribir pide al backend el diff y muestra al usuario cuántos
 * registros se agregan, cambian o quedan sin aplicar. Las filas que no vienen
 * en la lista solo se retiran si el usuario lo confirma de forma explícita.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-30
 */
async function guardarDetalles() {
    if (loadingGuardarDetalle.value) return;
    if (!detallesLocales.value.length) {
        toastMessage({
            heading: "Atención",
            text: "Agregue al menos un médico-producto",
            icon: "warning",
        });
        return;
    }

    loadingGuardarDetalle.value = true;
    try {
        const payload = {
            medicos: detallesLocales.value.map((d) => ({
                medico_id: d.medico_id,
                producto_id: d.producto_id,
                valor_consulta: d.valor_consulta ?? 0,
                valor_urgencias: d.valor_urgencias ?? 0,
                fecha_ingreso: d.fecha_ingreso || null,
            })),
        };

        const url = `/api/contratos/${contratoSeleccionado.value.id}/medicos`;
        const prev = await axios.post(`${url}/preview`, payload);
        const diff = prev.data.data;

        if (diff.errores?.length) {
            toastMessage({
                heading: "Error",
                text: diff.errores.join(" "),
                icon: "error",
            });
            return;
        }

        const resumen = `Se agregarán ${diff.altas?.length ?? 0} y se actualizarán ${diff.cambios?.length ?? 0}.`;

        if (diff.ausentes?.length) {
            const texto =
                `Hay ${diff.ausentes?.length ?? 0} registro(s) guardados que no están en la lista. ` +
                `Si los retira, se marcarán como inactivos; ` +
                `si cancela, quedarán activos con su tarifa anterior, ` +
                `pero las altas y cambios sí se guardarán. ` +
                `¿Desea retirarlos? (Sí, continuar = retirarlos; Cancelar = guardar sin retirar)`;

            const retirar = await confirmAction({
                title: "Registros que no están en la lista",
                text: resumen + " " + texto,
                icon: "warning",
            });

            payload.retirar_faltantes = retirar;
        } else if (
            !(await confirmAction({
                title: "Confirmar cambios",
                text: resumen + " ¿Desea aplicarlos?",
                icon: "question",
            }))
        ) {
            return;
        }

        await axios.put(url, payload);

        toastMessage({
            heading: "Ok",
            text: "Médicos-producto guardados correctamente",
            icon: "success",
        });

        const res = await axios.get(`/api/contratos/${contratoSeleccionado.value.id}`);
        contratoSeleccionado.value = res.data.data;
        detallesLocales.value = mapearMedicosDetalle(res.data.data.medicos || []);
        await cargarCobertura(contratoSeleccionado.value.id);
        await listar();
    } catch (_error) {
        const msg =
            _error.response?.data?.message ||
            Object.values(_error.response?.data?.errors || {})
                .flat()
                .join(", ");
        toastMessage({ heading: "Error", text: msg || "Error de validación", icon: "error" });
    } finally {
        loadingGuardarDetalle.value = false;
    }
}

// ─── IMPORTAR DESDE EXCEL ────────────────────────────────────────────────────

/**
 * Referencia al input de archivo del modal de importación
 * @type {import('vue').Ref<HTMLInputElement|null>}
 */
const archivoImportarRef = ref(null);

/**
 * Formulario del modal de importación
 * @type {import('vue').Ref<Object>}
 */
const importarForm = ref({
    contratoId: null,
    contratoLabel: "",
    medico_id: null,
});

/**
 * Flag de carga al procesar importación
 * @type {import('vue').Ref<boolean>}
 */
const loadingImportar = ref(false);

/**
 * Descarga la plantilla de detalle del contrato para cargar médicos-producto.
 * Incluye los productos configurados en el contrato con las columnas de
 * valores vacías, y el Contrato ID en la celda C2 para validación.
 *
 * @param {Object} item - Contrato con id y sede
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
function descargarPlantilla(item) {
    window.open(`/api/contratos/${item.id}/plantilla-detalle`, "_blank");
}

/**
 * Abre el modal de importación de médicos-producto para un contrato específico.
 * Puebla el formulario con la información del contrato.
 *
 * @param {Object} item - Contrato con id, sede y otros datos
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
function abrirModalImportar(item) {
    importarForm.value = {
        contratoId: item.id,
        contratoLabel: `Contrato #${item.id} - ${item.sede?.nom_sede || "N/A"} (${formatDate(item.fecha_inicio)} - ${formatDate(item.fecha_fin)})`,
        medico_id: null,
    };
    if (archivoImportarRef.value) {
        archivoImportarRef.value.value = "";
    }
    $("#modalImportar").modal("show");
}

/**
 * Cierra el modal de importación de médicos-producto.
 * Usa el API jQuery de Bootstrap para garantizar el cierre sin depender de data-dismiss.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
function cerrarModalImportar() {
    $("#modalImportar").modal("hide");
}

/**
 * Handler: se ejecuta cuando el usuario selecciona un médico en el Select2 del modal.
 *
 * @param {string|null} id - ID del médico seleccionado
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
function onMedicoSelectDetalleImport(id) {
    importarForm.value.medico_id = id;
}

/**
 * Construye el texto de confirmación para los CUPS faltantes detectados.
 * Muestra máximo 10 códigos y agrega un resumen del resto.
 *
 * @param {Array<{codigo: string, actividad: string}>} faltantes - Productos CUPS no encontrados en el catálogo
 * @returns {string} Texto formateado para el diálogo de confirmación
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
function construirTextoFaltantes(faltantes) {
    const MAX = 10;
    const visibles = faltantes.slice(0, MAX);
    const lista = visibles
        .map((f) => `${f.codigo}${f.actividad ? " - " + f.actividad : ""}`)
        .join("\n");
    const restantes = faltantes.length - visibles.length;
    const sufijo = restantes > 0 ? `\n... y ${restantes} más` : "";
    return `${faltantes.length} código(s) CUPS no existen en el catálogo:\n\n${lista}${sufijo}\n\n¿Desea crearlos automáticamente en el catálogo y continuar con la importación?`;
}

/**
 * Procesa la importación de médicos-producto desde un archivo Excel.
 * Envía el archivo y el medico_id al backend. Si el backend detecta CUPS
 * inexistentes en el catálogo, pregunta al usuario si desea crearlos y reintenta
 * con crear_faltantes=1. Tras éxito refresca el sidebar de detalle si está abierto.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
async function procesarImportacion() {
    if (loadingImportar.value) return;

    if (!importarForm.value.medico_id) {
        toastMessage({ heading: "Atención", text: "Seleccione un médico", icon: "warning" });
        return;
    }

    const file = archivoImportarRef.value?.files?.[0];
    if (!file) {
        toastMessage({ heading: "Atención", text: "Seleccione un archivo Excel", icon: "warning" });
        return;
    }

    loadingImportar.value = true;
    try {
        let crearFaltantes = false;
        let intentos = 0;
        const MAX_INTENTOS = 2;

        while (intentos < MAX_INTENTOS) {
            intentos++;
            const formData = new FormData();
            formData.append("archivo", file);
            formData.append("medico_id", importarForm.value.medico_id);
            if (crearFaltantes) {
                formData.append("crear_faltantes", "1");
            }

            const res = await axios.post(
                `/api/contratos/${importarForm.value.contratoId}/importar-medicos`,
                formData,
                {
                    headers: { "Content-Type": "multipart/form-data" },
                }
            );

            // Pre-validación: preguntar al usuario sobre los CUPS faltantes
            if (res.data?.requiere_confirmacion) {
                const ok = await confirmAction({
                    title: "Productos no encontrados",
                    text: construirTextoFaltantes(res.data.data?.faltantes || []),
                    icon: "warning",
                });
                if (!ok) {
                    toastMessage({
                        heading: "Información",
                        text: "Importación cancelada. Ningún dato fue modificado.",
                        icon: "info",
                    });
                    return;
                }
                crearFaltantes = true;
                continue;
            }

            toastMessage({
                heading: "Ok",
                text: "Detalle importado correctamente",
                icon: "success",
            });
            $("#modalImportar").modal("hide");

            // Refrescar sidebar de detalle si está abierto para este contrato
            if (contratoSeleccionado.value?.id === importarForm.value.contratoId) {
                const detalle = await axios.get(`/api/contratos/${importarForm.value.contratoId}`);
                contratoSeleccionado.value = detalle.data.data;
                detallesLocales.value = mapearMedicosDetalle(detalle.data.data.medicos || []);
            }
            await listar();
            break;
        }
    } catch (error) {
        const msg = error.response?.data?.message || "Error al importar el archivo";
        toastMessage({ heading: "Error", text: msg, icon: "error" });
    } finally {
        loadingImportar.value = false;
    }
}

// ─── LIFECYCLE ────────────────────────────────────────────────────────────────

/**
 * Lifecycle onMounted: inicializa la sede, carga catálogos y listado.
 * Los catálogos se cargan UNA VEZ aquí (patrón AsignarEstudio),
 * no al abrir el drawer, para que Select2 ya tenga datos disponibles.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
onMounted(() => {
    sedeActual.value = getSedeActual();
    cargarCatalogos();
    listar();
});

/**
 * Lifecycle onBeforeUnmount: destruye la DataTable para evitar memory leaks.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
onBeforeUnmount(() => {
    safeDestroyDataTable(dt.value || "#example23");
});
</script>
<style scoped>
.right-sidebar {
    position: fixed;
    right: -70%;
    width: 70%;
    display: none;
    z-index: 1100;
    background: #ffffff;
    top: 0px;
    padding-bottom: 20px;
    height: 100%;
    -webkit-box-shadow: 5px 1px 40px rgba(0, 0, 0, 0.1);
    box-shadow: 5px 1px 40px rgba(0, 0, 0, 0.1);
    -webkit-transition: all 0.3s ease;
    -o-transition: all 0.3s ease;
    transition: all 0.3s ease;
}

.shw-rside {
    right: 0px;
    width: 70%;
    display: block;
}

.sidebar-scroll {
    overflow-x: hidden;
    overflow-y: scroll;
    height: 100%;
}
</style>
