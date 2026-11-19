<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Oftalmología</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <button type="button" class="btn btn-link p-0 breadcrumb-btn">
                            Inicio
                        </button>
                    </li>
                    <li class="breadcrumb-item">
                        <button type="button" class="btn btn-link p-0 breadcrumb-btn">
                            Oftalmología
                        </button>
                    </li>
                    <li class="breadcrumb-item active">Admisionar Paciente</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-primary waves-effect waves-light m-r-5"
                    @click="abrirAdmisionDirecta('PARTICULAR')"
                    title="Admisionar paciente particular directamente sin requerir atención previa"
                >
                    <i class="fa fa-user-plus mr-1"></i>
                    + Particular
                </button>
                <button
                    type="button"
                    class="btn btn-warning text-white waves-effect waves-light m-r-5"
                    @click="abrirAdmisionDirecta('CORTESIA')"
                    title="Admisionar paciente de cortesía directamente sin requerir atención previa"
                >
                    <i class="fa fa-gift mr-1"></i>
                    + Cortesía
                </button>
                <button
                    type="button"
                    class="btn btn-info waves-effect waves-light"
                    @click="listarAtenciones()"
                    :disabled="isBuscandoAtenciones"
                    title="Actualizar listado de atenciones"
                >
                    <i
                        class="fa mr-1"
                        :class="isBuscandoAtenciones ? 'fa-spinner fa-spin' : 'fa-refresh'"
                    ></i>
                    Refrescar
                </button>
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
                            <i class="fa fa-filter"></i> Filtros para búsqueda de atención
                        </h4>
                        <form action="#" @submit.prevent="listarAtenciones()">
                            <div class="form-body">
                                <div class="row p-t-2">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label" for="bus_nom_num_docu">
                                                # de atención, # de documento ó Nombres de paciente
                                            </label>
                                            <input
                                                type="text"
                                                id="bus_nom_num_docu"
                                                name="bus_nom_num_docu"
                                                v-model="busqueda.bus_nom_num_docu"
                                                class="form-control"
                                                placeholder="Atención, Nombres de paciente o # de documento"
                                                @keyup.enter="listarAtenciones()"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label" for="bus_fech_ini"
                                                >Fecha Inicio</label
                                            >
                                            <input
                                                type="date"
                                                id="bus_fech_ini"
                                                name="bus_fech_ini"
                                                v-model="busqueda.bus_fech_ini"
                                                class="form-control"
                                                @keyup.enter="listarAtenciones()"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label" for="bus_fecha_fin"
                                                >Fecha Fin</label
                                            >
                                            <input
                                                type="date"
                                                id="bus_fecha_fin"
                                                name="bus_fecha_fin"
                                                v-model="busqueda.bus_fecha_fin"
                                                class="form-control"
                                                @keyup.enter="listarAtenciones()"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions mt-3">
                                    <button
                                        type="button"
                                        class="btn btn-success waves-effect waves-light m-r-5"
                                        @click="listarAtenciones()"
                                        :disabled="isBuscandoAtenciones"
                                    >
                                        <i
                                            class="fa mr-1"
                                            :class="
                                                isBuscandoAtenciones
                                                    ? 'fa-spinner fa-spin'
                                                    : 'fa-search'
                                            "
                                        ></i>
                                        {{ isBuscandoAtenciones ? "Buscando..." : "Buscar" }}
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-inverse waves-effect waves-light"
                                        @click="btnLimpiar()"
                                        :disabled="isBuscandoAtenciones || isGuardandoAdmision"
                                    >
                                        <i class="fa fa-refresh mr-1"></i> Limpiar
                                    </button>
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
                        <h4 class="card-title">
                            Listado de atenciones disponibles de Oftalmología.
                        </h4>
                        <h6 class="card-subtitle text-info">
                            Seleccione el paciente para su respectiva admisión clínica.
                        </h6>
                        <table-skeleton-loader
                            :visible="isBuscandoAtenciones"
                            :rows="6"
                            :columns="8"
                        />
                        <empty-state-component
                            v-if="!isBuscandoAtenciones && registros.length === 0"
                            icon="sl-icon-eye"
                            title="No hay atenciones para mostrar"
                            description="No se encontraron atenciones con los criterios de búsqueda seleccionados o la sede no cuenta con atenciones pendientes de SAHI."
                            action-text="Limpiar filtros"
                            action-icon="fa fa-refresh"
                            @action="btnLimpiar()"
                        />
                        <div
                            class="table-responsive"
                            v-show="!isBuscandoAtenciones && registros.length > 0"
                        >
                            <table
                                id="tablaAdmisionesOftalmologia"
                                ref="dt"
                                class="table table-hover table-bordered table-custom-compact"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 12%">Fecha / Ingreso</th>
                                        <th style="width: 20%">Paciente</th>
                                        <th style="width: 25%">Procedimiento / Servicio</th>
                                        <th style="width: 20%">Aseguramiento / EPS</th>
                                        <th style="width: 13%">Ubicación & Cama</th>
                                        <th style="width: 10%" class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(item, index) in registros"
                                        :key="item.id || item.IdAtencion || index"
                                    >
                                        <!-- 1. Fecha / Ingreso -->
                                        <td class="align-middle">
                                            <div
                                                class="font-weight-bold text-dark"
                                                style="font-size: 13px"
                                            >
                                                <i class="fa fa-calendar-o text-muted mr-1"></i>
                                                {{
                                                    formatearFecha(
                                                        item.FecIngreso || item.fechor_estudio
                                                    )
                                                }}
                                            </div>
                                            <small
                                                class="text-muted d-block"
                                                style="font-size: 11px"
                                            >
                                                <i class="fa fa-clock-o mr-1"></i
                                                >{{
                                                    formatearHora(
                                                        item.FecIngreso || item.fechor_estudio
                                                    )
                                                }}
                                            </small>
                                        </td>

                                        <!-- 2. Paciente + Documento -->
                                        <td class="align-middle">
                                            <div class="paciente-nombre font-weight-bold text-dark">
                                                {{ item.PrimerNombre || item.paciente_nombre }}
                                                {{ item.SegundoNombre || "" }}
                                                {{ item.PrimerApellido || "" }}
                                                {{ item.SegundoApellido || "" }}
                                            </div>
                                            <div
                                                class="text-primary mt-1"
                                                style="font-size: 12px; font-weight: 600"
                                            >
                                                <i class="fa fa-id-card-o mr-1"></i>
                                                {{ item.NumDocumento || item.num_docu }}
                                            </div>
                                        </td>

                                        <!-- 3. Examen / Procedimiento -->
                                        <td class="align-middle">
                                            <div
                                                class="examen-titulo font-weight-bold text-dark"
                                                :title="item.NomProducto || item.procedimiento"
                                            >
                                                {{
                                                    item.NomProducto ||
                                                    item.procedimiento ||
                                                    "Consulta de Oftalmología"
                                                }}
                                            </div>
                                            <div class="mt-1 d-flex flex-wrap align-items-center">
                                                <small
                                                    v-if="item.CodProducto || item.cod_cups"
                                                    class="text-muted mr-2"
                                                >
                                                    CUPS: {{ item.CodProducto || item.cod_cups }}
                                                </small>
                                            </div>
                                        </td>

                                        <!-- 4. Aseguramiento / EPS -->
                                        <td class="align-middle">
                                            <div
                                                class="font-weight-bold text-dark text-truncate"
                                                style="max-width: 220px"
                                                :title="
                                                    item.NomTercero ||
                                                    item.eps ||
                                                    'Particular / Directo'
                                                "
                                            >
                                                {{
                                                    item.NomTercero ||
                                                    item.eps ||
                                                    "Particular / Directo"
                                                }}
                                            </div>
                                            <div
                                                v-if="item.DesContrato || item.contrato"
                                                class="small text-muted mt-1"
                                                style="font-size: 11px; line-height: 1.3"
                                            >
                                                <div class="text-truncate" style="max-width: 220px">
                                                    <strong>C:</strong>
                                                    {{ item.DesContrato || item.contrato }}
                                                </div>
                                            </div>
                                        </td>

                                        <!-- 5. Ubicación & Cama -->
                                        <td class="align-middle">
                                            <div
                                                class="text-truncate"
                                                style="max-width: 140px"
                                                :title="
                                                    item.NomUbicacion ||
                                                    item.ubicacion ||
                                                    'Consulta Externa'
                                                "
                                            >
                                                <i class="fa fa-map-marker text-muted mr-1"></i>
                                                {{
                                                    item.NomUbicacion ||
                                                    item.ubicacion ||
                                                    "Consulta Externa"
                                                }}
                                            </div>
                                            <small
                                                v-if="item.Cama || item.cama"
                                                class="text-muted d-block"
                                            >
                                                Cama: {{ item.Cama || item.cama }}
                                            </small>
                                        </td>

                                        <!-- 6. Acciones -->
                                        <td class="text-center align-middle text-nowrap">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm mr-1"
                                                @click="
                                                    actualizar = true;
                                                    mostrarRegistro(item);
                                                "
                                                title="Admisionar paciente"
                                                aria-label="Admisionar paciente"
                                            >
                                                <i class="fa fa-check"></i>
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

        <!-- ============================================================== -->
        <!-- Drawer Lateral de Admisión (Estructura idéntica a Ecografías) -->
        <!-- ============================================================== -->
        <transition name="fade">
            <div
                v-if="sidebarVisible"
                class="sidebar-backdrop"
                @click="cerrarSidebar()"
                aria-hidden="true"
            ></div>
        </transition>

        <div
            id="drawerAdmisionarOftalmologia"
            class="right-sidebar sidebar-scroll"
            :class="{ 'shw-rside': sidebarVisible }"
        >
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <button
                        type="button"
                        class="right-side-toggle"
                        @click="cerrarSidebar()"
                        aria-label="Cerrar panel"
                    >
                        <i class="ti-close" id="btnCerrarModalForm"></i>
                    </button>
                </div>
                <div class="r-panel-body">
                    <form action="#" @submit.prevent="guardarRegistro()">
                        <div class="form-body">
                            <!-- Resumen informativo de EPS / Contrato -->
                            <div
                                v-if="
                                    !esParticular &&
                                    !esCortesia &&
                                    (registro.admin_salud ||
                                        registro.contrato ||
                                        registro.atencion_id)
                                "
                                class="alert alert-info py-2 px-3 mb-3 border-left-info shadow-sm"
                                style="
                                    font-size: 13px;
                                    line-height: 1.5;
                                    border-left: 4px solid #009efb;
                                "
                            >
                                <div class="row">
                                    <div class="col-md-4" v-if="registro.admin_salud">
                                        <strong
                                            ><i class="fa fa-hospital-o mr-1"></i>EPS /
                                            Tercero:</strong
                                        >
                                        <div class="text-dark font-weight-bold">
                                            {{ registro.admin_salud }}
                                        </div>
                                    </div>
                                    <div class="col-md-4" v-if="registro.contrato">
                                        <strong
                                            ><i class="fa fa-file-text-o mr-1"></i>Contrato:</strong
                                        >
                                        <div class="text-dark font-weight-bold">
                                            {{ registro.contrato }}
                                        </div>
                                    </div>
                                    <div class="col-md-4" v-if="registro.atencion_id">
                                        <strong
                                            ><i class="fa fa-hashtag mr-1"></i>Atención
                                            SAHI:</strong
                                        >
                                        <div class="text-dark font-weight-bold">
                                            {{ registro.atencion_id }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Selector de Tipo de Recaudo -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label
                                            class="control-label font-weight-bold"
                                            for="selTipoRecaudo"
                                            >Tipo de Recaudo
                                            <span class="text-danger">*</span></label
                                        >
                                        <select
                                            id="selTipoRecaudo"
                                            v-model="registro.tipo_recaudo_id"
                                            class="form-control"
                                            @change="alCambiarTipoRecaudo()"
                                        >
                                            <option
                                                v-for="tr in tiposRecaudo"
                                                :key="tr.id"
                                                :value="tr.id"
                                            >
                                                {{ tr.nombre }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6" v-if="!esParticular && !esCortesia">
                                    <div class="form-group">
                                        <label
                                            class="control-label font-weight-bold"
                                            for="txtAtencionId"
                                            ># de Atención SAHI
                                            <span class="text-danger">*</span></label
                                        >
                                        <input
                                            type="text"
                                            id="txtAtencionId"
                                            v-model="registro.atencion_id"
                                            class="form-control"
                                            placeholder="# Atención"
                                        />
                                    </div>
                                </div>
                            </div>

                            <h5 class="text-themecolor border-bottom pb-2 mt-2">
                                <i class="fa fa-user mr-1"></i> Datos del Paciente
                            </h5>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label" for="selTipoDoc"
                                            >Tipo Documento</label
                                        >
                                        <Select2
                                            id="selTipoDoc"
                                            ref="selTipoDoc"
                                            :value="registro.tipo_documento_id"
                                            :options="tipoDocumentoOptions"
                                            width="100%"
                                            placeholder="Seleccione tipo documento"
                                            @select="alSeleccionarTipoDocumento($event)"
                                        />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label" for="num_docu"
                                            >Documento <span class="text-danger">*</span></label
                                        >
                                        <input
                                            type="text"
                                            id="num_docu"
                                            v-model="registro.num_docu"
                                            class="form-control"
                                            placeholder="Número de documento"
                                            @blur="buscarPacientePorDocumento()"
                                            @keyup.enter="buscarPacientePorDocumento()"
                                        />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label" for="nombres"
                                            >Nombres / Razón Social
                                            <span class="text-danger">*</span></label
                                        >
                                        <input
                                            type="text"
                                            id="nombres"
                                            v-model="registro.nombres"
                                            class="form-control"
                                            placeholder="Primer y segundo nombre"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label" for="pri_ape"
                                            >Primer Apellido</label
                                        >
                                        <input
                                            type="text"
                                            id="pri_ape"
                                            v-model="registro.pri_ape"
                                            class="form-control"
                                            placeholder="Primer apellido"
                                        />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label" for="seg_ape"
                                            >Segundo Apellido</label
                                        >
                                        <input
                                            type="text"
                                            id="seg_ape"
                                            v-model="registro.seg_ape"
                                            class="form-control"
                                            placeholder="Segundo apellido"
                                        />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label" for="selSexo">Sexo</label>
                                        <select
                                            id="selSexo"
                                            v-model="registro.sexo"
                                            class="form-control"
                                        >
                                            <option value="">Seleccione...</option>
                                            <option value="M">Masculino</option>
                                            <option value="F">Femenino</option>
                                            <option value="O">Otro</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label" for="fec_naci"
                                            >Fecha de Nacimiento</label
                                        >
                                        <input
                                            type="date"
                                            id="fec_naci"
                                            v-model="registro.fec_naci"
                                            class="form-control"
                                        />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label" for="tel"
                                            >Teléfono / Celular</label
                                        >
                                        <input
                                            type="text"
                                            id="tel"
                                            v-model="registro.tel"
                                            class="form-control"
                                            placeholder="Celular"
                                        />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label" for="direccion"
                                            >Dirección</label
                                        >
                                        <input
                                            type="text"
                                            id="direccion"
                                            v-model="registro.direccion"
                                            class="form-control"
                                            placeholder="Dirección"
                                        />
                                    </div>
                                </div>
                            </div>

                            <h5 class="text-themecolor border-bottom pb-2 mt-3">
                                <i class="fa fa-stethoscope mr-1"></i> Asignación y Profesional
                            </h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label
                                            class="control-label font-weight-bold"
                                            for="selMedico"
                                            >Especialista de Oftalmología
                                            <span class="text-danger">*</span></label
                                        >
                                        <Select2
                                            id="selMedico"
                                            ref="selMedico"
                                            :value="registro.medico_id"
                                            :options="medicoOptions"
                                            width="100%"
                                            placeholder="Seleccione especialista"
                                            @select="alSeleccionarMedico($event)"
                                        />
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label" for="txtUbicacion"
                                            >Ubicación</label
                                        >
                                        <input
                                            type="text"
                                            id="txtUbicacion"
                                            v-model="registro.ubicacion"
                                            class="form-control"
                                            placeholder="Consultorio / Sala"
                                        />
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label" for="txtCama"
                                            >Cama / Cubículo</label
                                        >
                                        <input
                                            type="text"
                                            id="txtCama"
                                            v-model="registro.cama"
                                            class="form-control"
                                            placeholder="Cubículo"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Botones de Acción -->
                            <div class="form-actions mt-4 pt-3 border-top">
                                <button
                                    type="button"
                                    class="btn btn-success waves-effect waves-light mr-2"
                                    @click="guardarRegistro()"
                                    :disabled="isGuardandoAdmision"
                                    title="Guardar admisión de oftalmología"
                                >
                                    <i
                                        class="fa mr-1"
                                        :class="
                                            isGuardandoAdmision ? 'fa-spinner fa-spin' : 'fa-check'
                                        "
                                    ></i>
                                    {{ isGuardandoAdmision ? "Guardando..." : "Guardar Admisión" }}
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-inverse waves-effect waves-light"
                                    @click="cerrarSidebar()"
                                    :disabled="isGuardandoAdmision"
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
import { computed, nextTick, onBeforeUnmount, onMounted, ref, getCurrentInstance } from "vue";
const { appContext } = getCurrentInstance();
const emitter = appContext?.config?.globalProperties?.emitter;

import usePermissions from "@/helpers/permissions.js";
import { toastMessage } from "@/helpers/alerts.js";
import { useSede } from "@/composables/useSede.js";
import axios from "axios";

const props = defineProps({
    usuarioactual: { type: Object, default: () => ({}) },
    permisos: { type: Array, default: () => [] },
});

const { can: _can } = usePermissions(props.permisos);
const { sedeActual, sedeInterface, establecerSede: establecerSedeComposable } = useSede();

const dt = ref(null);
const actualizar = ref(false);
const sidebarVisible = ref(false);
const tituloModal = ref("Nueva admisión");
const isBuscandoAtenciones = ref(false);
const isGuardandoAdmision = ref(false);
const registros = ref([]);

const medicos = ref([]);
const tipoDocumentos = ref([]);
const tiposRecaudo = ref([]);

const selTipoDoc = ref(null);
const selMedico = ref(null);

const busqueda = ref({
    sede_id: sedeActual,
    bus_nom_num_docu: "",
    bus_fech_ini: "",
    bus_fecha_fin: "",
});

const registro = ref({
    paciente_id: "",
    tipo_documento_id: "",
    num_docu: "",
    nombres: "",
    seg_nom: "",
    pri_ape: "",
    seg_ape: "",
    sexo: "",
    fec_naci: "",
    direccion: "",
    tel: "",
    sede_id: sedeActual,
    medico_id: null,
    atencion_id: null,
    tipo_recaudo_id: 1,
    contrato: null,
    plan: null,
    admin_salud: null,
    ubicacion: "Consulta Externa",
    cama: "",
});

const esParticular = computed(() => {
    const tr = tiposRecaudo.value.find((t) => t.id === registro.value.tipo_recaudo_id);
    return tr ? tr.codigo === "PARTICULAR" : false;
});

const esCortesia = computed(() => {
    const tr = tiposRecaudo.value.find((t) => t.id === registro.value.tipo_recaudo_id);
    return tr ? tr.codigo === "CORTESIA" : false;
});

const tipoDocumentoOptions = computed(() => {
    return (tipoDocumentos.value || []).map((p) => ({
        id: String(p.id),
        codigo: p.codigo,
        tipo_documento: p.tipo_documento,
    }));
});

const medicoOptions = computed(() => {
    return (medicos.value || []).map((m) => ({
        id: String(m.id),
        name: m.name,
    }));
});

function safeDestroyDataTable(selector = "#tablaAdmisionesOftalmologia") {
    if (typeof window === "undefined" || !window.$ || !window.$.fn || !window.$.fn.DataTable)
        return;
    const $t = window.$(selector);
    if (!$t.length) return;
    if (window.$.fn.DataTable.isDataTable($t)) {
        try {
            $t.DataTable().destroy();
        } catch {
            // Noop
        }
    }
}

function safeInitDataTable(selector = "#tablaAdmisionesOftalmologia") {
    if (typeof window === "undefined" || !window.$ || !window.$.fn || !window.$.fn.DataTable)
        return;
    nextTick(() => {
        const $t = window.$(selector);
        if (!$t.length) return;
        try {
            $t.DataTable({
                pageLength: 15,
                language: {
                    search: "Buscar:",
                    lengthMenu: "Mostrar _MENU_ registros",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ atenciones",
                    infoEmpty: "Sin registros disponibles",
                    zeroRecords: "No se encontraron resultados",
                    paginate: {
                        first: "Primero",
                        last: "Último",
                        next: "Siguiente",
                        previous: "Anterior",
                    },
                },
                order: [[0, "desc"]],
            });
        } catch {
            // Noop
        }
    });
}

function formatearFecha(fecha) {
    if (!fecha) return "—";
    try {
        const d = new Date(fecha);
        if (isNaN(d.getTime())) return fecha;
        const dia = String(d.getDate()).padStart(2, "0");
        const mes = String(d.getMonth() + 1).padStart(2, "0");
        const anio = d.getFullYear();
        return `${dia}/${mes}/${anio}`;
    } catch {
        return fecha;
    }
}

function formatearHora(fecha) {
    if (!fecha) return "";
    try {
        const d = new Date(fecha);
        if (isNaN(d.getTime())) return "";
        let horas = d.getHours();
        const minutos = String(d.getMinutes()).padStart(2, "0");
        const ampm = horas >= 12 ? "PM" : "AM";
        horas = horas % 12;
        horas = horas ? horas : 12;
        return `${String(horas).padStart(2, "0")}:${minutos} ${ampm}`;
    } catch {
        return "";
    }
}

function btnLimpiar() {
    busqueda.value.bus_nom_num_docu = "";
    busqueda.value.bus_fech_ini = "";
    busqueda.value.bus_fecha_fin = "";
    safeDestroyDataTable(dt.value || "#tablaAdmisionesOftalmologia");
    registros.value = [];
    limpiarFormulario();
}

function limpiarFormulario() {
    registro.value = {
        paciente_id: "",
        tipo_documento_id: tipoDocumentos.value[0]?.id || "",
        num_docu: "",
        nombres: "",
        seg_nom: "",
        pri_ape: "",
        seg_ape: "",
        sexo: "",
        fec_naci: "",
        direccion: "",
        tel: "",
        sede_id: sessionStorage.getItem("ST-sede") || localStorage.getItem("ST-sede") || null,
        medico_id: medicos.value[0]?.id || null,
        atencion_id: null,
        tipo_recaudo_id: tiposRecaudo.value[0]?.id || 1,
        contrato: null,
        plan: null,
        admin_salud: null,
        ubicacion: "Consulta Externa",
        cama: "",
    };
}

function cerrarSidebar() {
    sidebarVisible.value = false;
}

async function abrirModal() {
    sidebarVisible.value = true;
    if (!medicos.value || medicos.value.length === 0) await listarMedicos();
    if (!tipoDocumentos.value || tipoDocumentos.value.length === 0) await listarTipoDocumentos();
    if (!tiposRecaudo.value || tiposRecaudo.value.length === 0) await listarTiposRecaudo();

    nextTick(() => {
        setTimeout(() => {
            selTipoDoc.value?.rebuild?.();
            selMedico.value?.rebuild?.();
        }, 50);
        document.getElementById("num_docu")?.focus();
    });
}

async function abrirAdmisionDirecta(tipoCodigo = "PARTICULAR") {
    limpiarFormulario();
    if (!tiposRecaudo.value || tiposRecaudo.value.length === 0) {
        await listarTiposRecaudo();
    }
    const tr = tiposRecaudo.value.find((t) => t.codigo === tipoCodigo);
    if (tr) {
        registro.value.tipo_recaudo_id = tr.id;
    }
    registro.value.atencion_id = "";
    tituloModal.value =
        tipoCodigo === "CORTESIA"
            ? "Nueva Admisión de Cortesía (Directa)"
            : "Nueva Admisión Particular (Directa)";
    await abrirModal();
}

function mostrarRegistro(data = {}) {
    tituloModal.value = `Admisionar Paciente: ${data.PrimerNombre || data.nombres || ""} ${data.PrimerApellido || data.pri_ape || ""}`;
    registro.value.paciente_id = data.paciente_id || "";
    registro.value.num_docu = data.NumDocumento || data.num_docu || "";
    registro.value.nombres = data.PrimerNombre || data.nombres || "";
    registro.value.seg_nom = data.SegundoNombre || data.seg_nom || "";
    registro.value.pri_ape = data.PrimerApellido || data.pri_ape || "";
    registro.value.seg_ape = data.SegundoApellido || data.seg_ape || "";

    const rawSexo = String(data.IdSexo ?? data.sexo ?? "")
        .trim()
        .toUpperCase();
    if (rawSexo === "1" || rawSexo === "M" || rawSexo === "MASCULINO") {
        registro.value.sexo = "M";
    } else if (rawSexo === "2" || rawSexo === "F" || rawSexo === "FEMENINO") {
        registro.value.sexo = "F";
    } else {
        registro.value.sexo = "O";
    }

    registro.value.fec_naci = data.FecNacimiento || data.fec_naci || "";
    registro.value.direccion = data.DirCasa || data.direccion || "";
    registro.value.tel = data.TelCelular || data.TelCasa || data.tel || "";
    registro.value.atencion_id = data.IdAtencion || data.atencion_id || "";
    registro.value.admin_salud = data.NomTercero || data.admin_salud || "";
    registro.value.contrato = data.DesContrato || data.contrato || "";
    registro.value.plan = data.DesPlan || data.plan || "";
    registro.value.ubicacion = data.NomUbicacion || data.ubicacion || "Consulta Externa";
    registro.value.cama = data.Cama || data.cama || "";

    // Si viene de SAHI institucional, seleccionar tipo recaudo institucional
    const recaudoInstitucional = tiposRecaudo.value.find(
        (t) => t.envia_sahi && t.codigo !== "PARTICULAR" && t.codigo !== "CORTESIA"
    );
    if (recaudoInstitucional) {
        registro.value.tipo_recaudo_id = recaudoInstitucional.id;
    }

    abrirModal();
}

async function listarAtenciones() {
    try {
        isBuscandoAtenciones.value = true;
        const sede = sessionStorage.getItem("ST-sede") || localStorage.getItem("ST-sede");
        busqueda.value.sede_id = sede;

        // Si la sede tiene interfaz SAHI, consultamos atenciones SAHI de oftalmología
        const endpoint = sedeInterface.value
            ? "/api/oftalmologia/atenciones/sahi"
            : "/api/oftalmologia/atenciones";

        const res = await axios.post(endpoint, busqueda.value);
        if (res.status === 200) {
            safeDestroyDataTable(dt.value || "#tablaAdmisionesOftalmologia");
            registros.value = res.data?.data?.data || res.data?.data || [];
            safeInitDataTable(dt.value || "#tablaAdmisionesOftalmologia");
        }
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: error?.response?.data?.message || "No fue posible consultar las atenciones.",
            icon: "error",
        });
    } finally {
        isBuscandoAtenciones.value = false;
    }
}

