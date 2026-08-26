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
                                        <td class="text-center text-muted py-3"></td>
                                        <td class="text-center text-muted py-3"></td>
                                        <td class="text-center text-muted py-3"></td>
                                        <td class="text-center text-muted py-3"></td>
                                        <td class="text-center text-muted py-3"></td>
                                        <td class="text-center text-muted py-3">Sin resultados</td>
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
                                                        sessionStorage.getItem('ST-urlsede'),
                                                        item.pat_id,
                                                        item.study_iuid,
                                                        sessionStorage.getItem('ST-servername'),
                                                        item.estudio_producto_id
                                                    )
                                                "
                                                title="Ver imagenes"
                                                aria-label="Ver imagenes"
                                                :disabled="
                                                    loadingVerImagen === item.estudio_producto_id
                                                "
                                            >
                                                <i
                                                    v-if="
                                                        loadingVerImagen ===
                                                        item.estudio_producto_id
                                                    "
                                                    class="fa fa-spinner fa-spin"
                                                ></i>
                                                <i v-else class="fa fa-file-photo-o"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                @click="abrirQuemador(item)"
                                                title="Quemar CD"
                                                aria-label="Quemar CD"
                                            >
                                                <i class="fa fa-fire"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm"
                                                @click="abrirDescargaZip(item)"
                                                title="Descargar ZIP"
                                                aria-label="Descargar ZIP"
                                            >
                                                <i class="fa fa-file-archive-o"></i>
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

        <!-- ============================================================== -->
        <!-- Modal Quemador CD -->
        <!-- ============================================================== -->
        <div
            class="modal fade"
            id="quemadorCdModal"
            tabindex="-1"
            role="dialog"
            aria-labelledby="quemadorCdModalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="quemadorCdModalLabel">
                            <i class="fa fa-compact-disc"></i> Quemador de CD/DVD
                        </h5>
                        <button
                            type="button"
                            class="close"
                            @click="cerrarQuemador()"
                            aria-label="Close"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <quemador-cd-component
                            v-if="estudioParaQuemar !== null"
                            :estudio="estudioParaQuemar"
                            :usuarioactual="usuarioactual"
                            @cerrar="cerrarQuemador"
                            @completado="onQuemadoCompletado"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- Modal Descargar ZIP -->
        <!-- ============================================================== -->
        <div
            class="modal fade"
            id="descargarZipModal"
            tabindex="-1"
            role="dialog"
            aria-labelledby="descargarZipModalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="descargarZipModalLabel">
                            <i class="fa fa-file-archive-o"></i> Descargar Estudio ZIP
                        </h5>
                        <button
                            type="button"
                            class="close"
                            @click="cerrarDescargaZip()"
                            aria-label="Close"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <descargar-zip-component
                            v-if="estudioParaDescargar !== null"
                            :estudio="estudioParaDescargar"
                            :usuarioactual="usuarioactual"
                            @cerrar="cerrarDescargaZip"
                            @completado="onDescargaZipCompletado"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, getCurrentInstance } from "vue";
import LoadingSpinner from "@/components/comunes/LoadingSpinner.vue";
import FormPaciente from "@/components/comunes/formPacienteComponent.vue";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { confirmAction, toastMessage } from "@/helpers/alerts";
import { normalizarFecha } from "@/helpers/dateUtils.js";
import { useSede } from "@/composables/useSede";
import { useEstudioHelpers } from "@/composables/useEstudioHelpers";
import QuemadorCdComponent from "@/components/modulos/estudios/QuemadorCdComponent.vue";
import DescargarZipComponent from "@/components/modulos/estudios/DescargarZipComponent.vue";
import axios from "axios";

const { proxy } = getCurrentInstance();
const emitter = proxy.emitter;

const { loadingVerImagen, verImagen } = useEstudioHelpers();

