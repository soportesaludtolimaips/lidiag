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
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button type="button" class="btn btn-info" @click="nuevoRegistro()">
                    <i class="fa fa-plus-circle m-r-5"></i> Nuevo registro
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
                            <div class="text-right d-flex align-items-center">
                                <button
                                    v-if="estudiosSeleccionados.length > 0"
                                    type="button"
                                    class="btn btn-danger m-r-5"
                                    @click="abrirSidebarQuemarMultiples"
                                >
                                    <i class="fa fa-fire"></i>
                                    Quemar {{ estudiosSeleccionados.length }}
                                    estudio(s)
                                </button>
                                <span
                                    v-if="estudiosSeleccionados.length > 0"
                                    class="badge badge-danger m-r-5"
                                >
                                    {{ estudiosSeleccionados.length }} seleccionado(s)
                                </span>
                                <span class="badge badge-primary" v-if="contadorRegistros > 0"
                                    >{{ contadorRegistros }} registro(s)</span
                                >
                                <span class="badge badge-secondary" v-else>Sin resultados</span>
                            </div>
                        </div>
                        <table-skeleton-loader :visible="loadingBuscar" :rows="6" :columns="7" />
                        <empty-state-component
                            v-if="!loadingBuscar && registros.length === 0"
                            icon="fa fa-folder-open-o"
                            title="No hay estudios para mostrar"
                            description="No se encontraron estudios con los filtros seleccionados o use los filtros para buscar."
                            action-text="Limpiar filtros"
                            action-icon="fa fa-refresh"
                            @action="btnLimpiar()"
                        />
                        <div
                            class="table-responsive"
                            v-show="!loadingBuscar && registros.length > 0"
                        >
                            <table
                                id="example23"
                                ref="dt"
                                class="table table-bordered table-striped"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th style="width: 3%">
                                            <input
                                                type="checkbox"
                                                :checked="todoSeleccionado"
                                                @change="toggleSeleccionarTodo"
                                                title="Seleccionar todo"
                                            />
                                        </th>
                                        <th with="5%">Fecha del Estudio</th>
                                        <th with="5%"># Documento</th>
                                        <th with="30%">Paciente</th>
                                        <th>Accession</th>
                                        <th>Descripción</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(item, index) in registros"
                                        :key="item.study_pk"
                                        :id="item.study_pk"
                                        :class="{
                                            'text-info': item.agendado !== 0,
                                            'selected-row': estudioSeleccionado(item),
                                        }"
                                    >
                                        <td class="text-center">
                                            <input
                                                type="checkbox"
                                                :checked="estudioSeleccionado(item)"
                                                @change="alternarSeleccion(item)"
                                            />
                                        </td>
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
                                                @click="mostrarRegistro(item, index)"
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
                                                @click="verImagenItem(item)"
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
        <div
            class="right-sidebar sidebar-scroll"
            id="right-sidebar"
            :class="{ 'shw-rside': sidebarVisible }"
        >
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
                                        :mostrar-errores="true"
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
                                                    :class="getInputClass(statusStudyDesc)"
                                                    ref="primerInput"
                                                    @blur="touch('study_desc')"
                                                />
                                                <div
                                                    class="invalid-feedback d-block"
                                                    v-if="statusStudyDesc.isInvalid"
                                                >
                                                    {{ statusStudyDesc.message }}
                                                </div>
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
                                                    :class="getInputClass(statusAccessionNo)"
                                                    @blur="touch('accession_no')"
                                                />
                                                <div
                                                    class="invalid-feedback d-block"
                                                    v-if="statusAccessionNo.isInvalid"
                                                >
                                                    {{ statusAccessionNo.message }}
                                                </div>
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
                                                    :class="getInputClass(statusStudyDatetime)"
                                                    @blur="touch('study_datetime')"
                                                />
                                                <div
                                                    class="invalid-feedback d-block"
                                                    v-if="statusStudyDatetime.isInvalid"
                                                >
                                                    {{ statusStudyDatetime.message }}
                                                </div>
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
                                                    :class="getInputClass(statusEmailReportar)"
                                                    placeholder="correo@ejemplo.com"
                                                    @blur="touch('email_reportar')"
                                                />
                                                <div
                                                    class="invalid-feedback d-block"
                                                    v-if="statusEmailReportar.isInvalid"
                                                >
                                                    {{ statusEmailReportar.message }}
                                                </div>
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
                                                <label
                                                    for="exampleInputFile1"
                                                    class="control-label"
                                                >
                                                    <i class="fa fa-paperclip"></i> Adjunto 1
                                                </label>
                                                <input
                                                    type="file"
                                                    class="form-control-file"
                                                    id="exampleInputFile1"
                                                    accept=".pdf,.jpg,.png,.doc,.docx"
                                                    @change="(e) => obtenerArchivo(e, 1)"
                                                />
                                                <small
                                                    v-if="soportesHC.archivo1"
                                                    class="text-success"
                                                >
                                                    <i class="fa fa-check"></i>
                                                    {{ soportesHC.archivo1.name }}
                                                </small>
                                            </div>
                                            <div class="form-group">
                                                <label
                                                    for="exampleInputFile2"
                                                    class="control-label"
                                                >
                                                    <i class="fa fa-paperclip"></i> Adjunto 2
                                                </label>
                                                <input
                                                    type="file"
                                                    class="form-control-file"
                                                    id="exampleInputFile2"
                                                    accept=".pdf,.jpg,.png,.doc,.docx"
                                                    @change="(e) => obtenerArchivo(e, 2)"
                                                />
                                                <small
                                                    v-if="soportesHC.archivo2"
                                                    class="text-success"
                                                >
                                                    <i class="fa fa-check"></i>
                                                    {{ soportesHC.archivo2.name }}
                                                </small>
                                            </div>
                                            <div class="form-group">
                                                <label
                                                    for="exampleInputFile3"
                                                    class="control-label"
                                                >
                                                    <i class="fa fa-paperclip"></i> Adjunto 3
                                                </label>
                                                <input
                                                    type="file"
                                                    class="form-control-file"
                                                    id="exampleInputFile3"
                                                    accept=".pdf,.jpg,.png,.doc,.docx"
                                                    @change="(e) => obtenerArchivo(e, 3)"
                                                />
                                                <small
                                                    v-if="soportesHC.archivo3"
                                                    class="text-success"
                                                >
                                                    <i class="fa fa-check"></i>
                                                    {{ soportesHC.archivo3.name }}
                                                </small>
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
                                                    ref="selMedico"
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
                                                    :class="getInputClass(statusPrioridadId)"
                                                    @blur="touch('prioridad_id')"
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
                                                <div
                                                    class="invalid-feedback d-block"
                                                    v-if="statusPrioridadId.isInvalid"
                                                >
                                                    {{ statusPrioridadId.message }}
                                                </div>
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
                                                ref="selProducto"
                                                :value="productoSelecciondo"
                                                :options="productoOptions"
                                                width="100%"
                                                :allow-clear="true"
                                                @input="onProductoSelect"
                                            />
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-info m-t-5"
                                                @click="guardarProductosEstudio()"
                                                :disabled="!productoSelecciondo"
                                            >
                                                <i class="fa fa-plus"></i> Agregar
                                            </button>
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
                                                ref="selDiagnostico"
                                                :value="diagnosticoSelecciondo"
                                                :options="diagnosticoOptions"
                                                width="100%"
                                                :allow-clear="true"
                                                :label-fields="['nom_diagnos']"
                                                @input="onDiagnosticoSelect"
                                            />
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-info m-t-5"
                                                @click="guardarDiagnosticosEstudio()"
                                                :disabled="!diagnosticoSelecciondo"
                                            >
                                                <i class="fa fa-plus"></i> Agregar
                                            </button>
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
                                    Cancelar
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
        <!-- Sidebar Quemar Múltiples Estudios -->
        <!-- ============================================================== -->
        <div
            class="right-sidebar sidebar-scroll"
            :class="{ 'shw-rside': sidebarQuemarMultiplesVisible }"
        >
            <div class="slimscrollright">
                <div class="rpanel-title">
                    <i class="fa fa-fire text-danger"></i> Quemar Múltiples Estudios
                    <button
                        type="button"
                        class="right-side-toggle"
                        @click="cerrarSidebarQuemarMultiples"
                        aria-label="Cerrar panel"
                    >
                        <i class="ti-close"></i>
                    </button>
                </div>
                <div class="r-panel-body">
                    <quemar-multiples-component
                        v-if="sidebarQuemarMultiplesVisible"
                        :estudios="estudiosSeleccionados"
                        :usuarioactual="usuarioactual"
                        @cerrar="cerrarSidebarQuemarMultiples"
                        @completado="onQuemadoMultiplesCompletado"
                    />
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- Modal Quemador CD -->
        <!-- ============================================================== -->
        <div
            v-if="quemadorModalVisible"
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
            v-if="descargaModalVisible"
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
import axios from "axios";

