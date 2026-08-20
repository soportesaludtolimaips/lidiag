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
                                                title="Ver detalle"
                                            >
                                                <i class="fa fa-list"></i>
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
        <div class="right-sidebar" style="width: 450px">
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
    </div>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, computed } from "vue";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { toastMessage, confirmAction } from "@/helpers/alerts.js";
import axios from "axios";
import Select2 from "@/components/shared/Select2.vue";

/**
 * Referencia a la tabla DataTable
 * @type {import('vue').Ref<HTMLElement|null>}
 */
const dt = ref(null);

/** Listado de contratos */
const registros = ref([]);

/** Formulario del contrato */
const registro = ref({
    id: 0,
    sede_id: "",
    fecha_inicio: "",
    fecha_fin: "",
    observaciones: "",
    estado: 1,
});

/** Título dinámico del sidebar de crear/editar */
const tituloModal = ref("Nuevo contrato");

/** Flag: true = actualizar, false = crear */
const actualizar = ref(false);

/** Errores de validación del formulario principal */
const errores = ref({});

/** Flags de carga */
const loadingListar = ref(false);
const loadingGuardar = ref(false);
const loadingEliminarId = ref(null);

// ─── SEDE ACTUAL DESDE SESSION STORAGE ────────────────────────────────────────

/**
 * Obtiene la sede actual desde sessionStorage (key: ST-sede)
 * @returns {{ id: number, nom_sede: string } | null}
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

const sedeActual = ref(getSedeActual());

/**
 * Nombre legible de la sede actual para el formulario
 * @returns {string}
 */
const sedeNombreActual = computed(() => sedeActual.value?.nom_sede ?? "No disponible");

// ─── DETALLE ──────────────────────────────────────────────────────────────────

/** Contrato seleccionado para ver/editar detalle */
const contratoSeleccionado = ref(null);

/** Detalle local: array de productos con valores */
const detallesLocales = ref([]);

/** Médico seleccionado (id) en Select2 */
const medicoSeleccionado = ref(null);

/** Producto seleccionado (id) en Select2 */
const productoSeleccionado = ref(null);

/** Catálogo de médicos (reg_med != null) */
const medicos = ref([]);

/** Catálogo completo de productos */
const productos = ref([]);

/** Flag de carga al guardar detalle */
const loadingGuardarDetalle = ref(false);

// ─── COMPUTED: OPTIONS PARA SELECT2 ──────────────────────────────────────────

/**
 * Opciones formateadas para Select2 de médicos
 * @returns {Array<{id: number, text: string}>}
 */
const medicoOptions = computed(() =>
    medicos.value.map((m) => ({
        id: m.id,
        text: `${m.name} ${m.reg_med ? "(" + m.reg_med + ")" : ""}`,
    }))
);

/**
 * Opciones formateadas para Select2 de productos
 * @returns {Array<{id: number, text: string}>}
 */
const productoOptions = computed(() =>
    productos.value.map((p) => ({
        id: p.id,
        text: `${p.cod_cups} - ${p.nom_produc}`,
    }))
);

// ─── UTILIDADES ───────────────────────────────────────────────────────────────

/**
 * Formatea una fecha ISO a formato local colombiano
 * @param {string|null} dateStr
 * @returns {string}
 */
function formatDate(dateStr) {
    if (!dateStr) return "";
    return new Date(dateStr).toLocaleDateString("es-CO");
}

/**
 * Formatea un número a moneda COP
 * @param {number|null} value
 * @returns {string}
 */
function formatMoney(value) {
    if (!value) return "$0";
    return new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP" }).format(value);
}

// ─── LISTADO PRINCIPAL ────────────────────────────────────────────────────────

/**
 * Carga el listado de contratos y renderiza la DataTable
 * @returns {Promise<void>}
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
 * Si se pasa item, se carga en modo edición.
 * @param {Object|null} item - Contrato existente o null para crear nuevo
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
 * Cierra el sidebar de crear/editar contrato
 */
function btnCerralModalForm() {
    $(".right-sidebar").removeClass("shw-rside");
}

/**
 * Guarda o actualiza el contrato actual
 * @returns {Promise<void>}
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
 * Elimina un contrato tras confirmación del usuario
 * @param {Object} item - Contrato a eliminar
 * @returns {Promise<void>}
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
 * Carga los catálogos de médicos y productos para los Select2 del detalle
 * @returns {Promise<void>}
 */
async function cargarCatalogos() {
    try {
        const [medRes, prodRes] = await Promise.all([
            axios.get("/api/seguridad-usuarios", { params: { per_page: 500 } }),
            axios.get("/api/config-productos", { params: { per_page: 500 } }),
        ]);
        medicos.value = (medRes.data.data?.data || medRes.data.data || []).filter((u) => u.reg_med);
        productos.value = prodRes.data.data?.data || prodRes.data.data || [];
    } catch (e) {
        console.error(e);
    }
}

// ─── DETALLE: SIDEBAR ─────────────────────────────────────────────────────────

/**
 * Abre el sidebar de detalle de un contrato.
 * Carga catálogos y puebla detallesLocales desde el contrato existente.
 * @param {Object} contrato
 */
async function abrirDetalle(contrato) {
    contratoSeleccionado.value = contrato;
    await cargarCatalogos();
    // Poblar detallesLocales desde el contrato existente
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
    $(".right-sidebar").addClass("shw-rside");
}

/**
 * Cierra el sidebar de detalle y limpia el estado
 */
function cerrarDetalle() {
    contratoSeleccionado.value = null;
    detallesLocales.value = [];
    medicoSeleccionado.value = null;
    productoSeleccionado.value = null;
    $(".right-sidebar").removeClass("shw-rside");
}

/**
 * Handler: cuando se selecciona un médico en el Select2
 * @param {number|null} id
 */
function onMedicoSelect(id) {
    medicoSeleccionado.value = id;
}

/**
 * Handler: cuando se selecciona un producto en el Select2.
 * Agrega el producto a la tabla local si no existe.
 * @param {number|null} id
 */
function onProductoSelect(id) {
    if (!id) return;
    // Evitar duplicados por producto_id
    const yaExiste = detallesLocales.value.some((d) => d.producto_id === id);
    if (yaExiste) {
        toastMessage("Este producto ya fue agregado", "warning");
        productoSeleccionado.value = null;
        return;
    }
    const prod = productos.value.find((p) => p.id === id);
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
 * Elimina un producto de la tabla local por índice
 * @param {number} index
 */
function quitarProductoDetalle(index) {
    detallesLocales.value.splice(index, 1);
}

/**
 * Guarda todos los detalles del contrato (sync completo) hacia el backend
 * @returns {Promise<void>}
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
        // Recargar contrato actualizado
        const res = await axios.get(`/api/contratos/${contratoSeleccionado.value.id}`);
        contratoSeleccionado.value = res.data.data;
        // Poblar detallesLocales de nuevo
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

// ─── LIFECYCLE ────────────────────────────────────────────────────────────────

onMounted(() => {
    sedeActual.value = getSedeActual();
    listar();
});

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
    overflow-y: auto;
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