const props = defineProps({
    usuarioactual: Object,
    permisos: { type: Array, default: () => [] },
});
const dataIndex = ref(0);
const id = ref(0);
const registros = ref([]);
const tituloModal = ref("Nuevo registro");
const sedeInterface = ref(false);
const sedeActual = ref(sessionStorage.getItem("ST-sede"));
const dt = ref(null);
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
    sede_id: sedeActual.value,
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
    destino_estudio: "Salud Tolima",
    motivo_examen: "",
});
const soportesHC = ref({ archivo1: null, archivo2: null, archivo3: null });
const busqueda = ref({
    sede_id: sedeActual.value,
    bus_nom_num_docu: "",
    bus_fech_ini: "",
    bus_fecha_fin: "",
});
const interfaceAtenciones = ref([]);
const interfaceAtencionSeleccionada = ref(null);
const errores = ref({});
const erroresBusqueda = ref({});
const medicos = ref([]);
const prioridades = ref([]);
const productos = ref([]);
const diagnosticos = ref([]);
const productoSelecciondo = ref("");
const diagnosticoSelecciondo = ref("");
const productosAtencion = ref([]);
const idRow = ref(null);
const selectedRows = ref({});
const loadingBuscar = ref(false);
const loadingGuardar = ref(false);
const loadingAbrirRegistroIndex = ref(null);
const progresoUpload = ref(null);
const primerInput = ref(null);
const estudioParaQuemar = ref(null);
const estudioParaDescargar = ref(null);

const contadorRegistros = computed(() => {
    return registros.value.length;
});
const diagnosticoOptions = computed(() => {
    // Adaptar opciones al Select2 local: usa optionValue='id' y labelFields.
    return (diagnosticos.value || []).map((d) => ({
        id: String(d.id),
        nom_diagnos: d.nom_diagnos,
    }));
});
const productoOptions = computed(() => {
    // Opciones para el Select2 local de productos.
    return (productos.value || []).map((p) => ({
        id: String(p.id),
        cod_cups: p.cod_cups,
        nom_produc: p.nom_produc,
    }));
});
const medicoOptions = computed(() => {
    // Opciones para el Select2 local de medicos.
    return (medicos.value || []).map((p) => ({ id: String(p.id), name: p.name }));
});

function destroyDataTable() {
    safeDestroyDataTable(dt.value || "#example23");
}

/**
 * Buscar estudios según los criterios de búsqueda.
 * @returns {Promise<void>}
 * @author: Jhon Javer Lozano Arce
 */
async function buscarStudy() {
    destroyDataTable();

    try {
        if (sessionStorage.getItem("ST-sede") == null) {
            toastMessage({
                heading: "Upsss!!!",
                text: "Debes establece la sede en la cual vas a trabajar.",
                icon: "warning",
            });
        } else if (
            busqueda.value.bus_nom_num_docu == "" &&
            busqueda.value.bus_fech_ini == "" &&
            busqueda.value.bus_fecha_fin == ""
        ) {
            toastMessage({
                heading: "Upsss!!!",
                text: "Debes establece la fecha de inicio o finalización para la busqueda.",
                icon: "warning",
            });
        } else if (
            busqueda.value.bus_nom_num_docu == "" &&
            (busqueda.value.bus_fech_ini == "" || busqueda.value.bus_fecha_fin == "")
        ) {
            toastMessage({
                heading: "Upsss!!!",
                text: "Para buscar por fecha debes seleccionar fecha inicio y fecha fin.",
                icon: "warning",
            });
        } else if (
            busqueda.value.bus_fech_ini &&
            busqueda.value.bus_fecha_fin &&
            busqueda.value.bus_fech_ini > busqueda.value.bus_fecha_fin
        ) {
            toastMessage({
                heading: "Upsss!!!",
                text: "La fecha inicio no puede ser mayor a la fecha fin.",
                icon: "warning",
            });
        } else {
            if (loadingBuscar.value) return;
            loadingBuscar.value = true;
            const res = await axios.post("/api/study.listarEstudios", busqueda.value);

            if (res.status == 200) {
                destroyDataTable();

                // Asegurar que siempre sea un array (por si llega string JSON)
                let data = res.data.data;
                if (typeof data === "string") {
                    try {
                        data = JSON.parse(data);
                    } catch (e) {
                        data = [];
                    }
                }
                registros.value = Array.isArray(data) ? data : [];
                nextTick(() => {
                    safeInitDataTable(dt.value || "#example23", {
                        dom: "Bfrtip",
                        buttons: ["copy", "csv", "excel", "pdf", "print"],
                    });
                });
            } else if (res.status == 500) {
                toastMessage({
                    heading: "Upsss!!!",
                    text: res.data.message,
                    icon: "danger",
                });
            }
        }
    } catch (error) {
        erroresBusqueda.value = error?.response?.data?.errors || {};
    } finally {
        loadingBuscar.value = false;
    }
}

/**
 * Guardar el registro del estudio asignado.
 * @returns {Promise<void>} - Una promesa que se resuelve cuando se completa el guardado del registro.
 * @author: Jhon Javer Lozano Arce
 */
