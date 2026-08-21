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
                    class="btn btn-success btn-sm m-r-5"
                    @click="descargarPlantillaGeneral()"
                    title="Descargar plantilla general (sin contrato específico)"
                >
                    <i class="fa fa-file-excel-o m-r-5"></i> Plantilla General
                </button>
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
                                        <th>Detalle</th>
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
                                                {{ item.detalles?.length || 0 }} productos
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
                                                title="Descargar plantilla Excel para este contrato"
                                            >
                                                <i class="fa fa-file-excel-o"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm m-r-5"
                                                @click="abrirModalImportar(item)"
                                                title="Importar detalles desde Excel"
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
        <div class="right-sidebar sidebar-scroll" style="width: 450px">
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
                    <form action="#" @submit.prevent>
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
        <div class="right-sidebar sidebar-scroll" style="width: 750px" v-if="contratoSeleccionado">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    Detalle: {{ contratoSeleccionado.sede?.nom_sede }}
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
                    <!-- Médico -->
                    <h5 class="card-title text-info"><i class="fa fa-user"></i> MÉDICO</h5>
                    <hr />
                    <div class="row">
                        <div class="col-md-12">
                            <Select2
                                :value="medicoSeleccionado"
                                :options="medicoOptions"
                                :label-fields="['name']"
                                width="100%"
                                :allow-clear="true"
                                placeholder="Buscar médico..."
                                @input="onMedicoSelect"
                            />
                        </div>
                    </div>

                    <!-- Productos -->
                    <h5 class="card-title text-info m-t-40">
                        <i class="fa fa-plus-circle"></i> PRODUCTOS
                    </h5>
                    <hr />
                    <div class="row">
                        <div class="col-md-12">
                            <Select2
                                :value="productoSeleccionado"
                                :options="productoOptions"
                                width="100%"
                                :allow-clear="true"
                                placeholder="Buscar producto..."
                                @input="onProductoSelect"
                            />
                        </div>
                    </div>

                    <!-- Tabla de productos -->
                    <div class="col-md-12 table-responsive table-scroll mt-3">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Val Contratado</th>
                                    <th>Val Consulta</th>
                                    <th>Val Urgencias</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(item, index) in detallesLocales"
                                    :key="item.producto_id"
                                >
                                    <td>{{ item.cod_cups }}</td>
                                    <td>{{ item.nom_produc }}</td>
                                    <td>
                                        <input
                                            type="number"
                                            v-model.number="item.valor_contrato"
                                            class="form-control"
                                            step="0.01"
                                            min="0"
                                        />
                                    </td>
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
                                    <td colspan="6" class="text-center text-muted">
                                        No hay productos agregados
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
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="modalImportarLabel">
                            <i class="fa fa-upload"></i> Importar Detalles de Contrato
                        </h5>
                        <button
                            type="button"
                            class="close text-white"
                            data-dismiss="modal"
                            aria-label="Cerrar"
                        >
                            <span aria-hidden="true">&times;</span>
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
                                Médico <span class="text-danger">*</span>
                            </label>
                            <Select2
                                :value="importarForm.medico_id"
                                :options="medicoOptions"
                                :label-fields="['name']"
                                width="100%"
                                :allow-clear="true"
                                placeholder="Seleccionar médico..."
                                @input="onImportMedicoSelect"
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
                                Formato: Código CUPS | Sede | Valor Contrato | Valor Consulta |
                                Valor Urgencias
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-success"
                            @click="procesarImportacion()"
                            :disabled="loadingImportar"
                        >
                            <i v-if="loadingImportar" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-upload"></i>
                            Importar
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            Cancelar
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
 * Lista local de detalles del contrato en edición.
 * Cada elemento: { id, medico_id, producto_id, cod_cups, nom_produc, valor_contrato, valor_consulta, valor_urgencias }
 * @type {import('vue').Ref<Array>}
 */
const detallesLocales = ref([]);

/**
 * ID del médico seleccionado en el Select2 de médicos
 * @type {import('vue').Ref<string|null>}
 */
const medicoSeleccionado = ref(null);

/**
 * ID del producto seleccionado en el Select2 de productos
 * @type {import('vue').Ref<string|null>}
 */
const productoSeleccionado = ref(null);

/**
 * Catálogo de usuarios médicos (con reg_med no nulo)
 * @type {import('vue').Ref<Array>}
 */
const medicos = ref([]);

/**
 * Catálogo completo de productos (config_productos)
 * @type {import('vue').Ref<Array>}
 */
