<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Producto</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Citas</a>
                    </li>
                    <li class="breadcrumb-item active">Recomendaciones por Producto</li>
                </ol>
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
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-filter"></i> Filtros de búsqueda
                        </h4>
                        <form action="#">
                            <div class="form-body">
                                <div class="row p-t-2">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Sede</label>
                                            <select
                                                v-model="filtro.sede_id"
                                                class="form-control"
                                                @change="
                                                    filtro.servicio_cita_id = '';
                                                    filtrarServicios();
                                                    cargarProductos();
                                                    buscar();
                                                "
                                            >
                                                <option value="">Todas</option>
                                                <option
                                                    v-for="s in sedes"
                                                    :key="s.id"
                                                    :value="s.id"
                                                >
                                                    {{ s.nom_sede }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Servicio</label>
                                            <select
                                                v-model="filtro.servicio_cita_id"
                                                class="form-control"
                                                @change="
                                                    cargarProductos();
                                                    buscar();
                                                "
                                            >
                                                <option value="">Todos</option>
                                                <option
                                                    v-for="s in serviciosFiltrados"
                                                    :key="s.id"
                                                    :value="s.id"
                                                >
                                                    {{ s.nombre }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group" id="filter-producto-parent">
                                            <label class="control-label">Producto</label>
                                            <Select2
                                                :value="filtro.producto_id"
                                                :options="filterProductoOptions"
                                                width="100%"
                                                :allow-clear="true"
                                                @input="onFilterProductoSelect"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <button
                                            type="button"
                                            class="btn btn-success m-r-5"
                                            @click="buscar()"
                                            :disabled="loadingBuscar"
                                        >
                                            <i
                                                class="fa"
                                                :class="
                                                    loadingBuscar
                                                        ? 'fa-spinner fa-spin'
                                                        : 'fa-search'
                                                "
                                            ></i>
                                            Buscar
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-inverse"
                                            @click="btnLimpiar()"
                                        >
                                            <i class="fa fa-refresh"></i> Limpiar
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-primary m-l-5"
                                            @click="mostrarRegistro()"
                                        >
                                            <i class="fa fa-plus"></i> Nuevo
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 table-responsive">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Listado de recomendaciones</h4>
                        <h6 class="card-subtitle text-info">
                            Texto enviado al paciente junto con la confirmación de su cita.
                        </h6>

                        <loading-spinner :visible="loadingBuscar" />

                        <div :class="{ 'd-none': loadingBuscar }">
                            <div class="table-responsive">
                                <table
                                    id="tabla-recomendaciones"
                                    ref="dt"
                                    class="table table-bordered table-striped"
                                    cellspacing="0"
                                    width="100%"
                                >
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th>Recomendación</th>
                                            <th>Estado</th>
                                            <th class="sr-only">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in registros" :key="item.id">
                                            <td>
                                                <strong>{{
                                                    item.producto?.nom_produc || "—"
                                                }}</strong
                                                ><br />
                                                <small class="text-muted">{{
                                                    item.producto?.cod_cups
                                                }}</small>
                                            </td>
                                            <td>
                                                <div
                                                    style="max-width: 500px; white-space: pre-wrap"
                                                >
                                                    {{ item.recomendacion || "—" }}
                                                </div>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge"
                                                    :class="
                                                        item.estado
                                                            ? 'badge-success'
                                                            : 'badge-secondary'
                                                    "
                                                >
                                                    {{ item.estado ? "Activo" : "Inactivo" }}
                                                </span>
                                            </td>
                                            <td class="text-nowrap">
                                                <button
                                                    type="button"
                                                    class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                    @click="mostrarRegistro(item)"
                                                    title="Editar"
                                                >
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                    @click="eliminarRegistro(item.id)"
                                                    :disabled="loadingEliminarId === item.id"
                                                    title="Eliminar"
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
                                        <tr v-if="registros.length === 0">
                                            <td colspan="4" class="text-center text-muted">
                                                No hay recomendaciones registradas
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Page Content -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Right sidebar -->
        <!-- ============================================================== -->
        <div
            class="right-sidebar"
            id="right-sidebar"
            style="overflow-x: hidden; overflow-y: scroll; height: 100%"
        >
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ editando ? "Editar recomendación" : "Nueva recomendación" }}
                    <span>
                        <i
                            class="ti-close right-side-toggle"
                            id="btnCerralModalForm"
                            @click="cerrarModal()"
                        ></i>
                    </span>
                </div>
                <div class="r-panel-body">
                    <form action="#">
                        <div class="form-body">
                            <div class="form-group" id="form-producto-parent">
                                <label class="control-label">Producto *</label>
                                <Select2
                                    :value="form.producto_id"
                                    :options="formProductoOptions"
                                    width="100%"
                                    :allow-clear="false"
                                    :disabled="editando"
                                    @input="onFormProductoSelect"
                                />
                                <span class="text-danger" v-if="errores.producto_id">{{
                                    errores.producto_id[0]
                                }}</span>
                            </div>
                            <div class="form-group">
                                <label class="control-label">Recomendación</label>
                                <FormattedTextEditor
                                    v-model="form.recomendacion"
                                    rows="8"
                                    placeholder="Texto de recomendación para el paciente. Use saltos de línea si es necesario."
                                />
                                <small class="text-muted"
                                    >Variables disponibles: <code>${num_docu_paciente}</code>
                                    <code>${nom_paciente}</code> <code>${cod_cups}</code>
                                    <code>${nom_produc}</code></small
                                >
                            </div>
                            <div class="form-group">
                                <label class="control-label">Estado</label>
                                <select v-model="form.estado" class="form-control">
                                    <option :value="true">Activo</option>
                                    <option :value="false">Inactivo</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-actions mt-3">
                            <button type="button" class="btn btn-inverse" @click="cerrarModal()">
                                Cancelar
                            </button>
                            <button
                                type="button"
                                class="btn btn-success m-l-5"
                                @click="guardar()"
                                :disabled="loadingGuardar"
                            >
                                <i
                                    class="fa"
                                    :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-check'"
                                ></i>
                                {{ loadingGuardar ? "Guardando..." : "Guardar" }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";

const dt = ref(null);

import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { alertSuccess, alertError, confirmAction } from "@/helpers/alerts";
import FormattedTextEditor from "@/components/shared/FormattedTextEditor.vue";
import LoadingSpinner from "@/components/comunes/LoadingSpinner.vue";
import axios from "axios";

const registros = ref([]);
const productos = ref([]);
const sedes = ref([]);
const servicios = ref([]);
const serviciosFiltrados = ref([]);
const filtro = ref({
    sede_id: "",
    servicio_cita_id: "",
    producto_id: "",
});
const form = ref({
    id: null,
    producto_id: "",
    recomendacion: "",
    estado: true,
});
const errores = ref({});
const editando = ref(false);
const loadingBuscar = ref(false);
const loadingGuardar = ref(false);
const loadingEliminarId = ref(null);

const filterProductoOptions = computed(() => {
    return (productos.value || []).map((p) => ({
        id: String(p.id),
        cod_cups: p.cod_cups,
        nom_produc: p.nom_produc,
    }));
});
const formProductoOptions = computed(() => {
    return (productos.value || []).map((p) => ({
        id: String(p.id),
        cod_cups: p.cod_cups,
        nom_produc: p.nom_produc,
    }));
});

/**
 * Carga la lista de sedes activas desde la API.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-06-11
 */
async function cargarSedes() {
    try {
        const res = await axios.get("/api/config-sedes/listar/sedes/activas");
        sedes.value = res.data?.data || [];
    } catch (error) {}
}
/**
 * Carga la lista de servicios de citas disponibles y aplica el filtro inicial.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-06-11
 */
async function cargarServicios() {
    try {
        const res = await axios.get("/api/citas/servicios");
        servicios.value = res.data?.data || [];
        filtrarServicios();
    } catch (error) {}
}
/**
 * Filtra la lista de servicios según la sede seleccionada en el filtro.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-06-11
 */
function filtrarServicios() {
    if (filtro.value.sede_id) {
        serviciosFiltrados.value = servicios.value.filter((s) => s.sede_id == filtro.value.sede_id);
    } else {
        serviciosFiltrados.value = servicios.value;
    }
}
/**
 * Carga los productos asociados al servicio y sede seleccionados en el filtro.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-06-11
 */
async function cargarProductos() {
    try {
        const servicioId = filtro.value.servicio_cita_id;
        const sedeId = filtro.value.sede_id;

        if (!servicioId) {
            productos.value = [];
            return;
        }

        var url = "/api/citas/productos/" + servicioId;
        if (sedeId) url += "?sede_id=" + sedeId;

        const res = await axios.get(url);
        productos.value = res.data?.data || [];
    } catch (error) {
        productos.value = [];
    }
}
/**
 * Maneja la selección de producto en el filtro y ejecuta la búsqueda.
 *
 * @param {string|null} value - Identificador del producto seleccionado.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-06-11
 */
function onFilterProductoSelect(value) {
    filtro.value.producto_id = value || "";
    buscar();
}
/**
 * Maneja la selección de producto en el formulario de registro/edición.
 *
 * @param {string|null} value - Identificador del producto seleccionado.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-06-11
 */
function onFormProductoSelect(value) {
    form.value.producto_id = value || "";
}
/**
 * Consulta las recomendaciones de producto aplicando los filtros y reinicializa el DataTable.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-06-11
 */
async function buscar() {
    if (loadingBuscar.value) return;
    loadingBuscar.value = true;
    try {
        const params = {};
        if (filtro.value.producto_id) params.producto_id = filtro.value.producto_id;
        const res = await axios.get("/api/config-recomendacion-productos", { params });
        safeDestroyDataTable(dt.value || "#tabla-recomendaciones");
        registros.value = res.data?.data || [];
        nextTick(() => {
            safeInitDataTable(dt.value || "#tabla-recomendaciones", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
            });
        });
    } catch (error) {
        registros.value = [];
    } finally {
        loadingBuscar.value = false;
    }
}
/**
 * Registra o actualiza una recomendación de producto según el modo del formulario.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-06-11
 */