async function guardarRegistro() {
    if (loadingGuardar.value) return;
    loadingGuardar.value = true;

    if (
        (props.usuarioactual.transcrip_remoto == 0 ||
            props.usuarioactual.transcrip_remoto == null) &&
        registro.value.motivo_examen == ""
    ) {
        toastMessage({
            heading: "Upsss!!!",
            text: "El motivo del examen es obligatorio. Debes ingresarlo antes de guardar.",
            icon: "warning",
        });

        loadingGuardar.value = false;
        return;
    }

    try {
        const formData = new FormData();
        formData.append("registro", JSON.stringify(registro.value));
        if (soportesHC.value.archivo1) formData.append("archivo1", soportesHC.value.archivo1);
        if (soportesHC.value.archivo2) formData.append("archivo2", soportesHC.value.archivo2);
        if (soportesHC.value.archivo3) formData.append("archivo3", soportesHC.value.archivo3);

        const res = await axios.post("/api/estudios", formData, {
            headers: { "Content-Type": "multipart/form-data" },
            onUploadProgress: (e) => {
                progresoUpload.value = Math.round((e.loaded * 100) / e.total);
            },
        });

        if (res.status == 200) {
            toastMessage({
                heading: "Completado",
                text: res.data.message,
                icon: "success",
            });

            // Actualiza el valor de `agendado` para que se refleje el cambio de clase
            registros.value[idRow.value].agendado = 1; // O el valor que necesites asignar
            selectedRows.value[idRow.value] = !selectedRows.value[idRow.value];
        }

        btnCerralModalForm();
        btnLimpiar();
    } catch (error) {
        errores.value =
            error?.response?.data?.errors && typeof error.response.data.errors === "object"
                ? error.response.data.errors
                : {};
        const status = error?.response?.status;
        if (status == 400) {
            toastMessage({
                heading: "Upsss!!!",
                text: error.response.data.message,
                icon: "warning",
                hideAfter: 5500,
            });
        } else {
        }
    } finally {
        loadingGuardar.value = false;
        progresoUpload.value = null;
    }
}

/**
 * Obtener el archivo seleccionado y verificar su tamaño.
 * @param {Event} e - El evento de cambio del input de archivo.
 * @returns {void}
 * @author: Jhon Javer Lozano Arce
 */
function obtenerArchivo1(e) {
    const file = e.target.files[0];
    if (file && file.size > 10 * 1024 * 1024) {
        toastMessage({
            heading: "Archivo muy grande",
            text: "El archivo no debe superar los 10MB.",
            icon: "warning",
        });
        e.target.value = "";
        return;
    }
    soportesHC.value.archivo1 = file || null;
}

/**
 * Obtener el archivo seleccionado y verificar su tamaño.
 * @param {Event} e - El evento de cambio del input de archivo.
 * @returns {void}
 * @author: Jhon Javer Lozano Arce
 */
function obtenerArchivo2(e) {
    const file = e.target.files[0];
    if (file && file.size > 10 * 1024 * 1024) {
        toastMessage({
            heading: "Archivo muy grande",
            text: "El archivo no debe superar los 10MB.",
            icon: "warning",
        });
        e.target.value = "";
        return;
    }
    soportesHC.value.archivo2 = file || null;
}

/**
 * Obtener el archivo seleccionado y verificar su tamaño.
 * @param {Event} e - El evento de cambio del input de archivo.
 * @returns {void}
 * @author: Jhon Javer Lozano Arce
 */
function obtenerArchivo3(e) {
    const file = e.target.files[0];
    if (file && file.size > 10 * 1024 * 1024) {
        toastMessage({
            heading: "Archivo muy grande",
            text: "El archivo no debe superar los 10MB.",
            icon: "warning",
        });
        e.target.value = "";
        return;
    }
    soportesHC.value.archivo3 = file || null;
}

/**
 * Mostrar el registro del estudio en el modal para agendar al paciente.
 * @param {Object} data - Objeto que contiene los datos del estudio a mostrar.
 * @param {number} index - Índice del registro en la lista de estudios.
 * @returns {Promise<void>} - Una promesa que se resuelve cuando se completa la carga del registro.
 * @author: Jhon Javer Lozano Arce
 */
