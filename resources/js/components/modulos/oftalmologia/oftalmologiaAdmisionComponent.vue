<template>
    <div class="row">
        <!-- Barra de título y acciones superiores -->
        <div class="col-12">
            <div class="card card-outline card-primary shadow-sm mb-3">
                <div
                    class="card-header d-flex flex-wrap align-items-center justify-content-between p-2"
                >
                    <div class="d-flex align-items-center">
                        <i class="fa fa-eye fa-2x text-primary mr-2" aria-hidden="true"></i>
                        <div>
                            <h5 class="mb-0 font-weight-bold text-dark">
                                Admisión de Oftalmología
                            </h5>
                            <small class="text-muted">
                                Sede activa:
                                <span class="font-weight-bold text-primary">{{
                                    sedeNombre || "Cargando sede..."
                                }}</span>
                                <span v-if="sedeInterface" class="badge badge-success ml-2"
                                    ><i class="fa fa-plug mr-1"></i>Interfaz SAHI Activa</span
                                >
                                <span v-else class="badge badge-secondary ml-2"
                                    ><i class="fa fa-database mr-1"></i>Modo Local</span
                                >
                            </small>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-2 mt-sm-0">
                        <button
                            type="button"
                            class="btn btn-sm btn-primary mr-1"
                            @click="abrirAdmisionDirecta('PARTICULAR')"
                        >
                            <i class="fa fa-plus-circle mr-1"></i>+ Particular
                        </button>
                        <button
                            type="button"
                            class="btn btn-sm btn-warning text-white mr-1"
                            @click="abrirAdmisionDirecta('CORTESIA')"
                        >
                            <i class="fa fa-gift mr-1"></i>+ Cortesía
                        </button>
                        <button
                            v-if="sedeInterface"
                            type="button"
                            class="btn btn-sm btn-info"
                            @click="tabActiva = 'sahi'"
                        >
                            <i class="fa fa-search mr-1"></i>Consultar SAHI
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pestañas de Vista (Atenciones Admisionadas vs Búsqueda SAHI si aplica) -->
        <div class="col-12">
            <div class="card card-tabs shadow-sm">
                <div class="card-header p-0 pt-1 border-bottom-0">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a
                                class="nav-link font-weight-bold"
                                :class="{ active: tabActiva === 'admisionadas' }"
                                href="#"
                                @click.prevent="tabActiva = 'admisionadas'"
                            >
                                <i class="fa fa-list mr-1"></i>Atenciones en Sala / Admisionadas
                                <span v-if="totalAtenciones > 0" class="badge badge-primary ml-1">{{
                                    totalAtenciones
                                }}</span>
                            </a>
                        </li>
                        <li v-if="sedeInterface" class="nav-item">
                            <a
                                class="nav-link font-weight-bold"
                                :class="{ active: tabActiva === 'sahi' }"
                                href="#"
                                @click.prevent="tabActiva = 'sahi'"
                            >
                                <i class="fa fa-hospital-o mr-1"></i>Atenciones Disponibles SAHI
                                (Espinal)
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    <!-- Tab 1: Atenciones Admisionadas en LIDiag -->
                    <div v-show="tabActiva === 'admisionadas'">
                        <!-- Filtros locales -->
                        <div class="row mb-3">
                            <div class="col-md-3 col-sm-6 mb-2">
                                <label class="small font-weight-bold mb-1"
                                    >Buscar Paciente / Atención:</label
                                >
                                <input
                                    type="text"
                                    v-model="filtroLocal.busqueda"
                                    class="form-control form-control-sm"
                                    placeholder="Doc, Nombre o # Atención..."
                                    @keyup.enter="cargarAtenciones()"
                                />
                            </div>
                            <div class="col-md-2 col-sm-6 mb-2">
                                <label class="small font-weight-bold mb-1">Estado:</label>
                                <select
                                    v-model="filtroLocal.estado"
                                    class="form-control form-control-sm"
                                    @change="cargarAtenciones()"
                                >
                                    <option value="">Todos los estados</option>
                                    <option value="PENDIENTE">PENDIENTE</option>
                                    <option value="EN_CONSULTA">EN CONSULTA</option>
                                    <option value="ATENDIDO">ATENDIDO</option>
                                    <option value="ANULADO">ANULADO</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <label class="small font-weight-bold mb-1">Fecha Ingreso:</label>
                                <div class="d-flex gap-1">
                                    <input
                                        type="date"
                                        v-model="filtroLocal.fecha_ini"
                                        class="form-control form-control-sm mr-1"
                                    />
                                    <input
                                        type="date"
                                        v-model="filtroLocal.fecha_fin"
                                        class="form-control form-control-sm"
                                    />
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6 mb-2 d-flex align-items-end">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-primary mr-1"
                                    @click="cargarAtenciones()"
                                    :disabled="cargandoAtenciones"
                                >
                                    <i
                                        class="fa mr-1"
                                        :class="
                                            cargandoAtenciones ? 'fa-spinner fa-spin' : 'fa-filter'
                                        "
                                    ></i
                                    >Filtrar
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    @click="limpiarFiltrosLocales()"
                                >
                                    <i class="fa fa-eraser mr-1"></i>Limpiar
                                </button>
                            </div>
                        </div>

                        <!-- Tabla de Atenciones -->
                        <div class="table-responsive">
                            <table
                                class="table table-sm table-hover table-bordered table-custom-compact"
                            >
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 12%">Fecha / Ingreso</th>
                                        <th style="width: 12%">Atención</th>
                                        <th style="width: 20%">Paciente</th>
                                        <th style="width: 14%">Recaudo</th>
                                        <th style="width: 16%">Aseguramiento</th>
                                        <th style="width: 10%">Estado</th>
                                        <th
                                            v-if="sedeInterface"
                                            style="width: 8%"
                                            class="text-center"
                                        >
                                            SAHI
                                        </th>
                                        <th style="width: 8%" class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="cargandoAtenciones">
                                        <td
                                            :colspan="sedeInterface ? 8 : 7"
                                            class="text-center py-4"
                                        >
                                            <i class="fa fa-spinner fa-spin fa-2x text-primary"></i>
                                            <p class="mb-0 mt-2 text-muted">
                                                Cargando atenciones...
                                            </p>
                                        </td>
                                    </tr>
                                    <tr v-else-if="listaAtenciones.length === 0">
                                        <td
                                            :colspan="sedeInterface ? 8 : 7"
                                            class="text-center py-4 text-muted"
                                        >
                                            <i
                                                class="fa fa-folder-open-o fa-2x text-muted mb-1"
                                            ></i>
                                            <p class="mb-0">
                                                No se encontraron atenciones registradas para los
                                                criterios aplicados.
                                            </p>
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="item in listaAtenciones"
                                        :key="item.id"
                                        :class="{
                                            'table-danger': item.estado === 'ANULADO',
                                        }"
                                    >
                                        <td>
                                            <span class="font-weight-bold">{{
                                                formatearFecha(item.created_at)
                                            }}</span>
                                            <small class="d-block text-muted">{{
                                                formatearHora(item.created_at)
                                            }}</small>
                                        </td>
                                        <td>
                                            <span
                                                class="badge badge-light border text-dark font-weight-bold"
                                            >
                                                #{{ item.atencion_id }}
                                            </span>
                                            <small v-if="item.ubicacion" class="d-block text-muted">
                                                <i class="fa fa-hospital-o mr-1"></i
                                                >{{ item.ubicacion }}
                                            </small>
                                        </td>
                                        <td>
                                            <span class="font-weight-bold text-dark d-block">
                                                {{
                                                    item.paciente
                                                        ? item.paciente.nombres
                                                        : "PACIENTE"
                                                }}
                                            </span>
                                            <small class="text-primary">
                                                <i class="fa fa-id-card-o mr-1"></i
                                                >{{ item.paciente ? item.paciente.num_docu : "-" }}
                                            </small>
                                        </td>
                                        <td>
                                            <span
                                                class="badge"
                                                :class="obtenerBadgeRecaudo(item.tipo_recaudo)"
                                            >
                                                {{
                                                    item.tipo_recaudo
                                                        ? item.tipo_recaudo.nombre
                                                        : "Institucional"
                                                }}
                                            </span>
                                        </td>
                                        <td>
                                            <span
                                                class="font-weight-bold text-dark d-block text-truncate"
                                                style="max-width: 180px"
                                            >
                                                {{
                                                    item.admin_salud
                                                        ? item.admin_salud.nom_admin
                                                        : "PARTICULAR"
                                                }}
                                            </span>
                                            <small
                                                v-if="item.contrato || item.plan"
                                                class="text-muted"
                                            >
                                                C: {{ item.contrato || "-" }} | P:
                                                {{ item.plan || "-" }}
                                            </small>
                                        </td>
                                        <td>
                                            <span
                                                class="badge"
                                                :class="obtenerBadgeEstado(item.estado)"
                                            >
                                                {{ item.estado }}
                                            </span>
                                        </td>
                                        <td v-if="sedeInterface" class="text-center">
                                            <span
                                                class="badge"
                                                :class="obtenerBadgeSahi(item.estado_sahi)"
                                            >
                                                {{ item.estado_sahi }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <button
                                                    v-if="item.estado !== 'ANULADO'"
                                                    type="button"
                                                    class="btn btn-outline-danger btn-xs"
                                                    title="Anular atención"
                                                    @click="abrirModalAnular(item)"
                                                >
                                                    <i class="fa fa-ban"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 2: Consultar Atenciones en SAHI (Espinal) -->
                    <div v-if="sedeInterface" v-show="tabActiva === 'sahi'">
                        <div class="row mb-3">
                            <div class="col-md-4 col-sm-6 mb-2">
                                <label class="small font-weight-bold mb-1"
                                    >Buscar en SAHI (Doc o Nombre):</label
                                >
                                <input
                                    type="text"
                                    v-model="filtroSahi.busqueda"
                                    class="form-control form-control-sm"
                                    placeholder="Número de documento o nombre..."
                                    @keyup.enter="consultarSahi()"
                                />
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <label class="small font-weight-bold mb-1"
                                    ># Atención Específica:</label
                                >
                                <input
                                    type="text"
                                    v-model="filtroSahi.atencion_id"
                                    class="form-control form-control-sm"
                                    placeholder="Ej: 7031401"
                                    @keyup.enter="consultarSahi()"
                                />
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <label class="small font-weight-bold mb-1">Rango de Fecha:</label>
                                <div class="d-flex gap-1">
                                    <input
                                        type="date"
                                        v-model="filtroSahi.fecha_ini"
                                        class="form-control form-control-sm mr-1"
                                    />
                                    <input
                                        type="date"
                                        v-model="filtroSahi.fecha_fin"
                                        class="form-control form-control-sm"
                                    />
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-6 mb-2 d-flex align-items-end">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-info btn-block"
                                    @click="consultarSahi()"
                                    :disabled="cargandoSahi"
                                >
                                    <i
                                        class="fa mr-1"
                                        :class="cargandoSahi ? 'fa-spinner fa-spin' : 'fa-search'"
                                    ></i
                                    >Buscar SAHI
                                </button>
                            </div>
                        </div>

                        <!-- Tabla SAHI -->
                        <div class="table-responsive">
                            <table
                                class="table table-sm table-hover table-bordered table-custom-compact"
                            >
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 10%">Atención</th>
                                        <th style="width: 14%">Fecha Ingreso</th>
                                        <th style="width: 22%">Paciente</th>
                                        <th style="width: 24%">Procedimiento / Consulta</th>
                                        <th style="width: 15%">Motivo</th>
                                        <th style="width: 15%" class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="cargandoSahi">
                                        <td colspan="6" class="text-center py-4">
                                            <i class="fa fa-spinner fa-spin fa-2x text-info"></i>
                                            <p class="mb-0 mt-2 text-muted">
                                                Consultando atenciones en SQL Server...
                                            </p>
                                        </td>
                                    </tr>
                                    <tr v-else-if="listaSahi.length === 0">
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="fa fa-info-circle fa-2x text-muted mb-1"></i>
                                            <p class="mb-0">
                                                No se encontraron atenciones de oftalmología en SAHI
                                                para el criterio de búsqueda.
                                            </p>
                                        </td>
                                    </tr>
                                    <tr v-for="item in listaSahi" :key="item.atencion_id">
                                        <td>
                                            <span
                                                class="badge badge-light border text-dark font-weight-bold"
                                            >
                                                #{{ item.atencion_id }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="font-weight-bold">{{
                                                formatearFecha(item.fecha_ingreso)
                                            }}</span>
                                            <small class="d-block text-muted">{{
                                                formatearHora(item.fecha_ingreso)
                                            }}</small>
                                        </td>
                                        <td>
                                            <span class="font-weight-bold text-dark d-block">{{
                                                item.paciente_nombre
                                            }}</span>
                                            <small class="text-primary">
                                                <i class="fa fa-id-card-o mr-1"></i
                                                >{{ item.documento }} ({{ item.sexo }})
                                            </small>
                                        </td>
                                        <td>
                                            <span class="font-weight-bold text-dark d-block">{{
                                                item.producto_nombre
                                            }}</span>
                                            <small class="text-muted">CUPS: {{ item.cups }}</small>
                                        </td>
                                        <td>
                                            <span
                                                class="small text-muted text-truncate d-block"
                                                style="max-width: 180px"
                                            >
                                                {{
                                                    item.motivo_consulta || "Sin motivo registrado"
                                                }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <button
                                                v-if="!item.ya_admitido"
                                                type="button"
                                                class="btn btn-sm btn-success"
                                                @click="admisionarDesdeSahi(item)"
                                            >
                                                <i class="fa fa-check-circle mr-1"></i>Admisionar
                                            </button>
                                            <span v-else class="badge badge-info">
                                                <i class="fa fa-check mr-1"></i>Ya en Sala
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Drawer / Modal Lateral de Admisión Directa -->
        <div v-if="drawerVisible" class="modal-backdrop fade show" @click="cerrarDrawer()"></div>
        <div class="right-drawer shadow" :class="{ 'right-drawer-open': drawerVisible }">
            <div
                class="drawer-header bg-primary text-white d-flex justify-content-between align-items-center p-3"
            >
                <h6 class="mb-0 font-weight-bold">
                    <i class="fa fa-user-plus mr-1"></i>{{ tituloDrawer }}
                </h6>
                <button type="button" class="close text-white" @click="cerrarDrawer()">
                    &times;
                </button>
            </div>
            <div class="drawer-body p-3">
                <form @submit.prevent="guardarAdmisionDirecta()">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold mb-1">Tipo de Recaudo:</label>
                        <select
                            v-model="formulario.tipo_recaudo_id"
                            class="form-control form-control-sm"
                            required
                        >
                            <option v-for="rec in catalogoRecaudos" :key="rec.id" :value="rec.id">
                                {{ rec.nombre }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group mb-2">
                        <label class="small font-weight-bold mb-1">Documento Paciente:</label>
                        <div class="input-group input-group-sm">
                            <input
                                type="text"
                                v-model="formulario.documento"
                                class="form-control form-control-sm"
                                placeholder="Número de documento..."
                                required
                                @blur="buscarPacientePorDocumento()"
                            />
                            <div class="input-group-append">
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    @click="buscarPacientePorDocumento()"
                                >
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-2">
                        <label class="small font-weight-bold mb-1">Nombre Completo:</label>
                        <input
                            type="text"
                            v-model="formulario.paciente_nombre"
                            class="form-control form-control-sm"
                            placeholder="Nombres y apellidos..."
                            required
                        />
                    </div>

                    <div class="row">
                        <div class="col-6 form-group mb-2">
                            <label class="small font-weight-bold mb-1">Sexo:</label>
                            <select v-model="formulario.sexo" class="form-control form-control-sm">
                                <option value="M">Masculino</option>
                                <option value="F">Femenino</option>
                                <option value="O">Otro</option>
                            </select>
                        </div>
                        <div class="col-6 form-group mb-2">
                            <label class="small font-weight-bold mb-1">Fec. Nacimiento:</label>
                            <input
                                type="date"
                                v-model="formulario.fecha_nacimiento"
                                class="form-control form-control-sm"
                            />
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 form-group mb-2">
                            <label class="small font-weight-bold mb-1">Teléfono:</label>
                            <input
                                type="text"
                                v-model="formulario.telefono"
                                class="form-control form-control-sm"
                                placeholder="Móvil..."
                            />
                        </div>
                        <div class="col-6 form-group mb-2">
                            <label class="small font-weight-bold mb-1">Ubicación / Sala:</label>
                            <input
                                type="text"
                                v-model="formulario.ubicacion"
                                class="form-control form-control-sm"
                                placeholder="Consultorio..."
                            />
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-end gap-2">
                        <button
                            type="button"
                            class="btn btn-sm btn-secondary mr-2"
                            @click="cerrarDrawer()"
                        >
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-sm btn-primary" :disabled="guardando">
                            <i
                                class="fa mr-1"
                                :class="guardando ? 'fa-spinner fa-spin' : 'fa-check'"
                            ></i
                            >Guardar Admisión
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from "vue";
import axios from "axios";
import { alertError, alertSuccess, confirmAction, toastMessage } from "@/helpers/alerts.js";
import { useSede } from "@/composables/useSede";

const { sedeActual, sedeInterface, sedeNombre, establecerSede } = useSede();

const tabActiva = ref("admisionadas");
const listaAtenciones = ref([]);
const totalAtenciones = ref(0);
const cargandoAtenciones = ref(false);

const listaSahi = ref([]);
const cargandoSahi = ref(false);

const catalogoRecaudos = ref([]);
const drawerVisible = ref(false);
const tituloDrawer = ref("Admisión Directa");
const guardando = ref(false);

const filtroLocal = reactive({
    busqueda: "",
    estado: "",
    fecha_ini: "",
    fecha_fin: "",
});

const filtroSahi = reactive({
    busqueda: "",
    atencion_id: "",
    fecha_ini: new Date().toISOString().slice(0, 10),
    fecha_fin: new Date().toISOString().slice(0, 10),
});

const formulario = reactive({
    sede_id: null,
    tipo_recaudo_id: null,
    atencion_id: "",
    paciente_id: null,
    documento: "",
    paciente_nombre: "",
    sexo: "M",
    fecha_nacimiento: "",
    telefono: "",
    ubicacion: "Consulta Externa Oftalmología",
});

const cargarAtenciones = async () => {
    if (!sedeActual.value) return;
    cargandoAtenciones.value = true;
    try {
        const params = {
            sede_id: sedeActual.value,
            ...filtroLocal,
        };
        const res = await axios.get("/api/oftalmologia/atenciones", { params });
        if (res.data?.success) {
            listaAtenciones.value = res.data.data?.data || [];
            totalAtenciones.value = res.data.data?.total || 0;
        }
    } catch (e) {
        console.error("Error cargando atenciones:", e);
    } finally {
        cargandoAtenciones.value = false;
    }
};

const consultarSahi = async () => {
    if (!sedeActual.value || !sedeInterface.value) return;
    cargandoSahi.value = true;
    try {
        const params = {
            sede_id: sedeActual.value,
            ...filtroSahi,
        };
        const res = await axios.get("/api/oftalmologia/atenciones/sahi", {
            params,
        });
        if (res.data?.success) {
            listaSahi.value = res.data.data || [];
        } else {
            toastMessage({
                heading: "Aviso SAHI",
                text: res.data?.message || "No se obtuvieron resultados.",
                icon: "warning",
            });
        }
    } catch (e) {
        alertError(
            e.response?.data?.message || "No fue posible consultar en el servidor SAHI.",
            "Error de Conexión"
        );
    } finally {
        cargandoSahi.value = false;
    }
};

const cargarRecaudos = async () => {
    try {
        const res = await axios.get("/api/config-tipos-recaudo/listar/activos");
        if (res.data?.success || Array.isArray(res.data)) {
            catalogoRecaudos.value = res.data.data || res.data || [];
        }
    } catch (e) {
        console.error("Error cargando tipos de recaudo:", e);
    }
};

const abrirAdmisionDirecta = (tipoCodigo) => {
    formulario.sede_id = sedeActual.value;
    formulario.atencion_id = "";
    formulario.paciente_id = null;
    formulario.documento = "";
    formulario.paciente_nombre = "";
    formulario.telefono = "";
    formulario.fecha_nacimiento = "";

    const rec = catalogoRecaudos.value.find(
        (r) => r.codigo === tipoCodigo || r.nombre?.toUpperCase().includes(tipoCodigo)
    );
    formulario.tipo_recaudo_id = rec ? rec.id : null;
    tituloDrawer.value = `+ Admisión ${tipoCodigo === "CORTESIA" ? "Cortesía" : "Particular"}`;
    drawerVisible.value = true;
};

const cerrarDrawer = () => {
    drawerVisible.value = false;
};

const buscarPacientePorDocumento = async () => {
    const doc = formulario.documento?.trim();
    if (!doc) return;
    try {
        const res = await axios.get(`/api/pacientes/listar?bus_documento=${doc}`);
        const lista = res.data?.data?.data || res.data?.data || [];
        if (lista.length > 0) {
            const p = lista[0];
            formulario.paciente_id = p.id;
            formulario.paciente_nombre = p.nombres || p.nom_completo || "";
            formulario.sexo = p.sexo || "M";
            formulario.fecha_nacimiento = p.fec_naci || "";
            formulario.telefono = p.cel || p.tel || "";
        }
    } catch (e) {
        console.error("Error buscando paciente:", e);
    }
};

const guardarAdmisionDirecta = async () => {
    guardando.value = true;
    try {
        const res = await axios.post("/api/oftalmologia/admisionar", formulario);
        if (res.data?.success) {
            toastMessage({
                heading: "Admisión Exitosa",
                text: res.data.message,
                icon: "success",
            });
            cerrarDrawer();
            await cargarAtenciones();
        }
    } catch (e) {
        alertError(
            e.response?.data?.message || "No fue posible registrar la admisión.",
            "Error al Admisionar"
        );
    } finally {
        guardando.value = false;
    }
};

const admisionarDesdeSahi = async (item) => {
    const recSahi = catalogoRecaudos.value.find((r) => r.envia_sahi) || catalogoRecaudos.value[0];
    const payload = {
        sede_id: sedeActual.value,
        tipo_recaudo_id: recSahi?.id || 1,
        atencion_id: item.atencion_id,
        documento: item.documento,
        paciente_nombre: item.paciente_nombre,
        sexo: item.sexo,
        fecha_nacimiento: item.fecha_nacimiento,
        telefono: item.telefono,
        direccion: item.direccion,
        ubicacion: "Consulta Externa Oftalmología",
        fec_ingreso: item.fecha_ingreso,
    };

    try {
        const res = await axios.post("/api/oftalmologia/admisionar", payload);
        if (res.data?.success) {
            toastMessage({
                heading: "Admisión Registrada",
                text: `Atención #${item.atencion_id} ingresada a sala.`,
                icon: "success",
            });
            item.ya_admitido = true;
            await cargarAtenciones();
        }
    } catch (e) {
        alertError(e.response?.data?.message || "No fue posible admisionar.", "Error");
    }
};

const abrirModalAnular = async (item) => {
    const confirmed = await confirmAction({
        title: `¿Anular Atención #${item.atencion_id}?`,
        text: "Esta acción anulará el ingreso del paciente a Oftalmología.",
        type: "warning",
    });

    if (confirmed) {
        try {
            const res = await axios.post(`/api/oftalmologia/atenciones/${item.id}/anular`, {
                motivo: "Anulado por el usuario desde módulo de admisión",
            });
            if (res.data?.success) {
                alertSuccess("La atención ha sido anulada con éxito.", "Anulada");
                await cargarAtenciones();
            }
        } catch (e) {
            alertError(e.response?.data?.message || "No se pudo anular la atención.", "Error");
        }
    }
};

const limpiarFiltrosLocales = () => {
    filtroLocal.busqueda = "";
    filtroLocal.estado = "";
    filtroLocal.fecha_ini = "";
    filtroLocal.fecha_fin = "";
    cargarAtenciones();
};

const formatearFecha = (fech) => {
    if (!fech) return "-";
    const d = new Date(fech);
    if (isNaN(d.getTime())) return fech;
    return d.toLocaleDateString("es-CO");
};

const formatearHora = (fech) => {
    if (!fech) return "";
    const d = new Date(fech);
    if (isNaN(d.getTime())) return "";
    return d.toLocaleTimeString("es-CO", { hour: "2-digit", minute: "2-digit" });
};

const obtenerBadgeRecaudo = (recaudo) => {
    if (!recaudo) return "badge-info";
    const cod = recaudo.codigo?.toUpperCase();
    if (cod === "PARTICULAR") return "badge-primary";
    if (cod === "CORTESIA") return "badge-warning text-white";
    return "badge-info";
};

const obtenerBadgeEstado = (estado) => {
    if (estado === "ATENDIDO") return "badge-success";
    if (estado === "EN_CONSULTA") return "badge-primary";
    if (estado === "ANULADO") return "badge-danger";
    return "badge-warning text-white";
};

const obtenerBadgeSahi = (estado) => {
    if (estado === "ENVIADO") return "badge-success";
    if (estado === "ERROR") return "badge-danger";
    if (estado === "OMITIDO") return "badge-light text-muted border";
    return "badge-secondary";
};

onMounted(async () => {
    await establecerSede();
    await cargarRecaudos();
    await cargarAtenciones();
});
</script>

<style scoped>
.table-custom-compact th,
.table-custom-compact td {
    padding: 6px 8px;
    font-size: 0.875rem;
    vertical-align: middle;
}
.right-drawer {
    position: fixed;
    top: 0;
    right: -420px;
    width: 420px;
    height: 100vh;
    background: #fff;
    z-index: 1050;
    transition: right 0.3s ease;
    display: flex;
    flex-direction: column;
}
.right-drawer-open {
    right: 0;
}
.drawer-body {
    overflow-y: auto;
    flex: 1;
}
</style>