async function buscarPacientePorDocumento() {
    const doc = String(registro.value.num_docu || "").trim();
    if (!doc || doc.length < 3) return;

    try {
        const sedeId = sessionStorage.getItem("ST-sede") || localStorage.getItem("ST-sede") || 1;
        const res = await axios.post("/api/pacientes/listar", {
            bus_documento: doc,
            sede_id: sedeId,
        });
        const lista = res.data?.data?.data || res.data?.data || [];
        const encontrado = Array.isArray(lista) ? lista[0] : null;

        if (encontrado) {
            registro.value.paciente_id = encontrado.id;
            registro.value.nombres = encontrado.nombres || "";
            registro.value.pri_ape = encontrado.pri_ape || "";
            registro.value.seg_ape = encontrado.seg_ape || "";
            registro.value.sexo = encontrado.sexo || "O";
            registro.value.fec_naci = encontrado.fec_naci || "";
            registro.value.tel = encontrado.cel || encontrado.tel || "";
            registro.value.direccion = encontrado.direccion || "";
            if (encontrado.id_tipo_documento) {
                registro.value.tipo_documento_id = encontrado.id_tipo_documento;
                selTipoDoc.value?.rebuild?.();
            }
        }
    } catch {
        // Noop
    }
}

async function guardarRegistro() {
    if (!esParticular.value && !esCortesia.value && !registro.value.atencion_id) {
        toastMessage({
            heading: "¡Atención!",
            text: "Para este tipo de recaudo es obligatorio ingresar el número de atención SAHI.",
            icon: "warning",
        });
        return;
    }

    if (!registro.value.num_docu || !registro.value.nombres) {
        toastMessage({
            heading: "¡Atención!",
            text: "El documento y los nombres del paciente son obligatorios.",
            icon: "warning",
        });
        return;
    }

    try {
        isGuardandoAdmision.value = true;
        registro.value.sede_id =
            sessionStorage.getItem("ST-sede") || localStorage.getItem("ST-sede");
        const res = await axios.post("/api/oftalmologia/admisionar", registro.value);

        if (res.status === 200 || res.status === 201) {
            toastMessage({
                heading: "¡Éxito!",
                text: "Admisión de oftalmología guardada correctamente.",
                icon: "success",
            });
            cerrarSidebar();
            limpiarFormulario();
            listarAtenciones();
        }
    } catch (error) {
        toastMessage({
            heading: "Error",
            text:
                error.response?.data?.message ||
                "No fue posible guardar la admisión de oftalmología.",
            icon: "error",
        });
    } finally {
        isGuardandoAdmision.value = false;
    }
}

