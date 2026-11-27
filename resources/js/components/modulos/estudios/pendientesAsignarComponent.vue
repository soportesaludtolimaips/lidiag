<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Pendientes de asignar</h3>
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
                    <li class="breadcrumb-item active">Pendientes de asignar</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-outline-secondary m-r-5"
                    :disabled="loadingBuscar || isReconciliandoPacs"
                    @click="listarPendientes(false)"
                >
                    <i
                        :class="loadingBuscar ? 'fa fa-spinner fa-spin' : 'fa fa-refresh'"
                        class="m-r-5"
                    ></i>
                    Refrescar
                </button>
                <button
                    type="button"
                    class="btn btn-outline-info"
                    :disabled="isReconciliandoPacs || loadingBuscar"
                    @click="reconciliarConPacs()"
                >
                    <i
                        :class="isReconciliandoPacs ? 'fa fa-spinner fa-spin' : 'fa fa-exchange'"
                        class="m-r-5"
                    ></i>
                    {{ isReconciliandoPacs ? "Sincronizando PACS..." : "Reconciliar PACS" }}
                </button>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- Filtros de búsqueda -->
        <!-- ============================================================== -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 id="headingFiltrosPendientes" class="card-title text-themecolor">
                            <i class="fa fa-filter" aria-hidden="true"></i> Filtros para estudios en
                            estado PENDIENTE LECTURA
                        </h4>
                        <search>
                            <form
                                action="/api/admisiones/pendientes-asignar"
                                method="POST"
                                aria-labelledby="headingFiltrosPendientes"
                                @submit.prevent="listarPendientes(false)"
                            >
                                <div class="form-body">
                                    <div class="row p-t-2">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label
                                                    class="control-label"
                                                    for="pen_bus_nom_num_docu"
                                                >
                                                    Paciente, # Documento o Accession / UID
                                                </label>
                                                <input
                                                    type="text"
                                                    id="pen_bus_nom_num_docu"
                                                    name="bus_nom_num_docu"
                                                    v-model="busqueda.bus_nom_num_docu"
                                                    class="form-control"
                                                    placeholder="Documento, nombre o Accession No."
                                                    autocorrect="off"
                                                    autocapitalize="none"
                                                    spellcheck="false"
                                                    @keydown.enter="onFiltroEnter"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label" for="pen_bus_fech_ini">
                                                    Fecha Inicio
                                                </label>
                                                <input
                                                    type="date"
                                                    id="pen_bus_fech_ini"
                                                    name="bus_fech_ini"
                                                    v-model="busqueda.bus_fech_ini"
                                                    class="form-control"
                                                />
                                                <span
                                                    class="text-danger"
                                                    v-if="erroresBusqueda.bus_fech_ini"
                                                >
                                                    {{ erroresBusqueda.bus_fech_ini[0] }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label
                                                    class="control-label"
                                                    for="pen_bus_fecha_fin"
                                                >
                                                    Fecha Fin
                                                </label>
                                                <input
                                                    type="date"
                                                    id="pen_bus_fecha_fin"
                                                    name="bus_fecha_fin"
                                                    v-model="busqueda.bus_fecha_fin"
                                                    class="form-control"
                                                />
                                                <span
                                                    class="text-danger"
                                                    v-if="erroresBusqueda.bus_fecha_fin"
                                                >
                                                    {{ erroresBusqueda.bus_fecha_fin[0] }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label" for="pen_modality">
                                                    Modalidad
                                                </label>
                                                <select
                                                    id="pen_modality"
                                                    name="modality"
                                                    v-model="busqueda.modality"
                                                    class="form-control custom-select"
                                                >
                                                    <option value="">Todas</option>
                                                    <option value="DX">DX - Rayos X Digital</option>
                                                    <option value="CR">
                                                        CR - Radiografía Comp.
                                                    </option>
                                                    <option value="CT">CT - Tomografía</option>
                                                    <option value="MR">MR - Resonancia</option>
                                                    <option value="MG">MG - Mamografía</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-actions mt-2">
                                        <button
                                            type="submit"
                                            class="btn btn-success m-r-5"
                                            :disabled="loadingBuscar"
                                        >
                                            <i
                                                v-if="loadingBuscar"
                                                class="fa fa-spinner fa-spin"
                                                aria-hidden="true"
                                            ></i>
                                            <i v-else class="fa fa-search" aria-hidden="true"></i>
                                            Buscar
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn-inverse"
                                            @click="btnLimpiar()"
                                        >
                                            <i class="fa fa-refresh" aria-hidden="true"></i> Limpiar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </search>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- Tabla de admisiones en PENDIENTE LECTURA -->
        <!-- ============================================================== -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h4 id="headingTablaPendientes" class="card-title">
                                    Admisiones con estudio tomado pendientes de asignar
                                </h4>
                                <h6 class="card-subtitle text-info">
                                    Estudios sincronizados con la Worklist en estado
                                    <strong>PENDIENTE LECTURA</strong> listos para enviar a lectura
                                    médica.
                                </h6>
                            </div>
                            <div class="text-right d-flex align-items-center">
                                <span class="badge badge-primary" v-if="contadorRegistros > 0">
                                    {{ contadorRegistros }} pendiente(s)
                                </span>
                                <span class="badge badge-secondary" v-else>Sin pendientes</span>
                            </div>
                        </div>

                        <table-skeleton-loader :visible="loadingBuscar" :rows="6" :columns="7" />

                        <empty-state-component
                            v-if="!loadingBuscar && registros.length === 0"
                            icon="fa fa-check-circle-o"
                            title="No hay estudios pendientes de asignar"
                            description="No se encontraron admisiones en estado PENDIENTE LECTURA para la sede actual o los filtros seleccionados. Puede presionar 'Reconciliar PACS' para verificar nuevas tomas."
                            action-text="Reconciliar PACS"
                            action-icon="fa fa-exchange"
                            @action="reconciliarConPacs()"
                        />

                        <div
                            class="table-responsive"
                            role="region"
                            aria-labelledby="headingTablaPendientes"
                            tabindex="0"
                            v-show="!loadingBuscar && registros.length > 0"
                        >
                            <table
                                id="tablaPendientesAsignar"
                                ref="dt"
                                class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th scope="col">Admisión / Toma</th>
                                        <th scope="col"># Documento</th>
                                        <th scope="col">Paciente</th>
                                        <th scope="col">Workflow / DICOM</th>
                                        <th scope="col">Procedimiento / Estudio</th>
                                        <th scope="col" class="text-center">Estado</th>
                                        <th scope="col" class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(item, index) in registros"
                                        :key="item.id"
                                        :id="`admision-${item.id}`"
                                    >
                                        <td class="text-nowrap align-middle">
                                            <div>
                                                <strong>Toma:</strong>
                                                {{ item.fecha_toma || item.fecha_admision }}
                                            </div>
                                            <small class="text-muted">
                                                Adm: {{ item.fecha_admision }}
                                            </small>
                                        </td>
                                        <td class="text-nowrap align-middle">
                                            <div
                                                class="text-primary font-weight-bold"
                                                style="font-size: 15px"
                                            >
                                                <i
                                                    class="fa fa-id-card-o mr-1"
                                                    aria-hidden="true"
                                                ></i>
                                                {{ item.pat_id }}
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <div class="font-weight-bold">{{ item.pat_name }}</div>
                                            <small v-if="item.eps" class="text-muted">
                                                EPS: {{ item.eps }}
                                            </small>
                                        </td>
                                        <td class="text-nowrap align-middle">
                                            <div>
                                                <span class="badge badge-secondary m-r-5">
                                                    {{ item.modality || "DX" }}
                                                </span>
                                                <strong>{{ item.accession_no || "S/N" }}</strong>
                                            </div>
                                            <small
                                                v-if="item.study_id"
                                                class="text-muted d-block"
                                                :title="`Study Instance UID: ${item.study_iuid || 'N/A'}`"
                                            >
                                                Study ID: {{ item.study_id }}
                                            </small>
                                            <small
                                                v-else-if="item.study_iuid"
                                                class="text-muted d-block"
                                                :title="item.study_iuid"
                                            >
                                                UID: ...{{ String(item.study_iuid).slice(-12) }}
                                            </small>
                                        </td>
                                        <td class="align-middle">
                                            <div>{{ item.study_desc }}</div>
                                            <small
                                                v-if="item.producto?.cod_cups"
                                                class="text-info font-weight-bold"
                                            >
                                                CUPS: {{ item.producto.cod_cups }}
                                            </small>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="label label-info">
                                                {{ item.estado }}
                                            </span>
                                        </td>
                                        <td class="text-nowrap text-center align-middle">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm m-r-5"
                                                :disabled="loadingAbrirRegistroIndex === index"
                                                :aria-label="`Asignar estudio de ${item.pat_name}`"
                                                title="Asignar y enviar a lectura"
                                                @click="mostrarRegistro(item, index)"
                                            >
                                                <i
                                                    v-if="loadingAbrirRegistroIndex === index"
                                                    class="fa fa-spinner fa-spin"
                                                    aria-hidden="true"
                                                ></i>
                                                <i
                                                    v-else
                                                    class="fa fa-child"
                                                    aria-hidden="true"
                                                ></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm m-r-5"
                                                :disabled="
                                                    !item.study_iuid || loadingVerImagen === item.id
                                                "
                                                :aria-label="`Ver imágenes DICOM de ${item.pat_name}`"
                                                title="Ver imágenes en visor Oviyam"
                                                @click="verImagenItem(item)"
                                            >
                                                <i
                                                    v-if="loadingVerImagen === item.id"
                                                    class="fa fa-spinner fa-spin"
                                                    aria-hidden="true"
                                                ></i>
                                                <i
                                                    v-else
                                                    class="fa fa-file-photo-o"
                                                    aria-hidden="true"
                                                ></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                :disabled="!item.study_iuid"
                                                :aria-label="`Quemar CD de ${item.pat_name}`"
                                                title="Quemar CD/DVD"
                                                @click="abrirQuemador(item)"
                                            >
                                                <i class="fa fa-fire" aria-hidden="true"></i>
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
        <!-- Backdrop compartido para drawers .right-sidebar -->
        <!-- ============================================================== -->
        <transition name="fade">
            <div
                v-if="sidebarVisible || quemadorModalVisible"
                class="sidebar-backdrop"
                @click="cerrarCualquierSidebar()"
                aria-hidden="true"
            ></div>
        </transition>

        <!-- ============================================================== -->
        <!-- Drawer .right-sidebar: Asignar y Enviar a Lectura -->
        <!-- ============================================================== -->
        <div
            class="right-sidebar sidebar-scroll"
            id="right-sidebar"
            role="dialog"
            aria-labelledby="tituloDrawerAsignarPendiente"
            :class="{ 'shw-rside': sidebarVisible }"
        >
            <div class="slimscrollright">
                <div class="rpanel-title" id="tituloDrawerAsignarPendiente">
                    {{ tituloModal }}
                    <button
                        type="button"
                        class="right-side-toggle"
                        @click="cerrarModal()"
                        aria-label="Cerrar panel de asignación"
                    >
                        <i class="ti-close" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="r-panel-body">
                    <!-- Resumen de trazabilidad con Workflow / PACS -->
                    <div
                        v-if="admisionSeleccionada"
                        class="alert alert-info py-2 px-3 mb-3 d-flex flex-wrap justify-content-between align-items-center"
                    >
                        <div>
                            <i class="fa fa-link m-r-5" aria-hidden="true"></i>
                            <strong>Admisión #{{ admisionSeleccionada.id }}</strong>
                            <span v-if="admisionSeleccionada.worklist_orden_id" class="ml-2">
                                | Worklist #{{ admisionSeleccionada.worklist_orden_id }}
                            </span>
                            <span v-if="registro.accession_no" class="ml-2">
                                | Acc: <strong>{{ registro.accession_no }}</strong>
                            </span>
                            <span v-if="registro.study_id" class="ml-2">
                                | Study ID: <strong>{{ registro.study_id }}</strong>
                            </span>
                        </div>
                        <small v-if="registro.study_iuid" class="text-dark">
                            <strong>Study IUID:</strong> {{ registro.study_iuid }}
                        </small>
                    </div>

                    <form
                        action="/api/admisiones/asignar-lectura"
                        method="POST"
                        aria-labelledby="tituloDrawerAsignarPendiente"
                        @submit.prevent="guardarRegistro()"
                    >
                        <div class="form-body">
                            <div class="row">
                                <!-- Columna Izquierda: Paciente, Datos Estudio DICOM y Soportes -->
                                <div class="col-md-6">
                                    <form-paciente
                                        :registro="registro"
                                        :errores="errores"
                                        :mostrar-errores="true"
                                    />

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-edit" aria-hidden="true"></i> Datos del
                                        Estudio
                                    </h5>
                                    <hr />

                                    <input
                                        type="hidden"
                                        name="study_pk"
                                        v-model="registro.study_pk"
                                    />
                                    <input
                                        type="hidden"
                                        name="study_iuid"
                                        v-model="registro.study_iuid"
                                    />
                                    <input
                                        type="hidden"
                                        name="study_id"
                                        v-model="registro.study_id"
                                    />

                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label" for="pen_study_desc">
                                                    Descripción
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <input
                                                    type="text"
                                                    id="pen_study_desc"
                                                    name="study_desc"
                                                    v-model="registro.study_desc"
                                                    class="form-control"
                                                    :class="getInputClass(statusStudyDesc)"
                                                    :aria-invalid="
                                                        statusStudyDesc.isInvalid ? 'true' : null
                                                    "
                                                    ref="primerInput"
                                                    required
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
                                                <label class="control-label" for="pen_accession_no">
                                                    Accession / Detalle
                                                </label>
                                                <input
                                                    type="text"
                                                    id="pen_accession_no"
                                                    name="accession_no"
                                                    v-model="registro.accession_no"
                                                    class="form-control"
                                                    autocorrect="off"
                                                    spellcheck="false"
                                                />
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label
                                                    class="control-label"
                                                    for="pen_study_datetime"
                                                >
                                                    Fecha Toma
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <input
                                                    type="text"
                                                    id="pen_study_datetime"
                                                    name="study_datetime"
                                                    v-model="registro.study_datetime"
                                                    class="form-control"
                                                    :class="getInputClass(statusStudyDatetime)"
                                                    :aria-invalid="
                                                        statusStudyDatetime.isInvalid
                                                            ? 'true'
                                                            : null
                                                    "
                                                    required
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
                                                <label
                                                    class="control-label"
                                                    for="pen_email_reportar"
                                                >
                                                    Reportar al Email
                                                    <small class="text-muted">(Opcional)</small>
                                                </label>
                                                <input
                                                    type="email"
                                                    id="pen_email_reportar"
                                                    name="email_reportar"
                                                    v-model="registro.email_reportar"
                                                    class="form-control"
                                                    autocomplete="email"
                                                    placeholder="correo@ejemplo.com"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label
                                                    class="control-label"
                                                    for="pen_observaciones"
                                                >
                                                    Observaciones
                                                </label>
                                                <input
                                                    type="text"
                                                    id="pen_observaciones"
                                                    name="observaciones"
                                                    v-model="registro.observaciones"
                                                    class="form-control"
                                                    placeholder="Observaciones administrativas o clínicas"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-paperclip" aria-hidden="true"></i> Adjuntos
                                        Soporte HC
                                    </h5>
                                    <hr />

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="penArchivo1" class="control-label">
                                                    <i
                                                        class="fa fa-paperclip"
                                                        aria-hidden="true"
                                                    ></i>
                                                    Adjunto 1
                                                </label>
                                                <input
                                                    type="file"
                                                    class="form-control-file"
                                                    id="penArchivo1"
                                                    name="archivo1"
                                                    accept=".pdf,.jpg,.png,.doc,.docx"
                                                    @change="(e) => obtenerArchivo(e, 1)"
                                                />
                                                <small
                                                    v-if="soportesHC.archivo1"
                                                    class="text-success"
                                                >
                                                    <i class="fa fa-check" aria-hidden="true"></i>
                                                    {{ soportesHC.archivo1.name }}
                                                </small>
                                            </div>
                                            <div class="form-group">
                                                <label for="penArchivo2" class="control-label">
                                                    <i
                                                        class="fa fa-paperclip"
                                                        aria-hidden="true"
                                                    ></i>
                                                    Adjunto 2
                                                </label>
                                                <input
                                                    type="file"
                                                    class="form-control-file"
                                                    id="penArchivo2"
                                                    name="archivo2"
                                                    accept=".pdf,.jpg,.png,.doc,.docx"
                                                    @change="(e) => obtenerArchivo(e, 2)"
                                                />
                                                <small
                                                    v-if="soportesHC.archivo2"
                                                    class="text-success"
                                                >
                                                    <i class="fa fa-check" aria-hidden="true"></i>
                                                    {{ soportesHC.archivo2.name }}
                                                </small>
                                            </div>
                                            <div class="form-group">
                                                <label for="penArchivo3" class="control-label">
                                                    <i
                                                        class="fa fa-paperclip"
                                                        aria-hidden="true"
                                                    ></i>
                                                    Adjunto 3
                                                </label>
                                                <input
                                                    type="file"
                                                    class="form-control-file"
                                                    id="penArchivo3"
                                                    name="archivo3"
                                                    accept=".pdf,.jpg,.png,.doc,.docx"
                                                    @change="(e) => obtenerArchivo(e, 3)"
                                                />
                                                <small
                                                    v-if="soportesHC.archivo3"
                                                    class="text-success"
                                                >
                                                    <i class="fa fa-check" aria-hidden="true"></i>
                                                    {{ soportesHC.archivo3.name }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Columna Derecha: Asignación Médica, Destino, Productos, Diagnósticos y Motivo -->
                                <div class="col-md-6">
                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user-md" aria-hidden="true"></i> Datos de la
                                        Asignación
                                    </h5>
                                    <hr />

                                    <div class="row">
                                        <div class="col-md-7">
                                            <div class="form-group">
                                                <label
                                                    class="control-label font-weight-bold"
                                                    for="penTipoRecaudo"
                                                >
                                                    Tipo de Estudio / Recaudo
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select
                                                    id="penTipoRecaudo"
                                                    name="tipo_recaudo_id"
                                                    class="form-control"
                                                    v-model="registro.tipo_recaudo_id"
                                                    required
                                                >
                                                    <option
                                                        v-for="tr in tiposRecaudo"
                                                        :key="tr.id"
                                                        :value="tr.id"
                                                    >
                                                        {{ tr.nombre }} — {{ tr.descripcion }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label
                                                    class="control-label font-weight-bold"
                                                    for="penDestinoEstudio"
                                                >
                                                    Destino de Lectura
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select
                                                    id="penDestinoEstudio"
                                                    name="destino_estudio"
                                                    class="form-control custom-select"
                                                    v-model="registro.destino_estudio"
                                                >
                                                    <option value="Salud Tolima">
                                                        Salud Tolima (Red Interna)
                                                    </option>
                                                    <option value="Tele Radiologia">
                                                        Tele-Radiología Externa
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div
                                            class="col-md-8"
                                            v-if="registro.destino_estudio !== 'Tele Radiologia'"
                                        >
                                            <div class="form-group" id="pen-medicos-parent">
                                                <label class="control-label" for="penSelMedico">
                                                    Médico Radiólogo
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <Select2
                                                    id="penSelMedico"
                                                    ref="selMedico"
                                                    :value="registro.medico_id"
                                                    :options="medicoOptions"
                                                    width="100%"
                                                    :allow-clear="true"
                                                    :label-fields="['name']"
                                                    dropdown-parent-selector="#pen-medicos-parent"
                                                    @input="onMedicoSelect"
                                                />
                                                <span class="text-danger" v-if="errores.medico_id">
                                                    {{ errores.medico_id[0] }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label" for="pen_prioridad_id">
                                                    Prioridad
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select
                                                    v-model="registro.prioridad_id"
                                                    name="prioridad_id"
                                                    id="pen_prioridad_id"
                                                    class="form-control custom-select"
                                                    :class="getInputClass(statusPrioridadId)"
                                                    :aria-invalid="
                                                        statusPrioridadId.isInvalid ? 'true' : null
                                                    "
                                                    required
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

                                        <div
                                            class="col-md-12"
                                            v-if="
                                                sedeInterface === true &&
                                                !esParticular &&
                                                !esCortesia
                                            "
                                        >
                                            <div class="form-group">
                                                <label
                                                    class="control-label"
                                                    for="penInterfaceAtencion"
                                                >
                                                    Atención HIS / SAHI
                                                </label>
                                                <select
                                                    v-model="interfaceAtencionSeleccionada"
                                                    name="interfaceAtencionSeleccionada"
                                                    id="penInterfaceAtencion"
                                                    class="form-control custom-select"
                                                    @change="interfaceListarProductosDiagnosticos()"
                                                >
                                                    <option :value="null">
                                                        -- Seleccione atención opcional --
                                                    </option>
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
                                                <div
                                                    v-if="contratoInfoResumen"
                                                    class="alert alert-info py-2 px-3 mt-2 mb-0"
                                                    style="font-size: 13px; line-height: 1.4"
                                                >
                                                    <div>
                                                        <strong>EPS:</strong>
                                                        {{
                                                            contratoInfoResumen.NomTercero || "N/A"
                                                        }}
                                                    </div>
                                                    <div>
                                                        <strong>Contrato:</strong>
                                                        {{
                                                            contratoInfoResumen.DesContrato || "N/A"
                                                        }}
                                                    </div>
                                                    <div>
                                                        <strong>Plan:</strong>
                                                        {{ contratoInfoResumen.DesPlan || "N/A" }}
                                                    </div>
                                                    <div v-if="contratoInfoResumen.NomUbicacion">
                                                        <strong>Servicio:</strong>
                                                        {{ contratoInfoResumen.NomUbicacion }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info m-t-20">
                                        <i class="fa fa-cube" aria-hidden="true"></i> PRODUCTOS /
                                        CUPS
                                    </h5>
                                    <hr />

                                    <span class="text-danger" v-if="errores.productosEstudio">
                                        {{ errores.productosEstudio[0] }}
                                    </span>

                                    <div class="row">
                                        <div class="col-md-12 mb-2" id="pen-productos-parent">
                                            <label class="control-label" for="penSelProducto">
                                                Agregar producto CUPS
                                            </label>
                                            <Select2
                                                id="penSelProducto"
                                                ref="selProducto"
                                                :value="productoSeleccionado"
                                                :options="productoOptions"
                                                width="100%"
                                                :allow-clear="true"
                                                dropdown-parent-selector="#pen-productos-parent"
                                                @input="onProductoSelect"
                                            />
                                        </div>
                                        <div class="col-md-12 table-responsive table-scroll">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">Sel.</th>
                                                        <th scope="col">Código</th>
                                                        <th scope="col">Producto</th>
                                                        <th scope="col">Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr
                                                        v-for="(
                                                            item, index
                                                        ) in registro.productosEstudio"
                                                        :key="`${item.id}-${index}`"
                                                    >
                                                        <td class="text-center">
                                                            <input
                                                                type="checkbox"
                                                                :id="`pen-chk-prod-${index}`"
                                                                v-model="item.checked"
                                                            />
                                                            <label
                                                                :for="`pen-chk-prod-${index}`"
                                                                class="sr-only"
                                                            >
                                                                Seleccionar {{ item.nom_produc }}
                                                            </label>
                                                        </td>
                                                        <td>{{ item.cod_cups }}</td>
                                                        <td>
                                                            {{ item.nom_produc }}
                                                            <div v-if="item.medico_ordena">
                                                                <small class="text-muted">
                                                                    <i
                                                                        class="fa fa-user-md"
                                                                        aria-hidden="true"
                                                                    ></i>
                                                                    Ordena:
                                                                    {{ item.medico_ordena }}
                                                                </small>
                                                            </div>
                                                        </td>
                                                        <td class="text-nowrap text-center">
                                                            <button
                                                                type="button"
                                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                                :aria-label="`Quitar producto ${item.nom_produc}`"
                                                                @click="
                                                                    quitarProductoEstudio(index)
                                                                "
                                                            >
                                                                <i
                                                                    class="fa fa-trash"
                                                                    aria-hidden="true"
                                                                ></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info m-t-20">
                                        <i class="fa fa-stethoscope" aria-hidden="true"></i>
                                        DIAGNÓSTICOS CIE-10
                                    </h5>

                                    <span class="text-danger" v-if="errores.diagnosticosEstudio">
                                        {{ errores.diagnosticosEstudio[0] }}
                                    </span>

                                    <div class="row">
                                        <div class="col-md-12 mb-2" id="pen-diagnosticos-parent">
                                            <label class="control-label" for="penSelDiagnostico">
                                                Agregar diagnóstico
                                            </label>
                                            <Select2
                                                id="penSelDiagnostico"
                                                ref="selDiagnostico"
                                                :value="diagnosticoSeleccionado"
                                                :options="diagnosticoOptions"
                                                width="100%"
                                                :allow-clear="true"
                                                :label-fields="['nom_diagnos']"
                                                dropdown-parent-selector="#pen-diagnosticos-parent"
                                                @input="onDiagnosticoSelect"
                                            />
                                        </div>
                                        <div class="col-md-12 table-responsive table-scroll-sm">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">Código</th>
                                                        <th scope="col">Diagnóstico</th>
                                                        <th scope="col">Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr
                                                        v-for="(
                                                            item, index
                                                        ) in registro.diagnosticosEstudio"
                                                        :key="`${item.cod_diagnos}-${index}`"
                                                    >
                                                        <td>{{ item.cod_diagnos }}</td>
                                                        <td>{{ item.nom_diagnos }}</td>
                                                        <td class="text-nowrap text-center">
                                                            <button
                                                                type="button"
                                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                                :aria-label="`Quitar diagnóstico ${item.nom_diagnos}`"
                                                                @click="
                                                                    quitarDiagnosticoEstudio(index)
                                                                "
                                                            >
                                                                <i
                                                                    class="fa fa-trash"
                                                                    aria-hidden="true"
                                                                ></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info m-t-20">
                                        <i class="fa fa-history" aria-hidden="true"></i> MOTIVO DEL
                                        EXAMEN
                                        <span
                                            v-if="
                                                props.usuarioactual.transcrip_remoto == 0 ||
                                                props.usuarioactual.transcrip_remoto == null
                                            "
                                            class="text-danger"
                                            >*</span
                                        >
                                    </h5>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="sr-only" for="pen_motivo_examen">
                                                    Motivo clínico del examen
                                                </label>
                                                <textarea
                                                    id="pen_motivo_examen"
                                                    name="motivo_examen"
                                                    v-model="registro.motivo_examen"
                                                    class="form-control"
                                                    placeholder="Ingrese la indicación clínica o motivo del examen"
                                                    rows="6"
                                                ></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions mt-3">
                                <button
                                    type="submit"
                                    class="btn btn-success m-r-5"
                                    :disabled="loadingGuardar"
                                >
                                    <i
                                        v-if="loadingGuardar"
                                        class="fa fa-spinner fa-spin"
                                        aria-hidden="true"
                                    ></i>
                                    <i v-else class="fa fa-check" aria-hidden="true"></i>
                                    Guardar y Enviar a Lectura
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
        <!-- Drawer .right-sidebar: Quemador CD Individual -->
        <!-- ============================================================== -->
        <div
            class="right-sidebar sidebar-scroll"
            role="dialog"
            aria-label="Quemador de CD/DVD"
            :class="{ 'shw-rside': quemadorModalVisible }"
        >
            <div class="slimscrollright">
                <div class="rpanel-title">
                    <i class="fa fa-compact-disc text-danger mr-1" aria-hidden="true"></i> Quemador
                    de CD/DVD
                    <button
                        type="button"
                        class="right-side-toggle"
                        @click="cerrarQuemador()"
                        aria-label="Cerrar quemador"
                    >
                        <i class="ti-close" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="r-panel-body">
                    <quemador-cd-component
                        v-if="quemadorModalVisible && estudioParaQuemar !== null"
                        :estudio="estudioParaQuemar"
                        :usuarioactual="usuarioactual"
                        @cerrar="cerrarQuemador"
                        @completado="onQuemadoCompletado"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
/**
 * @file Módulo Pendientes de asignar conectado a admin_admisiones (estado PENDIENTE LECTURA).
 * @description Consulta las admisiones con imágenes adquiridas en PACS y permite enviarlas a lectura
 * médica replicando el flujo completo de AsignarEstudioComponent.vue preservando workflow, study_iuid y study_id.
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-27
 */
import axios from "axios";
import { computed, getCurrentInstance, nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import FormPaciente from "@/components/comunes/formPacienteComponent.vue";
import QuemadorCdComponent from "@/components/modulos/estudios/QuemadorCdComponent.vue";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import {
    alertError,
    alertSuccess,
    confirmAction,
    getUserSafeError,
    swalSuccess,
    toastMessage,
} from "@/helpers/alerts";
import { normalizarFecha } from "@/helpers/dateUtils.js";
import { getModuloIdActual } from "@/helpers/usuarios";
import { useEstudioHelpers } from "@/composables/useEstudioHelpers";
import { useFormValidation } from "@/composables/useFormValidation";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts";

const props = defineProps({
    usuarioactual: { type: Object, default: () => ({}) },
    permisos: { type: Array, default: () => [] },
});

const instance = getCurrentInstance();
const emitter = instance?.appContext?.config?.globalProperties?.emitter;

const { loadingVerImagen, verImagen } = useEstudioHelpers();

const dt = ref(null);
const registros = ref([]);
const loadingBuscar = ref(false);
const isReconciliandoPacs = ref(false);
const loadingGuardar = ref(false);
const loadingAbrirRegistroIndex = ref(null);
const progresoUpload = ref(null);

const sidebarVisible = ref(false);
const quemadorModalVisible = ref(false);
const estudioParaQuemar = ref(null);
const tituloModal = ref("Asignar estudio a lectura");
const admisionSeleccionada = ref(null);

const sedeInterface = ref(false);
const sedeActual = ref(
    sessionStorage.getItem("ST-sede") || localStorage.getItem("ST-sede") || null
);

const busqueda = ref({
    sede_id: sedeActual.value,
    bus_nom_num_docu: "",
    bus_fech_ini: "",
    bus_fecha_fin: "",
    modality: "",
});

const registro = ref({
    admision_id: null,
    worklist_orden_id: null,
    paciente_id: null,
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
    quien_registro_id: props.usuarioactual?.id || null,
    tipo_documento_id: null,
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
    tipo_recaudo_id: 1,
    destino_estudio: "Salud Tolima",
    motivo_examen: "",
    eps: "",
    contrato: "",
    plan: "",
    servicio: "",
    ubicacion: "",
    cama: "",
    medico_ordena: "",
});

const soportesHC = ref({ archivo1: null, archivo2: null, archivo3: null });
const interfaceAtenciones = ref([]);
const interfaceAtencionSeleccionada = ref(null);
const contratoInfoResumen = ref(null);

const errores = ref({});
const erroresBusqueda = ref({});

const medicos = ref([]);
const prioridades = ref([]);
const productos = ref([]);
const diagnosticos = ref([]);
const tiposRecaudo = ref([]);

const productoSeleccionado = ref("");
const diagnosticoSeleccionado = ref("");

const primerInput = ref(null);
const selMedico = ref(null);
const selProducto = ref(null);
const selDiagnostico = ref(null);

const lockAsignParams = ref(null);
let lockHeartbeatTimer = null;

const contadorRegistros = computed(() => registros.value.length);

const { touch, touchAll, getFieldStatus, getInputClass, validators, resetTouched } =
    useFormValidation();

const statusStudyDesc = computed(() =>
    getFieldStatus("study_desc", registro.value.study_desc, [validators.required], errores.value)
);

const statusStudyDatetime = computed(() =>
    getFieldStatus(
        "study_datetime",
        registro.value.study_datetime,
        [validators.required],
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

const tipoRecaudoActual = computed(
    () =>
        tiposRecaudo.value.find((tr) => Number(tr.id) === Number(registro.value.tipo_recaudo_id)) ||
        null
);

const esCortesia = computed(
    () =>
        registro.value.tipo_recaudo_id == 3 ||
        tipoRecaudoActual.value?.codigo === "CORTESIA" ||
        (!tipoRecaudoActual.value?.aplica_recaudo && tipoRecaudoActual.value !== null)
);

const esParticular = computed(
    () => registro.value.tipo_recaudo_id == 2 || tipoRecaudoActual.value?.codigo === "PARTICULAR"
);

const medicoOptions = computed(() =>
    (medicos.value || [])
        .filter(
            (u) =>
                u &&
                (u.estado === undefined || u.estado === 1 || u.estado === true || u.estado === "1")
        )
        .map((p) => ({ id: String(p.id), name: (p.name || "").trim() }))
        .sort((a, b) => a.name.localeCompare(b.name, "es", { sensitivity: "base" }))
);

const productoOptions = computed(() =>
    (productos.value || []).map((p) => ({
        id: String(p.id),
        cod_cups: p.cod_cups,
        nom_produc: p.nom_produc,
    }))
);

const diagnosticoOptions = computed(() =>
    (diagnosticos.value || []).map((d) => ({
        id: String(d.id),
        nom_diagnos: d.nom_diagnos,
    }))
);

useKeyboardShortcuts({
    onSave: guardarRegistro,
    onEscape: () => {
        if (quemadorModalVisible.value) {
            cerrarQuemador();
        } else if (sidebarVisible.value) {
            cerrarModal();
        }
    },
    isActive: () => sidebarVisible.value || quemadorModalVisible.value,
    isLoading: loadingGuardar,
});

function onFiltroEnter(e) {
    if (e.isComposing) return;
    e.preventDefault();
    listarPendientes(false);
}

function destroyDataTable() {
    safeDestroyDataTable(dt.value || "#tablaPendientesAsignar");
}

function resolverSedeActiva() {
    const raw =
        sedeActual.value ||
        sessionStorage.getItem("ST-sede") ||
        localStorage.getItem("ST-sede") ||
        props.usuarioactual?.sedes?.[0]?.id ||
        null;
    if (!raw) return null;
    const parsed = parseInt(raw, 10);
    return isNaN(parsed) || parsed <= 0 ? null : parsed;
}

/**
 * Consulta en admin_admisiones los registros en estado PENDIENTE LECTURA de la sede activa.
 *
 * @param {boolean} [reconciliar=false] Indica si se debe reconciliar con PACS antes de consultar.
 * @returns {Promise<void>}
 */
async function listarPendientes(reconciliar = false) {
    if (loadingBuscar.value) return;
    destroyDataTable();

    const sedeId = resolverSedeActiva();
    if (!sedeId) {
        toastMessage({
            heading: "Sede requerida",
            text: "Debes seleccionar la sede asistencial en la cabecera para consultar los pendientes.",
            icon: "warning",
        });
        return;
    }

    if (
        busqueda.value.bus_fech_ini &&
        busqueda.value.bus_fecha_fin &&
        busqueda.value.bus_fech_ini > busqueda.value.bus_fecha_fin
    ) {
        toastMessage({
            heading: "Rango inválido",
            text: "La fecha de inicio no puede ser mayor a la fecha final.",
            icon: "warning",
        });
        return;
    }

    loadingBuscar.value = true;
    erroresBusqueda.value = {};

    try {
        const payload = {
            sede_id: sedeId,
            bus_nom_num_docu: busqueda.value.bus_nom_num_docu || null,
            bus_fech_ini: busqueda.value.bus_fech_ini || null,
            bus_fecha_fin: busqueda.value.bus_fecha_fin || null,
            modality: busqueda.value.modality || null,
            reconciliar: Boolean(reconciliar),
        };

        const res = await axios.post("/api/admisiones/pendientes-asignar", payload);
        const data = res.data?.data || [];
        registros.value = Array.isArray(data) ? data : [];
        loadingBuscar.value = false;

        nextTick(() => {
            destroyDataTable();
            if (registros.value.length > 0) {
                safeInitDataTable(dt.value || "#tablaPendientesAsignar", {
                    dom: "Bfrtip",
                    buttons: ["copy", "csv", "excel", "pdf", "print"],
                    pageLength: 10,
                    order: [[0, "desc"]],
                    columnDefs: [{ targets: [6], orderable: false }],
                    language: {
                        paginate: {
                            first: "Primero",
                            last: "Último",
                            next: "Siguiente",
                            previous: "Anterior",
                        },
                        info: "Mostrando _START_ a _END_ de _TOTAL_ estudios pendientes",
                        search: "Filtrar en tabla:",
                        emptyTable: "No hay estudios pendientes de asignar",
                    },
                });
            }
        });
    } catch (error) {
        erroresBusqueda.value = error?.response?.data?.errors || {};
        toastMessage({
            heading: "Atención",
            text: getUserSafeError(error, "No fue posible consultar los estudios pendientes."),
            icon: "error",
        });
    } finally {
        loadingBuscar.value = false;
    }
}

async function reconciliarConPacs() {
    if (isReconciliandoPacs.value) return;
    const sedeId = resolverSedeActiva();
    if (!sedeId) return;

    isReconciliandoPacs.value = true;
    try {
        const res = await axios.post("/api/pacientes/worklist-reconciliar", {
            sede_id: sedeId,
        });
        const data = res.data?.data;
        if (data?.adquiridas > 0 || data?.completadas > 0) {
            alertSuccess(
                "Sincronización PACS completada",
                `Se actualizaron ${data.adquiridas} admisión(es) a PENDIENTE LECTURA y ${data.completadas} completada(s).`
            );
        } else {
            toastMessage({
                heading: "PACS sincronizado",
                text: "No se detectaron nuevas tomas pendientes de sincronizar.",
                icon: "info",
            });
        }
        await listarPendientes(false);
    } catch (err) {
        alertError(
            "Error de Reconciliación",
            getUserSafeError(err, "No fue posible sincronizar con el PACS de la sede.")
        );
    } finally {
        isReconciliandoPacs.value = false;
    }
}

function btnLimpiar() {
    busqueda.value.bus_nom_num_docu = "";
    busqueda.value.bus_fech_ini = "";
    busqueda.value.bus_fecha_fin = "";
    busqueda.value.modality = "";
    erroresBusqueda.value = {};
    cerrarModal();
    listarPendientes(false);
}

async function verImagenItem(item) {
    let url = sessionStorage.getItem("ST-urlsede") || localStorage.getItem("ST-urlsede");
    let serverName =
        sessionStorage.getItem("ST-servername") || localStorage.getItem("ST-servername");

    if (!url || url === "undefined" || url === "null" || !url.trim()) {
        const sedeId = resolverSedeActiva();
        if (sedeId) {
            try {
                const res = await axios.get(`/api/config-sedes/${sedeId}/buscarPorId`);
                const sedeData = res?.data?.data || res?.data || {};
                url = sedeData.url_oviyam || "";
                serverName = sedeData.tap_oviyam || "";
                if (url) {
                    sessionStorage.setItem("ST-urlsede", url);
                    sessionStorage.setItem("ST-servername", serverName);
                    localStorage.setItem("ST-urlsede", url);
                    localStorage.setItem("ST-servername", serverName);
                }
            } catch {
                // Se notificará abajo si no existe URL
            }
        }
    }

    if (!url || url === "undefined" || url === "null" || !url.trim()) {
        toastMessage({
            heading: "Visor no configurado",
            text: "No hay visor Oviyam configurado para la sede actual.",
            icon: "warning",
        });
        return;
    }

    const cleanServerName =
        !serverName || serverName === "undefined" || serverName === "null"
            ? ""
            : String(serverName).trim();

    verImagen(url.trim(), item.pat_id, item.study_iuid, cleanServerName, item.id);
}

async function tomarLockAsignacion(data) {
    if (!data.study_iuid && !data.study_pk) {
        return true;
    }

    const params = {
        tipo: "asignacion",
        study_iuid: data.study_iuid || "",
        study_pk: data.study_pk || null,
        sede_id: resolverSedeActiva(),
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
                cerrarModal({ skipRelease: true });
            }
        }
    }, 45000);
}

function detenerHeartbeatLockAsignacion() {
    if (lockHeartbeatTimer) {
        clearInterval(lockHeartbeatTimer);
        lockHeartbeatTimer = null;
    }
}

async function liberarLockAsignacion() {
    if (!lockAsignParams.value) return;
    const params = lockAsignParams.value;
    lockAsignParams.value = null;
    detenerHeartbeatLockAsignacion();
    try {
        await axios.post("/api/estudio-lock/liberar", params);
    } catch {
        // Expiración automática en backend
    }
}

/**
 * Abre el drawer .right-sidebar pre-cargando los datos de la admisión, paciente, producto y trazabilidad DICOM.
 *
 * @param {Object} data Registro de AdminAdmisionResource seleccionado.
 * @param {number} index Índice en la tabla.
 * @returns {Promise<void>}
 */
async function mostrarRegistro(data, index) {
    if (loadingAbrirRegistroIndex.value !== null) return;
    loadingAbrirRegistroIndex.value = index;

    const reservado = await tomarLockAsignacion(data);
    if (!reservado) {
        loadingAbrirRegistroIndex.value = null;
        return;
    }

    limpiarFormulario();
    admisionSeleccionada.value = data;

    const pac = data.paciente || {};
    const emailRepor =
        sessionStorage.getItem("ST-email_repor") || localStorage.getItem("ST-email_repor") || "";

    tituloModal.value = `Enviar a lectura: ${data.pat_name} ${data.pat_id ? `(${data.pat_id})` : ""}`;

    registro.value.admision_id = data.id;
    registro.value.worklist_orden_id = data.worklist_orden_id || null;
    registro.value.paciente_id = data.paciente_id || pac.id || null;
    registro.value.sede_id = data.sede_id || resolverSedeActiva();
    registro.value.study_pk = data.study_pk || "";
    registro.value.study_iuid = data.study_iuid || "";
    registro.value.study_id = data.study_id || "";
    registro.value.study_datetime =
        data.study_datetime || data.fecha_toma || data.fecha_admision || "";
    registro.value.accession_no = data.accession_no || "";
    registro.value.study_desc = data.study_desc || data.producto?.nom_produc || "";
    registro.value.mods_in_study = data.modality || "DX";
    registro.value.observaciones = data.observaciones || "";
    registro.value.email_reportar = emailRepor;

    registro.value.tipo_documento_id = pac.tipo_documento_id || null;
    registro.value.num_docu = pac.num_docu || data.pat_id || "";
    registro.value.nombres = pac.nombre_completo || data.pat_name || pac.nombres || "";
    registro.value.sexo = pac.sexo || data.pat_sex || "M";
    registro.value.fec_naci =
        normalizarFecha(pac.fec_naci || data.pat_birthdate) ||
        pac.fec_naci ||
        data.pat_birthdate ||
        "";
    registro.value.direccion = pac.direccion || "";
    registro.value.tel = pac.tel || pac.cel || "";
    registro.value.email = pac.email || "";

    registro.value.atencion_id = data.atencion || null;
    registro.value.eps = data.eps || "";
    registro.value.contrato = data.contrato || "";
    registro.value.plan = data.plan || "";
    registro.value.servicio = data.servicio || "";
    registro.value.ubicacion = data.ubicacion || "";
    registro.value.cama = data.cama || "";
    registro.value.medico_ordena = data.medico_ordena || "";

    if (data.producto && data.producto.id) {
        registro.value.productosEstudio.push({
            id: data.producto.id,
            cod_cups: data.producto.cod_cups,
            nom_produc: data.producto.nom_produc,
            medico_ordena: data.medico_ordena || null,
            checked: true,
        });
    }

    if (!registro.value.prioridad_id && prioridades.value.length > 0) {
        registro.value.prioridad_id = prioridades.value[0].id;
    }

    abrirModal();

    try {
        const sedeId = resolverSedeActiva();
        if (sedeId) {
            const resSede = await axios.get(`/api/config-sedes/${sedeId}/buscarPorId`);
            sedeInterface.value = Boolean(resSede.data?.data?.interface_software);

            if (sedeInterface.value && registro.value.num_docu) {
                const resAtenciones = await axios.get(
                    `/api/interface-sahi-listar-atenciones/${encodeURIComponent(registro.value.num_docu)}`
                );
                interfaceAtenciones.value = resAtenciones.data?.data || [];

                if (data.atencion) {
                    const matchAtencion = interfaceAtenciones.value.find(
                        (a) => String(a.IdAtencion) === String(data.atencion)
                    );
                    if (matchAtencion) {
                        interfaceAtencionSeleccionada.value = matchAtencion.IdAtencion;
                        await interfaceListarProductosDiagnosticos(true);
                    }
                }
            }
        }
    } catch {
        // Si la interfaz SAHI no está accesible en red, se permite continuar con los datos ya cargados
    } finally {
        loadingAbrirRegistroIndex.value = null;
    }
}

async function interfaceListarProductosDiagnosticos(conservarProductoInicial = false) {
    if (!interfaceAtencionSeleccionada.value) return;

    try {
        const resProductos = await axios.get(
            `/api/interface-sahi-listar-productos-diagnosticos/${interfaceAtencionSeleccionada.value}`
        );
        const data = resProductos.data?.data || {};
        contratoInfoResumen.value = data.contrato_info || null;

        const cupsPreviamenteMarcados = new Set(
            conservarProductoInicial
                ? registro.value.productosEstudio.filter((p) => p.checked).map((p) => p.cod_cups)
                : []
        );

        if (Array.isArray(data.productos) && data.productos.length > 0) {
            const nuevos = data.productos.map((element) => ({
                checked: cupsPreviamenteMarcados.has(element.cod_cups),
                id: element.IdProducto,
                cod_cups: element.cod_cups,
                nom_produc: element.nom_produc,
                medico_ordena:
                    element.quien_ordeno ||
                    (data.contrato_info ? data.contrato_info.quien_ordeno : null) ||
                    null,
            }));

            // Conservar el producto de la admisión si no venía en la lista de la atención
            if (conservarProductoInicial) {
                registro.value.productosEstudio.forEach((prev) => {
                    if (!nuevos.some((n) => n.cod_cups === prev.cod_cups)) {
                        nuevos.unshift(prev);
                    }
                });
            }
            registro.value.productosEstudio = nuevos;
        }

        if (data.contrato_info) {
            registro.value.eps = data.contrato_info.NomTercero || registro.value.eps;
            registro.value.contrato = data.contrato_info.DesContrato || registro.value.contrato;
            registro.value.plan = data.contrato_info.DesPlan || registro.value.plan;
            registro.value.servicio = data.contrato_info.NomUbicacion || registro.value.servicio;
            registro.value.ubicacion = data.contrato_info.NomUbicacion || registro.value.ubicacion;
            registro.value.cama = data.contrato_info.Cama || registro.value.cama;
            registro.value.medico_ordena =
                data.contrato_info.quien_ordeno || registro.value.medico_ordena;
        }

        const consultas = data.consultas || [];
        let textoMotivo = "Enfermedad Actual: ";
        consultas.forEach((c) => {
            const enf =
                c.DesEnfermedadActual ||
                c.des_enfermedad_actual ||
                c.EnfermedadActual ||
                c.enfermedad_actual;
            if (enf) {
                textoMotivo += `\n ${enf} \n`;
            }
        });

        const diags = data.diagnosticos || [];
        textoMotivo += "\nDiagnósticos:";
        diags.forEach((d) => {
            const codigo = d.cod_diagnos || d.CodCie9 || "";
            const nombre = d.nom_diagnos || d.NomDiagnostico || "";
            if (codigo || nombre) {
                textoMotivo += `\n ${codigo} - ${nombre}`;
            }
        });

        if (consultas.length > 0 || diags.length > 0) {
            registro.value.motivo_examen = textoMotivo;
        }
        if (diags.length > 0) {
            registro.value.diagnosticosEstudio = diags;
        }

        registro.value.atencion_id = interfaceAtencionSeleccionada.value;
    } catch (err) {
        toastMessage({
            heading: "Aviso Interface",
            text: getUserSafeError(err, "No fue posible consultar los detalles de la atención."),
            icon: "warning",
        });
    }
}

async function guardarRegistro() {
    if (loadingGuardar.value || !admisionSeleccionada.value) return;
    loadingGuardar.value = true;

    touchAll(["study_desc", "study_datetime", "prioridad_id"]);

    if (
        statusStudyDesc.value.isInvalid ||
        statusStudyDatetime.value.isInvalid ||
        statusPrioridadId.value.isInvalid
    ) {
        toastMessage({
            heading: "Formulario incompleto",
            text: "Por favor complete los campos requeridos del estudio antes de continuar.",
            icon: "warning",
        });
        primerInput.value?.focus();
        loadingGuardar.value = false;
        return;
    }

    if (
        (props.usuarioactual.transcrip_remoto == 0 ||
            props.usuarioactual.transcrip_remoto == null) &&
        !String(registro.value.motivo_examen || "").trim()
    ) {
        toastMessage({
            heading: "Campo obligatorio",
            text: "El motivo del examen es obligatorio. Ingrese la indicación clínica antes de guardar.",
            icon: "warning",
        });
        document.getElementById("pen_motivo_examen")?.focus();
        loadingGuardar.value = false;
        return;
    }

    if (
        registro.value.destino_estudio !== "Tele Radiologia" &&
        (!registro.value.medico_id || registro.value.medico_id === "")
    ) {
        toastMessage({
            heading: "Médico requerido",
            text: "Debes seleccionar un médico radiólogo antes de guardar.",
            icon: "warning",
        });
        loadingGuardar.value = false;
        return;
    }

    const tieneProductoMarcado = registro.value.productosEstudio.some((p) => Boolean(p.checked));
    if (!tieneProductoMarcado) {
        toastMessage({
            heading: "Producto requerido",
            text: "Debe seleccionar al menos un producto CUPS para la lectura.",
            icon: "warning",
        });
        loadingGuardar.value = false;
        return;
    }

    try {
        const cleanEmail =
            registro.value.email_reportar &&
            registro.value.email_reportar !== "null" &&
            registro.value.email_reportar !== "undefined" &&
            String(registro.value.email_reportar).trim() !== ""
                ? String(registro.value.email_reportar).trim()
                : null;

        const registroPayload = {
            ...registro.value,
            sede_id: resolverSedeActiva(),
            email_reportar: cleanEmail,
        };

        const formData = new FormData();
        formData.append("registro", JSON.stringify(registroPayload));
        if (soportesHC.value.archivo1) formData.append("archivo1", soportesHC.value.archivo1);
        if (soportesHC.value.archivo2) formData.append("archivo2", soportesHC.value.archivo2);
        if (soportesHC.value.archivo3) formData.append("archivo3", soportesHC.value.archivo3);

        detenerHeartbeatLockAsignacion();

        const res = await axios.post(
            `/api/admisiones/${admisionSeleccionada.value.id}/asignar-lectura`,
            formData,
            {
                headers: { "Content-Type": "multipart/form-data" },
                onUploadProgress: (e) => {
                    if (e.total) {
                        progresoUpload.value = Math.round((e.loaded * 100) / e.total);
                    }
                },
            }
        );

        if (res.status === 200 || res.status === 201) {
            lockAsignParams.value = null;
            cerrarModal({ skipRelease: true });

            toastMessage({
                heading: "Enviado a lectura",
                text: res.data?.message || "El estudio fue asignado a lectura correctamente.",
                icon: "success",
            });

            await listarPendientes(false);
            return;
        }

        cerrarModal();
    } catch (error) {
        if (lockAsignParams.value && sidebarVisible.value) {
            iniciarHeartbeatLockAsignacion();
        }
        errores.value =
            error?.response?.data?.errors && typeof error.response.data.errors === "object"
                ? error.response.data.errors
                : {};
        toastMessage({
            heading: "Atención",
            text: getUserSafeError(error, "No fue posible asignar el estudio a lectura."),
            icon: error?.response?.status === 422 ? "warning" : "error",
            hideAfter: 5500,
        });
    } finally {
        loadingGuardar.value = false;
        progresoUpload.value = null;
    }
}

function obtenerArchivo(e, index) {
    const file = e.target.files[0];
    if (file && file.size > 10 * 1024 * 1024) {
        toastMessage({
            heading: "Archivo muy grande",
            text: "El archivo adjunto no debe superar los 10 MB.",
            icon: "warning",
        });
        e.target.value = "";
        return;
    }
    soportesHC.value[`archivo${index}`] = file || null;
}

function abrirModal() {
    quemadorModalVisible.value = false;
    sidebarVisible.value = true;
    if (!medicos.value || medicos.value.length === 0) {
        listarMedicos();
    }
    nextTick(() => {
        primerInput.value?.focus();
        selMedico.value?.rebuild();
        selProducto.value?.rebuild();
        selDiagnostico.value?.rebuild();
    });
}

function cerrarModal({ skipRelease = false } = {}) {
    sidebarVisible.value = false;
    errores.value = {};
    if (skipRelease) {
        detenerHeartbeatLockAsignacion();
        lockAsignParams.value = null;
    } else {
        liberarLockAsignacion();
    }
    requestAnimationFrame(() => {
        if (!sidebarVisible.value) {
            limpiarFormulario();
        }
    });
}

function cerrarCualquierSidebar() {
    if (sidebarVisible.value) {
        cerrarModal();
    }
    if (quemadorModalVisible.value) {
        cerrarQuemador();
    }
}

function abrirQuemador(item) {
    if (sidebarVisible.value) {
        cerrarModal();
    }
    estudioParaQuemar.value = item;
    quemadorModalVisible.value = true;
}

function cerrarQuemador() {
    quemadorModalVisible.value = false;
    estudioParaQuemar.value = null;
}

function onQuemadoCompletado() {
    cerrarQuemador();
    swalSuccess("¡Disco Quemado!", "El disco ha sido quemado exitosamente en la unidad de CD/DVD.");
}

function limpiarFormulario() {
    resetTouched();
    admisionSeleccionada.value = null;
    registro.value.admision_id = null;
    registro.value.worklist_orden_id = null;
    registro.value.paciente_id = null;
    registro.value.study_pk = "";
    registro.value.study_iuid = "";
    registro.value.study_id = "";
    registro.value.study_datetime = "";
    registro.value.accession_no = "";
    registro.value.study_desc = "";
    registro.value.mods_in_study = "";
    registro.value.observaciones = "";
    registro.value.email_reportar = "";
    registro.value.num_docu = "";
    registro.value.nombres = "";
    registro.value.sexo = "";
    registro.value.email = "";
    registro.value.fec_naci = "";
    registro.value.direccion = "";
    registro.value.tel = "";
    registro.value.destino_estudio = "Salud Tolima";
    registro.value.motivo_examen = "";
    registro.value.atencion_id = null;
    registro.value.eps = "";
    registro.value.contrato = "";
    registro.value.plan = "";
    registro.value.servicio = "";
    registro.value.ubicacion = "";
    registro.value.cama = "";
    registro.value.medico_ordena = "";
    registro.value.diagnosticosEstudio.splice(0, registro.value.diagnosticosEstudio.length);
    registro.value.productosEstudio.splice(0, registro.value.productosEstudio.length);

    interfaceAtenciones.value = [];
    interfaceAtencionSeleccionada.value = null;
    contratoInfoResumen.value = null;
    productoSeleccionado.value = "";
    diagnosticoSeleccionado.value = "";
    soportesHC.value = { archivo1: null, archivo2: null, archivo3: null };
}

async function listarMedicos(sedeId = null) {
    try {
        const moduloId = getModuloIdActual();
        const targetSede = sedeId ?? resolverSedeActiva();
        const sedeParam = targetSede ? `&sede_id=${encodeURIComponent(targetSede)}` : "";
        const res = await axios.get(
            `/api/user.listarUsuarios/Medico/${moduloId}?solo_activos=1${sedeParam}`
        );
        medicos.value = Array.isArray(res?.data?.data) ? res.data.data : [];
        nextTick(() => {
            selMedico.value?.rebuild();
        });
    } catch {
        medicos.value = [];
    }
}

async function listarPrioridades() {
    try {
        const res = await axios.get("/api/config-prioridades");
        prioridades.value = res.data?.data || [];
        if (prioridades.value.length > 0 && !registro.value.prioridad_id) {
            registro.value.prioridad_id = prioridades.value[0].id;
        }
    } catch {
        prioridades.value = [];
    }
}

async function listarProductos() {
    try {
        const res = await axios.get("/api/config-productos");
        productos.value = res.data?.data || [];
    } catch {
        productos.value = [];
    }
}

async function listarDiagnosticos() {
    try {
        const res = await axios.get("/api/config-diagnosticos");
        diagnosticos.value = res.data?.data || [];
    } catch {
        diagnosticos.value = [];
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

function cargarCatalogosSede(sedeId = null) {
    listarMedicos(sedeId);
    listarPrioridades();
    listarProductos();
    listarDiagnosticos();
    listarTiposRecaudo();
}

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
    productoSeleccionado.value = selectedId || "";
    if (selectedId && selectedId !== "0" && selectedId !== "") {
        agregarProductoEstudio(selectedId);
    }
}

function agregarProductoEstudio(idProd) {
    const prod = productos.value.find((x) => String(x.id) === String(idProd));
    if (!prod) return;

    const yaExiste = registro.value.productosEstudio.some(
        (p) => String(p.id) === String(prod.id) || p.cod_cups === prod.cod_cups
    );
    if (!yaExiste) {
        registro.value.productosEstudio.push({
            id: prod.id,
            cod_cups: prod.cod_cups,
            nom_produc: prod.nom_produc,
            medico_ordena: registro.value.medico_ordena || null,
            checked: true,
        });
    }
    productoSeleccionado.value = "";
}

async function quitarProductoEstudio(indexEliminar) {
    const ok = await confirmAction({
        title: "¿Retirar producto?",
        text: "¿Confirma retirar este procedimiento CUPS de la asignación de lectura?",
        icon: "warning",
    });
    if (!ok) return;
    registro.value.productosEstudio.splice(indexEliminar, 1);
}

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
    diagnosticoSeleccionado.value = selectedId || "";
    if (selectedId && selectedId !== "0" && selectedId !== "") {
        const diag = diagnosticos.value.find((x) => String(x.id) === String(selectedId));
        if (diag) {
            const yaExiste = registro.value.diagnosticosEstudio.some(
                (d) => d.cod_diagnos === diag.cod_diagnos
            );
            if (!yaExiste) {
                registro.value.diagnosticosEstudio.push({
                    id: diag.id,
                    cod_diagnos: diag.cod_diagnos,
                    nom_diagnos: diag.nom_diagnos,
                });
            }
        }
        diagnosticoSeleccionado.value = "";
    }
}

async function quitarDiagnosticoEstudio(indexEliminar) {
    const ok = await confirmAction({
        title: "¿Retirar diagnóstico?",
        text: "¿Confirma retirar este diagnóstico CIE-10 del estudio?",
        icon: "warning",
    });
    if (!ok) return;
    registro.value.diagnosticosEstudio.splice(indexEliminar, 1);
}

function onSedeSeleccionada(data) {
    const nuevaSede =
        data?.idSede ??
        data?.id ??
        sessionStorage.getItem("ST-sede") ??
        localStorage.getItem("ST-sede");
    if (!nuevaSede) return;

    const sedeCambio = String(sedeActual.value || "") !== String(nuevaSede);
    sedeActual.value = nuevaSede;
    busqueda.value.sede_id = nuevaSede;
    registro.value.sede_id = nuevaSede;

    if (sedeCambio) {
        registro.value.medico_id = "";
        cargarCatalogosSede(nuevaSede);
        listarPendientes(false);
    }
}

function onMedicoSeleccionado(data) {
    if (data && data.idMedico) {
        registro.value.medico_id = String(data.idMedico);
    }
}

onMounted(() => {
    const sedeInicial = resolverSedeActiva();
    sedeActual.value = sedeInicial;
    busqueda.value.sede_id = sedeInicial;
    registro.value.sede_id = sedeInicial;
    cargarCatalogosSede(sedeInicial);

    if (sedeInicial) {
        listarPendientes(false);
    } else if (emitter) {
        emitter.emit("solicitarSedeActiva");
    }

    if (emitter) {
        emitter.on("sedeSeleccionada", onSedeSeleccionada);
        emitter.on("medicoSeleccionado", onMedicoSeleccionado);
    }
});

onBeforeUnmount(() => {
    if (emitter) {
        emitter.off("sedeSeleccionada", onSedeSeleccionada);
        emitter.off("medicoSeleccionado", onMedicoSeleccionado);
    }
    liberarLockAsignacion();
    destroyDataTable();
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
    transition:
        right 0.35s cubic-bezier(0.16, 1, 0.3, 1),
        visibility 0.35s;
    visibility: hidden;
    pointer-events: none;
}

@media (max-width: 767px) {
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
    overflow-y: auto;
    max-height: 220px;
}

.table-scroll-sm {
    overflow-y: auto;
    max-height: 160px;
}

.progress-thin {
    height: 6px;
}

:deep(.select2-dropdown) {
    z-index: 9999 !important;
}
</style>