import { computed, nextTick, onBeforeUnmount, onMounted, ref, getCurrentInstance } from "vue";
import FormPaciente from "@/components/comunes/formPacienteComponent.vue";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { confirmAction, toastMessage, getUserSafeError } from "@/helpers/alerts";
import { normalizarFecha } from "@/helpers/dateUtils.js";
import { useEstudioHelpers } from "@/composables/useEstudioHelpers";
import QuemadorCdComponent from "@/components/modulos/estudios/QuemadorCdComponent.vue";
import QuemarMultiplesComponent from "@/components/modulos/estudios/QuemarMultiplesComponent.vue";
import DescargarZipComponent from "@/components/modulos/estudios/DescargarZipComponent.vue";
import { useFormValidation } from "@/composables/useFormValidation";

const { proxy } = getCurrentInstance();
const emitter = proxy.emitter;

const { loadingVerImagen, verImagen } = useEstudioHelpers();

/**
 * Abre el visor de imágenes del estudio. Lee sessionStorage en scope de
 * script porque el template de Vue 3 no puede acceder a globales del
 * navegador (compila a _ctx.sessionStorage = undefined).
 */
function verImagenItem(item) {
    const url = sessionStorage.getItem("ST-urlsede");
    if (!url) {
        toastMessage({
            heading: "Upsss!!!",
            text: "No hay visor de imágenes configurado para la sede actual.",
            icon: "warning",
        });
        return;
    }
    verImagen(
        url,
        item.pat_id,
        item.study_iuid,
        sessionStorage.getItem("ST-servername"),
        item.estudio_producto_id
    );
}

