<template>
    <div>
        <!-- Encabezado de página -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">WorkFlow Dispositivos</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Configuración</a></li>
                    <li class="breadcrumb-item active">Dispositivos</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-outline-secondary m-r-5"
                    @click="cargarDispositivos"
                    :disabled="loadingListar"
                    title="Actualizar listado"
                >
                    <i class="fa" :class="loadingListar ? 'fa-spinner fa-spin' : 'fa-refresh'"></i>
                    Refrescar
                </button>
                <button
                    type="button"
                    class="btn btn-info btnDesplegarRigthSidebar"
                    @click="abrirFormularioNuevo"
                >
                    <i class="fa fa-plus-circle m-r-5"></i> Nuevo Dispositivo
                </button>
            </div>
        </div>

        <!-- Banner Contextual Multisede -->
        <div class="alert alert-info py-2 px-3 d-flex align-items-center mb-3">
            <i class="fa fa-hospital-o mr-2 fs-5"></i>
            <div>
                <strong>Sede Activa:</strong> Mostrando dispositivos asociados a la sede en sesión
                <span class="badge badge-primary ml-1">ID: {{ sedeActivaId || "Todas" }}</span>
            </div>
        </div>

        <!-- Card Principal con Tabla -->
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Equipos y Dispositivos de Trabajo</h4>
                <h6 class="card-subtitle mb-3 text-muted">
                    Gestión de modalidades y terminales vinculadas a los módulos asistenciales.
                </h6>

                <div v-if="loadingListar" class="text-center py-5">
                    <i class="fa fa-spinner fa-spin fa-2x text-primary"></i>
                    <p class="mt-2 text-muted">Cargando dispositivos...</p>
                </div>

                <div v-else-if="dispositivos.length === 0" class="text-center py-5">
                    <i class="fa fa-laptop fa-3x text-muted mb-2"></i>
                    <h5>No hay dispositivos configurados</h5>
                    <p class="text-muted">
                        Crea un nuevo dispositivo para asociarlo a los flujos asistenciales.
                    </p>
                    <button class="btn btn-info btn-sm" @click="abrirFormularioNuevo">
                        <i class="fa fa-plus"></i> Registrar Dispositivo
                    </button>
                </div>

                <div v-else class="table-responsive">
                    <table class="table table-hover table-striped table-bordered align-middle">
                        <thead class="thead-light">
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Módulo</th>
                                <th>Modalidad DICOM</th>
                                <th>AE Title</th>
                                <th>IP / Puerto</th>
                                <th>Ubicación</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center" style="width: 120px">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in dispositivos" :key="item.id">
                                <td class="font-weight-bold">{{ item.codigo }}</td>
                                <td>{{ item.nombre }}</td>
                                <td>
                                    <span class="badge badge-info" v-if="item.modulo">
                                        {{ item.modulo.modulo }}
                                    </span>
                                    <span v-else class="text-muted">Sin Módulo</span>
                                </td>
                                <td>
                                    <span class="badge badge-secondary" v-if="item.modality">{{
                                        item.modality
                                    }}</span>
                                    <span v-else class="text-muted">-</span>
                                </td>
                                <td>
                                    <code>{{ item.ae_title || "-" }}</code>
                                </td>
                                <td>
                                    <small v-if="item.ip_address"
                                        >{{ item.ip_address }}:{{ item.puerto || "104" }}</small
                                    >
                                    <span v-else class="text-muted">-</span>
                                </td>
                                <td>{{ item.ubicacion || "-" }}</td>
                                <td class="text-center">
                                    <span
                                        class="badge cursor-pointer"
                                        :class="item.estado ? 'badge-success' : 'badge-danger'"
                                        @click="cambiarEstado(item)"
                                        title="Click para alternar estado"
                                        style="cursor: pointer"
                                    >
                                        {{ item.estado ? "Activo" : "Inactivo" }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button
                                        class="btn btn-sm btn-outline-primary mr-1"
                                        @click="abrirEditar(item)"
                                        title="Editar"
                                    >
                                        <i class="fa fa-pencil"></i>
                                    </button>
                                    <button
                                        class="btn btn-sm btn-outline-danger"
                                        @click="eliminarDispositivo(item)"
                                        title="Eliminar"
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

        <!-- Backdrop overlay para cerrar drawer al hacer clic afuera -->
        <div v-if="drawerVisible" class="sidebar-backdrop" @click="cerrarDrawer"></div>

        <!-- Drawer lateral nativo (Right-Sidebar LIDiag) -->
        <div
            class="right-sidebar"
            :class="{ 'shw-rside': drawerVisible }"
            style="overflow-x: hidden; overflow-y: auto; height: 100%"
        >
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ isEdit ? "Editar Dispositivo" : "Nuevo Dispositivo de Flujo" }}
                    <button
                        type="button"
                        class="right-side-toggle"
                        @click="cerrarDrawer"
                        aria-label="Cerrar panel"
                    >
                        <i class="ti-close"></i>
                    </button>
                </div>

                <div class="r-panel-body">
                    <form @submit.prevent="guardarDispositivo">
                        <div class="form-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label" for="modulo_id">
                                            Módulo Asistencial <span class="text-danger">*</span>
                                        </label>
                                        <select
                                            id="modulo_id"
                                            v-model="form.modulo_id"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.modulo_id }"
                                            required
                                        >
                                            <option :value="null" disabled>
                                                Seleccione un módulo...
                                            </option>
                                            <option
                                                v-for="mod in modulos"
                                                :key="mod.id"
                                                :value="mod.id"
                                            >
                                                {{ mod.modulo }}
                                            </option>
                                        </select>
                                        <span class="text-danger small" v-if="errores.modulo_id">
                                            {{ errores.modulo_id[0] }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label" for="codigo">
                                            Código / Identificador
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            id="codigo"
                                            ref="inputCodigoRef"
                                            v-model.trim="form.codigo"
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.codigo }"
                                            placeholder="Ej. RX-01, TAC-SEDE-P1"
                                            required
                                        />
                                        <span class="text-danger small" v-if="errores.codigo">
                                            {{ errores.codigo[0] }}
                                        </span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label" for="modality"
                                            >Modalidad DICOM</label
                                        >
                                        <select
                                            id="modality"
                                            v-model="form.modality"
                                            class="form-control"
                                        >
                                            <option value="">N/A</option>
                                            <option value="CR">
                                                CR (Radiografía Computarizada)
                                            </option>
                                            <option value="DX">DX (Radiografía Digital)</option>
                                            <option value="CT">CT (Tomografía)</option>
                                            <option value="MR">MR (Resonancia)</option>
                                            <option value="US">US (Ecografía)</option>
                                            <option value="MG">MG (Mamografía)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label" for="nombre">
                                            Nombre del Dispositivo
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            id="nombre"
                                            v-model.trim="form.nombre"
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.nombre }"
                                            placeholder="Ej. Equipo Rayos X Sala 2"
                                            required
                                        />
                                        <span class="text-danger small" v-if="errores.nombre">
                                            {{ errores.nombre[0] }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label" for="ae_title"
                                            >AE Title DICOM</label
                                        >
                                        <input
                                            id="ae_title"
                                            v-model.trim="form.ae_title"
                                            type="text"
                                            class="form-control"
                                            placeholder="Ej. MOD_RX01"
                                        />
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label" for="puerto"
                                            >Puerto DICOM</label
                                        >
                                        <input
                                            id="puerto"
                                            v-model.number="form.puerto"
                                            type="number"
                                            class="form-control"
                                            placeholder="104"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label" for="ip_address"
                                            >Dirección IP</label
                                        >
                                        <input
                                            id="ip_address"
                                            v-model.trim="form.ip_address"
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.ip_address }"
                                            placeholder="192.168.1.100"
                                        />
                                        <span class="text-danger small" v-if="errores.ip_address">
                                            {{ errores.ip_address[0] }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label" for="ubicacion"
                                            >Ubicación Física</label
                                        >
                                        <input
                                            id="ubicacion"
                                            v-model.trim="form.ubicacion"
                                            type="text"
                                            class="form-control"
                                            placeholder="Piso 1 - Sala de Radiología A"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label" for="descripcion"
                                            >Descripción / Notas</label
                                        >
                                        <textarea
                                            id="descripcion"
                                            v-model.trim="form.descripcion"
                                            class="form-control"
                                            rows="2"
                                            placeholder="Notas técnicas del equipo o mantenimiento..."
                                        ></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="checkbox checkbox-success">
                                            <input
                                                type="checkbox"
                                                id="checkEstado"
                                                v-model="form.estado"
                                            />
                                            <label for="checkEstado"
                                                >Dispositivo Activo en el Flujo</label
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions text-right p-t-10">
                            <button
                                type="button"
                                class="btn btn-secondary m-r-5"
                                @click="cerrarDrawer"
                            >
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-info" :disabled="loadingGuardar">
                                <i
                                    class="fa"
                                    :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-save'"
                                ></i>
                                {{ isEdit ? "Actualizar" : "Guardar" }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, nextTick, onMounted } from "vue";
import axios from "axios";

const dispositivos = ref([]);
const modulos = ref([]);
const loadingListar = ref(false);
const loadingGuardar = ref(false);
const isEdit = ref(false);
const editId = ref(null);
const errores = ref({});
const drawerVisible = ref(false);
const inputCodigoRef = ref(null);

const getSedeActiva = () => {
    return localStorage.getItem("ST-sede") || sessionStorage.getItem("ST-sede") || null;
};
const sedeActivaId = ref(getSedeActiva());

const form = reactive({
    sede_id: null,
    modulo_id: null,
    codigo: "",
    nombre: "",
    ae_title: "",
    modality: "",
    ip_address: "",
    puerto: 104,
    ubicacion: "",
    descripcion: "",
    estado: true,
});

const limpiarFormulario = () => {
    form.sede_id = sedeActivaId.value ? parseInt(sedeActivaId.value, 10) : null;
    form.modulo_id = null;
    form.codigo = "";
    form.nombre = "";
    form.ae_title = "";
    form.modality = "";
    form.ip_address = "";
    form.puerto = 104;
    form.ubicacion = "";
    form.descripcion = "";
    form.estado = true;
    errores.value = {};
};

const cargarModulos = async () => {
    try {
        const res = await axios.get("/api/config-modulos-listar");
        modulos.value = res.data?.data || res.data || [];
    } catch (e) {
        console.error("Error cargando módulos:", e);
    }
};

const cargarDispositivos = async () => {
    loadingListar.value = true;
    try {
        const params = {};
        if (sedeActivaId.value) {
            params.sede_id = sedeActivaId.value;
        }
        const res = await axios.get("/api/config-worklist-dispositivos", { params });
        dispositivos.value = res.data?.data || res.data || [];
    } catch (e) {
        console.error("Error cargando dispositivos:", e);
    } finally {
        loadingListar.value = false;
    }
};

const abrirDrawer = () => {
    drawerVisible.value = true;
    nextTick(() => {
        inputCodigoRef.value?.focus();
    });
};

const cerrarDrawer = () => {
    drawerVisible.value = false;
};

const abrirFormularioNuevo = () => {
    isEdit.value = false;
    editId.value = null;
    sedeActivaId.value = getSedeActiva();
    limpiarFormulario();
    abrirDrawer();
};

const abrirEditar = (item) => {
    isEdit.value = true;
    editId.value = item.id;
    errores.value = {};
    form.sede_id = item.sede_id;
    form.modulo_id = item.modulo_id;
    form.codigo = item.codigo;
    form.nombre = item.nombre;
    form.ae_title = item.ae_title || "";
    form.modality = item.modality || "";
    form.ip_address = item.ip_address || "";
    form.puerto = item.puerto || 104;
    form.ubicacion = item.ubicacion || "";
    form.descripcion = item.descripcion || "";
    form.estado = Boolean(item.estado);
    abrirDrawer();
};

const guardarDispositivo = async () => {
    loadingGuardar.value = true;
    errores.value = {};
    try {
        if (!form.sede_id && sedeActivaId.value) {
            form.sede_id = parseInt(sedeActivaId.value, 10);
        }

        if (isEdit.value) {
            await axios.put(`/api/config-worklist-dispositivos/${editId.value}`, form);
        } else {
            await axios.post("/api/config-worklist-dispositivos", form);
        }

        cerrarDrawer();
        await cargarDispositivos();
    } catch (e) {
        if (e.response && e.response.status === 422) {
            errores.value = e.response.data.errors || {};
        } else {
            console.error("Error al guardar dispositivo:", e);
        }
    } finally {
        loadingGuardar.value = false;
    }
};

const cambiarEstado = async (item) => {
    try {
        await axios.patch(`/api/config-worklist-dispositivos/${item.id}/toggle-estado`);
        item.estado = !item.estado;
    } catch (e) {
        console.error("Error alternando estado:", e);
    }
};

const eliminarDispositivo = async (item) => {
    if (!confirm(`¿Desea eliminar el dispositivo "${item.nombre}"?`)) return;

    try {
        await axios.delete(`/api/config-worklist-dispositivos/${item.id}`);
        await cargarDispositivos();
    } catch (e) {
        console.error("Error eliminando dispositivo:", e);
    }
};

onMounted(async () => {
    await Promise.all([cargarModulos(), cargarDispositivos()]);
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
    right: -50%;
    width: 50%;
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