async function mostrarRegistro(data = {}, index) {
    if (loadingAbrirRegistroIndex.value !== null) return;
    loadingAbrirRegistroIndex.value = index;
    btnLimpiar();

    tituloModal.value = "Agendar al paciente: " + data.pat_name;
    id.value = data.study_pk;
    registro.value.study_pk = data.study_pk;
    registro.value.study_iuid = data.study_iuid;
    registro.value.study_id = data.study_id;
    registro.value.study_datetime = data.study_datetime;
    registro.value.accession_no = data.accession_no;
    registro.value.study_desc = data.study_desc;
    registro.value.mods_in_study = data.mods_in_study;
    registro.value.email = "";
    registro.value.email_reportar = sessionStorage.getItem("ST-email_repor");
    registro.value.num_docu = data.pat_id;
    registro.value.nombres = data.pat_name;
    registro.value.sexo = data.pat_sex;
    registro.value.fec_naci = normalizarFecha(data.pat_birthdate) || data.pat_birthdate || "";
    idRow.value = index;

    //btnCerralModalForm();
    abrirModal();

    /**
     * Verificar si la sede tiene interface, traigo la informacion de las producto y diagnosticos
     */
    try {
        const res = await axios.get("/api/config-sedes/" + sedeActual.value + "/buscarPorId");

        sedeInterface.value = res.data.data.interface_software != null;
        if (sedeInterface.value) {
            const resAtenciones = await axios.get(
                "/api/interface-sahi-listar-atenciones/" + registro.value.num_docu
            );
            interfaceAtenciones.value = resAtenciones.data.data;
        }
    } catch (error) {
    } finally {
        loadingAbrirRegistroIndex.value = null;
    }
}

/**
 * Listar los productos y diagnosticos de la atencion seleccionada en la interface.
 * @returns {Promise<void>} - Una promesa que se resuelve cuando se completa la carga de productos y diagnosticos.
 * @author: Jhon Javer Lozano Arce
 */
async function interfaceListarProductosDiagnosticos() {
    const resProuctos = await axios.get(
        "/api/interface-sahi-listar-listarProductosDiagnosticos/" +
            interfaceAtencionSeleccionada.value
    );

    const data = resProuctos.data.data;
    registro.value.productosEstudio = [];
    if (data.productos.length > 0) {
        data.productos.forEach((element) => {
            registro.value.productosEstudio.push({
                checked: false,
                id: element.IdProducto,
                cod_cups: element.cod_cups,
                nom_produc: element.nom_produc,
            });
        });
    }

    let EnfermedadActualDiagnosticos = "";

    const consultas = data.consultas || [];
    EnfermedadActualDiagnosticos = "Enfermedad Actual: ";
    if (consultas.length > 0) {
        consultas.forEach((c) => {
            EnfermedadActualDiagnosticos = `${EnfermedadActualDiagnosticos}\n ${c.DesEnfermedadActual} \n`;
        });
    }

    const diagnosticos = data.diagnosticos || [];
    EnfermedadActualDiagnosticos += `\nDiagnosticos:`;
    if (diagnosticos.length > 0) {
        diagnosticos.forEach((d) => {
            EnfermedadActualDiagnosticos = `${EnfermedadActualDiagnosticos} \n ${d.cod_diagnos} - ${d.nom_diagnos}`;
        });
    }

    registro.value.motivo_examen = EnfermedadActualDiagnosticos;

    registro.value.diagnosticosEstudio = [];
    if (diagnosticos.length > 0) {
        registro.value.diagnosticosEstudio = diagnosticos;
    }

    registro.value.atencion_id = interfaceAtencionSeleccionada.value;
}
function cargarDatosSede() {
    listarMedicos();
    listarPrioridades();
    listarProductos();
    listarDiagnosticos();
}
function establecerSede() {
    registro.value.sede_id = sessionStorage.getItem("ST-sede");
    busqueda.value.sede_id = sessionStorage.getItem("ST-sede");
    sedeActual.value = sessionStorage.getItem("ST-sede");
    cargarDatosSede();
    registros.value = [];
}
function btnCerralModalForm() {
    $(".right-sidebar").slideUp(50);
    $(".right-sidebar").removeClass("shw-rside");
}
function abrirModal() {
    $(".right-sidebar").addClass("shw-rside");
    nextTick(() => {
        primerInput.value?.focus();
    });
}
function cerrarModal() {
    $(".right-sidebar").removeClass("shw-rside");
}
function btnLimpiar() {
    busqueda.value.bus_nom_num_docu = "";
    busqueda.value.bus_fech_ini = "";
    busqueda.value.bus_fecha_fin = "";

    registros.value = [];
    destroyDataTable();

    registro.value.study_pk = "";
    registro.value.study_iuid = "";
    registro.value.study_id = "";
    registro.value.study_datetime = "";
    registro.value.accession_no = "";
    registro.value.study_desc = "";
    registro.value.mods_in_study = "";
    registro.value.email_reportar = "";
    registro.value.num_docu = "";
    registro.value.nombres = "";
    registro.value.sexo = "";
    registro.value.email = "";
    registro.value.fec_naci = "";
    registro.value.diagnosticosEstudio.splice(0, registro.value.diagnosticosEstudio.length);
    registro.value.productosEstudio.splice(0, registro.value.productosEstudio.length);

    interfaceAtencionSeleccionada.value = null;

    productoSelecciondo.value = "";
    diagnosticoSelecciondo.value = "";

    soportesHC.value.archivo1 = null;
    soportesHC.value.archivo2 = null;
    soportesHC.value.archivo3 = null;

    cerrarModal();
    idRow.value = null;
}

