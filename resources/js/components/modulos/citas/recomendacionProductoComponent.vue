<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Producto</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="#">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="#">Citas</a>
                    </li>
                    <li class="breadcrumb-item active">Recomendaciones por Producto</li>
                </ol>
            </div>
        </div>

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
                                                    filtro.servicio_id = '';
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
                                                v-model="filtro.servicio_id"
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

        <!-- Backdrop overlay para cerrar al hacer clic afuera -->
        <div v-if="showSidebar" class="sidebar-backdrop" @click="cerrarModal()"></div>

        <div
            class="right-sidebar"
            id="right-sidebar"
            :class="{ 'shw-rside': showSidebar }"
            style="overflow-x: hidden; overflow-y: auto; height: 100%"
        >
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ editando ? "Editar recomendación" : "Nueva recomendación" }}
                    <button
                        type="button"
                        class="close right-side-toggle"
                        id="btnCerrarModalForm"
                        @click="cerrarModal()"
                        aria-label="Cerrar panel"
                        title="Cerrar (Esc)"
                    >
                        <i class="ti-close"></i>
                    </button>
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
                        <div
                            class="form-actions mt-3 d-flex justify-content-end align-items-center"
                        >
                            <button
                                type="button"
                                class="btn btn-inverse btn-action-clinical"
                                @click="cerrarModal()"
                                title="Cancelar cambios y cerrar panel (Esc)"
                            >
                                Cancelar
                                <span
                                    class="badge badge-light text-dark ml-1 d-none d-md-inline"
                                    style="font-size: 10px; opacity: 0.9"
                                    >Esc</span
                                >
                            </button>
                            <button
                                type="button"
                                class="btn btn-success m-l-5 btn-action-clinical"
                                @click="guardar()"
                                :disabled="loadingGuardar"
                                title="Guardar recomendación (Ctrl+S / Cmd+S)"
                            >
                                <i
                                    class="fa"
                                    :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-check'"
                                ></i>
                                {{ loadingGuardar ? "Guardando..." : "Guardar" }}
                                <span
                                    class="badge badge-light text-dark ml-1 d-none d-md-inline"
                                    style="font-size: 10px; opacity: 0.9"
                                    >Ctrl+S</span
                                >
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
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts";
import axios from "axios";

const registros = ref([]);
const productos = ref([]);
const sedes = ref([]);
const servicios = ref([]);
const serviciosFiltrados = ref([]);
const filtro = ref({
    sede_id: "",
    servicio_id: "",
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
const showSidebar = ref(false);

useKeyboardShortcuts({
    onSave: guardar,
    onEscape: cerrarModal,
    isActive: () => showSidebar.value,
    isLoading: loadingGuardar,
});

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

async function cargarSedes() {
    try {
        const res = await axios.get("/api/config-sedes/listar/sedes/activas");
        sedes.value = res.data?.data || [];
    } catch {
        alertError("Error", "No se pudieron cargar las sedes");
    }
}

async function cargarServicios() {
    try {
        const res = await axios.get("/api/citas/servicios");
        servicios.value = res.data?.data || [];
        filtrarServicios();
    } catch {
        alertError("Error", "No se pudieron cargar los servicios");
    }
}

function filtrarServicios() {
    if (filtro.value.sede_id) {
        serviciosFiltrados.value = servicios.value.filter((s) => s.sede_id == filtro.value.sede_id);
    } else {
        serviciosFiltrados.value = servicios.value;
    }
}

async function cargarProductos() {
    try {
        const servicioId = filtro.value.servicio_id;
        const sedeId = filtro.value.sede_id;

        if (!servicioId) {
            productos.value = [];
            return;
        }

        var url = "/api/citas/productos/" + servicioId;
        if (sedeId) url += "?sede_id=" + sedeId;

        const res = await axios.get(url);
        productos.value = res.data?.data || [];
    } catch {
        productos.value = [];
    }
}

function onFilterProductoSelect(value) {
    filtro.value.producto_id = value || "";
    buscar();
}

function onFormProductoSelect(value) {
    form.value.producto_id = value || "";
}

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
    } catch {
        registros.value = [];
    } finally {
        loadingBuscar.value = false;
    }
}

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
    } catch {
        alertError("Error", "No se pudo eliminar la recomendación");
    } finally {
        loadingEliminarId.value = null;
    }
}

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

function abrirModal() {
    showSidebar.value = true;
    nextTick(() => {
        const target =
            document.querySelector("#form-producto-parent .select2-selection") ||
            document.querySelector("#form-producto-parent select") ||
            document.querySelector(".r-panel-body textarea");
        if (target && typeof target.focus === "function") {
            target.focus();
        }
    });
}

function cerrarModal() {
    showSidebar.value = false;
}

function btnLimpiar() {
    filtro.value = { sede_id: "", servicio_id: "", producto_id: "" };
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
.sidebar-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.45);
    z-index: 1090;
    backdrop-filter: blur(1px);
}

.right-sidebar {
    position: fixed;
    right: -80%;
    width: 80%;
    visibility: hidden;
    z-index: 1100;
    background: #ffffff;
    top: 0;
    bottom: 0;
    height: 100%;
    overflow-y: auto;
    box-shadow: -5px 0 25px rgba(0, 0, 0, 0.15);
    transition:
        right 0.35s cubic-bezier(0.16, 1, 0.3, 1),
        visibility 0.35s ease;
}

.shw-rside {
    right: 0;
    visibility: visible;
}

@media (max-width: 768px) {
    .right-sidebar {
        width: 100%;
        right: -100%;
    }
}
</style>
