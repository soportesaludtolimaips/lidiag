<template>
    <div>
        <!-- Bread crumb -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Estudios</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <button
                            type="button"
                            class="btn btn-link p-0"
                            style="vertical-align: baseline; text-decoration: none; color: inherit"
                        >
                            Inicio
                        </button>
                    </li>
                    <li class="breadcrumb-item">
                        <button
                            type="button"
                            class="btn btn-link p-0"
                            style="vertical-align: baseline; text-decoration: none; color: inherit"
                        >
                            Estudios
                        </button>
                    </li>
                    <li class="breadcrumb-item active">Tele radiología</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary"
                    :disabled="loadingBuscar"
                    title="Actualizar listado de estudios"
                    @click="buscarStudy()"
                >
                    <i class="fa fa-refresh mr-1" :class="{ 'fa-spin': loadingBuscar }"></i>
                    Refrescar
                </button>
            </div>
        </div>

        <!-- Filtros -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-filter"></i> Filtros para busqueda de estudios
                        </h4>
                        <form action="#" @submit.prevent="buscarStudy()">
                            <div class="form-body">
                                <div class="row p-t-2">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label" for="bus_nom_num_docu"
                                                >Nombres de paciente o # de documento
                                            </label>
                                            <input
                                                type="text"
                                                id="bus_nom_num_docu"
                                                name="bus_nom_num_docu"
                                                v-model="busqueda.bus_nom_num_docu"
                                                class="form-control"
                                                placeholder="Nombres de paciente o # de documento"
                                                @keyup.enter="buscarStudy()"
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
                                                @keyup.enter="buscarStudy()"
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
                                                @keyup.enter="buscarStudy()"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions mt-3">
                                    <button
                                        type="button"
                                        class="btn btn-success m-r-5"
                                        @click="buscarStudy()"
                                        :disabled="loadingBuscar"
                                    >
                                        <i v-if="loadingBuscar" class="fa fa-spinner fa-spin"></i>
                                        <i v-else class="fa fa-search"></i> Buscar
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-outline-primary m-r-5"
                                        @click="buscarStudy()"
                                        :disabled="loadingBuscar"
                                    >
                                        <i
                                            class="fa fa-refresh"
                                            :class="{ 'fa-spin': loadingBuscar }"
                                        ></i>
                                        Actualizar
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-inverse"
                                        @click="btnLimpiar()"
                                    >
                                        <i class="fa fa-eraser"></i> Limpiar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla estudios -->
        <div class="row">
            <div class="col-12 table-responsive">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Listado de estudios disponibles</h4>
                        <h6 class="card-subtitle text-info">
                            Asigne los estudios a los médicos para su respectiva lectura.
                        </h6>
                        <table-skeleton-loader :visible="loadingBuscar" :rows="6" :columns="6" />
                        <empty-state-component
                            v-if="!loadingBuscar && registros.length === 0"
                            title="No hay estudios para mostrar"
                            description="No se encontraron estudios con los filtros seleccionados. Use los filtros para buscar."
                            icon="fa-search"
                        />
                        <div
                            v-show="!loadingBuscar && registros.length > 0"
                            class="table-responsive m-t-20"
                        >
                            <table
                                id="tablaTeleRadiologiaEnviar"
                                ref="dt"
                                class="table table-bordered table-striped"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th>Fecha del Estudio</th>
                                        <th># Documento</th>
                                        <th>Paciente</th>
                                        <th>Accession</th>
                                        <th>Descripción</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in registros"
                                        :key="item.study_pk"
                                        :id="item.study_pk"
                                        :class="{ 'text-info': item.agendado !== 0 }"
                                    >
                                        <td>{{ item.study_datetime }}</td>
                                        <td class="text-nowrap align-middle">
                                            <div
                                                class="text-primary font-weight-bold"
                                                style="font-size: 15px"
                                            >
                                                <i class="fa fa-id-card-o mr-1"></i>
                                                {{ item.pat_id }}
                                            </div>
                                        </td>
                                        <td>{{ item.pat_name }}</td>
                                        <td>{{ item.accession_no }}</td>
                                        <td>{{ item.study_desc }}</td>
                                        <td class="text-nowrap">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm m-r-5"
                                                :disabled="loadingAbrirIndex === item.study_pk"
                                                @click="mostrarRegistro(item)"
                                                title="Ordenar estudio"
                                            >
                                                <i
                                                    v-if="loadingAbrirIndex === item.study_pk"
                                                    class="fa fa-spinner fa-spin"
                                                ></i>
                                                <i v-else class="fa fa-child"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm"
                                                :disabled="loadingImagenIndex === item.study_pk"
                                                @click="
                                                    verImagen(
                                                        item.pat_id,
                                                        item.study_iuid,
                                                        item.study_pk
                                                    )
                                                "
                                                title="Ver imagenes"
                                            >
                                                <i
                                                    v-if="loadingImagenIndex === item.study_pk"
                                                    class="fa fa-spinner fa-spin"
                                                ></i>
                                                <i v-else class="fa fa-file-photo-o"></i>
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

        <!-- Backdrop para cerrar al hacer clic y atenuar fondo -->
        <transition name="fade">
            <div
                v-if="sidebarVisible"
                class="sidebar-backdrop"
                @click="btnCerrarModal()"
                aria-hidden="true"
            ></div>
        </transition>

        <!-- Right sidebar -->
        <div
            class="right-sidebar"
            :class="{ 'shw-rside': sidebarVisible }"
            style="overflow-x: hidden; overflow-y: scroll; height: 100%"
        >
            <div class="slimscrollright">
                <div class="rpanel-title d-flex justify-content-between align-items-center">
                    <span>{{ tituloModal }}</span>
                    <button
                        type="button"
                        class="right-side-toggle text-white border-0 bg-transparent"
                        @click="btnCerrarModal()"
                        aria-label="Cerrar panel"
                        title="Cerrar panel (Esc)"
                    >
                        <i class="ti-close"></i>
                    </button>
                </div>
                <div class="r-panel-body">
                    <form action="#">
                        <div class="form-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> Datos del Paciente
                                    </h5>
                                    <hr />
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label"># Documento</label>
                                                <input
                                                    type="text"
                                                    v-model="orden.paciente.num_docu"
                                                    class="form-control"
                                                    placeholder="# de Documento"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">Nombres</label>
                                                <input
                                                    type="text"
                                                    v-model="orden.paciente.nom_pacien"
                                                    class="form-control"
                                                    placeholder="Nombres"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label">Sexo</label>
                                                <input
                                                    type="text"
                                                    v-model="orden.paciente.sexo"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label">Fec. Nacim.</label>
                                                <input
                                                    type="date"
                                                    v-model="orden.paciente.fec_naci"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label">Dirección</label>
                                                <input
                                                    type="text"
                                                    v-model="orden.paciente.direccion"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label">Télefono</label>
                                                <input
                                                    type="number"
                                                    v-model="orden.paciente.tel"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">E - Mail</label>
                                                <input
                                                    type="email"
                                                    v-model="orden.paciente.email"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-edit"></i> Datos del Estudio
                                    </h5>
                                    <hr />

                                    <input type="hidden" v-model="orden.study_pk" />
                                    <input type="hidden" v-model="orden.study_iuid" />
                                    <input type="hidden" v-model="orden.study_id" />

                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">Descripción</label>
                                                <input
                                                    type="text"
                                                    v-model="orden.study_desc"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label">Detalle</label>
                                                <input
                                                    type="text"
                                                    v-model="orden.accession_no"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label">Fecha</label>
                                                <input
                                                    type="text"
                                                    v-model="orden.fec_estudio"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label">Observaciones</label>
                                                <input
                                                    type="text"
                                                    v-model="orden.observaciones"
                                                    class="form-control"
                                                    placeholder="Ingrese las observaciones del estudio"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-edit"></i> Adjuntos
                                    </h5>
                                    <hr />

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div
                                                v-for="(adj, adjIdx) in adjuntosFileInputs"
                                                :key="adjIdx"
                                                class="form-group"
                                            >
                                                <label class="control-label">
                                                    Archivo adjunto {{ adjIdx + 1 }}
                                                    <span
                                                        v-if="adj.nombre"
                                                        class="text-success ml-2"
                                                    >
                                                        <i class="fa fa-check"></i>
                                                        {{ adj.nombre }}
                                                    </span>
                                                </label>
                                                <input
                                                    type="file"
                                                    class="form-control"
                                                    :accept="ADJUNTO_ACCEPT"
                                                    @change="(e) => obtenerArchivo(e, adjIdx)"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> Datos de la Asignacion
                                    </h5>
                                    <hr />
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label">Prioridad</label>
                                                <select
                                                    v-model="orden.priori.id"
                                                    class="form-control custom-select"
                                                    @change="establecerPrioridad"
                                                >
                                                    <option
                                                        v-for="item in prioridades"
                                                        :key="item.id"
                                                        :value="item.id"
                                                    >
                                                        {{ item.nom_priori }} - {{ item.tiempo }}
                                                        {{ item.tipo_tiempo }}
                                                    </option>
                                                </select>
                                                <span
                                                    class="text-danger"
                                                    v-if="errores.prioridad_id"
                                                >
                                                    {{ errores.prioridad_id[0] }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-4" v-if="sedeInterface">
                                            <div class="form-group">
                                                <label class="control-label">Atención</label>
                                                <select
                                                    v-model="interfaceAtencionSeleccionada"
                                                    class="form-control custom-select"
                                                    @change="interfaceListarProductosDiagnosticos()"
                                                >
                                                    <option
                                                        v-for="item in interfaceAtenciones"
                                                        :key="item.IdAtencion"
                                                        :value="item.IdAtencion"
                                                    >
                                                        {{ item.IdAtencion }} -
                                                        {{ item.FecIngreso }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> PRODUCTOS
                                    </h5>
                                    <hr />

                                    <span class="text-danger" v-if="errores.productosEstudio">
                                        {{ errores.productosEstudio[0] }}
                                    </span>
                                    <div class="row">
                                        <div
                                            class="col-md-12"
                                            v-if="!sedeInterface"
                                            id="productos-parent"
                                        >
                                            <label class="control-label" for="select-producto">
                                                Buscar y agregar producto
                                            </label>
                                            <Select2
                                                ref="selProducto"
                                                select-id="select-producto"
                                                :value="productoSeleccionado"
                                                :options="productoOptions"
                                                width="100%"
                                                :allow-clear="true"
                                                placeholder="Buscar por código CUPS o nombre del producto..."
                                                dropdown-parent-selector="#productos-parent"
                                                @input="onProductoSelect"
                                            />
                                        </div>
                                        <div
                                            class="col-md-12 table-responsive m-t-10"
                                            style="overflow-y: scroll; height: 250px"
                                        >
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th></th>
                                                        <th>Código</th>
                                                        <th>Producto</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr
                                                        v-for="(item, pIdx) in sedeInterface
                                                            ? productosInterface
                                                            : registro.productosEstudio"
                                                        :key="item.id || item.cod_cups || pIdx"
                                                    >
                                                        <td>
                                                            <input
                                                                type="checkbox"
                                                                :id="`prod-check-${pIdx}`"
                                                                :checked="
                                                                    orden.producto.some(
                                                                        (p) =>
                                                                            p.cod_cups ==
                                                                            item.cod_cups
                                                                    )
                                                                "
                                                                @change="
                                                                    guardarProductosEstudio(
                                                                        item.id,
                                                                        item.cod_cups,
                                                                        item.nom_produc
                                                                    )
                                                                "
                                                            />
                                                            <label
                                                                :for="`prod-check-${pIdx}`"
                                                            ></label>
                                                        </td>
                                                        <td>{{ item.cod_cups }}</td>
                                                        <td>{{ item.nom_produc }}</td>
                                                        <td class="text-nowrap">
                                                            <button
                                                                type="button"
                                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                                @click="
                                                                    quitarProductoEstudio(
                                                                        item.cod_cups
                                                                    )
                                                                "
                                                                title="Quitar producto"
                                                            >
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info m-t-20">
                                        <i class="fa fa-user"></i> DIAGNÓSTICOS
                                    </h5>
                                    <hr />

                                    <span class="text-danger" v-if="errores.diagnosticosEstudio">
                                        {{ errores.diagnosticosEstudio[0] }}
                                    </span>

                                    <div class="row">
                                        <div
                                            class="col-md-12"
                                            v-if="!sedeInterface"
                                            id="diagnosticos-parent"
                                        >
                                            <label class="control-label" for="select-diagnostico">
                                                Buscar y agregar diagnóstico
                                            </label>
                                            <Select2
                                                ref="selDiagnostico"
                                                select-id="select-diagnostico"
                                                :value="diagnosticoSeleccionado"
                                                :options="diagnosticoOptions"
                                                width="100%"
                                                :allow-clear="true"
                                                :label-fields="['cod_diagnos', 'nom_diagnos']"
                                                placeholder="Buscar por código CIE o nombre del diagnóstico..."
                                                dropdown-parent-selector="#diagnosticos-parent"
                                                @input="onDiagnosticoSelect"
                                            />
                                        </div>
                                        <div
                                            class="col-md-12 table-responsive m-t-10"
                                            style="overflow-y: scroll; max-height: 200px"
                                        >
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Código</th>
                                                        <th>Diagnóstico</th>
                                                        <th v-if="!sedeInterface"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr
                                                        v-for="(
                                                            item, dIdx
                                                        ) in registro.diagnosticosEstudio"
                                                        :key="
                                                            item.id ||
                                                            item.cod_diagnos ||
                                                            item.cod_cie9 ||
                                                            dIdx
                                                        "
                                                    >
                                                        <td>
                                                            {{
                                                                item.cod_diagnos ||
                                                                item.cod_cie9 ||
                                                                item.CodCie9
                                                            }}
                                                        </td>
                                                        <td>
                                                            {{
                                                                item.nom_diagnos ||
                                                                item.nom_diagnostico ||
                                                                item.NomDiagnostico
                                                            }}
                                                        </td>
                                                        <td
                                                            v-if="!sedeInterface"
                                                            class="text-nowrap"
                                                        >
                                                            <button
                                                                type="button"
                                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                                @click="
                                                                    quitarDiagnosticoEstudio(dIdx)
                                                                "
                                                                title="Quitar diagnóstico"
                                                            >
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <tr
                                                        v-if="
                                                            !registro.diagnosticosEstudio ||
                                                            registro.diagnosticosEstudio.length ===
                                                                0
                                                        "
                                                    >
                                                        <td
                                                            :colspan="!sedeInterface ? 3 : 2"
                                                            class="text-center text-muted"
                                                        >
                                                            No hay diagnósticos seleccionados
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info m-t-20">
                                        <i class="fa fa-pencil"></i> MOTIVO DEL EXAMEN
                                    </h5>
                                    <hr />

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <textarea
                                                    v-model="orden.motivo_examen"
                                                    class="form-control"
                                                    placeholder="Ingrese las observaciones del estudio"
                                                    rows="11"
                                                ></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions mt-3">
                                <button
                                    type="button"
                                    class="btn btn-success m-r-5"
                                    @click="guardarRegistro()"
                                    :disabled="loadingGuardar"
                                    title="Guardar orden"
                                >
                                    <i v-if="loadingGuardar" class="fa fa-spinner fa-spin"></i>
                                    <i v-else class="fa fa-check"></i> Guardar
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-inverse"
                                    @click="btnCerrarModal()"
                                    title="Cerrar panel"
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
import {
    computed,
    getCurrentInstance,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from "vue";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { confirmAction, getUserSafeError, toastMessage } from "@/helpers/alerts";
import { normalizarFecha } from "@/helpers/dateUtils";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts.js";
import axios from "axios";
import usePermissions from "@/helpers/permissions.js";

const { appContext } = getCurrentInstance();
const emitter = appContext.config.globalProperties.emitter;

const DESTINO_TELE_RADIOLOGIA = "Tele Radiologia";
const ADJUNTO_ACCEPT = ".pdf,.doc,.docx";
const ADJUNTO_COUNT = 3;

const props = defineProps({
    usuarioactual: { type: Object, required: true },
    permisos: { type: Array, default: () => [] },
});

usePermissions(props.permisos);

const dt = ref(null);
const selProducto = ref(null);
const selDiagnostico = ref(null);
const sidebarVisible = ref(false);
const registros = ref([]);
const tituloModal = ref("Nuevo registro");
const orden = ref({
    study_pk: "",
    study_iuid: "",
    study_id: "",
    fec_estudio: "",
    accession_no: "",
    study_desc: "",
    mods_in_study: "",
    observaciones: "",
    paciente: {
        tipo_docu: "CC",
        num_docu: "",
        nom_pacien: "",
        fec_naci: "",
        sexo: "",
        tel: "",
        telefono: "",
        email: "",
        direccion: "",
    },
    priori: {
        id: "",
        nom_priori: "",
    },
    sede: {
        id: "",
        nom_sede: "",
    },
    producto: [],
    adjuntos: [],
    motivo_examen: null,
});
const registro = ref({
    study_pk: "",
    study_iuid: "",
    study_datetime: "",
    study_id: "",
    accession_no: "",
    study_desc: "",
    mods_in_study: "",
    observaciones: "",
    medico_id: "",
    prioridad_id: "",
    sede_id: "",
    quien_registro_id: props.usuarioactual.id,
    num_docu: "",
    nombres: "",
    sexo: "",
    fec_naci: "",
    email: "",
    email_reportar: "",
    direccion: "",
    tel: "",
    productosEstudio: [],
    diagnosticosEstudio: [],
    atencion_id: null,
    destino_estudio: DESTINO_TELE_RADIOLOGIA,
    destino_estudio_id: null,
    motivo_examen: null,
});
const adjuntosFileInputs = ref(
    Array.from({ length: ADJUNTO_COUNT }, () => ({
        nombre: null,
        archivo: null,
    }))
);
const busqueda = ref({
    sede_id: "",
    bus_nom_num_docu: "",
    bus_fech_ini: "",
    bus_fecha_fin: "",
});
const sedeInterface = ref(false);
const interfaceAtenciones = ref([]);
const interfaceAtencionSeleccionada = ref(null);
const errores = ref({});
const prioridades = ref([]);
const productos = ref([]);
const productosInterface = ref([]);
const productoSeleccionado = ref("");
const diagnosticos = ref([]);
const diagnosticoSeleccionado = ref("");
const idSedeActual = ref("");
const nomSedeActual = ref("");
const loadingBuscar = ref(false);
const loadingGuardar = ref(false);
const loadingAbrirIndex = ref(null);
const loadingImagenIndex = ref(null);

const productoOptions = computed(() => {
    return (productos.value || []).map((p) => ({
        id: String(p.id),
        cod_cups: p.cod_cups,
        nom_produc: p.nom_produc,
    }));
});

const diagnosticoOptions = computed(() => {
    return (diagnosticos.value || []).map((d) => ({
        id: String(d.id),
        cod_diagnos: d.cod_diagnos || d.cod_cie9 || "",
        nom_diagnos: d.nom_diagnos || "",
    }));
});

function initDataTable() {
    if (registros.value.length === 0) return;
    safeInitDataTable(dt.value || "#tablaTeleRadiologiaEnviar", {
        dom: "Bfrtip",
        buttons: ["copy", "csv", "excel", "pdf", "print"],
        autoWidth: false,
        columnDefs: [{ targets: [5], orderable: false }],
    });
}

function destroyDataTable() {
    safeDestroyDataTable(dt.value || "#tablaTeleRadiologiaEnviar");
}

async function buscarStudy() {
    if (loadingBuscar.value) return;

    const sedeActual =
        sessionStorage.getItem("ST-sede") ||
        localStorage.getItem("ST-sede") ||
        busqueda.value.sede_id ||
        idSedeActual.value;

    if (!sedeActual) {
        toastMessage({
            heading: "Upsss!!!",
            text: "Debes seleccionar una sede para realizar la busqueda.",
            icon: "warning",
        });
        return;
    }

    if (!sessionStorage.getItem("ST-sede")) {
        sessionStorage.setItem("ST-sede", String(sedeActual));
    }
    if (
        busqueda.value.bus_nom_num_docu == "" &&
        busqueda.value.bus_fech_ini == "" &&
        busqueda.value.bus_fecha_fin == ""
    ) {
        toastMessage({
            heading: "Upsss!!!",
            text: "Debes establecer la fecha de inicio o finalización para la busqueda.",
            icon: "warning",
        });
        return;
    }

    loadingBuscar.value = true;
    destroyDataTable();

    try {
        const res = await axios.post("/api/tele-radiologia-listar-estudios", busqueda.value);
        let data = res?.data?.data;
        if (typeof data === "string") {
            try {
                data = JSON.parse(data);
            } catch (_error) {
                data = [];
            }
        }
        registros.value = Array.isArray(data) ? data : [];
    } catch (error) {
        toastMessage({
            heading: "Upsss!!!",
            text: getUserSafeError(error, "Error al listar estudios."),
            icon: "error",
        });
        registros.value = [];
    } finally {
        loadingBuscar.value = false;
        await nextTick();
        if (registros.value.length > 0) {
            destroyDataTable();
            initDataTable();
        }
    }
}

async function guardarRegistro() {
    if (loadingGuardar.value) return;
    loadingGuardar.value = true;
    try {
        if (orden.value.producto.length === 0) {
            toastMessage({
                heading: "Upsss!!!",
                text: "Debes seleccionar al menos un producto",
                icon: "warning",
            });
            return;
        }

        if (orden.value.priori.id === "") {
            toastMessage({
                heading: "Upsss!!!",
                text: "Debes seleccionar la prioridad de la orden",
                icon: "warning",
            });
            return;
        }

        orden.value.paciente.telefono = orden.value.paciente.tel;
        registro.value.tel = orden.value.paciente.tel;
        registro.value.direccion = orden.value.paciente.direccion;
        registro.value.email = orden.value.paciente.email;
        registro.value.motivo_examen = orden.value.motivo_examen;

        const primerCups = orden.value.producto[0]?.cod_cups || "";
        const payloadOrden = {
            study_pk: orden.value.study_pk,
            study_iuid: orden.value.study_iuid,
            study_id: orden.value.study_id || String(orden.value.study_pk || ""),
            fec_estudio: orden.value.fec_estudio,
            accession_no: orden.value.accession_no,
            study_desc: orden.value.study_desc,
            mods_in_study: orden.value.mods_in_study,
            observaciones: orden.value.observaciones,
            paciente: { ...orden.value.paciente },
            priori: { ...orden.value.priori },
            sede: { ...orden.value.sede },
            cod_cups: primerCups,
            medico: {
                num_docu: String(props.usuarioactual?.num_docu || props.usuarioactual?.id || ""),
                name: props.usuarioactual?.name || "Usuario Asignador",
            },
            producto: orden.value.producto.map((p) => ({ ...p })),
            diagnostico: (registro.value.diagnosticosEstudio || []).map((d) => ({
                cod_diagnos: d.cod_diagnos || d.cod_cie9 || d.CodCie9 || "",
                nom_diagnos: d.nom_diagnos || d.nom_diagnostico || d.NomDiagnostico || "",
            })),
            adjuntos: orden.value.adjuntos.map((a) => ({
                file_exten: a.file_exten,
            })),
            motivo_examen: orden.value.motivo_examen,
        };

        const resOrdenTele = await axios.post("/api/tele-radiologia-enviar-orden", {
            orden: payloadOrden,
        });

        const confirmation =
            resOrdenTele?.data?.confirmation ??
            resOrdenTele?.data?.data?.confirmation ??
            resOrdenTele?.data?.id ??
            null;

        if (resOrdenTele.status == 201 || resOrdenTele.status == 200) {
            registro.value.destino_estudio_id =
                confirmation !== null && !isNaN(Number(confirmation)) ? Number(confirmation) : null;
            registro.value.medico_id = null;
            registro.value.prioridad_id = orden.value.priori.id;
            registro.value.fec_estudio = orden.value.fec_estudio;
            registro.value.study_datetime =
                orden.value.fec_estudio || registro.value.study_datetime;
            registro.value.destino_estudio = DESTINO_TELE_RADIOLOGIA;

            toastMessage({
                heading: "Ok!!!",
                text: resOrdenTele?.data?.details || "Orden enviada correctamente.",
                icon: "success",
            });

            const formData = new FormData();
            formData.append("registro", JSON.stringify(registro.value));
            adjuntosFileInputs.value.forEach((adj, idx) => {
                if (adj.archivo) {
                    formData.append(`archivo${idx + 1}`, adj.archivo);
                }
            });

            const resEstudio = await axios.post("/api/estudios", formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });

            if (resEstudio.status == 200 || resEstudio.status == 201) {
                toastMessage({
                    heading: "Ok!!!",
                    text: resEstudio.data.message || "Estudio guardado correctamente.",
                    icon: "success",
                });
                btnCerrarModal();
                btnLimpiar();
            }
        }
    } catch (error) {
        if (error.response) {
            errores.value = error.response.data;
        }
        toastMessage({
            heading: "Error",
            text: error.response?.data?.message || "No fue posible enviar la orden.",
            icon: "error",
        });
    } finally {
        loadingGuardar.value = false;
    }
}

function toBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = () => resolve(reader.result);
        reader.onerror = (error) => reject(error);
    });
}

