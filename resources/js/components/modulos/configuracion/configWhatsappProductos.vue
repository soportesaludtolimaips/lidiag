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

                            <div v-else-if="loadingProductos" class="text-center m-t-20">
                                <i class="fa fa-spinner fa-spin fa-2x"></i>
                                <p class="text-muted m-t-10">Cargando productos...</p>
                            </div>

                            <div v-else-if="errorProductos" class="alert alert-danger m-t-20">
                                <i class="fa fa-exclamation-triangle"></i>
                                {{ errorProductos }}
                            </div>

                            <div class="table-responsive m-t-20" v-else>
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
                                            <td colspan="5" class="text-center text-muted">
                                                No hay productos vinculados en esta sede.
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

        <div
            id="drawerWhatsappProductos"
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
                                            >Servicio<span class="text-danger">*</span></label
                                        >
                                        <select
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
                                    class="btn btn-inverse"
                                    @click="cerrarFormSidebar()"
                                >
                                    Cerrar
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-success m-l-5"
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
        servicios.value = res.data.data;
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
        productosVinculados.value = res.data.data;
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
        productosDisponibles.value = res.data.data;
        await nextTick();
        select2Productos.value?.rebuild();
    } catch (error) {
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
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible desvincular el producto.",
            icon: "error",
        });
    }
}

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
    overflow-y: auto;
}
</style>