/**
 * Listar los médicos disponibles para la sede actual.
 * @returns {Promise<void>} - Una promesa que se resuelve cuando se completa la carga de médicos.
 * @author: Jhon Javer Lozano Arce
 */
async function listarMedicos() {
    try {
        const moduloId = sessionStorage.getItem("ST-modulo") || "1";
        const res = await axios.get(`/api/user.listarUsuarios/Medico/${moduloId}`);
        medicos.value = res.data.data || [];
    } catch (error) {}
}

/**
 * Listar las prioridades disponibles para la sede actual.
 * @returns {Promise<void>} - Una promesa que se resuelve cuando se completa la carga de prioridades.
 * @author: Jhon Javer Lozano Arce
 */
async function listarPrioridades() {
    try {
        const res = await axios.get("/api/config-prioridades");
        prioridades.value = res.data.data || [];
    } catch (error) {}
}

/**
 * Listar los productos disponibles para la sede actual.
 * @returns {Promise<void>} - Una promesa que se resuelve cuando se completa la carga de productos.
 * @author: Jhon Javer Lozano Arce
 */
async function listarProductos() {
    try {
        const res = await axios.get("/api/config-productos");
        productos.value = res.data.data || [];
    } catch (error) {}
}

/**
 * Listar los diagnósticos disponibles para la sede actual.
 * @returns {Promise<void>} - Una promesa que se resuelve cuando se completa la carga de diagnósticos.
 * @author: Jhon Javer Lozano Arce
 */
async function listarDiagnosticos() {
    try {
        const res = await axios.get("/api/config-diagnosticos");
        diagnosticos.value = res.data.data || [];
    } catch (error) {}
}

/**
 * Manejar la selección de un médico en el formulario.
 * @param {any} value - El valor seleccionado del médico.
 * @returns {void}
 * @author: Jhon Javer Lozano Arce
 */
function onMedicoSelect(value) {
    let selectedId = null;
    if (Array.isArray(value) && value.length > 0) {
        const v = value[0];
        selectedId = String(typeof v === "object" && v && v.id != null ? v.id : v);
    } else if (value && typeof value === "object" && value.id != null) {
        selectedId = String(value.id);
    } else if (value != null) {
        selectedId = String(value);
    }
    registro.value.medico_id = selectedId || "";
}

/**
 * Manejar la selección de un producto en el formulario.
 * @param {any} value - El valor seleccionado del producto.
 * @returns {void}
 * @author: Jhon Javer Lozano Arce
 */
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
    if (selectedId != null) {
        productoSelecciondo.value = selectedId;
        guardarProductosEstudio();
    }
}

/**
 * Manejar la selección de un diagnóstico en el formulario.
 * @param {any} value - El valor seleccionado del diagnóstico.
 * @returns {void}
 * @author: Jhon Javer Lozano Arce
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
    if (selectedId != null) {
        diagnosticoSelecciondo.value = selectedId;
        guardarDiagnosticosEstudio();
    }
}

/**
 * Guardar un producto seleccionado en el estudio.
 * @returns {void}
 * @author: Jhon Javer Lozano Arce
 */