function alSeleccionarTipoDocumento(val) {
    registro.value.tipo_documento_id = val?.id || val || "";
}

function alSeleccionarMedico(val) {
    registro.value.medico_id = val?.id || val || null;
}

function alCambiarTipoRecaudo() {
    if (esParticular.value || esCortesia.value) {
        registro.value.atencion_id = "";
    }
}

async function listarMedicos() {
    try {
        const res = await axios.get("/api/user.listarUsuarios/Medico/2");
        medicos.value = res.data?.data || [];
    } catch {
        medicos.value = [];
    }
}

async function listarTipoDocumentos() {
    try {
        const res = await axios.get("/api/config-tipos-documentos/listar/activos");
        tipoDocumentos.value = res.data?.data || [];
    } catch {
        tipoDocumentos.value = [];
    }
}

async function listarTiposRecaudo() {
    try {
        const res = await axios.get("/api/config-tipos-recaudo/listar/activos");
        tiposRecaudo.value = res.data?.data || res.data || [];
        if (tiposRecaudo.value.length > 0 && !registro.value.tipo_recaudo_id) {
            registro.value.tipo_recaudo_id = tiposRecaudo.value[0].id;
        }
    } catch {
        tiposRecaudo.value = [];
    }
}

function onSedeSeleccionada(nuevaSede) {
    busqueda.value.sede_id = nuevaSede?.id || nuevaSede;
    registro.value.sede_id = busqueda.value.sede_id;
    listarAtenciones();
}