function extensionDesdeBase64(base64) {
    const mime = base64.substring(base64.indexOf(":") + 1, base64.indexOf(";"));
    const mapa = {
        "image/jpeg": "jpg",
        "image/png": "png",
        "application/pdf": "pdf",
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document": "docx",
        "application/msword": "doc",
    };
    return mapa[mime] ?? null;
}

async function obtenerArchivo(e, index) {
    const file = e.target.files[0];
    if (!file) return;
    const fileBase64 = await toBase64(file);
    const extension = extensionDesdeBase64(fileBase64);
    orden.value.adjuntos.push({
        file: fileBase64,
        file_exten: extension,
    });
    adjuntosFileInputs.value[index].nombre = file.name;
    adjuntosFileInputs.value[index].archivo = file;
}

async function mostrarRegistro(data = {}) {
    limpiar();
    tituloModal.value = `Generar orden: ${data.pat_name} ${data.pat_id ? `(${data.pat_id})` : ""}`;
    orden.value.study_pk = data.study_pk;
    orden.value.study_iuid = data.study_iuid;
    orden.value.study_id = data.study_id ?? String(data.study_pk ?? "");
    orden.value.fec_estudio = data.study_datetime;
    orden.value.accession_no = data.accession_no;
    orden.value.study_desc = data.study_desc;
    orden.value.mods_in_study = data.mods_in_study;
    orden.value.paciente.num_docu = data.pat_id;
    orden.value.paciente.nom_pacien = data.pat_name;
    orden.value.paciente.sexo = data.pat_sex;
    orden.value.paciente.fec_naci = normalizarFecha(data.pat_birthdate) ?? "";

    registro.value.study_pk = data.study_pk;
    registro.value.study_iuid = data.study_iuid;
    registro.value.study_id = data.study_id;
    registro.value.study_datetime = data.study_datetime;
    registro.value.accession_no = data.accession_no;
    registro.value.study_desc = data.study_desc;
    registro.value.mods_in_study = data.mods_in_study;
    registro.value.email = "";
    registro.value.email_reportar =
        sessionStorage.getItem("ST-email_repor") || localStorage.getItem("ST-email_repor") || "";
    registro.value.num_docu = data.pat_id;
    registro.value.nombres = data.pat_name;
    registro.value.sexo = data.pat_sex;
    registro.value.fec_naci = normalizarFecha(data.pat_birthdate) ?? "";

    abrirModal();

    try {
        const res = await axios.get("/api/config-sedes/" + idSedeActual.value + "/buscarPorId");
        sedeInterface.value = res.data.data.interface_software != null;
        if (sedeInterface.value) {
            const resAtenciones = await axios.get(
                "/api/interface-sahi-listar-atenciones/" + orden.value.paciente.num_docu
            );
            interfaceAtenciones.value = resAtenciones.data.data;
        } else {
            await nextTick();
            setTimeout(() => {
                selProducto.value?.rebuild();
                selDiagnostico.value?.rebuild();
            }, 60);
        }
    } catch (_e) {
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar los datos de la sede.",
            icon: "error",
        });
    }
}