function guardarProductosEstudio() {
    const indexProductoEstudio = productos.value.findIndex(
        (x) => x.id == productoSelecciondo.value
    );

    if (indexProductoEstudio >= 0) {
        const productoEstudioId = productos.value[indexProductoEstudio].id;
        const productoEstudioCod_Cubs = productos.value[indexProductoEstudio].cod_cups;
        const productoEstudioNomProduc = productos.value[indexProductoEstudio].nom_produc;

        const yaExiste = registro.value.productosEstudio.some((p) => p.id == productoEstudioId);
        if (!yaExiste) {
            registro.value.productosEstudio.push({
                id: productoEstudioId,
                cod_cups: productoEstudioCod_Cubs,
                nom_produc: productoEstudioNomProduc,
                checked: true,
            });
        }

        // Limpiar selección
        productoSelecciondo.value = "";
    }
}

/**
 * Quitar un producto del estudio según el índice proporcionado.
 * Pide confirmación al usuario antes de eliminarlo del arreglo local.
 * @param {number} indexEliminar - Índice del producto a eliminar en el arreglo productosEstudio.
 * @returns {Promise<void>}
 * @author: Jhon Javer Lozano Arce
 */
async function quitarProductoEstudio(indexEliminar) {
    const ok = await confirmAction({
        title: "¿Está seguro?",
        text: "¿Desea eliminar este producto del estudio?",
        icon: "warning",
    });
    if (!ok) return;
    registro.value.productosEstudio.splice(indexEliminar, 1);
}

/**
 * Guardar un diagnóstico seleccionado en el estudio.
 * @returns {void}
 * @author: Jhon Javer Lozano Arce
 */
function guardarDiagnosticosEstudio() {
    const idx = diagnosticos.value.findIndex(
        (x) => String(x.id) === String(diagnosticoSelecciondo.value)
    );
    if (idx >= 0) {
        const diag = diagnosticos.value[idx];
        const yaExiste = registro.value.diagnosticosEstudio.some(
            (d) => String(d.id) === String(diag.id)
        );
        if (!yaExiste) {
            registro.value.diagnosticosEstudio.push({
                id: diag.id,
                cod_diagnos: diag.cod_diagnos,
                nom_diagnos: diag.nom_diagnos,
            });
        }
        // Limpiar selección
        diagnosticoSelecciondo.value = "";
    }
}

/**
 * Quitar un diagnóstico del estudio según el índice proporcionado.
 * Pide confirmación al usuario antes de eliminarlo del arreglo local.
 * @param {number} indexEliminar - Índice del diagnóstico a eliminar en el arreglo diagnosticosEstudio.
 * @returns {Promise<void>}
 * @author: Jhon Javer Lozano Arce
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

function abrirQuemador(estudio) {
    estudioParaQuemar.value = estudio;
    $("#quemadorCdModal").modal("show");
}

function cerrarQuemador() {
    $("#quemadorCdModal").modal("hide");
    estudioParaQuemar.value = null;
}

function onQuemadoCompletado() {
    toastMessage({
        heading: "Completado",
        text: "El disco ha sido quemado exitosamente.",
        icon: "success",
    });
    cerrarQuemador();
}

function abrirDescargaZip(estudio) {
    estudioParaDescargar.value = estudio;
    $("#descargarZipModal").modal("show");
}

function cerrarDescargaZip() {
    $("#descargarZipModal").modal("hide");
    estudioParaDescargar.value = null;
}

function onDescargaZipCompletado() {
    toastMessage({
        heading: "Completado",
        text: "El archivo ZIP ha sido descargado exitosamente.",
        icon: "success",
    });
    cerrarDescargaZip();
}

/**
 * Handler del evento global de sede seleccionada: actualiza la sede activa,
 * recarga los catálogos y limpia la tabla de resultados.
 *
 * @param {Object} data - Payload emitido con { idSede }
 * @returns {void}
 */
function onSedeSeleccionada(data) {
    registro.value.sede_id = data.idSede;
    busqueda.value.sede_id = data.idSede;
    establecerSede();
    destroyDataTable();
}

onMounted(() => {
    registro.value.email_reportar = "";
    // Los catálogos se cargan una sola vez vía establecerSede() → cargarDatosSede()
    establecerSede();
    btnLimpiar();

    emitter.on("sedeSeleccionada", onSedeSeleccionada);
});

onBeforeUnmount(() => {
    emitter.off("sedeSeleccionada", onSedeSeleccionada);
    destroyDataTable();
    registros.value = [];
    registro.value = { productosEstudio: [], diagnosticosEstudio: [] };
    soportesHC.value = { archivo1: null, archivo2: null, archivo3: null };
});
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