async function guardar() {
    if (loadingGuardar.value) return;
    loadingGuardar.value = true;
    errores.value = {};
    try {
        if (!form.value.producto_id) {
            alertError("Advertencia", "Debe seleccionar un producto.");
            return;
        }
        let res;
        if (editando.value && form.value.id) {
            res = await axios.put("/api/config-recomendacion-productos/" + form.value.id, {
                recomendacion: form.value.recomendacion,
                estado: form.value.estado,
            });
        } else {
            res = await axios.post("/api/config-recomendacion-productos", {
                producto_id: form.value.producto_id,
                recomendacion: form.value.recomendacion,
                estado: form.value.estado,
            });
        }
        alertSuccess("Éxito", res?.data?.message || "Recomendación guardada.");
        cerrarModal();
        await buscar();
    } catch (error) {
        errores.value = error?.response?.data?.errors || {};
    } finally {
        loadingGuardar.value = false;
    }
}
/**
 * Elimina una recomendación de producto después de confirmación del usuario.
 *
 * @param {number} id - Identificador de la recomendación a eliminar.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-06-11
 */
async function eliminarRegistro(id) {
    const ok = await confirmAction({
        title: "¿Está seguro?",
        text: "No podrá recuperar este registro.",
        icon: "warning",
    });
    if (!ok) return;
    if (loadingEliminarId.value) return;
    loadingEliminarId.value = id;
    try {
        const res = await axios.delete("/api/config-recomendacion-productos/" + id);
        alertSuccess("Éxito", res?.data?.message || "Recomendación eliminada.");
        await buscar();
    } catch (error) {
    } finally {
        loadingEliminarId.value = null;
    }
}
/**
 * Prepara y abre el panel lateral con el formulario para crear o editar una recomendación.
 *
 * @param {Object|null} item - Registro a editar o null para crear uno nuevo.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-06-11
 */