async function interfaceListarProductosDiagnosticos() {
    try {
        const resProductos = await axios.get(
            "/api/interface-sahi-listar-productos-diagnosticos/" +
                interfaceAtencionSeleccionada.value
        );
        const data = resProductos.data.data;
        productosInterface.value = data.productos;

        let EnfermedadActualDiagnosticos = "";

        const consultas = data.consultas || [];
        EnfermedadActualDiagnosticos = "Enfermedad Actual: ";
        if (consultas.length > 0) {
            consultas.forEach((c) => {
                const enfermedad =
                    c.DesEnfermedadActual ||
                    c.des_enfermedad_actual ||
                    c.EnfermedadActual ||
                    c.enfermedad_actual;
                if (enfermedad) {
                    EnfermedadActualDiagnosticos = `${EnfermedadActualDiagnosticos}\n ${enfermedad} \n`;
                }
            });
        }

        const diagnosticos = data.diagnosticos || [];
        EnfermedadActualDiagnosticos += `\nDiagnosticos:`;
        if (diagnosticos.length > 0) {
            diagnosticos.forEach((d) => {
                const codigo = d.cod_diagnos || d.CodCie9 || d.cod_cie9 || "";
                const nombre = d.nom_diagnos || d.NomDiagnostico || d.nom_diagnostico || "";
                if (codigo || nombre) {
                    EnfermedadActualDiagnosticos = `${EnfermedadActualDiagnosticos} \n ${codigo} - ${nombre}`;
                }
            });
        }

        orden.value.motivo_examen = EnfermedadActualDiagnosticos;
        registro.value.motivo_examen = EnfermedadActualDiagnosticos;

        registro.value.diagnosticosEstudio = diagnosticos.length > 0 ? diagnosticos : [];
        registro.value.atencion_id = interfaceAtencionSeleccionada.value;
    } catch (_e) {
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar productos/diagnósticos de la atención.",
            icon: "error",
        });
    }
}