const props = defineProps({
    usuarioactual: { type: Object, default: () => ({}) },
    permisos: { type: Array, default: () => [] },
});
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
const idRow = ref(null);
const loadingBuscar = ref(false);
const loadingGuardar = ref(false);
const loadingAbrirRegistroIndex = ref(null);
const lockAsignParams = ref(null);
let lockHeartbeatTimer = null;
const progresoUpload = ref(null);
const primerInput = ref(null);
const selMedico = ref(null);
const selProducto = ref(null);
const selDiagnostico = ref(null);
const estudioParaQuemar = ref(null);
const estudioParaDescargar = ref(null);
const quemadorModalVisible = ref(false);
const descargaModalVisible = ref(false);
const sidebarVisible = ref(false);

const estudiosSeleccionados = ref([]);
const sidebarQuemarMultiplesVisible = ref(false);

const contadorRegistros = computed(() => {
    return registros.value.length;
});

const { touch, touchAll, getFieldStatus, getInputClass, validators, resetTouched } =
    useFormValidation();

const statusStudyDesc = computed(() =>
    getFieldStatus("study_desc", registro.value.study_desc, [validators.required], errores.value)
);

const statusAccessionNo = computed(() =>
    getFieldStatus(
        "accession_no",
        registro.value.accession_no,
        [validators.required],
        errores.value
    )
);

const statusStudyDatetime = computed(() =>
    getFieldStatus(
        "study_datetime",
        registro.value.study_datetime,
        [validators.required],
        errores.value
    )
);

const statusEmailReportar = computed(() =>
    getFieldStatus(
        "email_reportar",
        registro.value.email_reportar,
        [validators.required, validators.email],
        errores.value
    )
);

const statusPrioridadId = computed(() =>
    getFieldStatus(
        "prioridad_id",
        registro.value.prioridad_id,
        [validators.required],
        errores.value
    )
);

