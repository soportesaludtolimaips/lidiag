<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Configuración</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Configuración</a>
                    </li>
                    <li class="breadcrumb-item active">Servicios de Citas</li>
                </ol>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Servicios</h4>
                        <h6 class="card-subtitle text-info">
                            Seleccione un servicio para administrar sus productos.
                        </h6>
                        <div class="list-group">
                            <button
                                v-for="servicio in servicios"
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
                                <h4 class="card-title">{{ servicioActual?.nombre }}</h4>
                                <h6 class="card-subtitle text-info">
                                    Productos vinculados a este servicio
                                </h6>
                            </div>
                            <button
                                type="button"
                                class="btn btn-info"
                                @click="mostrarModalAgregar()"
                            >
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
                                            <span class="label label-danger" v-else>Inactivo</span>
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
                    Agregar Producto
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
                            <div class="form-group" id="producto-parent">
                                <label class="control-label">Producto *</label>
                                <Select2
                                    :value="productoSeleccionado"
                                    :options="productosDisponiblesOptions"
                                    width="100%"
                                    :allow-clear="false"
                                    @input="onProductoSelect"
                                />
                            </div>
                            <div class="form-group">
                                <code
                                    >${num_docu_paciente}, ${nom_paciente} ${cod_cups},
                                    ${nom_produc}</code
                                >
                                <label class="control-label">Recomendación</label>
                                <FormattedTextEditor
                                    v-model="recomendacion"
                                    rows="8"
                                    placeholder="Texto de recomendación para el paciente. Use saltos de línea si es necesario."
                                />
                                <small class="text-muted"
                                    >Variables disponibles: <code>${num_docu_paciente}</code>
                                    <code>${nom_paciente}</code> <code>${cod_cups}</code>
                                    <code>${nom_produc}</code></small
                                >
                            </div>
                        </div>
                        <div class="form-actions mt-3">
                            <button type="button" class="btn btn-inverse" @click="cerrarModal()">
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
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { computed, onMounted, ref, getCurrentInstance } from "vue";

const { proxy } = getCurrentInstance();
import { alertError, alertSuccess, confirmAction } from "@/helpers/alerts.js";
import FormattedTextEditor from "@/components/shared/FormattedTextEditor.vue";
import axios from "axios";

const servicios = ref([]);
const servicioSeleccionado = ref(null);
const servicioActual = ref(null);
const productosVinculados = ref([]);
const productosDisponibles = ref([]);
const productoSeleccionado = ref("");
const recomendacion = ref("");
const loadingGuardar = ref(false);
const loadingListar = ref(false);

/**
 * Obtiene el identificador de servicio indicado en la URL para precargar la selección.
 *
 * @returns {string|null}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-26
 */
const servicioIdDesdeUrl = computed(() => {
    const params = new URLSearchParams(window.location.search);
    return params.get("servicio_id");
});
/**
 * Genera las opciones para el selector de productos disponibles.
 *
 * @returns {Array<Object>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-26
 */
const productosDisponiblesOptions = computed(() => {
    return (productosDisponibles.value || []).map(function (p) {
        return {
            id: String(p.id),
            cod_cups: p.cod_cups,
            nom_produc: p.nom_produc,
        };
    });
});

/**
 * Obtiene los servicios de cita del usuario y selecciona el primero o el indicado en la URL.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-26
 */
function cargarServicios() {
    var thisVue = proxy;

    axios
        .get("/api/config-servicio-citas/mis-servicios")
        .then(function (response) {
            thisVue.servicios = response.data;

            var servicioIdUrl = thisVue.servicioIdDesdeUrl;
            if (servicioIdUrl) {
                var encontrado = thisVue.servicios.find(function (s) {
                    return s.id == servicioIdUrl;
                });
                if (encontrado) {
                    thisVue.seleccionarServicio(encontrado);
                    return;
                }
            }

            if (thisVue.servicios.length > 0) {
                thisVue.seleccionarServicio(thisVue.servicios[0]);
            }
        })
        .catch(function (error) {
            console.error("Error:", error);
        });
}
/**
 * Define el servicio seleccionado y carga sus productos vinculados.
 *
 * @param {Object} servicio Servicio de cita seleccionado por el usuario.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-26
 */
function seleccionarServicio(servicio) {
    servicioSeleccionado.value = servicio.id;
    servicioActual.value = servicio;
    cargarProductosVinculados();
}
/**
 * Obtiene los productos vinculados al servicio de cita seleccionado.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-26
 */