function establecerPrioridad() {
    const seleccion = prioridades.value.find((p) => p.id === orden.value.priori.id);
    if (seleccion) {
        orden.value.priori.nom_priori = seleccion.nom_priori;
        registro.value.prioridad_id = seleccion.id;
    }
}

async function establecerSede() {
    const sede = sessionStorage.getItem("ST-sede") || localStorage.getItem("ST-sede") || null;
    const nomSede =
        sessionStorage.getItem("ST-nomsede") || localStorage.getItem("ST-nomsede") || "";
    orden.value.sede.id = sede;
    busqueda.value.sede_id = sede;
    idSedeActual.value = sede;
    nomSedeActual.value = nomSede;
    registro.value.sede_id = sede;
    registros.value = [];
    if (!sede && emitter) {
        emitter.emit("solicitarSedeActiva");
    }
}

/**
 * Maneja el evento global sedeSeleccionada sincronizando la sede activa
 * y limpiando los resultados previos para evitar mezclar información.
 *
 * @param {Object} data - Datos del evento con idSede y nomSede.
 * @returns {void}
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-16
 */
function onSedeSeleccionada(data) {
    const idSede =
        data?.idSede ?? sessionStorage.getItem("ST-sede") ?? localStorage.getItem("ST-sede");
    if (!idSede) return;
    const cambioSede = String(idSedeActual.value || "") !== String(idSede);
    orden.value.sede.id = idSede;
    busqueda.value.sede_id = idSede;
    idSedeActual.value = idSede;
    nomSedeActual.value =
        data?.nomSede ??
        sessionStorage.getItem("ST-nomsede") ??
        localStorage.getItem("ST-nomsede") ??
        "";
    registro.value.sede_id = idSede;
    if (cambioSede) {
        destroyDataTable();
        registros.value = [];
        limpiar();
    }
}

