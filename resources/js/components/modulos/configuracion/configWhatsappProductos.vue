<template>
    <div>
        <div class="m-t-20">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div v-if="!sedeActual" class="alert alert-warning m-t-20">
                                <i class="fa fa-exclamation-triangle"></i>
                                Seleccione una sede en el menú superior para administrar sus
                                productos.
                            </div>

                            <div v-else-if="errorProductos" class="alert alert-danger m-t-20">
                                <i class="fa fa-exclamation-triangle"></i>
                                {{ errorProductos }}
                            </div>

                            <template v-else>
                                <table-skeleton-loader
                                    :visible="loadingProductos"
                                    :rows="4"
                                    :columns="5"
                                />
                                <empty-state-component
                                    v-if="!loadingProductos && productosVinculados.length === 0"
                                    icon="fa fa-cubes"
                                    title="No hay productos vinculados"
                                    description="No hay productos vinculados en esta sede."
                                    action-text="Agregar producto"
                                    action-icon="fa fa-plus"
                                    @action="agregar()"
                                />

                                <div
                                    v-show="!loadingProductos && productosVinculados.length > 0"
                                    class="table-responsive m-t-20"
                                >
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Código</th>
                                                <th>Producto</th>
                                                <th>Servicio</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in productosVinculados" :key="item.id">
                                                <td>{{ item.producto?.cod_cups }}</td>
                                                <td>{{ item.producto?.nom_produc }}</td>
                                                <td>{{ item.servicio?.nombre }}</td>
                                                <td>
                                                    <span
                                                        class="label label-success"
                                                        v-if="item.estado"
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
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Backdrop overlay para cerrar al hacer clic afuera -->
        <div v-if="sidebarVisible" class="sidebar-backdrop" @click="cerrarFormSidebar"></div>

        <div
            id="drawerWhatsappProductos"
            class="right-sidebar"
            ref="sidebarRef"
            :class="{ 'shw-rside': sidebarVisible }"
            style="overflow-x: hidden; overflow-y: scroll; height: 100%"
        >
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <span>
                        <button
                            type="button"
                            class="close right-side-toggle"
                            aria-label="Cerrar panel"
                            title="Cerrar"
                            @click="cerrarFormSidebar"
                        >
                            &times;
                        </button>
                    </span>
                </div>
                <div class="r-panel-body">
                    <form action="#">
                        <div class="form-body">
                            <div class="row p-t-20">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label"
                                            >Servicio<span class="text-danger">*</span></label
                                        >
                                        <select
                                            id="form_wp_servicio"
                                            v-model="servicioSeleccionado"
                                            class="form-control"
                                            @change="onServicioChange"
                                            :disabled="loadingServicios"
                                        >
                                            <option value="">
                                                {{
                                                    loadingServicios
                                                        ? "Cargando servicios..."
                                                        : "Seleccione el servicio"
                                                }}
                                            </option>
                                            <option
                                                v-for="s in servicios"
                                                :key="s.id"
                                                :value="String(s.id)"
                                            >
                                                {{ s.nombre }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label"
                                            >Producto<span class="text-danger">*</span></label
                                        >
                                        <Select2
                                            ref="select2Productos"
                                            :value="productoSeleccionado"
                                            :options="productosDisponiblesOptions"
                                            width="100%"
                                            :allow-clear="true"
                                            dropdown-parent-selector="#drawerWhatsappProductos"
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
                                    class="btn btn-success m-r-5"
                                    @click="agregarProducto()"
                                    :disabled="
                                        loadingGuardar ||
                                        !productoSeleccionado ||
                                        !servicioSeleccionado
                                    "
                                >
                                    <i
                                        class="fa"
                                        :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-save'"
                                    ></i>
                                    {{ loadingGuardar ? "Guardando..." : "Guardar" }}
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-inverse"
                                    @click="cerrarFormSidebar()"
                                >
                                    Cancelar
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
import { computed, nextTick, onMounted, ref, watch } from "vue";
import axios from "axios";

import { confirmAction, toastMessage } from "@/helpers/alerts.js";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts.js";
import FormattedTextEditor from "@/components/shared/FormattedTextEditor.vue";
import Select2 from "@/components/shared/Select2.vue";

const props = defineProps({
    sedeId: {
        type: [Number, String],
        default: "",
    },
});

const sedeActual = computed(() => {
    return props.sedeId || sessionStorage.getItem("ST-sede") || "";
});
const nomSede = computed(() => sessionStorage.getItem("ST-nomsede") || "");

const servicios = ref([]);
const loadingServicios = ref(false);
const servicioSeleccionado = ref("");
const productosVinculados = ref([]);
const loadingProductos = ref(false);
const errorProductos = ref("");
const productosDisponibles = ref([]);
const productoSeleccionado = ref("");
const recomendacion = ref("");
const tituloModal = ref("Nuevo registro");
const sidebarVisible = ref(false);
const select2Productos = ref(null);
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

async function cargarServicios() {
    if (!sedeActual.value) {
        servicios.value = [];
        return;
    }
    loadingServicios.value = true;
    try {
        const res = await axios.get("/api/config-servicio-citas", {
            params: { sede_id: sedeActual.value },
        });
        servicios.value = res?.data?.data || res?.data || [];
    } catch (error) {
        servicios.value = [];
        toastMessage({
            heading: "Error",
            text:
                error?.response?.data?.message || "No fue posible cargar los servicios de la sede.",
            icon: "error",
        });
    } finally {
        loadingServicios.value = false;
    }
}
async function cargarProductosVinculados() {
    if (!sedeActual.value) {
        productosVinculados.value = [];
        return;
    }
    loadingProductos.value = true;
    errorProductos.value = "";
    try {
        const res = await axios.get("/api/servicio-producto", {
            params: { sede_id: sedeActual.value },
        });
        productosVinculados.value = res?.data?.data || res?.data || [];
    } catch (error) {
        const msg =
            error?.response?.data?.message ||
            error.message ||
            "No fue posible cargar los productos vinculados.";
        errorProductos.value = msg;
        productosVinculados.value = [];
    } finally {
        loadingProductos.value = false;
    }
}
function recargar() {
    cargarServicios();
    cargarProductosVinculados();
}
async function cargarProductosDisponibles() {
    if (!sedeActual.value || !servicioSeleccionado.value) {
        productosDisponibles.value = [];
        return;
    }
    try {
        const res = await axios.get("/api/servicio-producto/productos-no-vinculados", {
            params: {
                sede_id: sedeActual.value,
                servicio_id: servicioSeleccionado.value,
            },
        });
        productosDisponibles.value = res?.data?.data || res?.data || [];
        await nextTick();
        select2Productos.value?.rebuild();
    } catch {
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar los productos disponibles.",
            icon: "error",
        });
        productosDisponibles.value = [];
    }
}
function onServicioChange() {
    productoSeleccionado.value = "";
    cargarProductosDisponibles();
}
async function agregar() {
    if (!sedeActual.value) {
        toastMessage({
            heading: "Seleccione una sede",
            text: "Primero seleccione una sede en el menú superior.",
            icon: "warning",
        });
        return;
    }
    tituloModal.value = "Agregar producto en: " + (nomSede.value || "sede seleccionada");
    servicioSeleccionado.value = "";
    productoSeleccionado.value = "";
    productosDisponibles.value = [];
    recomendacion.value = "";
    loadingAgregarProducto.value = false;
    sidebarVisible.value = true;
    await nextTick();
    select2Productos.value?.rebuild();
    await cargarServicios();
    if (servicios.value.length > 0 && !servicioSeleccionado.value) {
        servicioSeleccionado.value = String(servicios.value[0].id);
        await cargarProductosDisponibles();
    }
    nextTick(() => {
        document.getElementById("form_wp_servicio")?.focus();
    });
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
    if (!productoSeleccionado.value || !servicioSeleccionado.value) return;
    loadingAgregarProducto.value = true;
    loadingGuardar.value = true;
    try {
        await axios.post("/api/servicio-producto", {
            sede_id: sedeActual.value,
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
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: error?.response?.data?.message || "No fue posible agregar el producto.",
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
        text: "El producto se desvinculará del servicio en esta sede.",
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
    } catch {
        toastMessage({
            heading: "Error",
            text: "No fue posible desvincular el producto.",
            icon: "error",
        });
    }
}

useKeyboardShortcuts({
    onSave: agregarProducto,
    onEscape: cerrarFormSidebar,
    isActive: sidebarVisible,
    isLoading: loadingGuardar,
});

defineExpose({ agregar, listar: recargar });

watch(
    () => props.sedeId,
    () => {
        recargar();
    }
);

onMounted(() => {
    recargar();
});
</script>
<style scoped>
.sidebar-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1090;
}

.right-sidebar {
    position: fixed;
    right: -70%;
    width: 70%;
    z-index: 1100;
    background: #ffffff;
    top: 0px;
    padding-bottom: 20px;
    height: 100%;
    box-shadow: -5px 0 25px rgba(0, 0, 0, 0.15);
    transition:
        right 0.35s cubic-bezier(0.16, 1, 0.3, 1),
        visibility 0.35s;
    visibility: hidden;
    pointer-events: none;
}

.shw-rside {
    right: 0px !important;
    visibility: visible !important;
    pointer-events: auto !important;
}

@media (max-width: 767px) {
    .right-sidebar {
        width: 100% !important;
        right: -100% !important;
    }
}
</style>