function cargarProductosVinculados() {
    var thisVue = proxy;

    if (!thisVue.servicioSeleccionado) {
        return;
    }

    axios
        .get("/api/servicio-producto", {
            params: { servicio_cita_id: thisVue.servicioSeleccionado },
        })
        .then(function (response) {
            thisVue.productosVinculados = response.data;
        })
        .catch(function (error) {
            console.error("Error:", error);
        });
}
/**
 * Carga los productos no vinculados y abre el panel lateral para agregar un producto.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-26
 */
function mostrarModalAgregar() {
    var thisVue = proxy;

    thisVue.productoSeleccionado = "";
    thisVue.recomendacion = "";
    thisVue.loadingGuardar = false;

    axios
        .get("/api/servicio-producto/productos-no-vinculados", {
            params: { servicio_cita_id: thisVue.servicioSeleccionado },
        })
        .then(function (response) {
            thisVue.productosDisponibles = response.data;
            thisVue.abrirModal();
        })
        .catch(function (error) {
            console.error("Error:", error);
        });
}
/**
 * Actualiza el producto seleccionado y precarga su recomendación si existe.
 *
 * @param {*} value Valor seleccionado en el componente Select2.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-26
 */
function onProductoSelect(value) {
    productoSeleccionado.value = value || "";

    var encontrado = productosDisponibles.value.find(function (p) {
        return String(p.id) === String(value);
    });
    recomendacion.value =
        (encontrado &&
            encontrado.recomendacion_producto &&
            encontrado.recomendacion_producto.recomendacion) ||
        "";
}
/**
 * Muestra el panel lateral del formulario agregando la clase correspondiente.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-26
 */
function abrirModal() {
    $(".right-sidebar").addClass("shw-rside");
}
/**
 * Oculta el panel lateral del formulario removiendo la clase correspondiente.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-26
 */
function cerrarModal() {
    $(".right-sidebar").removeClass("shw-rside");
}
/**
 * Vincula el producto seleccionado al servicio y guarda su recomendación si se indicó.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-26
 */
function agregarProducto() {
    var thisVue = proxy;

    if (!thisVue.productoSeleccionado) {
        return;
    }

    thisVue.loadingGuardar = true;

    axios
        .post("/api/servicio-producto", {
            servicio_cita_id: thisVue.servicioSeleccionado,
            producto_id: thisVue.productoSeleccionado,
        })
        .then(function (response) {
            // Guardar también la recomendación si se ingresó texto
            if (thisVue.recomendacion && thisVue.recomendacion.trim()) {
                return axios.post("/api/config-recomendacion-productos", {
                    producto_id: thisVue.productoSeleccionado,
                    recomendacion: thisVue.recomendacion,
                });
            }
        })
        .then(function () {
            thisVue.mostrarExito("Producto agregado correctamente");
            thisVue.cerrarModal();
            thisVue.cargarProductosVinculados();
        })
        .catch(function (error) {
            thisVue.mostrarError(error);
        })
        .then(function () {
            thisVue.loadingGuardar = false;
        });
}
/**
 * Desvincula un producto del servicio previa confirmación del usuario.
 *
 * @param {number} id Identificador del vínculo producto-servicio a eliminar.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-26
 */
function eliminarVinculo(id) {
    var thisVue = proxy;

    confirmAction({
        title: "¿Está seguro?",
        text: "El producto se desvinculará del servicio.",
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then(function (confirmado) {
        if (confirmado) {
            axios
                .delete("/api/servicio-producto/" + id)
                .then(function () {
                    thisVue.mostrarExito("Producto desvinculado");
                    thisVue.cargarProductosVinculados();
                })
                .catch(function (error) {
                    thisVue.mostrarError(error);
                });
        }
    });
}
/**
 * Muestra una alerta de error con el mensaje obtenido de la respuesta o la excepción.
 *
 * @param {Error|Object} error Error capturado al ejecutar una petición.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-26
 */
function mostrarError(error) {
    var mensaje = error.response?.data?.message || error.message || "Error desconocido";
    alertError("Error", mensaje);
}
/**
 * Muestra una alerta de éxito con el mensaje indicado.
 *
 * @param {string} mensaje Texto de confirmación a mostrar al usuario.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-26
 */
function mostrarExito(mensaje) {
    alertSuccess("Éxito", mensaje);
}

/**
 * Hook de ciclo de vida que carga los servicios al montar el componente.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-26
 */
onMounted(() => {
    cargarServicios();
});
</script>
<style scoped>
.right-sidebar {
    position: fixed;
    right: -50%;
    width: 50%;
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
    width: 50%;
    display: block;
}
</style>
<style>
#producto-parent .select2-container--default .select2-selection--single {
    height: 36px;
}

#producto-parent
    .select2-container--default
    .select2-selection--single
    .select2-selection__rendered {
    line-height: 36px;
}

#producto-parent .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 34px;
}
</style>
