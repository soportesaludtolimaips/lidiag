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

<script>
import { alertError, alertSuccess, confirmAction } from "@/helpers/alerts.js";
import FormattedTextEditor from "@/components/shared/FormattedTextEditor.vue";

export default {
    components: { FormattedTextEditor },
    data: function () {
        return {
            servicios: [],
            servicioSeleccionado: null,
            servicioActual: null,
            productosVinculados: [],
            productosDisponibles: [],
            productoSeleccionado: "",
            recomendacion: "",
            loadingGuardar: false,
            loadingListar: false,
        };
    },

    mounted: function () {
        this.cargarServicios();
    },

    computed: {
        servicioIdDesdeUrl: function () {
            const params = new URLSearchParams(window.location.search);
            return params.get("servicio_id");
        },
        productosDisponiblesOptions: function () {
            return (this.productosDisponibles || []).map(function (p) {
                return {
                    id: String(p.id),
                    cod_cups: p.cod_cups,
                    nom_produc: p.nom_produc,
                };
            });
        },
    },

    methods: {
        cargarServicios: function () {
            var thisVue = this;

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
        },

        seleccionarServicio: function (servicio) {
            this.servicioSeleccionado = servicio.id;
            this.servicioActual = servicio;
            this.cargarProductosVinculados();
        },

        cargarProductosVinculados: function () {
            var thisVue = this;

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
        },

        mostrarModalAgregar: function () {
            var thisVue = this;

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
        },

        onProductoSelect: function (value) {
            this.productoSeleccionado = value || "";

            var encontrado = this.productosDisponibles.find(function (p) {
                return String(p.id) === String(value);
            });
            this.recomendacion =
                (encontrado &&
                    encontrado.recomendacion_producto &&
                    encontrado.recomendacion_producto.recomendacion) ||
                "";
        },

        abrirModal: function () {
            $(".right-sidebar").addClass("shw-rside");
        },

        cerrarModal: function () {
            $(".right-sidebar").removeClass("shw-rside");
        },

        agregarProducto: function () {
            var thisVue = this;

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
        },

        eliminarVinculo: function (id) {
            var thisVue = this;

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
        },

        mostrarError: function (error) {
            var mensaje = error.response?.data?.message || error.message || "Error desconocido";
            alertError("Error", mensaje);
        },

        mostrarExito: function (mensaje) {
            alertSuccess("Éxito", mensaje);
        },
    },
};
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
