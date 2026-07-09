<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
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
                    <li class="breadcrumb-item active">Estudios asignados</li>
                </ol>
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
                            <i class="fa fa-filter"></i> Filtros para busqueda de estudios asignados
                        </h4>
                        <form action="#">
                            <div class="form-body">
                                <div class="row p-t-2">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="quien_asigno_id">Quien asigno</label>
                                            <select
                                                v-model="busqueda.quien_asigno_id"
                                                name="quien_asigno_id"
                                                id="quien_asigno_id"
                                                class="form-control custom-select"
                                            >
                                                <option value="">
                                                    ...::: Todos los usuarios :::...
                                                </option>
                                                <option
                                                    v-for="(
                                                        ItemUsuario, index
                                                    ) in quienes_asignaron"
                                                    :key="index"
                                                    :value="ItemUsuario.id"
                                                >
                                                    {{ ItemUsuario.name }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="medico_id">Medicos</label>
                                            <select
                                                v-model="busqueda.medico_id"
                                                name="medico_id"
                                                id="medico_id"
                                                class="form-control custom-select"
                                            >
                                                <option value="" selected>
                                                    ...::: Todos los medicos :::...
                                                </option>
                                                <option
                                                    v-for="(ItemUsuario, index) in medicos"
                                                    :key="index"
                                                    :value="ItemUsuario.id"
                                                >
                                                    {{ ItemUsuario.name }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label" for="bus_nom_num_docu"
                                                >Nombres o # documento</label
                                            >
                                            <input
                                                type="text"
                                                id="bus_nom_num_docu"
                                                name="bus_nom_num_docu"
                                                v-model="busqueda.bus_nom_num_docu"
                                                class="form-control"
                                                placeholder="Nombres de paciente o # de documento"
                                                @keyup.enter="listarEstudiosAsignados()"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label" for="bus_fehc_ini"
                                                >Fecha Inicio</label
                                            >
                                            <input
                                                type="date"
                                                id="bus_fehc_ini"
                                                name="bus_fehc_ini"
                                                v-model="busqueda.bus_fehc_ini"
                                                class="form-control"
                                                @keyup.enter="listarEstudiosAsignados()"
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
                                                @keyup.enter="listarEstudiosAsignados()"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions mt-3">
                                    <button
                                        type="button"
                                        class="btn btn-success m-r-5"
                                        :disabled="loadingBuscar"
                                        @click="listarEstudiosAsignados()"
                                    >
                                        <i v-if="loadingBuscar" class="fa fa-spinner fa-spin"></i>
                                        <i v-else class="fa fa-search"></i>
                                        Buscar
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-inverse"
                                        @click="btnLimpiar()"
                                    >
                                        <i class="fa fa-refresh"></i> Limpiar
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
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h4 class="card-title">Listado de estudios asignados</h4>
                                <h6 class="card-subtitle text-info">
                                    Seguimiento a los estudios asignados.
                                </h6>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-primary" v-if="contadorRegistros > 0"
                                    >{{ contadorRegistros }} registro(s)</span
                                >
                                <span class="badge badge-secondary" v-else>Sin resultados</span>
                            </div>
                        </div>
                        <loading-spinner :visible="loadingBuscar" />
                        <div class="table-responsive" :class="{ 'd-none': loadingBuscar }">
                            <table
                                id="example23"
                                ref="dt"
                                class="table table-bordered table-striped"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Sede</th>
                                        <th>Fecha de la Toma</th>
                                        <th>Quien Asigno</th>
                                        <th>Medico</th>
                                        <th>Paciente</th>
                                        <th>Detalle</th>
                                        <th>Producto</th>
                                        <th>Leido</th>
                                        <th>Transcrito</th>
                                        <th>Activo</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in registros"
                                        :key="item.estudio_producto_id"
                                        :id="'row-' + item.estudio_producto_id"
                                    >
                                        <td>
                                            <button
                                                type="button"
                                                v-if="item.priori_id == 1"
                                                class="btn waves-effect waves-light btn-rounded btn-sm btn-danger"
                                            >
                                                A
                                            </button>
                                            <button
                                                type="button"
                                                v-else-if="item.priori_id == 2"
                                                class="btn waves-effect waves-light btn-rounded btn-sm btn-warning"
                                            >
                                                M
                                            </button>
                                            <button
                                                type="button"
                                                v-else-if="item.priori_id == 3"
                                                class="btn waves-effect waves-light btn-rounded btn-sm btn-info"
                                            >
                                                B
                                            </button>
                                            <button
                                                type="button"
                                                v-else-if="item.priori_id == 4"
                                                class="btn waves-effect waves-light btn-rounded btn-sm"
                                            >
                                                S
                                            </button>
                                        </td>
                                        <td>{{ item.nom_sede }}</td>
                                        <td>{{ item.fec_estudio }}</td>
                                        <td>{{ item.quien_registro }}</td>
                                        <td>{{ item.medico }}</td>
                                        <td>
                                            # Docu: {{ item.num_docu }} <br />
                                            Nombres: {{ item.nom_pacien }}
                                        </td>
                                        <td>
                                            Atención: {{ item.atencion }} <br />
                                            Descrip: {{ item.study_desc }} <br />
                                            {{ item.accession_no }}
                                        </td>
                                        <td>{{ item.nom_produc }}</td>
                                        <td>
                                            <span
                                                class="label label-success"
                                                v-if="item.fechor_lectura != null"
                                                >Si</span
                                            >
                                            <span
                                                class="label label-danger"
                                                v-if="item.fechor_lectura === null"
                                                >No</span
                                            >
                                        </td>
                                        <td>
                                            <span
                                                class="label label-success"
                                                v-if="item.transcriptor_id != null"
                                                >Si</span
                                            >
                                            <span
                                                class="label label-danger"
                                                v-if="item.transcriptor_id === null"
                                                >No</span
                                            >
                                        </td>
                                        <td>
                                            <span
                                                class="label label-success"
                                                v-if="item.activo === 1"
                                                >Si</span
                                            >
                                            <span
                                                class="label label-danger"
                                                v-if="item.activo === 0"
                                                >No</span
                                            >
                                        </td>
                                        <td class="text-nowrap">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                :disabled="
                                                    loadingAbrirRegistroId ===
                                                    item.estudio_producto_id
                                                "
                                                @click="mostrarRegistro(item)"
                                            >
                                                <i
                                                    v-if="
                                                        loadingAbrirRegistroId ===
                                                        item.estudio_producto_id
                                                    "
                                                    class="fa fa-spinner fa-spin"
                                                ></i>
                                                <i v-else class="fa fa-pencil"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm m-r-5"
                                                :disabled="
                                                    loadingAnularId === item.estudio_producto_id
                                                "
                                                @click="
                                                    anularAsignacion(
                                                        item.estudio_producto_id,
                                                        item.nom_pacien,
                                                        item.nom_produc
                                                    )
                                                "
                                            >
                                                <i
                                                    v-if="
                                                        loadingAnularId === item.estudio_producto_id
                                                    "
                                                    class="fa fa-spinner fa-spin"
                                                ></i>
                                                <i v-else class="fa fa-eraser"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div
                                v-if="registros.length === 0 && !loadingBuscar"
                                class="text-center py-4 text-muted"
                            >
                                <i class="fa fa-inbox fa-3x mb-3"></i>
                                <p>No hay estudios asignados</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End PAge Content -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Right sidebar -->
        <!-- ============================================================== -->
        <!-- .right-sidebar -->
        <div class="right-sidebar" style="overflow-x: hidden; overflow-y: scroll; height: 100%">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <button
                        type="button"
                        class="right-side-toggle"
                        @click="btnCerralModalForm()"
                        aria-label="Cerrar panel"
                    >
                        <i class="ti-close"></i>
                    </button>
                </div>
                <div class="r-panel-body">
                    <!-- ============================================================== -->
                    <!-- Aqui va el contenido de los formularios -->
                    <!-- ============================================================== -->
                    <form action="#">
                        <div class="form-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> Datos del Paciente
                                    </h5>
                                    <hr />

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <dl>
                                                    <dt>Paciente:</dt>
                                                    <dd>{{ registro.nom_pacien }}</dd>
                                                    <dt># Documento:</dt>
                                                    <dd>{{ registro.num_docu }}</dd>
                                                </dl>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <dl>
                                                    <dt>Fecha de nacimiento:</dt>
                                                    <dd>{{ registro.fec_naci }}</dd>
                                                    <dt>Sexo:</dt>
                                                    <dd>{{ registro.sexo }}</dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label" for="direccion"
                                                    >Dirección
                                                    <span class="text-danger">*</span></label
                                                >
                                                <input
                                                    type="text"
                                                    id="direccion"
                                                    name="direccion"
                                                    v-model="registro.direccion"
                                                    class="form-control"
                                                    ref="primerInput"
                                                />
                                                <span class="text-danger" v-if="errores.direccion">
                                                    {{ errores.direccion[0] }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label" for="telefono"
                                                    >Teléfono
                                                    <span class="text-danger">*</span></label
                                                >
                                                <input
                                                    type="number"
                                                    id="telefono"
                                                    name="telefono"
                                                    v-model="registro.tel"
                                                    class="form-control"
                                                />
                                                <span class="text-danger" v-if="errores.tel">
                                                    {{ errores.tel[0] }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label" for="email"
                                                    >E - Mail
                                                    <span class="text-danger">*</span></label
                                                >
                                                <input
                                                    type="email"
                                                    id="email"
                                                    name="email"
                                                    v-model="registro.email"
                                                    class="form-control"
                                                />
                                                <span class="text-danger" v-if="errores.email">
                                                    {{ errores.email[0] }}</span
                                                >
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> Datos del Estudio
                                    </h5>
                                    <hr />

                                    <input
                                        type="hidden"
                                        id="estudio_producto_id"
                                        name="estudio_producto_id"
                                        v-model="registro.estudio_producto_id"
                                    />
                                    <input
                                        type="hidden"
                                        id="email_hidden"
                                        name="email"
                                        v-model="registro.email"
                                    />

                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <dl>
                                                    <dt>Descripción:</dt>
                                                    <dd>{{ registro.study_desc }}</dd>
                                                </dl>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <dl>
                                                    <dt>Detalle</dt>
                                                    <dd>{{ registro.accession_no }}</dd>
                                                </dl>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <dl>
                                                    <dt>Fecha del estudio</dt>
                                                    <dd>{{ registro.fec_estudio }}</dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label" for="email_reportar"
                                                    >Reportar al Email
                                                    <span class="text-danger">*</span></label
                                                >
                                                <input
                                                    type="email"
                                                    id="email_reportar"
                                                    name="email_reportar"
                                                    v-model="registro.email_reportar"
                                                    class="form-control"
                                                    placeholder="Ingrese aquí el email a reportar"
                                                />
                                            </div>

                                            <div class="form-group">
                                                <dl>
                                                    <dt>Observaciones</dt>
                                                    <dd>{{ registro.observaciones }}</dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> Producto
                                    </h5>
                                    <hr />

                                    <div class="row table-responsive">
                                        <div class="col-md-12">
                                            <dl>
                                                <dd>{{ registro.nom_produc }}</dd>
                                            </dl>
                                        </div>
                                    </div>
                                    <br />
                                </div>
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-9">
                                            <h5 class="card-title text-info">
                                                <i class="fa fa-edit"></i> Lectura
                                            </h5>
                                            <hr />
                                        </div>
                                        <div class="col-3">
                                            <div
                                                class="col-md-7 align-self-center text-right d-none d-md-block"
                                            >
                                                <button
                                                    type="button"
                                                    class="btn btn-secondary btn-xs"
                                                    :disabled="loadingVerImagen"
                                                    @click="verImagen()"
                                                >
                                                    <i
                                                        v-if="loadingVerImagen"
                                                        class="fa fa-spinner fa-spin"
                                                    ></i>
                                                    <i v-else class="fa fa-file-image-o"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="text-danger" v-if="errores.lectura">{{
                                        errores.lectura[0]
                                    }}</span>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <textarea
                                                    v-model="registro.lectura"
                                                    class="textarea_editor form-control"
                                                    rows="20"
                                                    id="lectura"
                                                    style="height: 100%"
                                                    placeholder="Ingrese aquí la lectura del estudio ..."
                                                ></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <!-- Nav tabs -->
                                            <ul class="nav nav-tabs" role="tablist">
                                                <li class="nav-item">
                                                    <a
                                                        class="nav-link active"
                                                        data-toggle="tab"
                                                        href="#home8"
                                                        role="tab"
                                                    >
                                                        <h5 class="card-title text-info">
                                                            <i class="fa fa-paperclip"></i> Adjuntos
                                                        </h5>
                                                    </a>
                                                </li>
                                                <li class="nav-item">
                                                    <a
                                                        class="nav-link"
                                                        data-toggle="tab"
                                                        href="#profile8"
                                                        role="tab"
                                                    >
                                                        <span>
                                                            <h5 class="card-title text-info">
                                                                <i class="fa fa-file-audio-o"></i>
                                                                Audios
                                                            </h5>
                                                        </span>
                                                    </a>
                                                </li>
                                            </ul>
                                            <!-- Tab panes -->
                                            <div class="tab-content tabcontent-border">
                                                <div
                                                    class="tab-pane active p-20"
                                                    id="home8"
                                                    role="tabpanel"
                                                >
                                                    <div
                                                        class="col-md-8 col-lg-9"
                                                        v-for="(itemArchivo, index) in soportesHC"
                                                        :key="index"
                                                    >
                                                        <a
                                                            href="javascript:void(0)"
                                                            :style="{
                                                                pointerEvents:
                                                                    loadingDescargaSoporte ===
                                                                    itemArchivo.archivo_encrip
                                                                        ? 'none'
                                                                        : 'auto',
                                                            }"
                                                            @click="
                                                                descargaSoportte(
                                                                    itemArchivo.archivo_original,
                                                                    itemArchivo.archivo_encrip
                                                                )
                                                            "
                                                        >
                                                            <i
                                                                v-if="
                                                                    loadingDescargaSoporte ===
                                                                    itemArchivo.archivo_encrip
                                                                "
                                                                class="fa fa-spinner fa-spin"
                                                            ></i>
                                                            {{ itemArchivo.archivo_original }}
                                                        </a>
                                                    </div>
                                                </div>
                                                <div
                                                    class="tab-pane p-20"
                                                    id="profile8"
                                                    role="tabpanel"
                                                >
                                                    <template
                                                        v-for="(item, index) in audiosDeEstudio"
                                                        :key="index"
                                                    >
                                                        <audio
                                                            :src="item.url || item.audio"
                                                            controls
                                                            style="height: 15px; width: 100%"
                                                        ></audio>
                                                    </template>
                                                </div>
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
                                >
                                    <i v-if="loadingGuardar" class="fa fa-spinner fa-spin"></i>
                                    <i v-else class="fa fa-check"></i> Guardar
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-inverse"
                                    @click="btnCerralModalForm()"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Right sidebar -->
        <!-- ============================================================== -->
    </div>
</template>

<script>
import LoadingSpinner from "@/components/comunes/LoadingSpinner.vue";
import { listarUsuariosYSedes } from "../../../helpers/usuarios";
import {
    safeDestroyDataTable,
    safeInitDataTable,
    safeRemoveDataTableRow,
} from "@/helpers/datatables.js";
import {
    alertError,
    alertSuccess,
    confirmAction,
    getUserSafeError,
    handleAxiosErrorToast,
} from "@/helpers/alerts.js";

export default {
    props: ["usuarioactual"],
    components: {
        LoadingSpinner,
    },
    computed: {
        contadorRegistros() {
            return this.registros.length;
        },
    },
    async mounted() {
        this.$nextTick(() => this.initWysiwyg());

        await listarUsuariosYSedes(this);
    },
    beforeUnmount() {
        this.destroyDataTable();
        this.registros = [];
        this.soportesHC = [];
        this.audiosDeEstudio = [];
        this.registro = { productosEstudio: [], diagnosticosEstudio: [], soportesHC: [] };
    },
    data() {
        return {
            id: 0,
            diagnosticosEstudio: [],
            soportesHC: [],
            registros: [],
            tituloModal: "Transcirbir estudio",
            registro: {
                id_estudio: 0,
                estudio_producto_id: 0,
                lectura: "",
                fec_lectura: "",
                fec_estudio: "",
                accession_no: "",
                study_desc: "",
                observaciones: "",
                email_reportar: "",
                num_docu: "",
                nom_pacien: "",
                sexo: "",
                fec_naci: "",
                email: "",
                tel: "",
                direccion: "",
                diagnosticosEstudio: [],
                sede_id: "",
                atencion: null,
                servicio: null,
                eps: null,
                contrato: null,
                medico_id: null,
                cod_cups: "",
                nom_produc: "",
                num_docu_medi: "",
                reg_med: "",
                medico: "",
                firma: "",
            },
            busqueda: {
                estudioParaTranscibir: "si",
                quien_asigno_id: "",
                medico_id: "",
                sede_id: sessionStorage.getItem("ST-sede"),
                bus_nom_num_docu: "",
                bus_fehc_ini: "",
                bus_fecha_fin: "",
            },
            datosImagen: { urlOviyam: "", patientId: "", studyUID: "", serverName: "" },
            audiosDeEstudio: [],
            quienes_asignaron: [],
            medicos: [],
            errores: {},
            idRow: null,
            loadingBuscar: false,
            loadingAnularId: null,
            loadingAbrirRegistroId: null,
            loadingGuardar: false,
            loadingDescargaSoporte: null,
            loadingAudios: false,
            loadingVerImagen: false,
        };
    },
    methods: {
        initWysiwyg() {
            try {
                const $root = $(this.$el);
                $root.find(".textarea_editor").each((_, el) => {
                    const $el = $(el);
                    if ($el.data("wysihtml5")) return;
                    if ($el.prevAll(".wysihtml5-toolbar").length) return;
                    $el.wysihtml5({ stylesheets: ["/lib/css/wysiwyg-color.css"] });
                });
            } catch (e) {}
        },
        initDataTable() {
            safeInitDataTable(this.$refs.dt || "#example23", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
                retrieve: true,
            });
        },
        destroyDataTable() {
            safeDestroyDataTable(this.$refs.dt || "#example23");
        },
        btnLimpiar() {
            this.busqueda.quien_asigno_id = "";
            this.busqueda.medico_id = "";
            this.busqueda.bus_nom_num_docu = "";
            this.busqueda.bus_fehc_ini = "";
            this.busqueda.bus_fecha_fin = "";
            this.errores = {};
            this.destroyDataTable();
            this.registros = [];
            // limpiar sidebar (best-effort)
            this.id = 0;
            this.idRow = null;
            this.diagnosticosEstudio = [];
            this.soportesHC = [];
            this.audiosDeEstudio = [];
        },
        async listarEstudiosAsignados() {
            if (this.loadingBuscar) return;
            this.loadingBuscar = true;
            try {
                if (sessionStorage.getItem("ST-sede") == null) {
                    $.toast({
                        heading: "Upsss!!!",
                        text: "Debes establece la sede en la cual vas a trabajar.",
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "warning",
                        hideAfter: 3500,
                        stack: 6,
                    });
                } else {
                    this.destroyDataTable();
                    this.registros = [];

                    const res = await axios.post(
                        "/api/estudio-listarEstudiosAsignados",
                        this.busqueda
                    );

                    this.registros = res.data;

                    this.$nextTick(() => {
                        this.initDataTable();
                    });
                }
            } catch (error) {
                const status = error?.response?.status;
                const message = error?.response?.data?.message;
                // Si el backend devuelve errores de validación, mostrar mensaje genérico
                if (
                    status === 400 &&
                    error?.response?.data &&
                    typeof error.response.data === "object"
                ) {
                    $.toast({
                        heading: "Upss!!!",
                        text: "Revisa los filtros de búsqueda.",
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "warning",
                        hideAfter: 3500,
                        stack: 6,
                    });
                } else {
                    handleAxiosErrorToast(
                        error,
                        message || "No fue posible listar los estudios asignados."
                    );
                }
            } finally {
                this.loadingBuscar = false;
            }
        },
        async listarUsuariosYSedes() {
            /**
             * Lisato los transcriptores
             */
            const pathname = String(window?.location?.pathname || "").toLowerCase();
            const moduloId =
                sessionStorage.getItem("ST-modulo") || (pathname.includes("ecografia") ? "2" : "1");
            const res = await axios.get(`/api/user.listarUsuarios/Transcriptor/${moduloId}`);
            this.quienes_asignaron = res.data;

            /**
             * Listo los medicos
             */
            const resMedicos = await axios.get(`/api/user.listarUsuarios/Medico/${moduloId}`);
            this.medicos = resMedicos.data;

            /**
             * Listo las sedes
             */
            this.busqueda.sede_id = sessionStorage.getItem("ST-sede");
        },
        async listarAudios() {
            this.loadingAudios = true;
            try {
                const res = await axios.get("/api/listar-audios-estudio/" + this.idRow);
                this.audiosDeEstudio = res.data;
            } catch (e) {
                handleAxiosErrorToast(e, "No fue posible listar audios.");
                this.audiosDeEstudio = [];
            } finally {
                this.loadingAudios = false;
            }
        },
        descargaSoportte(nomArchivoOriginal, nomArchivoEncriptado) {
            if (this.loadingDescargaSoporte) return;
            this.loadingDescargaSoporte = nomArchivoEncriptado;
            const url = `/api/descarga-soportes-hc/${nomArchivoEncriptado}`;
            axios
                .get(url, { responseType: "blob" })
                .then((response) => {
                    const blobUrl = window.URL.createObjectURL(new Blob([response.data]));
                    const link = document.createElement("a");
                    link.href = blobUrl;
                    link.setAttribute("download", nomArchivoOriginal || nomArchivoEncriptado);
                    document.body.appendChild(link);
                    link.click();
                    setTimeout(() => window.URL.revokeObjectURL(blobUrl), 2000);
                })
                .catch(() => {})
                .finally(() => {
                    this.loadingDescargaSoporte = null;
                });
        },
        async guardarRegistro() {
            if (this.loadingGuardar) return;
            this.loadingGuardar = true;
            try {
                const payload = {
                    ...this.registro,
                    id_estudio: this.id,
                    estudio_producto_id: this.registro.estudio_producto_id,
                };
                const res = await axios.post("/api/estudio-guardarTranscripcion", payload);

                if (res.status === 200) {
                    $.toast({
                        heading: "Completado",
                        text: res.data.message || "Guardado correctamente.",
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "success",
                        hideAfter: 3500,
                        stack: 6,
                    });

                    const idx = this.registros.findIndex(
                        (x) => x.estudio_producto_id == this.idRow
                    );
                    if (idx >= 0) {
                        // En guardado NO destruimos/reinicializamos la tabla: removemos solo la fila guardada.
                        try {
                            safeRemoveDataTableRow(
                                this.$refs.dt || "#example23",
                                "#row-" + this.idRow
                            );
                        } catch (e) {}
                        this.registros.splice(idx, 1);
                    }

                    this.btnCerralModalForm();
                    this.limpiar();
                } else {
                    $.toast({
                        heading: "Upss!!!",
                        text: res.data.message || "No fue posible guardar.",
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "danger",
                        hideAfter: 3500,
                        stack: 6,
                    });
                }
            } catch (error) {
                const status = error?.response?.status;
                if (
                    status == 400 &&
                    error?.response?.data &&
                    typeof error.response.data === "object"
                ) {
                    const data = error.response.data;
                    this.errores =
                        data.errors && typeof data.errors === "object" ? data.errors : data;
                }
                $.toast({
                    heading: "Upss!!!",
                    text: getUserSafeError(error, "No fue posible guardar la transcripción."),
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "error",
                    hideAfter: 3500,
                    stack: 6,
                });
            } finally {
                this.loadingGuardar = false;
            }
        },
        async anularAsignacion(idProductoEstudio, paciente, producto) {
            const ok = await confirmAction({
                title: `Estas seguro de anular la lectura del paciente ${paciente} con el producto ${producto} ?`,
                text: "No podrás recuperar este registro!",
                icon: "warning",
            });
            if (!ok) return;
            if (this.loadingAnularId) return;

            this.loadingAnularId = idProductoEstudio;
            try {
                const res = await axios.post("/api/estudio-anular-asignacion", {
                    id: idProductoEstudio,
                });
                if (res.status === 200) {
                    const idx = this.registros.findIndex(
                        (x) => x.estudio_producto_id == idProductoEstudio
                    );
                    if (idx >= 0) {
                        // Al anular, remover solo la fila afectada.
                        try {
                            safeRemoveDataTableRow(
                                this.$refs.dt || "#example23",
                                "#row-" + idProductoEstudio
                            );
                        } catch (e) {}
                        this.registros.splice(idx, 1);
                    }
                    alertSuccess("Anulado!", "El producto se anulo correctamente.", "success");
                } else {
                    alertError("Ups!", "No fue posible anular el producto.", "error");
                }
            } catch (err) {
                handleAxiosErrorToast(err, "No fue posible anular el producto.");
                alertError("Error", "No fue posible anular el producto.", "error");
            } finally {
                this.loadingAnularId = null;
            }
        },
        verImagen() {
            if (this.loadingVerImagen) return;
            this.loadingVerImagen = true;
            const base = this.datosImagen.urlOviyam || "";
            const params = new URLSearchParams({
                patientID: String(this.datosImagen.patientId ?? ""),
                studyUID: String(this.datosImagen.studyUID ?? ""),
                serverName: String(this.datosImagen.serverName ?? ""),
            });
            try {
                window.open(`${base}?${params.toString()}`, "_blank", "noopener,noreferrer");
            } finally {
                setTimeout(() => {
                    this.loadingVerImagen = false;
                }, 500);
            }
        },
        async mostrarRegistro(data = {}) {
            if (this.loadingAbrirRegistroId !== null) return;
            this.loadingAbrirRegistroId = data.estudio_producto_id;
            try {
                // reset panel data
                this.errores = {};
                this.diagnosticosEstudio = [];
                this.soportesHC = [];
                this.audiosDeEstudio = [];

                this.tituloModal = "Paciente: " + data.nom_pacien;
                this.id = data.id;
                this.idRow = data.estudio_producto_id;

                this.registro.id_estudio = data.id;
                this.registro.estudio_producto_id = data.estudio_producto_id;
                this.registro.fec_estudio = data.fec_estudio;
                this.registro.fec_lectura = data.fechor_lectura;
                this.registro.accession_no = data.accession_no;
                this.registro.study_desc = data.study_desc;
                this.registro.email_reportar = data.email_reportar;
                this.registro.atencion = data.atencion;
                this.registro.medico_id = data.medico_id;
                this.registro.num_docu = data.num_docu;
                this.registro.nom_pacien = data.nom_pacien;
                this.registro.direccion = data.direccion;
                this.registro.email = data.email;
                this.registro.tel = data.tel;
                this.registro.sexo = data.sexo;
                this.registro.fec_naci = data.fec_naci;
                this.registro.cod_cups = data.cod_cups;
                this.registro.nom_produc = data.nom_produc;
                this.registro.num_docu_medi = data.num_docu_medi;
                this.registro.reg_med = data.reg_med;
                this.registro.medico = data.medico;
                this.registro.firma = data.firma;
                this.registro.lectura = `Atención: ${data.atencion}\nFecha y Hora: ${data.fec_estudio}\n\n${data.nom_produc}\n\n${data.lectura}`;

                this.datosImagen.urlOviyam = data.url_oviyam;
                this.datosImagen.patientId = data.num_docu;
                this.datosImagen.studyUID = data.study_iuid;
                this.datosImagen.serverName = data.tap_oviyam;

                this.registro.sede_id = data.sede_id;
                this.registro.nom_sede = data.nom_sede;

                const res = await axios.get(
                    "/api/study-listar-diagnostico-por-estudio?id=" + this.id
                );
                this.diagnosticosEstudio = res.data;

                const resSoportesHC = await axios.get(
                    "/api/listar-soportes-hc-estudio?id=" + this.id
                );
                this.soportesHC = resSoportesHC.data;

                this.audiosDeEstudio = [];
                this.abrirModal();

                await this.listarAudios();

                this.$nextTick(() => {
                    const $editor = $(this.$el).find(".textarea_editor");
                    const wysi = $editor.data("wysihtml5");
                    if (wysi) {
                        const el = document.createElement("div");
                        el.appendChild(document.createTextNode(this.registro.lectura || ""));
                        wysi.editor.setValue(el.innerHTML);
                    }
                });
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible cargar el estudio seleccionado.");
            } finally {
                this.loadingAbrirRegistroId = null;
            }
        },
        limpiar() {
            this.tituloModal = "Nuevo registro";
            this.registro.email_reportar = "";
            this.registro.email = "";
            this.registro.tel = "";
            this.registro.direccion = "";
            this.registro.lectura = "";
            this.registro.estudio_producto_id = 0;
            this.registro.id_estudio = 0;

            this.btnCerralModalForm();
            this.errores = {};
        },
        abrirModal() {
            $(".right-sidebar").slideDown(50);
            $(".right-sidebar").addClass("shw-rside");
            this.$nextTick(() => {
                this.$refs.primerInput?.focus();
            });
        },
        btnCerralModalForm() {
            $(".right-sidebar").slideUp(50);
            $(".right-sidebar").removeClass("shw-rside");
        },
    },
};
</script>

<style scoped>
.right-sidebar {
    position: fixed;
    right: -80%;
    width: 80%;
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

@media (max-width: 767px) {
    .right-sidebar {
        right: -100%;
        width: 100%;
    }
}

.shw-rside {
    right: 0px;
    width: 80%;
    display: block;
}

@media (max-width: 767px) {
    .shw-rside {
        width: 100%;
    }
}
</style>