function btnCerrarModal() {
    sidebarVisible.value = false;
    errores.value = {};
}

function abrirModal() {
    sidebarVisible.value = true;
    nextTick(() => {
        setTimeout(() => {
            selProducto.value?.rebuild();
            selDiagnostico.value?.rebuild();
        }, 60);
    });
}

function limpiar() {
    orden.value.study_pk = "";
    orden.value.study_iuid = "";
    orden.value.study_id = "";
    orden.value.fec_estudio = "";
    orden.value.accession_no = "";
    orden.value.study_desc = "";
    orden.value.mods_in_study = "";
    orden.value.observaciones = "";
    orden.value.paciente.num_docu = "";
    orden.value.paciente.nom_pacien = "";
    orden.value.paciente.sexo = "";
    orden.value.paciente.fec_naci = "";
    orden.value.paciente.tel = "";
    orden.value.paciente.email = "";
    orden.value.paciente.direccion = "";
    orden.value.priori.id = "";
    orden.value.priori.nom_priori = "";
    orden.value.producto = [];
    orden.value.adjuntos = [];
    orden.value.motivo_examen = null;

    registro.value.diagnosticosEstudio = [];
    registro.value.productosEstudio = [];
    registro.value.atencion_id = null;
    registro.value.destino_estudio_id = null;
    registro.value.destino_estudio = DESTINO_TELE_RADIOLOGIA;

    productoSeleccionado.value = "";
    diagnosticoSeleccionado.value = "";
    productosInterface.value = [];
    interfaceAtenciones.value = [];
    interfaceAtencionSeleccionada.value = null;
    sedeInterface.value = false;

    adjuntosFileInputs.value = Array.from({ length: ADJUNTO_COUNT }, () => ({
        nombre: null,
        archivo: null,
    }));

    errores.value = {};
}

