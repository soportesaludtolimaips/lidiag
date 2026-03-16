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
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Estudios</a>
                    </li>
                    <li class="breadcrumb-item active">Trascribir Estudios Tele radiologia</li>
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
                            <i class="fa fa-filter"></i> Filtros para busqueda de mis pendientes por
                            transcribir
                        </h4>
                        <form action="#">
                            <div class="form-body">
                                <div class="row p-t-2">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>Quien asigno</label>
                                            <select v-model="busqueda.quien_asigno_id" name="quien_asigno_id"
                                                id="quien_asigno_id" class="form-control custom-select">
                                                <option value="">
                                                    ...::: Todos los usuarios :::...
                                                </option>
                                                <option v-for="(
ItemUsuario, index
                                                    ) in quienes_asignaron" :key="index" :value="ItemUsuario.id">
                                                    {{ ItemUsuario.name }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label">Nombres o # documento</label>
                                            <input type="text" id="bus_nom_num_docu" name="bus_nom_num_docu"
                                                v-model="busqueda.bus_nom_num_docu" class="form-control"
                                                placeholder="Nombres de paciente o # de documento" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Inicio</label>
                                            <input type="date" id="nom_diagnos" name="nom_diagnos"
                                                v-model="busqueda.bus_fehc_ini" class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Fin</label>
                                            <input type="date" id="fecha_fin" name="fecha_fin"
                                                v-model="busqueda.bus_fecha_fin" class="form-control" />
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions mt-3">
                                    <button type="button" class="btn btn-success m-r-5" :disabled="loadingBuscar"
                                        @click="listarPendientesTrascribir()">
                                        <i v-if="loadingBuscar" class="fa fa-spinner fa-spin"></i>
                                        <i v-else class="fa fa-search"></i>
                                        Buscar
                                    </button>
                                    <button type="button" class="btn btn-inverse" @click="btnLimpiar()">
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
                        <h4 class="card-title">Listado de estudios disponibles</h4>
                        <h6 class="card-subtitle text-info">
                            Asigne los estudios a los médicos para su respectiva lectura.
                        </h6>
                        <div class="table-responsive">
                            <table id="example23" ref="dt" class="table table-bordered table-striped" cellspacing="0"
                                width="100%">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Sede</th>
                                        <th>Toma</th>
                                        <th with="50%">Lectura</th>
                                        <th>Asignación</th>
                                        <th>Quien Asigno</th>
                                        <th with="50%">Medico</th>
                                        <th with="50%">Paciente</th>
                                        <th>Detalle</th>
                                        <th>Producto</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.estudio_producto_id"
                                        :id="'row-' + item.estudio_producto_id">
                                        <td>
                                            <button type="button" v-if="item.priori_id == 1"
                                                class="btn waves-effect waves-light btn-rounded btn-sm btn-danger">
                                                A
                                            </button>
                                            <button type="button" v-else-if="item.priori_id == 2"
                                                class="btn waves-effect waves-light btn-rounded btn-sm btn-warning">
                                                M
                                            </button>
                                            <button type="button" v-else-if="item.priori_id == 3"
                                                class="btn waves-effect waves-light btn-rounded btn-sm btn-info">
                                                B
                                            </button>
                                            <button type="button" v-else-if="item.priori_id == 4"
                                                class="btn waves-effect waves-light btn-rounded btn-sm">
                                                S
                                            </button>
                                        </td>
                                        <td>{{ item.nom_sede }}</td>
                                        <td>{{ item.fec_estudio }}</td>
                                        <td>{{ item.fechor_lectura }}</td>
                                        <td>{{ formatDate(item.created_at) }}</td>
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
                                        <td class="text-nowrap">
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                :disabled="loadingAbrirId === item.estudio_producto_id
                                                    " @click="
                                                        actualizar = true;
                                                    mostrarRegistro(item);
                                                    ">
                                                <i v-if="
                                                    loadingAbrirId === item.estudio_producto_id
                                                " class="fa fa-spinner fa-spin"></i>
                                                <i v-else class="fa fa-pencil"></i>
                                            </button>
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm m-r-5"
                                                :disabled="loadingAnularId === item.estudio_producto_id
                                                    " @click="
                                                        anularAsignacion(
                                                            item.estudio_producto_id,
                                                            item.nom_pacien,
                                                            item.nom_produc
                                                        )
                                                        ">
                                                <i v-if="
                                                    loadingAnularId === item.estudio_producto_id
                                                " class="fa fa-spinner fa-spin"></i>
                                                <i v-else class="fa fa-eraser"></i>
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
        <!-- End PAge Content -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Right sidebar -->
        <!-- ============================================================== -->
        <!-- .right-sidebar -->
        <div class="right-sidebar" style="overflow-x: hidden; overflow-y: scroll; overflow-x: hidden; height: 100%">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <span @click="btnCerralModalForm()">
                        <i class="ti-close right-side-toggle" id="btnCerralModalForm" @click="btnCerralModalForm()"></i>
                    </span>
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
                                                <label class="control-label">Dirección</label>
                                                <input type="text" id="direccion" name="direccion"
                                                    v-model="registro.direccion" class="form-control" />
                                                <span class="text-danger" v-if="errores.direccion">
                                                    {{ errores.direccion[0] }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label">Télefono</label>
                                                <input type="number" id="telefono" name="telefono"
                                                    v-model="registro.tel" class="form-control" />
                                                <span class="text-danger" v-if="errores.tel">
                                                    {{ errores.tel[0] }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">E - Mail</label>
                                                <input type="email" id="email" name="email" v-model="registro.email"
                                                    class="form-control" />
                                                <span class="text-danger" v-if="errores.email">
                                                    {{ errores.email[0] }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> Datos del Estudio
                                    </h5>
                                    <hr />

                                    <input type="hidden" id="estudio_producto_id" name="estudio_producto_id"
                                        v-model="registro.estudio_producto_id" />
                                    <input type="hidden" id="email" name="email" v-model="registro.email" />

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
                                                <label class="control-label">Reportar al Email</label>
                                                <input type="text" id="email_reportar" name="email_reportar"
                                                    v-model="registro.email_reportar" class="form-control"
                                                    placeholder="Ingrese aqi el email a reportar" />
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
                                            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                                                <button type="button" class="btn btn-secondary btn-xs"
                                                    :disabled="loadingVerImagen" @click="verImagen()">
                                                    <i v-if="loadingVerImagen" class="fa fa-spinner fa-spin"></i>
                                                    <i v-else class="fa fa-file-image-o"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="text-danger" v-if="errores.lectura">{{
                                        errores.lectura[0]
                                    }}</span>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group" id="medicos-parent">
                                                <label>{{ registro.nombres_medico }}</label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group">
                                                <textarea v-model="registro.lectura"
                                                    class="textarea_editor form-control" rows="20" id="lectura"
                                                    style="height: 100%"
                                                    placeholder="Ingrese aquí la lectura del estudio ..."></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <!-- Nav tabs -->
                                            <ul class="nav nav-tabs" role="tablist">
                                                <li class="nav-item">
                                                    <a class="nav-link active" data-toggle="tab" href="#home8"
                                                        role="tab">
                                                        <h5 class="card-title text-info">
                                                            <i class="fa fa-paperclip"></i> Adjuntos
                                                        </h5>
                                                    </a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" data-toggle="tab" href="#profile8" role="tab">
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
                                                <div class="tab-pane active p-20" id="home8" role="tabpanel">
                                                    <div class="col-md-8 col-lg-9"
                                                        v-for="(itemArchivo, index) in soportesHC" :key="index">
                                                        <a href="javascript:void(0)" :style="{
                                                            pointerEvents:
                                                                loadingDescargaSoporte ===
                                                                    itemArchivo.archivo_encrip
                                                                    ? 'none'
                                                                    : 'auto',
                                                        }" @click="
                                                            descargaSoportte(
                                                                itemArchivo.archivo_original,
                                                                itemArchivo.archivo_encrip
                                                            )
                                                            ">
                                                            <i v-if="
                                                                loadingDescargaSoporte ===
                                                                itemArchivo.archivo_encrip
                                                            " class="fa fa-spinner fa-spin"></i>
                                                            {{ itemArchivo.archivo_original }}
                                                        </a>
                                                    </div>
                                                </div>
                                                <div class="tab-pane p-20" id="profile8" role="tabpanel">
                                                    <template v-for="(item, index) in audiosDeEstudio" :key="index">
                                                        <audio :src="item.url" controls
                                                            style="height: 15px; width: 100%"></audio>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions mt-3">
                                <button type="button" class="btn btn-success m-r-5" @click="guardarRegistro()"
                                    :disabled="loadingGuardar">
                                    <i v-if="loadingGuardar" class="fa fa-spinner fa-spin"></i>
                                    <i v-else class="fa fa-check"></i> Guardar
                                </button>

                                <button type="button" class="btn btn-inverse" ref="btnCerrarForm"
                                    @click="btnCerralModalForm()">
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
    handleAxiosErrorToast,
} from "@/helpers/alerts.js";

import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import timezone from "dayjs/plugin/timezone";

dayjs.extend(utc);
dayjs.extend(timezone);

export default {
    props: ["usuarioactual"],
    async mounted() {
        this.listarMedicos();
        this.establecerSede();
        this.busqueda.sede_id = sessionStorage.getItem("ST-sede");
        this.listarUsuariosYSedes();

        await listarUsuariosYSedes(this);

        this.emitter.on("sedeSeleccionada", (data) => {
            console.log("Ok");
            console.log(data);
            this.busqueda.sede_id = data.idSede;
            this.destroyDataTable();
            this.listarPendientesTrascribir();
        });
    },
    beforeUnmount() {
        this.destroyDataTable();
    },
    data() {
        return {
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
                num_docu_medico: "",
                reg_medico: "",
                nombres_medico: "",
                firma_medico: "",
                url_oviyam: null,
                tap_oviyam: null,
                study_iuid: null,
                destino_estudio: "Tele Radiologia",
            },
            busqueda: {
                estudioParaTranscibir: "si",
                quien_asigno_id: "",
                medico_id: "",
                sede_id: "",
                bus_nom_num_docu: "",
                bus_fehc_ini: "",
                bus_fecha_fin: "",
                destino_estudio: "Tele Radiologia",
            },
            datosImagen: { urlOviyam: "", patientId: "", studyUID: "", serverName: "" },
            audiosDeEstudio: [],
            quienes_asignaron: [],
            medicos: [],
            errores: {},
            idRow: null,
            loadingBuscar: false,
            loadingGuardar: false,
            loadingAbrirId: null,
            loadingAnularId: null,
            loadingDescargaSoporte: null,
            loadingAudios: false,
            loadingVerImagen: false,
        };
    },
    computed: {
        medicoOptions() {
            // Opciones para el Select2 local de medicos.
            return (this.medicos || []).map((p) => ({ id: p.id, name: p.name }));
        },
    },
    methods: {
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
        },
        async listarPendientesTrascribir() {
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

                    const res = await axios.post(
                        "/api/estudio-listarPendientesTrascribir",
                        this.busqueda
                    );
                    console.log(this.busqueda);
                    this.registros = res.data;

                    this.$nextTick(() => {
                        this.initDataTable();
                    });
                }
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar los pendientes.");
            } finally {
                this.loadingBuscar = false;
            }
        },
        async guardarRegistro() {
            if (this.loadingGuardar) return;
            this.loadingGuardar = true;
            try {
                $.toast({
                    heading: "Ok!!!",
                    text: "Enviando lectura, por favor espere",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "info",
                    hideAfter: 3500,
                    stack: 6,
                });

                const res = await axios.post("/api/estudio-guardarTranscripcion", this.registro);

                if (res.status == 200) {
                    const estudioEliminar = this.registros.findIndex(
                        (x) => x.estudio_producto_id == this.idRow
                    );
                    if (estudioEliminar >= 0) {
                        try {
                            safeRemoveDataTableRow(
                                this.$refs.dt || "#example23",
                                "#row-" + this.idRow
                            );
                        } catch (e) { }
                        this.registros.splice(estudioEliminar, 1);
                    }

                    $.toast({
                        heading: "Ok!!!",
                        text: res.data.message,
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "success",
                        hideAfter: 3500,
                        stack: 6,
                    });
                    this.$refs.btnCerrarForm.click();

                    this.btnCerralModalForm();
                    this.limpiar();
                } else {
                    $.toast({
                        heading: "Upss!!!",
                        text: res.data.message,
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
                    this.errores = error.response.data;
                }
                if (status == 402) {
                    $.toast({
                        heading: "Upss!!!",
                        text:
                            error?.response?.data?.message ||
                            "La plantilla no está configurada para esta sede.",
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "error",
                        hideAfter: 3500,
                        stack: 6,
                    });
                } else {
                    $.toast({
                        heading: "Upss!!!",
                        text:
                            error?.response?.data?.message ||
                            "No fue posible guardar la transcripción.",
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "error",
                        hideAfter: 3500,
                        stack: 6,
                    });
                }
            } finally {
                this.loadingGuardar = false;
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
            /* const resMedicos = await axios.get('/user.listarUsuarios/Medico/{moduloId}');
            this.medicos = resMedicos.data; */

            /**
             * Listo las sedes
             */
            this.busqueda.sede_id = sessionStorage.getItem("ST-sede");
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
                .catch((error) => {
                    console.error(error);
                })
                .finally(() => {
                    this.loadingDescargaSoporte = null;
                });
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
                        try {
                            safeRemoveDataTableRow(
                                this.$refs.dt || "#example23",
                                "#row-" + idProductoEstudio
                            );
                        } catch (e) { }
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
                window.open(`${base}?${params.toString()}`, "_blank");
            } finally {
                setTimeout(() => {
                    this.loadingVerImagen = false;
                }, 500);
            }
        },
        async listarAudios() {
            this.loadingAudios = true;
            try {
                const res = await axios.get("/api/listar-audios-estudio/" + this.id);
                this.audiosDeEstudio = res.data;
            } catch (e) {
                handleAxiosErrorToast(e, "No fue posible listar audios.");
                this.audiosDeEstudio = [];
            } finally {
                this.loadingAudios = false;
            }
        },
        async listarMedicos() {
            try {
                const pathname = String(window?.location?.pathname || "").toLowerCase();
                const moduloId =
                    sessionStorage.getItem("ST-modulo") ||
                    (pathname.includes("ecografia") ? "2" : "1");
                const res = await axios.get(`/api/user.listarUsuarios/Medico/${moduloId}`);
                this.medicos = res.data;
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar médicos.");
                this.medicos = [];
            }
        },
        onMedicoSelect(value) {
            this.registro.medico_id = value ? String(value) : null;
        },
        establecerSede() {
            this.busqueda.sede_id = sessionStorage.getItem("ST-sede");
            console.log(sessionStorage.getItem("ST-sede"));
            this.registros = [];
        },
        formatDate(timestamp) {
            // Convertir el timestamp UTC a la zona horaria local y formatear
            return dayjs.utc(timestamp).local().format("YYYY-MM-DD HH:mm:ss");
        },
        async mostrarRegistro(data = {}) {
            console.log("data", data);

            if (this.loadingAbrirId !== null) return;
            this.loadingAbrirId = data.estudio_producto_id;
            const date = new Date();
            const fechaActual =
                ("0" + date.getDate()).slice(-2) +
                "-" +
                ("0" + (date.getMonth() + 1)).slice(-2) +
                "-" +
                date.getFullYear() +
                " " +
                ("0" + date.getHours()).slice(-2) +
                ":" +
                ("0" + date.getMinutes()).slice(-2) +
                ":" +
                ("0" + date.getSeconds()).slice(-2);

            this.tituloModal = "Transcribir la lectura del paciente: " + data.nom_pacien;
            this.id = data.id;
            this.idRow = data.estudio_producto_id;
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
            this.registro.sexo = data.sexo ?? data.pat_sex;
            this.registro.fec_naci = data.fec_naci ?? data.pat_birthdate;
            this.registro.cod_cups = data.cod_cups;
            this.registro.nom_produc = data.nom_produc;
            this.registro.num_docu_medico = data.num_docu_medico;
            this.registro.reg_medico = data.reg_medico;
            this.registro.nombres_medico = `Médico: ${data.nombres_medico}`;
            this.registro.firma_medico = data.firma_medico;
            this.registro.lectura = `Atención: ${data.atencion}\nFecha y Hora del estudio: ${data.fec_estudio}\nFecha y Hora de transcripción: ${fechaActual}\n\n${data.nom_produc}\n\n${data.lectura}`;

            this.datosImagen.urlOviyam = data.url_oviyam;
            this.datosImagen.patientId = data.num_docu;
            this.datosImagen.studyUID = data.study_iuid;
            this.datosImagen.serverName = data.tap_oviyam;

            this.registro.sede_id = data.sede_id;
            this.registro.nom_sede = data.nom_sede;

            this.registro.url_oviyam = data.url_oviyam;
            this.registro.study_iuid = data.study_iuid;
            this.registro.tap_oviyam = data.tap_oviyam;

            const res = await axios.get("/api/study-listar-diagnostico-por-estudio?id=" + this.id);
            this.diagnosticosEstudio = res.data;

            const resSoportesHC = await axios.get("/api/listar-soportes-hc-estudio?id=" + this.id);
            this.soportesHC = resSoportesHC.data;

            this.audiosDeEstudio = [];
            this.listarAudios();
            this.btnCerralModalForm();
            this.errores = {};
            this.loadingAbrirId = null;
        },
        limpiar() {
            this.actualizar = false;
            this.tituloModal = "Nuevo registro";
            this.registro.email_reportar = "";
            this.registro.email = "";

            $("#btnCerralModalForm").click();
            this.errores = {};
        },
        btnCerralModalForm() {
            $(".right-sidebar").slideDown(50);
            $(".right-sidebar").toggleClass("shw-rside");

            this.audiosDeEstudio = [];
        },
    },
};
</script>

<style scoped>
.right-sidebar {
    position: fixed;
    right: -95%;
    width: 95%;
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
    width: 95%;
    display: block;
}
</style>