const todoSeleccionado = computed(() => {
    if (registros.value.length === 0) return false;
    return registros.value.every((r) =>
        estudiosSeleccionados.value.some((s) => s.study_pk === r.study_pk)
    );
});

/**
 * Indica si un estudio ya está en la lista de selección múltiple.
 *
 * @param {Object} item Estudio de la fila, identificado por `study_pk`.
 *
 * @returns {boolean} true si el estudio está seleccionado.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function estudioSeleccionado(item) {
    return estudiosSeleccionados.value.some((s) => s.study_pk === item.study_pk);
}

/**
 * Selecciona todos los estudios listados o vacía la selección si ya estaban todos.
 * Solo se guardan los campos necesarios para las acciones de descarga y quema.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function toggleSeleccionarTodo() {
    if (todoSeleccionado.value) {
        estudiosSeleccionados.value = [];
    } else {
        estudiosSeleccionados.value = registros.value.map((r) => ({
            study_pk: r.study_pk,
            study_iuid: r.study_iuid,
            pat_name: r.pat_name,
            pat_id: r.pat_id,
            study_desc: r.study_desc,
            study_datetime: r.study_datetime,
            accession_no: r.accession_no,
        }));
    }
}

/**
 * Agrega o quita un estudio de la selección múltiple.
 *
 * @param {Object} item Estudio de la fila.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function alternarSeleccion(item) {
    const idx = estudiosSeleccionados.value.findIndex((s) => s.study_pk === item.study_pk);
    if (idx >= 0) {
        estudiosSeleccionados.value.splice(idx, 1);
    } else {
        estudiosSeleccionados.value.push({
            study_pk: item.study_pk,
            study_iuid: item.study_iuid,
            pat_name: item.pat_name,
            pat_id: item.pat_id,
            study_desc: item.study_desc,
            study_datetime: item.study_datetime,
            accession_no: item.accession_no,
        });
    }
}

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

/**
 * Destruye la instancia de DataTables antes de recargar o al desmontar el componente.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
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
                // Asegurar que siempre sea un array (por si llega string JSON)
                let data = res.data.data;
                if (typeof data === "string") {
                    try {
                        data = JSON.parse(data);
                    } catch (_error) {
                        data = [];
                    }
                }
                registros.value = Array.isArray(data) ? data : [];
                nextTick(() => {
                    destroyDataTable();
                    safeInitDataTable(dt.value || "#example23", {
                        dom: "Bfrtip",
                        buttons: ["copy", "csv", "excel", "pdf", "print"],
                    });
                });
            }
        }
    } catch (error) {
        erroresBusqueda.value = error?.response?.data?.errors || {};
        toastMessage({
            heading: "Error",
            text: getUserSafeError(error, "No fue posible listar los estudios."),
            icon: "error",
        });
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

    touchAll(["study_desc", "accession_no", "study_datetime", "email_reportar", "prioridad_id"]);

    if (
        statusStudyDesc.value.isInvalid ||
        statusAccessionNo.value.isInvalid ||
        statusStudyDatetime.value.isInvalid ||
        statusEmailReportar.value.isInvalid ||
        statusPrioridadId.value.isInvalid
    ) {
        toastMessage({
            heading: "Formulario incompleto",
            text: "Por favor complete o corrija los campos requeridos del estudio antes de continuar.",
            icon: "warning",
        });

        loadingGuardar.value = false;
        return;
    }

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

    if (
        registro.value.destino_estudio !== "Tele Radiologia" &&
        (!registro.value.medico_id || registro.value.medico_id === "")
    ) {
        toastMessage({
            heading: "Upsss!!!",
            text: "Debes seleccionar un médico antes de guardar.",
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

            if (idRow.value >= 0 && idRow.value < registros.value.length) {
                registros.value[idRow.value].agendado = 1;
            }
        }

        cerrarModal();
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
            toastMessage({
                heading: "Error",
                text: error.response?.data?.message || "No fue posible guardar el registro.",
                icon: "error",
            });
        }
    } finally {
        loadingGuardar.value = false;
        progresoUpload.value = null;
    }
}

/**
 * Obtener el archivo seleccionado y verificar su tamaño.
 * @param {Event} e - El evento de cambio del input de archivo.
 * @param {number} index - Índice del archivo (1, 2 o 3).
 * @returns {void}
 * @author: Jhon Javer Lozano Arce
 */