function btnLimpiar() {
    busqueda.value.bus_nom_num_docu = "";
    busqueda.value.bus_fech_ini = "";
    busqueda.value.bus_fecha_fin = "";

    registros.value = [];
    destroyDataTable();

    limpiar();
    btnCerrarModal();
}

async function listarPrioridades() {
    try {
        const res = await axios.get("/api/config-prioridades");
        prioridades.value = res.data.data;
    } catch (_e) {
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar las prioridades.",
            icon: "error",
        });
        prioridades.value = [];
    }
}

async function listarProductos() {
    try {
        const res = await axios.get("/api/config-productos");
        productos.value = res.data.data;
    } catch (_e) {
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar los productos.",
            icon: "error",
        });
        productos.value = [];
    }
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
    if (selectedId != null && selectedId !== "" && selectedId !== "0" && selectedId !== 0) {
        productoSeleccionado.value = selectedId;
        const prod = productos.value.find((p) => String(p.id) === selectedId);
        if (prod) {
            guardarProductosEstudio(prod.id, prod.cod_cups, prod.nom_produc);
        }
    }
}

function guardarProductosEstudio(id, cod_cups, nom_produc) {
    const yaExisteIndex = orden.value.producto.findIndex((p) => p.cod_cups == cod_cups);
    if (yaExisteIndex !== -1) {
        orden.value.producto.splice(yaExisteIndex, 1);
        const registroIndex = registro.value.productosEstudio.findIndex(
            (p) => p.cod_cups == cod_cups
        );
        if (registroIndex !== -1) {
            registro.value.productosEstudio.splice(registroIndex, 1);
        }
    } else {
        orden.value.producto.push({
            cod_cups: cod_cups,
            nom_produc: nom_produc,
        });
        registro.value.productosEstudio.push({
            checked: true,
            id: id,
            cod_cups: cod_cups,
            nom_produc: nom_produc,
        });
    }
    productoSeleccionado.value = "";
    nextTick(() => {
        selProducto.value?.rebuild();
    });
}

