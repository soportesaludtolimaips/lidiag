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
                    <li class="breadcrumb-item active">Asignar Estudios</li>
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
                            <i class="fa fa-filter"></i> Filtros para busqueda de estudios
                        </h4>
                        <form action="#">
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
                                            <span
                                                class="text-danger"
                                                v-if="erroresBusqueda.bus_fech_ini"
                                            >
                                                {{ erroresBusqueda.bus_fech_ini[0] }}
                                            </span>
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
                                            <span
                                                class="text-danger"
                                                v-if="erroresBusqueda.bus_fecha_fin"
                                            >
                                                {{ erroresBusqueda.bus_fecha_fin[0] }}
                                            </span>
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
                                <h4 class="card-title">Listado de estudios disponibles</h4>
                                <h6 class="card-subtitle text-info">
                                    Asigne los estudios a los médicos para su respectiva lectura.
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
                        <div class="table-responsive" v-show="!loadingBuscar">
                            <table
                                id="example23"
                                ref="dt"
                                class="table table-bordered table-striped"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th with="5%">Fecha del Estudio</th>
                                        <th with="5%"># Documento</th>
                                        <th with="30%">Paciente</th>
                                        <th>Accession</th>
                                        <th>Descripción</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="registros.length === 0">
                                        <td colspan="6" class="text-center text-muted py-3">
                                            Sin resultados
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="(item, index) in registros"
                                        :key="item.study_pk"
                                        :id="item.study_pk"
                                        :class="{ 'text-info': item.agendado !== 0 }"
                                    >
                                        <td>{{ item.study_datetime }}</td>
                                        <td>{{ item.pat_id }}</td>
                                        <td>{{ item.pat_name }}</td>
                                        <td>{{ item.accession_no }}</td>
                                        <td>{{ item.study_desc }}</td>
                                        <td class="text-nowrap">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm m-r-5"
                                                :disabled="loadingAbrirRegistroIndex === index"
                                                @click="
                                                    actualizar = true;
                                                    mostrarRegistro(item, index);
                                                "
                                                title="Asignar estudio"
                                                aria-label="Asignar estudio"
                                            >
                                                <i
                                                    v-if="loadingAbrirRegistroIndex === index"
                                                    class="fa fa-spinner fa-spin"
                                                ></i>
                                                <i v-else class="fa fa-child"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm"
                                                @click="
                                                    verImagen(
                                                        item.pat_id,
                                                        item.study_iuid,
                                                        item.estudio_producto_id
                                                    )
                                                "
                                                title="Ver imagenes"
                                                aria-label="Ver imagenes"
                                                :disabled="
                                                    loadingImagenId === item.estudio_producto_id
                                                "
                                            >
                                                <i
                                                    v-if="
                                                        loadingImagenId === item.estudio_producto_id
                                                    "
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
        <!-- ============================================================== -->
        <!-- End Page Content -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Right sidebar -->
        <!-- ============================================================== -->
        <!-- .right-sidebar -->
        <div class="right-sidebar sidebar-scroll" id="right-sidebar">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <button
                        type="button"
                        class="right-side-toggle"
                        @click="cerrarModal()"
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
                                    <form-paciente
                                        :registro="registro"
                                        :errores="errores"
                                        :mostrar-errores="false"
                                    />

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-edit"></i> Datos del Estudio
                                    </h5>
                                    <hr />

                                    <input
                                        type="hidden"
                                        id="study_pk"
                                        name="study_pk"
                                        v-model="registro.study_pk"
                                    />
                                    <input
                                        type="hidden"
                                        id="study_iuid"
                                        name="study_iuid"
                                        v-model="registro.study_iuid"
                                    />
                                    <input
                                        type="hidden"
                                        id="study_id"
                                        name="study_id"
                                        v-model="registro.study_id"
                                    />

                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label" for="study_desc"
                                                    >Descripción
                                                    <span class="text-danger">*</span></label
                                                >
                                                <input
                                                    type="text"
                                                    id="study_desc"
                                                    name="study_desc"
                                                    v-model="registro.study_desc"
                                                    class="form-control"
                                                    ref="primerInput"
                                                />
                                                <span class="text-danger" v-if="errores.study_desc">
                                                    {{ errores.study_desc[0] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label" for="accession_no"
                                                    >Detalle
                                                    <span class="text-danger">*</span></label
                                                >
                                                <input
                                                    type="text"
                                                    id="accession_no"
                                                    name="accession_no"
                                                    v-model="registro.accession_no"
                                                    class="form-control"
                                                />
                                                <span
                                                    class="text-danger"
                                                    v-if="errores.accession_no"
                                                >
                                                    {{ errores.accession_no[0] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label" for="study_datetime"
                                                    >Fecha <span class="text-danger">*</span></label
                                                >
                                                <input
                                                    type="text"
                                                    id="study_datetime"
                                                    name="study_datetime"
                                                    v-model="registro.study_datetime"
                                                    class="form-control"
                                                />
                                                <span
                                                    class="text-danger"
                                                    v-if="errores.study_datetime"
                                                >
                                                    {{ errores.study_datetime[0] }}
                                                </span>
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
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label" for="observaciones"
                                                    >Observaciones</label
                                                >
                                                <input
                                                    type="text"
                                                    id="observaciones"
                                                    name="observaciones"
                                                    v-model="registro.observaciones"
                                                    class="form-control"
                                                    placeholder="Ingrese aquí las el motivo del estudio"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-edit"></i> Adjunto
                                    </h5>
                                    <hr />

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <input
                                                    type="file"
                                                    class="form-control"
                                                    id="exampleInputFile1"
                                                    aria-describedby="fileHelp"
                                                    accept=".pdf,.jpg,.png,.doc,.docx"
                                                    @change="obtenerArchivo1"
                                                />
                                            </div>
                                            <div class="form-group">
                                                <input
                                                    type="file"
                                                    class="form-control"
                                                    id="exampleInputFile2"
                                                    aria-describedby="fileHelp"
                                                    accept=".pdf,.jpg,.png,.doc,.docx"
                                                    @change="obtenerArchivo2"
                                                />
                                            </div>
                                            <div class="form-group">
                                                <input
                                                    type="file"
                                                    class="form-control"
                                                    id="exampleInputFile3"
                                                    aria-describedby="fileHelp"
                                                    accept=".pdf,.jpg,.png,.doc,.docx"
                                                    @change="obtenerArchivo3"
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
                                        <div class="col-md-8">
                                            <div class="form-group" id="medicos-parent">
                                                <label class="control-label"
                                                    >Médico
                                                    <span class="text-danger">*</span></label
                                                >
                                                <Select2
                                                    :value="registro.medico_id"
                                                    :options="medicoOptions"
                                                    width="100%"
                                                    :allow-clear="true"
                                                    :label-fields="['name']"
                                                    @input="onMedicoSelect"
                                                />

                                                <span class="text-danger" v-if="errores.medico_id">
                                                    {{ errores.medico_id[0] }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label" for="prioridad_id"
                                                    >Prioridad
                                                    <span class="text-danger">*</span></label
                                                >
                                                <select
                                                    v-model="registro.prioridad_id"
                                                    name="prioridad_id"
                                                    id="prioridad_id"
                                                    class="form-control custom-select"
                                                >
                                                    <option
                                                        v-for="(
                                                            ItemPrioridad, index
                                                        ) in prioridades"
                                                        :key="index"
                                                        :value="ItemPrioridad.id"
                                                    >
                                                        {{ ItemPrioridad.nom_priori }} -
                                                        {{ ItemPrioridad.tiempo }}
                                                        {{ ItemPrioridad.tipo_tiempo }}
                                                        {{ ItemPrioridad.observacion }}
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

                                        <div class="col-md-4" v-if="sedeInterface === true">
                                            <div class="form-group">
                                                <label
                                                    class="control-label"
                                                    for="interfaceAtencionSeleccionada"
                                                    >Atención</label
                                                >
                                                <select
                                                    v-model="interfaceAtencionSeleccionada"
                                                    name="interfaceAtencionSeleccionada"
                                                    id="interfaceAtencionSeleccionada"
                                                    class="form-control custom-select"
                                                    @change="interfaceListarProductosDiagnosticos()"
                                                >
                                                    <option
                                                        v-for="(
                                                            ItemAtencion, index
                                                        ) in interfaceAtenciones"
                                                        :key="index"
                                                        :value="ItemAtencion.IdAtencion"
                                                    >
                                                        {{ ItemAtencion.IdAtencion }} -
                                                        {{ ItemAtencion.FecIngreso }}
                                                    </option>
                                                </select>
                                                <!-- <span class="text-danger" v-if="errores.prioridad_id">
                                                    {{ errores.prioridad_id[0] }}
                                                </span> -->
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info m-t-40">
                                        <i class="fa fa-user"></i> PRODUCTOS
                                    </h5>

                                    <hr />

                                    <span class="text-danger" v-if="errores.productosEstudio">
                                        {{ errores.productosEstudio[0] }}
                                    </span>
                                    <div class="row">
                                        <div
                                            class="col-md-12"
                                            v-if="sedeInterface === false"
                                            id="productos-parent"
                                        >
                                            <Select2
                                                :value="productoSelecciondo"
                                                :options="productoOptions"
                                                width="100%"
                                                :allow-clear="true"
                                                @input="onProductoSelect"
                                            />
                                        </div>
                                        <div class="col-md-12 table-responsive table-scroll">
                                            <table
                                                id="myTable"
                                                class="table table-bordered table-striped"
                                            >
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
                                                        v-for="(
                                                            item, index
                                                        ) in registro.productosEstudio"
                                                        :key="item.id"
                                                    >
                                                        <td>
                                                            <input
                                                                type="checkbox"
                                                                class="iCheck-helper"
                                                                :id="`flat-checkbox-producto-${index}`"
                                                                data-checkbox="icheckbox_flat-red"
                                                                v-model="item.checked"
                                                            />
                                                            <label
                                                                :for="`flat-checkbox-producto-${index}`"
                                                                aria-label="Seleccionar"
                                                            ></label>
                                                        </td>
                                                        <td>{{ item.cod_cups }}</td>
                                                        <td>{{ item.nom_produc }}</td>
                                                        <td class="text-nowrap">
                                                            <button
                                                                type="button"
                                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                                @click="
                                                                    quitarProductoEstudio(index)
                                                                "
                                                            >
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info m-t-40">
                                        <i class="fa fa-user"></i> DIAGNOSTICOS
                                    </h5>

                                    <span class="text-danger" v-if="errores.diagnosticosEstudio">
                                        {{ errores.diagnosticosEstudio[0] }}
                                    </span>

                                    <div class="row">
                                        <div
                                            class="col-md-12"
                                            v-if="sedeInterface === false"
                                            id="diagnosticos-parent"
                                        >
                                            <Select2
                                                :value="diagnosticoSelecciondo"
                                                :options="diagnosticoOptions"
                                                width="100%"
                                                :allow-clear="true"
                                                :label-fields="['nom_diagnos']"
                                                @input="onDiagnosticoSelect"
                                            />
                                        </div>
                                        <div class="col-md-12 table-responsive table-scroll">
                                            <table
                                                id="myTable"
                                                class="table table-bordered table-striped"
                                            >
                                                <thead>
                                                    <tr>
                                                        <th>Código</th>
                                                        <th>Diagnostico</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr
                                                        v-for="(
                                                            item, index
                                                        ) in registro.diagnosticosEstudio"
                                                        :key="item.id"
                                                    >
                                                        <td>{{ item.cod_diagnos }}</td>
                                                        <td>{{ item.nom_diagnos }}</td>
                                                        <td class="text-nowrap">
                                                            <button
                                                                type="button"
                                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                                @click="
                                                                    quitarDiagnosticoEstudio(index)
                                                                "
                                                            >
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info m-t-40">
                                        <i class="fa fa-history"></i> MOTIVO DEL EXAMEN
                                    </h5>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div
                                                class="tab-pane"
                                                id="motivo_examen_hc"
                                                role="tabpanel"
                                            >
                                                <div class="form-group">
                                                    <textarea
                                                        id="motivo_examen"
                                                        name="motivo_examen"
                                                        v-model="registro.motivo_examen"
                                                        class="form-control"
                                                        placeholder="Ingrese aquí las observaciones del estudio"
                                                        rows="11"
                                                    ></textarea>
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
                                    <i v-else class="fa fa-check"></i>
                                    Guardar
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-inverse"
                                    @click="cerrarModal()"
                                >
                                    Cancel
                                </button>
                            </div>
                            <div class="progress mt-2 progress-thin" v-if="progresoUpload !== null">
                                <div
                                    class="progress-bar progress-bar-success"
                                    role="progressbar"
                                    :style="{ width: progresoUpload + '%' }"
                                    :aria-valuenow="progresoUpload"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                ></div>
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
import FormPaciente from "@/components/comunes/formPacienteComponent.vue";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { confirmAction, handleAxiosErrorToast } from "@/helpers/alerts";
import { normalizarFecha } from "@/helpers/dateUtils.js";
import { useSede } from "@/composables/useSede";

export default {
    props: ["usuarioactual", "permisos"],
    components: {
        LoadingSpinner,
        FormPaciente,
    },
    setup() {
        const { sedeInterface, establecerSede: cargarDatosSede } = useSede();
        return { sedeInterface, cargarDatosSede };
    },
    computed: {
        contadorRegistros() {
            return this.registros.length;
        },
        diagnosticoOptions() {
            // Adaptar opciones al Select2 local: usa optionValue='id' y labelFields.
            return (this.diagnosticos || []).map((d) => ({
                id: String(d.id),
                nom_diagnos: d.nom_diagnos,
            }));
        },
        productoOptions() {
            // Opciones para el Select2 local de productos.
            return (this.productos || []).map((p) => ({
                id: String(p.id),
                cod_cups: p.cod_cups,
                nom_produc: p.nom_produc,
            }));
        },
        medicoOptions() {
            // Opciones para el Select2 local de medicos.
            return (this.medicos || []).map((p) => ({ id: String(p.id), name: p.name }));
        },
    },
    mounted() {
        this.registro.email_reportar = "";
        this.listarMedicos();
        this.listarPrioridades();
        this.listarProductos();
        this.listarDiagnosticos();
        this.establecerSede();
        this.limpiar();

        this.emitter.on("sedeSeleccionada", (data) => {
            this.registro.sede_id = data.idSede;
            this.busqueda.sede_id = data.idSede;
            this.establecerSede();
            this.destroyDataTable();
        });
    },
    beforeUnmount() {
        this.destroyDataTable();
        this.registros = [];
        this.registro = { productosEstudio: [], diagnosticosEstudio: [] };
        this.soportesHC = { archivo1: null, archivo2: null, archivo3: null };
    },
    data() {
        const sedeActual = sessionStorage.getItem("ST-sede");
        return {
            dataIndex: 0,
            id: 0,
            registros: [],
            tituloModal: "Nuevo registro",
            registro: {
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
                sede_id: sedeActual,
                quien_registro_id: this.usuarioactual.id,
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
                destino_estudio: "Salud Tolima",
                motivo_examen: "",
            },
            soportesHC: { archivo1: null, archivo2: null, archivo3: null }, //Soporte de historia clinica
            busqueda: {
                sede_id: sedeActual,
                bus_nom_num_docu: "",
                bus_fech_ini: "",
                bus_fecha_fin: "",
            },
            interfaceAtenciones: [], //Ateniones disponibles del paciente
            interfaceAtencionSeleccionada: null, //Atencion seleccinada para listar los productos y diagnosticos
            errores: {},
            erroresBusqueda: {},
            medicos: [],
            prioridades: [], //Listo las prioridades
            productos: [], //Listo todos los productos
            diagnosticos: [], //Listo todos los diagnosticos
            productoSelecciondo: "",
            diagnosticoSelecciondo: "",
            sedeActual,
            productosAtencion: [],
            idRow: null,
            selectedRows: {},
            loadingBuscar: false,
            loadingGuardar: false,
            loadingAbrirRegistroIndex: null,
            loadingImagenId: null,
            progresoUpload: null,
        };
    },
    methods: {
        destroyDataTable() {
            safeDestroyDataTable(this.$refs.dt || "#example23");
        },
        async buscarStudy() {
            this.destroyDataTable();

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
                } else if (
                    this.busqueda.bus_nom_num_docu == "" &&
                    this.busqueda.bus_fech_ini == "" &&
                    this.busqueda.bus_fecha_fin == ""
                ) {
                    $.toast({
                        heading: "Upsss!!!",
                        text: "Debes establece la fecha de inicio o finalización para la busqueda.",
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "warning",
                        hideAfter: 3500,
                        stack: 6,
                    });
                } else if (
                    this.busqueda.bus_nom_num_docu == "" &&
                    (this.busqueda.bus_fech_ini == "" || this.busqueda.bus_fecha_fin == "")
                ) {
                    $.toast({
                        heading: "Upsss!!!",
                        text: "Para buscar por fecha debes seleccionar fecha inicio y fecha fin.",
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "warning",
                        hideAfter: 3500,
                        stack: 6,
                    });
                } else if (
                    this.busqueda.bus_fech_ini &&
                    this.busqueda.bus_fecha_fin &&
                    this.busqueda.bus_fech_ini > this.busqueda.bus_fecha_fin
                ) {
                    $.toast({
                        heading: "Upsss!!!",
                        text: "La fecha inicio no puede ser mayor a la fecha fin.",
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "warning",
                        hideAfter: 3500,
                        stack: 6,
                    });
                } else {
                    if (this.loadingBuscar) return;
                    this.loadingBuscar = true;
                    const res = await axios.post("/api/study.listarEstudios", this.busqueda);

                    if (res.status == 200) {
                        this.destroyDataTable();

                        // Asegurar que siempre sea un array (por si llega string JSON)
                        let data = res.data;
                        if (typeof data === "string") {
                            try {
                                data = JSON.parse(data);
                            } catch (e) {
                                data = [];
                            }
                        }
                        this.registros = Array.isArray(data) ? data : [];
                        this.$nextTick(() => {
                            safeInitDataTable(this.$refs.dt || "#example23", {
                                dom: "Bfrtip",
                                buttons: ["copy", "csv", "excel", "pdf", "print"],
                            });
                        });
                    } else if (res.status == 500) {
                        $.toast({
                            heading: "Upsss!!!",
                            text: res.data.message,
                            position: "top-right",
                            loaderBg: "#ff6849",
                            icon: "danger",
                            hideAfter: 3500,
                            stack: 6,
                        });
                    }
                }
            } catch (error) {
                this.erroresBusqueda = error?.response?.data?.errors || {};
                handleAxiosErrorToast(error, "No fue posible listar estudios.");
            } finally {
                this.loadingBuscar = false;
            }
        },
        async guardarRegistro() {
            if (this.loadingGuardar) return;
            this.loadingGuardar = true;

            if (
                (this.usuarioactual.transcrip_remoto == 0 ||
                    this.usuarioactual.transcrip_remoto == null) &&
                this.registro.motivo_examen == ""
            ) {
                $.toast({
                    heading: "Upsss!!!",
                    text: "El motivo del examen es obligatorio. Debes ingresarlo antes de guardar.",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "warning",
                    hideAfter: 3500,
                    stack: 6,
                });

                this.loadingGuardar = false;
                return;
            }

            try {
                const formData = new FormData();
                formData.append("registro", JSON.stringify(this.registro));
                formData.append("archivo1", this.soportesHC.archivo1);
                formData.append("archivo2", this.soportesHC.archivo2);
                formData.append("archivo3", this.soportesHC.archivo3);

                const res = await axios.post("/api/estudios", formData, {
                    headers: { "Content-Type": "multipart/form-data" },
                    onUploadProgress: (e) => {
                        this.progresoUpload = Math.round((e.loaded * 100) / e.total);
                    },
                });

                if (res.status == 200) {
                    $.toast({
                        heading: "Completado",
                        text: res.data.message,
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "success",
                        hideAfter: 3500,
                        stack: 6,
                    });

                    // Actualiza el valor de `agendado` para que se refleje el cambio de clase
                    this.registros[this.idRow].agendado = 1; // O el valor que necesites asignar
                    this.selectedRows[this.idRow] = !this.selectedRows[this.idRow];
                }

                this.btnCerralModalForm();
                this.limpiar();
            } catch (error) {
                this.errores =
                    error?.response?.data?.errors && typeof error.response.data.errors === "object"
                        ? error.response.data.errors
                        : {};
                const status = error?.response?.status;
                if (status == 400) {
                    $.toast({
                        heading: "Upsss!!!",
                        text: error.response.data.message,
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "warning",
                        hideAfter: 5500,
                        stack: 6,
                    });
                } else {
                    handleAxiosErrorToast(error, "No fue posible guardar el estudio.");
                }
            } finally {
                this.loadingGuardar = false;
                this.progresoUpload = null;
            }
        },
        obtenerArchivo1(e) {
            const file = e.target.files[0];
            if (file && file.size > 10 * 1024 * 1024) {
                $.toast({
                    heading: "Archivo muy grande",
                    text: "El archivo no debe superar los 10MB.",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "warning",
                    hideAfter: 3500,
                    stack: 6,
                });
                e.target.value = "";
                return;
            }
            this.soportesHC.archivo1 = file || null;
        },
        obtenerArchivo2(e) {
            const file = e.target.files[0];
            if (file && file.size > 10 * 1024 * 1024) {
                $.toast({
                    heading: "Archivo muy grande",
                    text: "El archivo no debe superar los 10MB.",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "warning",
                    hideAfter: 3500,
                    stack: 6,
                });
                e.target.value = "";
                return;
            }
            this.soportesHC.archivo2 = file || null;
        },
        obtenerArchivo3(e) {
            const file = e.target.files[0];
            if (file && file.size > 10 * 1024 * 1024) {
                $.toast({
                    heading: "Archivo muy grande",
                    text: "El archivo no debe superar los 10MB.",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "warning",
                    hideAfter: 3500,
                    stack: 6,
                });
                e.target.value = "";
                return;
            }
            this.soportesHC.archivo3 = file || null;
        },
        async mostrarRegistro(data = {}, index) {
            if (this.loadingAbrirRegistroIndex !== null) return;
            this.loadingAbrirRegistroIndex = index;
            this.limpiar();
            this.tituloModal = "Agendar al paciente: " + data.pat_name;
            this.id = data.study_pk;
            this.registro.study_pk = data.study_pk;
            this.registro.study_iuid = data.study_iuid;
            this.registro.study_id = data.study_id;
            this.registro.study_datetime = data.study_datetime;
            this.registro.accession_no = data.accession_no;
            this.registro.study_desc = data.study_desc;
            this.registro.mods_in_study = data.mods_in_study;
            this.registro.email = "";
            this.registro.email_reportar = sessionStorage.getItem("ST-email_repor");
            this.registro.num_docu = data.pat_id;
            this.registro.nombres = data.pat_name;
            this.registro.sexo = data.pat_sex;
            this.registro.fec_naci =
                normalizarFecha(data.pat_birthdate) || data.pat_birthdate || "";
            this.idRow = index;

            //this.btnCerralModalForm();
            this.abrirModal();

            /**
             * Verificar si la sede tiene interface, traigo la informacion de las producto y diagnosticos
             */
            try {
                const res = await axios.get(
                    "/api/config-sedes/" + this.sedeActual + "/buscarPorId"
                );

                if (res.data.interface_software != null) {
                    const resAtenciones = await axios.get(
                        "/api/interface-sahi-listar-atenciones/" + this.registro.num_docu
                    );
                    this.interfaceAtenciones = resAtenciones.data;
                }
            } catch (error) {
                handleAxiosErrorToast(
                    error,
                    "No fue posible cargar la configuración/atenciones de la sede."
                );
            } finally {
                this.loadingAbrirRegistroIndex = null;
            }
        },
        async interfaceListarProductosDiagnosticos() {
            const resProuctos = await axios.get(
                "/api/interface-sahi-listar-listarProductosDiagnosticos/" +
                    this.interfaceAtencionSeleccionada
            );

            this.registro.productosEstudio = [];
            if (resProuctos.data.productos.length > 0) {
                resProuctos.data.productos.forEach((element) => {
                    this.registro.productosEstudio.push({
                        checked: false,
                        id: element.IdProducto,
                        cod_cups: element.cod_cups,
                        nom_produc: element.nom_produc,
                    });
                });
            }

            let EnfermedadActualDiagnosticos = "";

            const consultas = resProuctos.data.consultas;
            EnfermedadActualDiagnosticos = "Enfermedad Actual: ";
            if (consultas.length > 0) {
                consultas.forEach((c) => {
                    EnfermedadActualDiagnosticos = `${EnfermedadActualDiagnosticos}\n ${c.DesEnfermedadActual} \n`;
                });
            }

            const diagnosticos = resProuctos.data.diagnosticos;
            EnfermedadActualDiagnosticos += `\nDiagnosticos:`;
            if (diagnosticos.length > 0) {
                diagnosticos.forEach((d) => {
                    EnfermedadActualDiagnosticos = `${EnfermedadActualDiagnosticos} \n ${d.cod_diagnos} - ${d.nom_diagnos}`;
                });
            }

            this.registro.motivo_examen = EnfermedadActualDiagnosticos;

            this.registro.diagnosticosEstudio = [];
            if (resProuctos.data.diagnosticos.length > 0) {
                this.registro.diagnosticosEstudio = resProuctos.data.diagnosticos;
            }

            this.registro.atencion_id = this.interfaceAtencionSeleccionada;
        },
        establecerSede() {
            this.registro.sede_id = sessionStorage.getItem("ST-sede");
            this.busqueda.sede_id = sessionStorage.getItem("ST-sede");
            this.sedeActual = sessionStorage.getItem("ST-sede");
            this.cargarDatosSede();
            this.registros = [];
        },
        btnCerralModalForm() {
            $(".right-sidebar").slideUp(50);
            $(".right-sidebar").removeClass("shw-rside");
        },
        abrirModal() {
            $(".right-sidebar").addClass("shw-rside");
            this.$nextTick(() => {
                this.$refs.primerInput?.focus();
            });
        },
        cerrarModal() {
            $(".right-sidebar").removeClass("shw-rside");
        },
        limpiar() {
            this.registro.study_pk = "";
            this.registro.study_iuid = "";
            this.registro.study_id = "";
            this.registro.study_datetime = "";
            this.registro.accession_no = "";
            this.registro.study_desc = "";
            this.registro.mods_in_study = "";
            this.registro.email_reportar = "";

            this.registro.num_docu = "";
            this.registro.nombres = "";
            this.registro.sexo = "";
            this.registro.email = "";
            this.registro.fec_naci = "";

            this.registro.diagnosticosEstudio.splice(0, this.registro.diagnosticosEstudio.length);
            this.registro.productosEstudio.splice(0, this.registro.productosEstudio.length);

            /* this.registro.medico_id = 0;
            this.registro.prioridad_id = 0; */
            this.productoSelecciondo = 0;
            this.diagnosticoSelecciondo = 0;

            this.soportesHC.archivo1 = null;
            this.soportesHC.archivo2 = null;
            this.soportesHC.archivo3 = null;

            /* this.listarMedicos();
            this.listarPrioridades(); */
        },
        async listarMedicos() {
            try {
                const moduloId = sessionStorage.getItem("ST-modulo") || "1";
                const res = await axios.get(`/api/user.listarUsuarios/Medico/${moduloId}`);
                this.medicos = res.data;
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar médicos.");
            }
        },
        async listarPrioridades() {
            try {
                const res = await axios.get("/api/config-prioridades");
                this.prioridades = res.data;
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar prioridades.");
            }
        },
        async listarProductos() {
            try {
                const res = await axios.get("/api/config-productos");
                this.productos = res.data;
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar productos.");
            }
        },
        async listarDiagnosticos() {
            try {
                const res = await axios.get("/api/config-diagnosticos");
                this.diagnosticos = res.data;
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar diagnósticos.");
            }
        },
        onMedicoSelect(value) {
            let selectedId = null;
            if (Array.isArray(value) && value.length > 0) {
                const v = value[0];
                selectedId = String(typeof v === "object" && v && v.id != null ? v.id : v);
            } else if (value && typeof value === "object" && value.id != null) {
                selectedId = String(value.id);
            } else if (value != null) {
                selectedId = String(value);
            }
            this.registro.medico_id = selectedId || "";
        },
        onProductoSelect(value) {
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
                this.productoSelecciondo = selectedId;
                this.guardarProductosEstudio();
            }
        },
        onDiagnosticoSelect(value) {
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
                this.diagnosticoSelecciondo = selectedId;
                this.guardarDiagnosticosEstudio();
            }
        },
        guardarProductosEstudio() {
            const indexProductoEstudio = this.productos.findIndex(
                (x) => x.id == this.productoSelecciondo
            );

            if (indexProductoEstudio >= 0) {
                const productoEstudioId = this.productos[indexProductoEstudio].id;
                const productoEstudioCod_Cubs = this.productos[indexProductoEstudio].cod_cups;
                const productoEstudioNomProduc = this.productos[indexProductoEstudio].nom_produc;

                const yaExiste = this.registro.productosEstudio.some(
                    (p) => p.id == productoEstudioId
                );
                if (!yaExiste) {
                    this.registro.productosEstudio.push({
                        id: productoEstudioId,
                        cod_cups: productoEstudioCod_Cubs,
                        nom_produc: productoEstudioNomProduc,
                        checked: true,
                    });
                }

                // Limpiar selección
                this.productoSelecciondo = "";
            }
        },
        quitarProductoEstudio(indexEliminar) {
            if (!confirm("¿Está seguro de eliminar este producto?")) return;
            confirmAction({
                title: "¿Está seguro?",
                text: "¿Desea eliminar este producto del estudio?",
                icon: "warning",
                confirmButtonText: "Sí, eliminar",
                onConfirm: () => {
                    this.registro.productosEstudio.splice(indexEliminar, 1);
                },
            });
        },
        guardarDiagnosticosEstudio() {
            const idx = this.diagnosticos.findIndex(
                (x) => String(x.id) === String(this.diagnosticoSelecciondo)
            );
            if (idx >= 0) {
                const diag = this.diagnosticos[idx];
                const yaExiste = this.registro.diagnosticosEstudio.some(
                    (d) => String(d.id) === String(diag.id)
                );
                if (!yaExiste) {
                    this.registro.diagnosticosEstudio.push({
                        id: diag.id,
                        cod_diagnos: diag.cod_diagnos,
                        nom_diagnos: diag.nom_diagnos,
                    });
                }
                // Limpiar selección
                this.diagnosticoSelecciondo = "";
            }
        },
        quitarDiagnosticoEstudio(indexEliminar) {
            if (!confirm("¿Está seguro de eliminar este diagnóstico?")) return;
            confirmAction({
                title: "¿Está seguro?",
                text: "¿Desea eliminar este diagnóstico del estudio?",
                icon: "warning",
                confirmButtonText: "Sí, eliminar",
                onConfirm: () => {
                    this.registro.diagnosticosEstudio.splice(indexEliminar, 1);
                },
            });
        },
        verImagen(pat_id, study_iuid, estudio_producto_id = null) {
            if (estudio_producto_id !== null) {
                if (this.loadingImagenId) return;
                this.loadingImagenId = estudio_producto_id;
            }
            try {
                const base = sessionStorage.getItem("ST-urlsede") || "";
                const params = new URLSearchParams({
                    patientID: String(pat_id ?? ""),
                    studyUID: String(study_iuid ?? ""),
                    serverName: String(sessionStorage.getItem("ST-servername") || ""),
                });
                window.open(`${base}?${params.toString()}`, "_blank", "noopener,noreferrer");
            } finally {
                setTimeout(() => {
                    this.loadingImagenId = null;
                }, 500);
            }
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
    z-index: 180;
    background: #ffffff;
    top: 0px;
    padding-bottom: 20px;
    height: 80%;
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

.sidebar-scroll {
    overflow-x: hidden;
    overflow-y: scroll;
    height: 100%;
}
.table-scroll {
    overflow-y: scroll;
    height: 250px;
}
.progress-thin {
    height: 6px;
}
.audio {
    width: 50px;
    height: 5px;
}
</style>