function obtenerArchivo(e, index) {
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
    soportesHC.value[`archivo${index}`] = file || null;
}

/**
 * Mostrar el registro del estudio en el modal para agendar al paciente.
 * @param {Object} data - Objeto que contiene los datos del estudio a mostrar.
 * @param {number} index - Índice del registro en la lista de estudios.
 * @returns {Promise<void>} - Una promesa que se resuelve cuando se completa la carga del registro.
 * @author: Jhon Javer Lozano Arce
 */
/**
 * Toma la reserva (lock) del estudio DICOM antes de abrir el formulario.
 * Si otro usuario lo tiene reservado, muestra el aviso y devuelve false.
 * @param {Object} data - Datos del estudio DICOM seleccionado.
 * @returns {Promise<boolean>} true si la reserva fue adquirida.
 * @author: Jhon Javer Lozano Arce
 */
async function tomarLockAsignacion(data) {
    const params = {
        tipo: "asignacion",
        study_iuid: data.study_iuid || "",
        study_pk: data.study_pk || null,
        sede_id: sedeActual.value,
    };

    try {
        await liberarLockAsignacion();
        await axios.post("/api/estudio-lock/tomar", params);
        lockAsignParams.value = params;
        iniciarHeartbeatLockAsignacion();
        return true;
    } catch (error) {
        if (error.response?.status === 409) {
            toastMessage({
                heading: "Estudio en uso",
                text: error.response.data.message,
                icon: "warning",
                hideAfter: 5500,
            });
        } else {
            toastMessage({
                heading: "Error",
                text: getUserSafeError(error, "No fue posible reservar el estudio."),
                icon: "error",
            });
        }
        return false;
    }
}

/**
 * Renueva la reserva cada 45 segundos mientras el formulario esté abierto.
 * Si la reserva la tomó otro usuario, cierra el formulario.
 * @returns {void}
 * @author: Jhon Javer Lozano Arce
 */
function iniciarHeartbeatLockAsignacion() {
    detenerHeartbeatLockAsignacion();
    lockHeartbeatTimer = setInterval(async () => {
        if (!lockAsignParams.value) return;
        try {
            await axios.post("/api/estudio-lock/tomar", lockAsignParams.value);
        } catch (error) {
            if (error.response?.status === 409) {
                detenerHeartbeatLockAsignacion();
                lockAsignParams.value = null;
                toastMessage({
                    heading: "Reserva perdida",
                    text: "Otro usuario tomó la reserva de este estudio. El formulario se cerrará.",
                    icon: "warning",
                    hideAfter: 5500,
                });
                cerrarModal();
            }
        }
    }, 45000);
}

/**
 * Detiene el timer de renovación de la reserva.
 * @returns {void}
 * @author: Jhon Javer Lozano Arce
 */
function detenerHeartbeatLockAsignacion() {
    if (lockHeartbeatTimer) {
        clearInterval(lockHeartbeatTimer);
        lockHeartbeatTimer = null;
    }
}

/**
 * Libera la reserva activa del formulario (idempotente, best-effort).
 * @returns {Promise<void>}
 * @author: Jhon Javer Lozano Arce
 */
async function liberarLockAsignacion() {
    if (!lockAsignParams.value) return;
    const params = lockAsignParams.value;
    lockAsignParams.value = null;
    detenerHeartbeatLockAsignacion();
    try {
        await axios.post("/api/estudio-lock/liberar", params);
    } catch {
        /* la reserva expira sola si falla la liberación */
    }
}

