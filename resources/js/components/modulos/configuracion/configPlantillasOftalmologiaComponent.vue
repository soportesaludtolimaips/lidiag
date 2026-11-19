<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-7 align-self-center">
                <h3 class="text-themecolor">Configuración de Plantillas de Oftalmología</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/dashboard">Inicio</a></li>
                    <li class="breadcrumb-item">Configuración</li>
                    <li class="breadcrumb-item">Oftalmología</li>
                    <li class="breadcrumb-item active">{{ tituloSeccion }}</li>
                </ol>
            </div>
            <div class="col-md-5 align-self-center text-right">
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary mr-2"
                    @click="cargarPlantillas()"
                    :disabled="cargando"
                >
                    <i class="fa fa-refresh mr-1" :class="{ 'fa-spin': cargando }"></i> Refrescar
                </button>
                <button type="button" class="btn btn-sm btn-primary" @click="abrirModalCrear()">
                    <i class="fa fa-plus-circle mr-1"></i> Nueva Plantilla
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <!-- Filtro y Pestañas por Sección -->
                        <ul class="nav nav-tabs customtab mb-3">
                            <li class="nav-item">
                                <a
                                    class="nav-link"
                                    :class="{ active: seccionFiltro === 'CONCEPTO_PLAN' }"
                                    href="javascript:void(0)"
                                    @click="cambiarSeccion('CONCEPTO_PLAN')"
                                >
                                    <i class="fa fa-file-text-o mr-1 text-primary"></i> Plantillas
                                    Consulta
                                </a>
                            </li>
                            <li class="nav-item">
                                <a
                                    class="nav-link"
                                    :class="{ active: seccionFiltro === 'MEDICAMENTOS' }"
                                    href="javascript:void(0)"
                                    @click="cambiarSeccion('MEDICAMENTOS')"
                                >
                                    <i class="fa fa-medkit mr-1 text-success"></i> Plantillas
                                    Medicamentos
                                </a>
                            </li>
                            <li class="nav-item">
                                <a
                                    class="nav-link"
                                    :class="{ active: seccionFiltro === 'PROCEDIMIENTOS' }"
                                    href="javascript:void(0)"
                                    @click="cambiarSeccion('PROCEDIMIENTOS')"
                                >
                                    <i class="fa fa-list-alt mr-1 text-info"></i> Plantillas
                                    Procedimientos
                                </a>
                            </li>
                        </ul>

                        <!-- Listado de Plantillas -->
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-bordered mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 25%">Título</th>
                                        <th style="width: 45%">Contenido / Formulación</th>
                                        <th style="width: 15%">Alcance / Médico</th>
                                        <th style="width: 8%" class="text-center">Estado</th>
                                        <th style="width: 7%" class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="cargando">
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i
                                                class="fa fa-spinner fa-spin fa-2x mb-2 text-primary"
                                            ></i>
                                            <p class="mb-0">
                                                Cargando plantillas de oftalmología...
                                            </p>
                                        </td>
                                    </tr>
                                    <tr v-else-if="plantillas.length === 0">
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i
                                                class="fa fa-folder-open-o fa-2x mb-2 text-muted"
                                            ></i>
                                            <p class="mb-0">
                                                No se encontraron plantillas registradas para esta
                                                sección.
                                            </p>
                                        </td>
                                    </tr>
                                    <tr v-for="item in plantillas" :key="item.id">
                                        <td class="font-weight-bold text-dark align-middle">
                                            {{ item.titulo }}
                                        </td>
                                        <td
                                            class="align-middle text-muted small"
                                            style="white-space: pre-wrap"
                                        >
                                            {{ truncarTexto(item.contenido, 180) }}
                                        </td>
                                        <td class="align-middle small">
                                            <span v-if="item.medico" class="badge badge-info">
                                                <i class="fa fa-user-md mr-1"></i
                                                >{{ item.medico.nombre }} {{ item.medico.apellido }}
                                            </span>
                                            <span v-else class="badge badge-secondary">
                                                <i class="fa fa-building-o mr-1"></i>Institucional
                                            </span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span
                                                class="badge"
                                                :class="
                                                    item.activo ? 'badge-success' : 'badge-danger'
                                                "
                                            >
                                                {{ item.activo ? "Activo" : "Inactivo" }}
                                            </span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <button
                                                type="button"
                                                class="btn btn-outline-primary btn-xs mr-1"
                                                title="Editar"
                                                @click="editarPlantilla(item)"
                                            >
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-outline-danger btn-xs"
                                                title="Eliminar"
                                                @click="confirmarEliminar(item)"
                                            >
                                                <i class="fa fa-trash"></i>
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

        <!-- Modal Crear / Editar -->
        <div
            class="modal fade"
            :class="{ show: modalVisible, 'd-block': modalVisible }"
            tabindex="-1"
            role="dialog"
            style="background: rgba(0, 0, 0, 0.5)"
            v-if="modalVisible"
        >
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white py-2 px-3">
                        <h5 class="modal-title font-weight-bold">
                            <i class="fa fa-file-text-o mr-1"></i>
                            {{
                                modoEdicion
                                    ? "Editar Plantilla de Oftalmología"
                                    : "Nueva Plantilla de Oftalmología"
                            }}
                        </h5>
                        <button type="button" class="close text-white" @click="cerrarModal()">
                            &times;
                        </button>
                    </div>
                    <form @submit.prevent="guardarPlantilla()">
                        <div class="modal-body p-3">
                            <div class="row">
                                <div class="col-md-6 form-group mb-2">
                                    <label class="small font-weight-bold mb-1"
                                        >Sección Clínica:</label
                                    >
                                    <select
                                        v-model="form.seccion"
                                        class="form-control form-control-sm"
                                        required
                                    >
                                        <option value="CONCEPTO_PLAN">
                                            Plantilla Consulta (Concepto y Plan)
                                        </option>
                                        <option value="MEDICAMENTOS">
                                            Plantilla Medicamentos (Formulación)
                                        </option>
                                        <option value="PROCEDIMIENTOS">
                                            Plantilla Procedimientos (Órdenes)
                                        </option>
                                        <option value="EXAMEN_FISICO">
                                            Examen Físico Oftalmológico
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6 form-group mb-2">
                                    <label class="small font-weight-bold mb-1"
                                        >Título de la Plantilla:</label
                                    >
                                    <input
                                        type="text"
                                        v-model="form.titulo"
                                        class="form-control form-control-sm"
                                        placeholder="Ej: Glaucoma de ángulo abierto / Gotas Lubricantes..."
                                        required
                                    />
                                </div>
                            </div>

                            <div class="form-group mb-2">
                                <label class="small font-weight-bold mb-1"
                                    >Contenido de la Plantilla / Texto Predeterminado:</label
                                >
                                <textarea
                                    v-model="form.contenido"
                                    class="form-control form-control-sm"
                                    rows="6"
                                    placeholder="Escriba aquí el texto o formato que se insertará en la historia clínica..."
                                    required
                                ></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group mb-0">
                                    <div class="custom-control custom-checkbox mt-2">
                                        <input
                                            type="checkbox"
                                            class="custom-control-input"
                                            id="checkInstitucional"
                                            v-model="form.es_institucional"
                                        />
                                        <label
                                            class="custom-control-label small"
                                            for="checkInstitucional"
                                        >
                                            Plantilla institucional (disponible para todos los
                                            médicos)
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 form-group mb-0">
                                    <div class="custom-control custom-checkbox mt-2">
                                        <input
                                            type="checkbox"
                                            class="custom-control-input"
                                            id="checkActivo"
                                            v-model="form.activo"
                                        />
                                        <label class="custom-control-label small" for="checkActivo">
                                            Plantilla activa
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer py-2 px-3">
                            <button
                                type="button"
                                class="btn btn-sm btn-secondary"
                                @click="cerrarModal()"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                class="btn btn-sm btn-primary"
                                :disabled="guardando"
                            >
                                <i class="fa fa-save mr-1" :class="{ 'fa-spin': guardando }"></i>
                                {{ guardando ? "Guardando..." : "Guardar Plantilla" }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import axios from "axios";
import { alertError, alertSuccess, confirmAction } from "@/helpers/alerts.js";

const route = useRoute();

// Inicializa según parámetro de query ?seccion=MEDICAMENTOS o similar
const seccionFiltro = ref(route.query.seccion || "CONCEPTO_PLAN");
const plantillas = ref([]);
const cargando = ref(false);
const guardando = ref(false);

const modalVisible = ref(false);
const modoEdicion = ref(false);
const plantillaSeleccionadaId = ref(null);

const form = reactive({
    seccion: "CONCEPTO_PLAN",
    titulo: "",
    contenido: "",
    es_institucional: true,
    activo: true,
});

const tituloSeccion = computed(() => {
    switch (seccionFiltro.value) {
        case "MEDICAMENTOS":
            return "Plantillas de Órdenes de Medicamentos";
        case "PROCEDIMIENTOS":
            return "Plantillas de Órdenes de Procedimientos";
        default:
            return "Plantillas de Consulta";
    }
});

const cambiarSeccion = (sec) => {
    seccionFiltro.value = sec;
    cargarPlantillas();
};

const cargarPlantillas = async () => {
    cargando.value = true;
    try {
        const res = await axios.get("/api/config-plantillas-oftalmologia", {
            params: { seccion: seccionFiltro.value },
        });
        if (res.data?.success) {
            plantillas.value = res.data.data || [];
        }
    } catch (e) {
        console.error("Error al cargar plantillas:", e);
    } finally {
        cargando.value = false;
    }
};

const abrirModalCrear = () => {
    modoEdicion.value = false;
    plantillaSeleccionadaId.value = null;
    form.seccion = seccionFiltro.value;
    form.titulo = "";
    form.contenido = "";
    form.es_institucional = true;
    form.activo = true;
    modalVisible.value = true;
};

const editarPlantilla = (item) => {
    modoEdicion.value = true;
    plantillaSeleccionadaId.value = item.id;
    form.seccion = item.seccion;
    form.titulo = item.titulo;
    form.contenido = item.contenido;
    form.es_institucional = !item.medico_id;
    form.activo = Boolean(item.activo);
    modalVisible.value = true;
};

const cerrarModal = () => {
    modalVisible.value = false;
};

const guardarPlantilla = async () => {
    guardando.value = true;
    try {
        if (modoEdicion.value) {
            const res = await axios.put(
                `/api/config-plantillas-oftalmologia/${plantillaSeleccionadaId.value}`,
                form
            );
            if (res.data?.success) {
                alertSuccess("Plantilla actualizada con éxito.", "Guardado");
                cerrarModal();
                await cargarPlantillas();
            }
        } else {
            const res = await axios.post("/api/config-plantillas-oftalmologia", form);
            if (res.data?.success) {
                alertSuccess("Plantilla registrada con éxito.", "Guardado");
                cerrarModal();
                await cargarPlantillas();
            }
        }
    } catch (e) {
        alertError(e.response?.data?.message || "No fue posible guardar la plantilla.", "Error");
    } finally {
        guardando.value = false;
    }
};

const confirmarEliminar = async (item) => {
    const confirmed = await confirmAction({
        title: "¿Eliminar Plantilla?",
        text: `¿Está seguro de eliminar la plantilla "${item.titulo}"?`,
        type: "warning",
    });

    if (confirmed) {
        try {
            const res = await axios.delete(`/api/config-plantillas-oftalmologia/${item.id}`);
            if (res.data?.success) {
                alertSuccess("Plantilla eliminada correctamente.", "Eliminado");
                await cargarPlantillas();
            }
        } catch (e) {
            alertError(
                e.response?.data?.message || "No fue posible eliminar la plantilla.",
                "Error"
            );
        }
    }
};

const truncarTexto = (txt, max) => {
    if (!txt) return "";
    return txt.length > max ? txt.substring(0, max) + "..." : txt;
};

onMounted(async () => {
    await cargarPlantillas();
});
</script>