const productos = ref([]);

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
 * @returns {Array<{id: string, name: string, reg_med: string}>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
const medicoOptions = computed(() =>
    medicos.value.map((m) => ({
        id: String(m.id),
        name: m.name,
    }))
);

/**
 * Opciones formateadas para Select2 de productos.
 * Convierte IDs a String para compatibilidad con Select2.
 *
 * @returns {Array<{id: string, cod_cups: string, nom_produc: string}>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
const productoOptions = computed(() =>
    productos.value.map((p) => ({
        id: String(p.id),
        cod_cups: p.cod_cups || "",
        nom_produc: p.nom_produc || "",
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
 * Formatea un número a moneda colombiana (COP)
 *
 * @param {number|null} value - Valor numérico a formatear
 * @returns {string} Cadena con formato de moneda o "$0"
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
function formatMoney(value) {
    if (!value) return "$0";
    return new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP" }).format(value);
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
    } catch (e) {
        console.error(e);
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
    $(".right-sidebar").slideDown(50);
    $(".right-sidebar").addClass("shw-rside");
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
    $(".right-sidebar").removeClass("shw-rside");
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
            toastMessage("Contrato actualizado correctamente");
        } else {
            await axios.post("/api/contratos", payload);
            toastMessage("Contrato creado correctamente");
        }
        btnCerralModalForm();
        await listar();
    } catch (e) {
        if (e.response?.status === 422) {
            errores.value = e.response.data.errors || {};
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
    const confirmed = await confirmAction("¿Está seguro de eliminar este contrato?");
    if (!confirmed) return;
    loadingEliminarId.value = item.id;
    try {
        await axios.delete(`/api/contratos/${item.id}`);
        toastMessage("Contrato eliminado correctamente");
        await listar();
    } catch (e) {
        console.error(e);
    } finally {
        loadingEliminarId.value = null;
    }
}

// ─── CATÁLOGOS ────────────────────────────────────────────────────────────────

/**
 * Carga los catálogos de médicos y productos para los Select2 del detalle.
 * Se ejecuta una vez en onMounted (patrón AsignarEstudio).
 * Médicos: usuarios con reg_med no nulo.
 * Productos: todos los config_productos activos.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
async function cargarCatalogos() {
    try {
        const moduloId = sessionStorage.getItem("ST-modulo") || "1";
        const [medRes, prodRes] = await Promise.all([
            axios.get(`/api/user.listarUsuarios/Medico/${moduloId}`),
            axios.get("/api/config-productos"),
        ]);
        medicos.value = medRes.data.data || [];
        productos.value = prodRes.data.data || [];
    } catch (e) {
        console.error("Error cargando catálogos:", e);
        const msg = e.response?.data?.message || e.message || "Error desconocido";
        toastMessage("Error cargando médicos/productos: " + msg, "error");
    }
}

// ─── DETALLE: SIDEBAR ─────────────────────────────────────────────────────────

/**
 * Abre el sidebar de detalle de un contrato.
 * Puebla detallesLocales desde el contrato existente y muestra el sidebar.
 * Los catálogos ya se cargaron en onMounted.
 *
 * @param {Object} contrato - Contrato con su sede y detalles
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
async function abrirDetalle(contrato) {
    contratoSeleccionado.value = contrato;
    detallesLocales.value = (contrato.detalles || []).map((d) => ({
        id: d.id,
        medico_id: d.medico_id,
        producto_id: d.producto_id,
        cod_cups: d.producto?.cod_cups || "",
        nom_produc: d.producto?.nom_produc || "",
        valor_contrato: d.valor_contrato ?? 0,
        valor_consulta: d.valor_consulta ?? 0,
        valor_urgencias: d.valor_urgencias ?? 0,
    }));
    medicoSeleccionado.value = null;
    productoSeleccionado.value = null;
    await nextTick();
    $(".right-sidebar").addClass("shw-rside");
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
    detallesLocales.value = [];
    medicoSeleccionado.value = null;
    productoSeleccionado.value = null;
    $(".right-sidebar").removeClass("shw-rside");
}

/**
 * Handler: se ejecuta cuando el usuario selecciona un médico en el Select2.
 * Almacena el ID del médico seleccionado.
 *
 * @param {string|null} id - ID del médico seleccionado (String por Select2)
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
function onMedicoSelect(value) {
    let selectedId = null;
    if (Array.isArray(value) && value.length > 0) {
        const v = value[0];
        selectedId = String(typeof v === "object" && v && v.id != null ? v.id : v);
    } else if (value && typeof value === "object" && value.id != null) {
        selectedId = String(value.id);
    } else if (value != null) {
        selectedId = String(value);
    }
    medicoSeleccionado.value = selectedId || "";
}

/**
 * Handler: se ejecuta cuando el usuario selecciona un producto en el Select2.
 * Agrega el producto a la tabla local de detalles si no existe previamente.
 *
 * @param {string|null} id - ID del producto seleccionado (String por Select2)
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
function onProductoSelect(id) {
    if (!id) return;
    const yaExiste = detallesLocales.value.some((d) => String(d.producto_id) === String(id));
    if (yaExiste) {
        toastMessage("Este producto ya fue agregado", "warning");
        productoSeleccionado.value = null;
        return;
    }
    const prod = productos.value.find((p) => String(p.id) === String(id));
    if (!prod) return;
    detallesLocales.value.push({
        id: 0,
        medico_id: medicoSeleccionado.value || null,
        producto_id: prod.id,
        cod_cups: prod.cod_cups || "",
        nom_produc: prod.nom_produc || "",
        valor_contrato: 0,
        valor_consulta: 0,
        valor_urgencias: 0,
    });
    productoSeleccionado.value = null;
}

/**
 * Elimina un producto de la tabla local de detalles por índice.
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
 * Guarda todos los detalles del contrato (sync completo) hacia el backend.
 * Reemplaza todos los detalles existentes por la lista local actual.
 * Después de guardar, recarga el contrato y el listado principal.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
async function guardarDetalles() {
    if (loadingGuardarDetalle.value) return;
    if (!detallesLocales.value.length) {
        toastMessage("Agregue al menos un producto", "warning");
        return;
    }
    loadingGuardarDetalle.value = true;
    try {
        const payload = {
            detalles: JSON.stringify(
                detallesLocales.value.map((d) => ({
                    medico_id: d.medico_id,
                    producto_id: d.producto_id,
                    valor_contrato: d.valor_contrato,
                    valor_consulta: d.valor_consulta,
                    valor_urgencias: d.valor_urgencias,
                }))
            ),
        };
        await axios.put(`/api/contratos/${contratoSeleccionado.value.id}/detalles`, payload);
        toastMessage("Detalle guardado correctamente");
        const res = await axios.get(`/api/contratos/${contratoSeleccionado.value.id}`);
        contratoSeleccionado.value = res.data.data;
        detallesLocales.value = (res.data.data.detalles || []).map((d) => ({
            id: d.id,
            medico_id: d.medico_id,
            producto_id: d.producto_id,
            cod_cups: d.producto?.cod_cups || "",
            nom_produc: d.producto?.nom_produc || "",
            valor_contrato: d.valor_contrato ?? 0,
            valor_consulta: d.valor_consulta ?? 0,
            valor_urgencias: d.valor_urgencias ?? 0,
        }));
        await listar();
    } catch (e) {
        if (e.response?.status === 422) {
            const errs = e.response.data.errors || {};
            const msg = Object.values(errs).flat().join(", ");
            toastMessage(msg || "Error de validación", "error");
        }
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
 * Descarga la plantilla Excel para un contrato específico.
 * Abre el enlace de descarga en una nueva pestaña.
 *
 * @param {Object} item - Contrato con id y sede
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
function descargarPlantilla(item) {
    window.open(`/api/contratos/${item.id}/plantilla`, "_blank");
}

/**
 * Descarga la plantilla general (sin contrato específico).
 * Redirige a una ruta que genera una plantilla vacía.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
function descargarPlantillaGeneral() {
    toastMessage("Seleccione un contrato específico para descargar la plantilla", "info");
}

/**
 * Abre el modal de importación para un contrato específico.
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
 * Handler: se ejecuta cuando el usuario selecciona un médico en el Select2 del modal.
 *
 * @param {string|null} id - ID del médico seleccionado
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
function onImportMedicoSelect(id) {
    importarForm.value.medico_id = id;
}

/**
 * Procesa la importación de detalles desde un archivo Excel.
 * Envía el archivo y el medico_id al backend.
 * Si hay error (CUPS no existe), muestra el mensaje con número de fila.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
async function procesarImportacion() {
    if (loadingImportar.value) return;

    if (!importarForm.value.medico_id) {
        toastMessage("Seleccione un médico", "warning");
        return;
    }

    const file = archivoImportarRef.value?.files?.[0];
    if (!file) {
        toastMessage("Seleccione un archivo Excel", "warning");
        return;
    }

    loadingImportar.value = true;
    try {
        const formData = new FormData();
        formData.append("archivo", file);
        formData.append("medico_id", importarForm.value.medico_id);

        await axios.post(`/api/contratos/${importarForm.value.contratoId}/importar`, formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });

        toastMessage("Detalles importados correctamente");
        $("#modalImportar").modal("hide");
        await listar();
    } catch (e) {
        const msg = e.response?.data?.message || "Error al importar el archivo";
        toastMessage(msg, "error");
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
    right: -750px;
    width: 750px;
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
.sidebar-scroll {
    overflow-x: hidden;
    overflow-y: scroll;
    height: 100%;
}
.shw-rside {
    right: 0px;
    display: block;
}
.table-scroll {
    max-height: 400px;
    overflow-y: auto;
}
.table-scroll thead th {
    position: sticky;
    top: 0;
    z-index: 1;
    background: #fff;
}
</style>