function quitarProductoEstudio(codCups) {
    const idxOrden = orden.value.producto.findIndex((p) => p.cod_cups === codCups);
    if (idxOrden !== -1) orden.value.producto.splice(idxOrden, 1);
    const idxRegistro = registro.value.productosEstudio.findIndex((p) => p.cod_cups === codCups);
    if (idxRegistro !== -1) registro.value.productosEstudio.splice(idxRegistro, 1);
}

/**
 * Consulta la lista de diagnósticos configurados en el sistema.
 *
 * @returns {Promise<void>}
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-09
 */
async function listarDiagnosticos() {
    try {
        const res = await axios.get("/api/config-diagnosticos");
        diagnosticos.value = res.data.data || [];
    } catch (_e) {
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar los diagnósticos.",
            icon: "error",
        });
        diagnosticos.value = [];
    }
}

/**
 * Maneja el evento de selección de diagnóstico desde el componente Select2.
 *
 * @param {Array|Object|string|number} value - Valor emitido por el Select2.
 * @returns {void}
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-09
 */
function onDiagnosticoSelect(value) {
    let selectedId = null;
    if (Array.isArray(value) && value.length > 0) {
        const v = value[0];
        selectedId = String(typeof v === "object" && v && v.id != null ? v.id : v);
    } else if (value && typeof value === "object" && value.id != null) {
        selectedId = String(value.id);
    } else if (value != null) {
        selectedId = String(value);
    }
    if (selectedId != null && selectedId !== "" && selectedId !== "0" && selectedId !== 0) {
        diagnosticoSeleccionado.value = selectedId;
        guardarDiagnosticosEstudio(selectedId);
    }
}

