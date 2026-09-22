<template>
    <div>
        <div class="m-t-20">
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Servicios</h4>
                            <h6 class="card-subtitle text-info">
                                Seleccione un servicio para administrar sus productos.
                            </h6>

                            <div v-if="loadingServiciosProductos" class="text-center m-t-20">
                                <i class="fa fa-spinner fa-spin fa-2x"></i>
                                <p class="text-muted m-t-10">Cargando servicios...</p>
                            </div>

                            <div
                                v-else-if="errorServiciosProductos"
                                class="alert alert-danger m-t-20"
                            >
                                <i class="fa fa-exclamation-triangle"></i>
                                {{ errorServiciosProductos }}
                            </div>

                            <div
                                v-else-if="serviciosProductos.length === 0"
                                class="text-center m-t-20"
                            >
                                <p class="text-muted">
                                    No hay servicios disponibles. Contacte al administrador para
                                    asignarle servicios.
                                </p>
                            </div>

                            <div class="list-group m-t-10" v-else>
                                <button
                                    v-for="servicio in serviciosProductos"
                                    :key="servicio.id"
                                    type="button"
                                    class="list-group-item list-group-item-action"
                                    :class="servicioSeleccionado === servicio.id ? 'active' : ''"
                                    @click="seleccionarServicio(servicio)"
                                >
                                    <div
                                        class="d-flex justify-content-between align-items-center w-100"
                                    >
                                        <div>
                                            <div>{{ servicio.nombre }}</div>
                                            <small
                                                class="d-block"
                                                style="font-size: 11px; opacity: 0.75"
                                            >
                                                <i class="fa fa-map-marker"></i>
                                                {{ servicio.sede?.nom_sede || "Sin sede" }}
                                            </small>
                                        </div>
                                        <span
                                            class="badge badge-danger badge-pill"
                                            v-if="servicio.productos_count"
                                        >
                                            {{ servicio.productos_count }}
                                        </span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card" v-if="servicioSeleccionado">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="card-title">
                                        {{ servicioActual?.nombre }}
                                    </h4>
                                    <h6 class="card-subtitle text-info">
                                        Productos vinculados a este servicio
                                    </h6>
                                </div>
                                <button type="button" class="btn btn-info" @click="agregar()">
                                    <i class="fa fa-plus-circle m-r-5"></i> Agregar Producto
                                </button>
                            </div>
                            <div class="table-responsive m-t-20">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Producto</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in productosVinculados" :key="item.id">
                                            <td>{{ item.producto?.cod_cups }}</td>
                                            <td>{{ item.producto?.nom_produc }}</td>
                                            <td>
                                                <span class="label label-success" v-if="item.estado"
                                                    >Activo</span
                                                >
                                                <span class="label label-danger" v-else
                                                    >Inactivo</span
                                                >
                                            </td>
                                            <td class="text-nowrap">
                                                <button
                                                    type="button"
                                                    class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                    @click="eliminarVinculo(item.id)"
                                                >
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="productosVinculados.length === 0">
                                            <td colspan="4" class="text-center text-muted">
                                                No hay productos vinculados a este servicio.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card" v-else>
                        <div class="card-body text-center">
                            <p class="text-muted">
                                Seleccione un servicio de la lista para ver sus productos.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="right-sidebar"
            :class="{ 'shw-rside': sidebarVisible }"
            style="overflow-x: hidden; overflow-y: scroll; height: 100%"
        >
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <span>
                        <i class="ti-close right-side-toggle" @click="cerrarFormSidebar"></i>
                    </span>
                </div>
                <div class="r-panel-body">
                    <form action="#">
                        <div class="form-body">
                            <div class="row p-t-20">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label"
                                            >Producto<span class="text-danger">*</span></label
                                        >
                                        <Select2
                                            :value="productoSeleccionado"
                                            :options="productosDisponiblesOptions"
                                            width="100%"
                                            :allow-clear="true"
                                            @input="onProductoSelect"
                                        />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Recomendación</label>
                                        <FormattedTextEditor
                                            v-model="recomendacion"
                                            rows="18"
                                            placeholder="Texto de recomendación para el paciente. Use saltos de línea si es necesario."
                                        />
                                        <small class="text-muted">
                                            Variables disponibles:
                                            <code>${num_docu_paciente}</code>
                                            <code>${nom_paciente}</code>
                                            <code>${cod_cups}</code>
                                            <code>${nom_produc}</code>
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions mt-3">
                                <button
                                    type="button"
                                    class="btn btn-inverse"
                                    @click="cerrarFormSidebar()"
                                >
                                    Cerrar
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-success m-l-5"
                                    @click="agregarProducto()"
                                    :disabled="loadingGuardar || !productoSeleccionado"
                                >
                                    <i
                                        class="fa"
                                        :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-save'"
                                    ></i>
                                    {{ loadingGuardar ? "Guardando..." : "Guardar" }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { computed, onMounted, ref } from "vue";
import axios from "axios";

import { confirmAction, toastMessage } from "@/helpers/alerts.js";
import FormattedTextEditor from "@/components/shared/FormattedTextEditor.vue";
import Select2 from "@/components/shared/Select2.vue";

const serviciosProductos = ref([]);
const loadingServiciosProductos = ref(false);
const errorServiciosProductos = ref("");
const servicioSeleccionado = ref(null);
const servicioActual = ref(null);
const productosVinculados = ref([]);
const productosDisponibles = ref([]);
const productoSeleccionado = ref("");
const recomendacion = ref("");
const tituloModal = ref("Nuevo registro");
const sidebarVisible = ref(false);
const loadingGuardar = ref(false);
const loadingAgregarProducto = ref(false);

const productosDisponiblesOptions = computed(() => {
    return (productosDisponibles.value || []).map(function (p) {
        return {
            id: String(p.id),
            cod_cups: p.cod_cups,
            nom_produc: p.nom_produc,
            recomendacion_producto: p.recomendacion_producto,
        };
    });
});

async function cargarServiciosProductos() {
    loadingServiciosProductos.value = true;
    errorServiciosProductos.value = "";
    try {
        const res = await axios.get("/api/config-servicio-citas");
        serviciosProductos.value = res.data.data;
        if (serviciosProductos.value.length > 0) {
            seleccionarServicio(serviciosProductos.value[0]);
        }
    } catch (error) {
        const msg = error?.response?.data?.message || error.message || "Error al cargar servicios";
        errorServiciosProductos.value = msg;
        serviciosProductos.value = [];
    } finally {
        loadingServiciosProductos.value = false;
    }
}
function seleccionarServicio(servicio) {
    servicioSeleccionado.value = servicio.id;
    servicioActual.value = servicio;
    cargarProductosVinculados();
}
async function cargarProductosVinculados() {
    if (!servicioSeleccionado.value) return;
    try {
        const res = await axios.get("/api/servicio-producto", {
            params: { servicio_id: servicioSeleccionado.value },
        });
        productosVinculados.value = res.data.data;
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar los productos vinculados.",
            icon: "error",
        });
    }
}
async function agregar() {
    if (!servicioSeleccionado.value) {
        toastMessage({
            heading: "Seleccione un servicio",
            text: "Primero seleccione un servicio de la lista para agregar productos.",
            icon: "warning",
        });
        return;
    }
    try {
        const res = await axios.get("/api/servicio-producto/productos-no-vinculados", {
            params: { servicio_id: servicioSeleccionado.value },
        });
        productosDisponibles.value = res.data.data;
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar los productos disponibles.",
            icon: "error",
        });
        return;
    }
    tituloModal.value = "Agregar producto a: " + (servicioActual.value?.nombre || "");
    productoSeleccionado.value = "";
    recomendacion.value = "";
    loadingAgregarProducto.value = false;
    sidebarVisible.value = true;
}
function onProductoSelect(value) {
    let selectedId = null;
    if (Array.isArray(value) && value.length > 0) {
        const v = value[0];
        selectedId = String(typeof v === "object" && v && v.id != null ? v.id : v);
    } else if (value && typeof value === "object" && value.id != null) {
        selectedId = String(value.id);
    } else if (value != null) {
        selectedId = String(value);
    }
    if (selectedId != null) {
        productoSeleccionado.value = selectedId;
    }
}
function cerrarFormSidebar() {
    sidebarVisible.value = false;
}
async function agregarProducto() {
    if (!productoSeleccionado.value) return;
    loadingAgregarProducto.value = true;
    loadingGuardar.value = true;
    try {
        await axios.post("/api/servicio-producto", {
            servicio_id: servicioSeleccionado.value,
            producto_id: productoSeleccionado.value,
        });
        if (recomendacion.value && recomendacion.value.trim()) {
            await axios.post("/api/config-recomendacion-productos", {
                producto_id: productoSeleccionado.value,
                recomendacion: recomendacion.value,
            });
        }
        toastMessage({
            heading: "Éxito",
            text: "Producto agregado correctamente",
            icon: "success",
        });
        cerrarFormSidebar();
        cargarProductosVinculados();
        cargarServiciosProductos();
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible agregar el producto.",
            icon: "error",
        });
    } finally {
        loadingAgregarProducto.value = false;
        loadingGuardar.value = false;
        productoSeleccionado.value = "";
        recomendacion.value = "";
    }
}
async function eliminarVinculo(id) {
    const ok = await confirmAction({
        title: "¿Está seguro?",
        text: "El producto se desvinculará del servicio.",
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    });
    if (!ok) return;
    try {
        await axios.delete("/api/servicio-producto/" + id);
        toastMessage({
            heading: "Éxito",
            text: "Producto desvinculado",
            icon: "success",
        });
        cargarProductosVinculados();
        cargarServiciosProductos();
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible desvincular el producto.",
            icon: "error",
        });
    }
}

defineExpose({ agregar, listar: cargarServiciosProductos });

onMounted(() => {
    cargarServiciosProductos();
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
    overflow-y: auto;
}
</style>