onMounted(() => {
    listarMedicos();
    listarTipoDocumentos();
    listarTiposRecaudo();

    if (emitter) {
        emitter.on("sedeSeleccionada", onSedeSeleccionada);
    }
    const sede = sessionStorage.getItem("ST-sede") || localStorage.getItem("ST-sede");
    if (sede) {
        busqueda.value.sede_id = sede;
        registro.value.sede_id = sede;
        establecerSedeComposable();
        listarAtenciones();
    }
});

onBeforeUnmount(() => {
    if (emitter) {
        emitter.off("sedeSeleccionada", onSedeSeleccionada);
    }
});
</script>

<style scoped>
.sidebar-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1090;
    cursor: pointer;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.right-sidebar {
    position: fixed;
    right: -80%;
    width: 80%;
    z-index: 1100;
    background: #ffffff;
    top: 0px;
    padding-bottom: 20px;
    height: 100%;
    box-shadow: -5px 0 35px rgba(0, 0, 0, 0.15);
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    visibility: hidden;
    pointer-events: none;
}

@media (max-width: 768px) {
    .right-sidebar {
        right: -100%;
        width: 100%;
    }
}

.shw-rside {
    right: 0px !important;
    width: 80%;
    visibility: visible;
    pointer-events: auto;
}

@media (max-width: 768px) {
    .shw-rside {
        width: 100% !important;
    }
}

.sidebar-scroll {
    overflow-x: hidden;
    overflow-y: scroll;
    height: 100%;
}

.breadcrumb-btn {
    vertical-align: baseline;
}

:deep(.select2-dropdown) {
    z-index: 9999 !important;
}

:deep(.select2-container) {
    width: 100% !important;
}

.table-custom-compact {
    font-size: 13px;
    margin-bottom: 0;
}

.table-custom-compact thead th {
    background-color: #f8fafc;
    color: #495057;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 10px 8px !important;
    vertical-align: middle !important;
    border-bottom: 2px solid #e9ecef;
}

.table-custom-compact tbody td {
    padding: 8px 8px !important;
    vertical-align: middle !important;
}

.paciente-nombre {
    font-size: 13px;
    line-height: 1.25;
    color: #2b3035;
}

.examen-titulo {
    font-size: 12.5px;
    color: #2b3035;
    line-height: 1.25;
}
</style>