/**
 * Agrega un diagnóstico seleccionado a la lista de diagnósticos del estudio.
 *
 * @param {string|number|null} idDiag - Identificador del diagnóstico a agregar.
 * @returns {void}
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-09
 */
function guardarDiagnosticosEstudio(idDiag = null) {
    const targetId = idDiag ?? diagnosticoSeleccionado.value;
    if (!targetId || targetId === "" || targetId === "0" || targetId === 0) return;

    const idx = diagnosticos.value.findIndex((x) => String(x.id) === String(targetId));
    if (idx >= 0) {
        const diag = diagnosticos.value[idx];
        const yaExiste = registro.value.diagnosticosEstudio.some(
            (d) => String(d.id) === String(diag.id)
        );
        if (!yaExiste) {
            registro.value.diagnosticosEstudio.push({
                id: diag.id,
                cod_diagnos: diag.cod_diagnos || diag.cod_cie9 || "",
                nom_diagnos: diag.nom_diagnos || "",
            });
        }
    }
    diagnosticoSeleccionado.value = "";
    nextTick(() => {
        selDiagnostico.value?.rebuild();
    });
}

/**
 * Elimina un diagnóstico del estudio solicitando confirmación previa.
 *
 * @param {number} indexEliminar - Índice del diagnóstico en registro.diagnosticosEstudio.
 * @returns {Promise<void>}
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-09
 */
async function quitarDiagnosticoEstudio(indexEliminar) {
    const ok = await confirmAction({
        title: "¿Está seguro?",
        text: "¿Desea eliminar este diagnóstico del estudio?",
        icon: "warning",
    });
    if (!ok) return;
    registro.value.diagnosticosEstudio.splice(indexEliminar, 1);
}

function verImagen(pat_id, study_iuid, studyPk) {
    if (loadingImagenIndex.value !== null) return;

    const base = sessionStorage.getItem("ST-urlsede") || localStorage.getItem("ST-urlsede") || "";
    if (!base || base === "undefined" || base === "null" || !base.trim()) {
        toastMessage({
            heading: "Upsss!!!",
            text: "No hay visor de imágenes configurado para la sede actual.",
            icon: "warning",
        });
        return;
    }

    loadingImagenIndex.value = studyPk;

    const rawServer =
        sessionStorage.getItem("ST-servername") || localStorage.getItem("ST-servername");
    const cleanServer =
        !rawServer || rawServer === "undefined" || rawServer === "null"
            ? ""
            : String(rawServer).trim();

    const queryObj = {
        patientID: String(pat_id ?? "").trim(),
        studyUID: String(study_iuid ?? "").trim(),
    };
    if (cleanServer) {
        queryObj.serverName = cleanServer;
    }

    const params = new URLSearchParams(queryObj);
    const separator = base.includes("?") ? "&" : "?";

    try {
        const newWindow = window.open(
            `${base.trim()}${separator}${params.toString()}`,
            "_blank",
            "noopener,noreferrer"
        );
        if (!newWindow) {
            toastMessage({
                heading: "Atención",
                text: "El navegador bloqueó la apertura de la ventana. Permita pop-ups para este sitio.",
                icon: "warning",
            });
        }
    } finally {
        setTimeout(() => {
            loadingImagenIndex.value = null;
        }, 500);
    }
}

useKeyboardShortcuts({
    onSave: () => {
        if (sidebarVisible.value && !loadingGuardar.value) {
            guardarRegistro();
        }
    },
    onEscape: () => {
        if (sidebarVisible.value) {
            btnCerrarModal();
        }
    },
    onSearch: () => {
        if (!sidebarVisible.value) {
            buscarStudy();
        }
    },
});

watch(
    () => [sidebarVisible.value, sedeInterface.value],
    ([visible, isInterface]) => {
        if (visible && !isInterface) {
            nextTick(() => {
                setTimeout(() => {
                    selProducto.value?.rebuild();
                    selDiagnostico.value?.rebuild();
                }, 60);
            });
        }
    }
);

onMounted(() => {
    listarPrioridades();
    listarProductos();
    listarDiagnosticos();
    establecerSede();
    emitter.on("sedeSeleccionada", onSedeSeleccionada);
    if (!idSedeActual.value && emitter) {
        emitter.emit("solicitarSedeActiva");
    }
});

onBeforeUnmount(() => {
    emitter.off("sedeSeleccionada", onSedeSeleccionada);
    destroyDataTable();
});
</script>

<style scoped>
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
    transition:
        right 0.35s cubic-bezier(0.16, 1, 0.3, 1),
        visibility 0.35s;
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
        width: 100%;
    }
}

.sidebar-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1050;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

:deep(.select2-container) {
    width: 100% !important;
}

:deep(.select2-dropdown) {
    z-index: 9999 !important;
}

:deep(.select2-container--default .select2-selection--single) {
    height: 38px;
    border: 1px solid #ced4da;
    border-radius: 4px;
}

:deep(.select2-container--default .select2-selection--single .select2-selection__rendered) {
    line-height: 36px;
    padding-left: 12px;
}

:deep(.select2-container--default .select2-selection--single .select2-selection__arrow) {
    height: 36px;
    right: 8px;
}
</style>