async function mostrarRegistro(data = {}, index) {
    if (loadingAbrirRegistroIndex.value !== null) return;
    loadingAbrirRegistroIndex.value = index;

    const reservado = await tomarLockAsignacion(data);
    if (!reservado) {
        loadingAbrirRegistroIndex.value = null;
        return;
    }

    limpiarFormulario();
    if (!registro.value.medico_id) {
        establecerMedico();
    }

    tituloModal.value = "Agendar al paciente: " + data.pat_name;
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
        toastMessage({
            heading: "Error",
            text:
                error.response?.data?.message ||
                "No fue posible cargar la configuración de la sede.",
            icon: "error",
        });
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
        "/api/interface-sahi-listar-productos-diagnosticos/" + interfaceAtencionSeleccionada.value
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

    registro.value.motivo_examen = EnfermedadActualDiagnosticos;

    registro.value.diagnosticosEstudio = [];
    if (diagnosticos.length > 0) {
        registro.value.diagnosticosEstudio = diagnosticos;
    }

    registro.value.atencion_id = interfaceAtencionSeleccionada.value;
}
/**
 * Carga los catálogos que dependen de la sede: médicos, prioridades, productos y diagnósticos.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function cargarDatosSede() {
    listarMedicos();
    listarPrioridades();
    listarProductos();
    listarDiagnosticos();
}

/**
 * Toma la sede activa de sessionStorage, la aplica al registro y a la búsqueda,
 * recarga los catálogos y vacía la tabla de estudios.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function establecerSede() {
    registro.value.sede_id = sessionStorage.getItem("ST-sede");
    busqueda.value.sede_id = sessionStorage.getItem("ST-sede");
    sedeActual.value = sessionStorage.getItem("ST-sede");
    cargarDatosSede();
    registros.value = [];
}

/**
 * Abre el panel lateral de asignación. El panel nace oculto (`display:none`) y
 * Select2 no se inicializa dentro de contenedores ocultos, así que en el siguiente
 * tick se reconstruyen los tres selectores (médico, producto y diagnóstico).
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function abrirModal() {
    sidebarQuemarMultiplesVisible.value = false;
    sidebarVisible.value = true;
    nextTick(() => {
        primerInput.value?.focus();
        selMedico.value?.rebuild();
        selProducto.value?.rebuild();
        selDiagnostico.value?.rebuild();
    });
}

/**
 * Prepara el panel para crear un estudio nuevo: limpia el formulario, deja el
 * médico configurado en el usuario y abre el panel.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function nuevoRegistro() {
    limpiarFormulario();
    establecerMedico();
    tituloModal.value = "Nuevo registro de estudio";
    abrirModal();
}

/**
 * Cierra el panel lateral y descarta los datos capturados y los errores mostrados.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function cerrarModal() {
    sidebarVisible.value = false;
    limpiarFormulario();
    errores.value = {};
    liberarLockAsignacion();
}

/**
 * Vacía el formulario de asignación. Además de los datos del estudio, reinicia
 * médico, prioridad y atención, la lista de atenciones del interface y los
 * soportes, para que al agendar el siguiente estudio no herede nada del anterior.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function limpiarFormulario() {
    resetTouched();
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

    registro.value.medico_id = "";
    registro.value.prioridad_id = "";
    registro.value.atencion_id = null;
    interfaceAtenciones.value = [];
    interfaceAtencionSeleccionada.value = null;

    productoSelecciondo.value = "";
    diagnosticoSelecciondo.value = "";

    soportesHC.value.archivo1 = null;
    soportesHC.value.archivo2 = null;
    soportesHC.value.archivo3 = null;
}

/**
 * Limpia la búsqueda y vacía la tabla de estudios. Se usa desde el botón
 * "Limpiar" del formulario de búsqueda; el guardado de un estudio NO lo invoca
 * para no perder los resultados que el usuario está revisando.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function btnLimpiar() {
    busqueda.value.bus_nom_num_docu = "";
    busqueda.value.bus_fech_ini = "";
    busqueda.value.bus_fecha_fin = "";

    registros.value = [];
    destroyDataTable();

    limpiarFormulario();

    cerrarModal();
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
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar los médicos.",
            icon: "error",
        });
    }
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
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar las prioridades.",
            icon: "error",
        });
    }
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
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar los productos.",
            icon: "error",
        });
    }
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
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar los diagnósticos.",
            icon: "error",
        });
    }
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
    productoSelecciondo.value = selectedId || "";
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
    diagnosticoSelecciondo.value = selectedId || "";
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

/**
 * Abre el modal de quema de disco para un estudio.
 *
 * @param {Object} estudio Estudio seleccionado en la tabla.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function abrirQuemador(estudio) {
    estudioParaQuemar.value = estudio;
    quemadorModalVisible.value = true;
}

/**
 * Cierra el modal de quema y descarta el estudio pendiente.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function cerrarQuemador() {
    quemadorModalVisible.value = false;
    estudioParaQuemar.value = null;
}

/**
 * Confirma que el disco se quemó correctamente y cierra el modal.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function onQuemadoCompletado() {
    toastMessage({
        heading: "Completado",
        text: "El disco ha sido quemado exitosamente.",
        icon: "success",
    });
    cerrarQuemador();
}

/**
 * Abre el modal de descarga en ZIP para un estudio.
 *
 * @param {Object} estudio Estudio seleccionado en la tabla.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function abrirDescargaZip(estudio) {
    estudioParaDescargar.value = estudio;
    descargaModalVisible.value = true;
}

/**
 * Cierra el modal de descarga y descarta el estudio pendiente.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function cerrarDescargaZip() {
    descargaModalVisible.value = false;
    estudioParaDescargar.value = null;
}

/**
 * Confirma que el ZIP se descargó correctamente y cierra el modal.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function onDescargaZipCompletado() {
    toastMessage({
        heading: "Completado",
        text: "El archivo ZIP ha sido descargado exitosamente.",
        icon: "success",
    });
    cerrarDescargaZip();
}

/**
 * Abre el panel lateral para quemar en lote los estudios seleccionados.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function abrirSidebarQuemarMultiples() {
    if (sidebarVisible.value) {
        cerrarModal();
    }
    sidebarQuemarMultiplesVisible.value = true;
}

/**
 * Cierra el panel de quema en lote.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function cerrarSidebarQuemarMultiples() {
    sidebarQuemarMultiplesVisible.value = false;
}

/**
 * Confirma que se quemaron los estudios en lote, cierra el panel y vacía la
 * selección múltiple.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function onQuemadoMultiplesCompletado() {
    toastMessage({
        heading: "Completado",
        text: "El disco ha sido quemado exitosamente.",
        icon: "success",
    });
    cerrarSidebarQuemarMultiples();
    estudiosSeleccionados.value = [];
}

/**
 * Handler del evento global de sede seleccionada: actualiza la sede activa,
 * recarga los catálogos y limpia la tabla de resultados.
 *
 * @param {Object} data          Payload emitido con { idSede }.
 * @param {number} data.idSede   Id de la sede elegida.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function onSedeSeleccionada(data) {
    registro.value.sede_id = data.idSede;
    busqueda.value.sede_id = data.idSede;
    establecerSede();
    destroyDataTable();
}

/**
 * Asigna al registro el médico configurado en el usuario (sessionStorage
 * ST-medico o localStorage ST-ecografia-medico). Es el valor por defecto al
 * abrir el panel de asignación; el Select2 lo sobreescribe al elegir otro.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function establecerMedico() {
    const medicoId =
        sessionStorage.getItem("ST-medico") || localStorage.getItem("ST-ecografia-medico") || "";
    if (medicoId) {
        registro.value.medico_id = String(medicoId);
    }
}

/**
 * Handler del evento global de médico seleccionado: prellena el médico de la
 * asignación con el elegido en la cabecera.
 *
 * @param {Object} data           Payload emitido con { idMedico, nomMedico }.
 * @param {number} data.idMedico  Id del médico elegido.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-29
 */
function onMedicoSeleccionado(data) {
    if (data && data.idMedico) {
        registro.value.medico_id = String(data.idMedico);
    }
}

onMounted(() => {
    registro.value.email_reportar = "";
    // Los catálogos se cargan una sola vez vía establecerSede() → cargarDatosSede()
    establecerSede();
    establecerMedico();
    btnLimpiar();

    emitter.on("sedeSeleccionada", onSedeSeleccionada);
    emitter.on("medicoSeleccionado", onMedicoSeleccionado);
});

onBeforeUnmount(() => {
    emitter.off("sedeSeleccionada", onSedeSeleccionada);
    emitter.off("medicoSeleccionado", onMedicoSeleccionado);
    liberarLockAsignacion();
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

@media (max-width: 768px) {
    .right-sidebar {
        height: 100% !important;
        overflow-y: auto;
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

.selected-row {
    background-color: #e3f2fd !important;
}

.selected-row:hover {
    background-color: #bbdefb !important;
}
</style>