function mostrarRegistro(item) {
    errores.value = {};
    if (item) {
        editando.value = true;
        form.value = {
            id: item.id,
            producto_id: item.producto_id,
            recomendacion: item.recomendacion || "",
            estado: item.estado,
        };
    } else {
        editando.value = false;
        form.value = {
            id: null,
            producto_id: "",
            recomendacion: "",
            estado: true,
        };
    }
    abrirModal();
}
/**
 * Muestra el panel lateral derecho deslizándolo hacia la vista.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-06-11
 */
function abrirModal() {
    $(".right-sidebar").addClass("shw-rside");
}
/**
 * Oculta el panel lateral derecho y resetea el estado del formulario.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-06-11
 */
function cerrarModal() {
    $(".right-sidebar").removeClass("shw-rside");
}
/**
 * Limpia todos los filtros de búsqueda, recarga los servicios y productos, y ejecuta la búsqueda.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-06-11
 */
function btnLimpiar() {
    filtro.value = { sede_id: "", servicio_cita_id: "", producto_id: "" };
    serviciosFiltrados.value = servicios.value;
    cargarProductos();
    buscar();
}

onMounted(() => {
    cargarSedes();
    cargarServicios();
    cargarProductos();
    buscar();
});

onBeforeUnmount(() => {
    safeDestroyDataTable(dt.value || "#tabla-recomendaciones");
});
</script>
<style scoped>
.right-sidebar {
    position: fixed;
    right: -80%;
    width: 80%;
    display: none;
    z-index: 180;
    background: #ffffff;
    top: 0px;
    padding-bottom: 20px;
    height: 80%;
    -webkit-box-shadow: 5px 1px 40px rgba(0, 0, 0, 0.1);
    box-shadow: 5px 1px 40px rgba(0, 0, 0, 0.1);
    -webkit-transition: all 0.3s ease;
    -o-transition: all 0.3s ease;
    transition: all 0.3s ease;
}

.shw-rside {
    right: 0px;
    width: 80%;
    display: block;
}
</style>
