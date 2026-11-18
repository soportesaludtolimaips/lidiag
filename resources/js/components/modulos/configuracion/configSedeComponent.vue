<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Configuración</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="/" class="text-muted" style="text-decoration: none">
                            <i class="fa fa-home mr-1"></i>Inicio
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="text-muted">Configuración</span>
                    </li>
                    <li class="breadcrumb-item active text-themecolor font-weight-bold">Sedes</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-outline-secondary m-r-5"
                    @click="ListarDatos"
                    :disabled="loadingListar"
                    title="Actualizar listado de sedes"
                    aria-label="Actualizar listado de sedes"
                >
                    <i class="fa" :class="loadingListar ? 'fa-spinner fa-spin' : 'fa-refresh'"></i>
                    Refrescar
                </button>
                <button
                    type="button"
                    class="btn btn-info btnDesplegarRigthSidebar"
                    @click="
                        actualizar = false;
                        mostrarRegistro();
                    "
                    title="Registrar nueva sede"
                    aria-label="Registrar nueva sede"
                >
                    <i class="fa fa-plus-circle m-r-5"></i> Nueva sede
                </button>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Tarjetas KPI Resumen -->
        <!-- ============================================================== -->
        <div class="row m-b-20" v-if="!loadingListar && registros.length > 0">
            <div class="col-md-4 col-sm-6">
                <div class="card card-body p-3 mb-0 shadow-sm border">
                    <div class="d-flex align-items-center">
                        <div class="round round-info mr-3">
                            <i class="fa fa-hospital-o"></i>
                        </div>
                        <div>
                            <h3 class="font-weight-bold mb-0">{{ totalSedes }}</h3>
                            <span class="text-muted">Total de Sedes</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card card-body p-3 mb-0 shadow-sm border">
                    <div class="d-flex align-items-center">
                        <div class="round round-success mr-3">
                            <i class="fa fa-check-circle-o"></i>
                        </div>
                        <div>
                            <h3 class="font-weight-bold mb-0 text-success">{{ totalActivas }}</h3>
                            <span class="text-muted">Sedes Activas</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card card-body p-3 mb-0 shadow-sm border">
                    <div class="d-flex align-items-center">
                        <div class="round round-warning mr-3">
                            <i class="fa fa-pause-circle-o"></i>
                        </div>
                        <div>
                            <h3 class="font-weight-bold mb-0 text-warning">{{ totalInactivas }}</h3>
                            <span class="text-muted">Sedes Inactivas</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- Start Page Content -->
        <!-- ============================================================== -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div
                            class="d-flex justify-content-between align-items-center flex-wrap mb-2"
                        >
                            <div>
                                <h4 class="card-title mb-1">Listado de sedes</h4>
                                <h6 class="card-subtitle text-muted mb-0">
                                    Administración de sedes para la distribución y asignación de
                                    estudios y agenda de lecturas de imágenes diagnósticas.
                                </h6>
                            </div>
                        </div>

                        <!-- Skeleton Loader durante consulta -->
                        <table-skeleton-loader :visible="loadingListar" :rows="5" :columns="8" />

                        <!-- Empty State cuando no hay sedes -->
                        <empty-state-component
                            v-if="!loadingListar && registros.length === 0"
                            icon="fa fa-hospital-o"
                            title="No hay sedes registradas"
                            description="No se encontraron sedes configuradas en el sistema. Comience creando una nueva sede."
                            action-text="Nueva sede"
                            action-icon="fa fa-plus"
                            @action="
                                actualizar = false;
                                mostrarRegistro();
                            "
                        />

                        <!-- Tabla interactiva con v-show para preservar DOM DataTables -->
                        <div
                            v-show="!loadingListar && registros.length > 0"
                            class="table-responsive m-t-20"
                        >
                            <table
                                id="tablaSedes"
                                ref="dt"
                                class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th>Sede</th>
                                        <th>IP PACS</th>
                                        <th>BD PACS</th>
                                        <th>Usuario PACS</th>
                                        <th>Usuario Oviyam</th>
                                        <th>URL Oviyam</th>
                                        <th>Estado</th>
                                        <th class="text-center" style="min-width: 100px">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td class="font-weight-bold text-dark">
                                            <i class="fa fa-hospital-o text-info mr-1"></i>
                                            {{ item.nom_sede }}
                                        </td>
                                        <td>
                                            <code>{{ item.ip_dcm || "—" }}</code>
                                        </td>
                                        <td>{{ item.bd_dcm || "—" }}</td>
                                        <td>{{ item.usuario_dcm || "—" }}</td>
                                        <td>{{ item.usuario_oviyam || "—" }}</td>
                                        <td>
                                            <a
                                                v-if="item.url_oviyam"
                                                :href="item.url_oviyam"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="text-info font-weight-bold text-truncate d-inline-block"
                                                style="max-width: 220px"
                                                :title="item.url_oviyam"
                                            >
                                                <i class="fa fa-external-link mr-1"></i
                                                >{{ item.url_oviyam }}
                                            </a>
                                            <span v-else class="text-muted">—</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-success" v-if="item.estado">
                                                <i class="fa fa-check mr-1"></i>Activa
                                            </span>
                                            <span class="badge badge-secondary" v-else>
                                                <i class="fa fa-ban mr-1"></i>Inactiva
                                            </span>
                                        </td>
                                        <td class="text-nowrap text-center">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                @click="
                                                    actualizar = true;
                                                    mostrarRegistro(item);
                                                "
                                                title="Editar configuración de sede"
                                                aria-label="Editar sede"
                                            >
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                @click="eliminarRegistro(item.id)"
                                                :disabled="loadingEliminarId === item.id"
                                                title="Eliminar sede"
                                                aria-label="Eliminar sede"
                                            >
                                                <i
                                                    class="fa"
                                                    :class="
                                                        loadingEliminarId === item.id
                                                            ? 'fa-spinner fa-spin'
                                                            : 'fa-trash'
                                                    "
                                                ></i>
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

        <!-- Backdrop overlay para cerrar al hacer clic afuera -->
        <div v-if="sidebarVisible" class="sidebar-backdrop" @click="cerrarSidebar()"></div>

        <!-- Right sidebar (Drawer) 80% desktop / 100% mobile -->
        <div
            ref="sidebarRef"
            class="right-sidebar"
            :class="{ 'shw-rside': sidebarVisible }"
            role="dialog"
            aria-modal="true"
            :aria-label="tituloModal"
        >
            <div class="slimscrollright">
                <div class="rpanel-title">
                    <span class="font-weight-bold">
                        <i
                            class="fa mr-2"
                            :class="
                                actualizar
                                    ? 'fa-pencil-square-o text-warning'
                                    : 'fa-plus-circle text-info'
                            "
                        ></i>
                        {{ tituloModal }}
                    </span>
                    <button
                        type="button"
                        class="right-side-toggle"
                        @click="cerrarSidebar()"
                        aria-label="Cerrar panel"
                        title="Cerrar panel (Esc)"
                    >
                        <i class="ti-close"></i>
                    </button>
                </div>
                <div class="r-panel-body">
                    <!-- ============================================================== -->
                    <!-- Contenido de los formularios -->
                    <!-- ============================================================== -->
                    <form action="#" @submit.prevent="guardarRegistro()">
                        <div class="form-body">
                            <!-- Datos Básicos -->
                            <div class="row p-t-10">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label
                                            class="control-label font-weight-bold"
                                            for="nom_sede"
                                        >
                                            Nombre de la Sede <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            id="nom_sede"
                                            ref="primerInput"
                                            name="nom_sede"
                                            v-model="registro.nom_sede"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.nom_sede }"
                                            placeholder="Ingrese el nombre de la sede"
                                            required
                                            aria-required="true"
                                        />
                                        <div
                                            class="invalid-feedback d-block"
                                            v-if="errores.nom_sede"
                                        >
                                            {{ errores.nom_sede[0] }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label" for="email_repor">
                                            Email para Reportar Lecturas
                                        </label>
                                        <input
                                            type="email"
                                            id="email_repor"
                                            name="email_repor"
                                            v-model="registro.email_repor"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.email_repor }"
                                            placeholder="Ingrese el email para reportar las lecturas"
                                        />
                                        <div
                                            class="invalid-feedback d-block"
                                            v-if="errores.email_repor"
                                        >
                                            {{ errores.email_repor[0] }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección Configuración DCM4CHEE -->
                            <h5 class="card-title text-info mt-3 font-weight-bold">
                                <i class="fa fa-server mr-1"></i> Datos de Configuración de DCM4CHEE
                                (PACS)
                            </h5>
                            <hr class="mt-1 mb-3" />

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label" for="ip_dcm">
                                            IP BD DCM4CHEE
                                        </label>
                                        <input
                                            type="text"
                                            id="ip_dcm"
                                            name="ip_dcm"
                                            v-model="registro.ip_dcm"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.ip_dcm }"
                                            placeholder="Ej: 192.168.1.50"
                                        />
                                        <div class="invalid-feedback d-block" v-if="errores.ip_dcm">
                                            {{ errores.ip_dcm[0] }}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label" for="bd_dcm">
                                            Nombre BD DCM4CHEE
                                        </label>
                                        <input
                                            type="text"
                                            id="bd_dcm"
                                            name="bd_dcm"
                                            v-model="registro.bd_dcm"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.bd_dcm }"
                                            placeholder="Ej: pacsdb"
                                        />
                                        <div class="invalid-feedback d-block" v-if="errores.bd_dcm">
                                            {{ errores.bd_dcm[0] }}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label" for="usuario_dcm">
                                            Usuario BD
                                        </label>
                                        <input
                                            type="text"
                                            id="usuario_dcm"
                                            name="usuario_dcm"
                                            v-model="registro.usuario_dcm"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.usuario_dcm }"
                                            placeholder="Usuario base de datos"
                                        />
                                        <div
                                            class="invalid-feedback d-block"
                                            v-if="errores.usuario_dcm"
                                        >
                                            {{ errores.usuario_dcm[0] }}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label" for="password_dcm">
                                            Contraseña BD
                                        </label>
                                        <input
                                            type="password"
                                            id="password_dcm"
                                            name="password_dcm"
                                            v-model="registro.password_dcm"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.password_dcm }"
                                            placeholder="Contraseña base de datos"
                                            autocomplete="new-password"
                                        />
                                        <div
                                            class="invalid-feedback d-block"
                                            v-if="errores.password_dcm"
                                        >
                                            {{ errores.password_dcm[0] }}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label" for="puerto_dcm">
                                            Puerto BD/PACS
                                        </label>
                                        <input
                                            type="number"
                                            id="puerto_dcm"
                                            name="puerto_dcm"
                                            v-model="registro.puerto_dcm"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.puerto_dcm }"
                                            placeholder="Ej: 3306 o 11112"
                                            min="1"
                                            max="65535"
                                        />
                                        <div
                                            class="invalid-feedback d-block"
                                            v-if="errores.puerto_dcm"
                                        >
                                            {{ errores.puerto_dcm[0] }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección Configuración OVIYAM -->
                            <h5 class="card-title text-info mt-3 font-weight-bold">
                                <i class="fa fa-desktop mr-1"></i> Datos de Configuración de OVIYAM
                                (Visor Web)
                            </h5>
                            <hr class="mt-1 mb-3" />

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label" for="usuario_oviyam">
                                            Usuario Oviyam
                                        </label>
                                        <input
                                            type="text"
                                            id="usuario_oviyam"
                                            name="usuario_oviyam"
                                            v-model="registro.usuario_oviyam"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.usuario_oviyam }"
                                            placeholder="Usuario del visor Oviyam"
                                        />
                                        <div
                                            class="invalid-feedback d-block"
                                            v-if="errores.usuario_oviyam"
                                        >
                                            {{ errores.usuario_oviyam[0] }}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label" for="password_oviyam">
                                            Contraseña Oviyam
                                        </label>
                                        <input
                                            type="password"
                                            id="password_oviyam"
                                            name="password_oviyam"
                                            v-model="registro.password_oviyam"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.password_oviyam }"
                                            placeholder="Contraseña del visor Oviyam"
                                            autocomplete="new-password"
                                        />
                                        <div
                                            class="invalid-feedback d-block"
                                            v-if="errores.password_oviyam"
                                        >
                                            {{ errores.password_oviyam[0] }}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label" for="url_oviyam">
                                            URL Oviyam
                                        </label>
                                        <input
                                            type="text"
                                            id="url_oviyam"
                                            name="url_oviyam"
                                            v-model="registro.url_oviyam"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.url_oviyam }"
                                            placeholder="Ej: http://192.168.1.100:8080/oviyam2"
                                        />
                                        <div
                                            class="invalid-feedback d-block"
                                            v-if="errores.url_oviyam"
                                        >
                                            {{ errores.url_oviyam[0] }}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label" for="tap_oviyam">
                                            Pestaña / Tab Oviyam
                                        </label>
                                        <input
                                            type="text"
                                            id="tap_oviyam"
                                            name="tap_oviyam"
                                            v-model="registro.tap_oviyam"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.tap_oviyam }"
                                            placeholder="Ej: Oviyam"
                                        />
                                        <div
                                            class="invalid-feedback d-block"
                                            v-if="errores.tap_oviyam"
                                        >
                                            {{ errores.tap_oviyam[0] }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección Plantillas y Variables Asistenciales -->
                            <div class="row mt-2">
                                <!-- Columna de subida de plantillas -->
                                <div class="col-md-4">
                                    <h5 class="card-title text-info font-weight-bold">
                                        <i class="fa fa-file-word-o mr-1"></i> Archivos de Plantilla
                                    </h5>
                                    <hr class="mt-1 mb-3" />

                                    <div class="form-group">
                                        <label
                                            class="control-label font-weight-bold"
                                            for="exampleInputFileRx"
                                        >
                                            Plantilla para Rx, TAC y Resonancia
                                        </label>
                                        <div
                                            v-if="registro.plantilla_rx"
                                            class="small text-muted mb-1"
                                        >
                                            <i class="fa fa-file-word-o text-primary mr-1"></i>
                                            Actual:
                                            <strong>{{ registro.plantilla_rx }}</strong>
                                        </div>
                                        <input
                                            type="file"
                                            class="form-control"
                                            id="exampleInputFileRx"
                                            aria-describedby="fileHelpRx"
                                            @change="obtenerPlantillaRx"
                                            accept=".doc, .docx"
                                        />
                                        <div
                                            v-if="plantillaRx"
                                            class="mt-1 small text-info d-flex align-items-center justify-content-between"
                                        >
                                            <span>
                                                <i class="fa fa-check mr-1"></i>
                                                {{ plantillaRx.name }} ({{
                                                    (plantillaRx.size / 1024).toFixed(1)
                                                }}
                                                KB)
                                            </span>
                                            <button
                                                type="button"
                                                class="btn btn-xs btn-link text-danger p-0 ml-2"
                                                @click="limpiarPlantillaRx"
                                                title="Quitar archivo seleccionado"
                                            >
                                                Quitar
                                            </button>
                                        </div>
                                        <small id="fileHelpRx" class="form-text text-muted">
                                            Formatos admitidos: .doc, .docx
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <label
                                            class="control-label font-weight-bold"
                                            for="exampleInputFileEcografia"
                                        >
                                            Plantilla para Ecografías
                                        </label>
                                        <div
                                            v-if="registro.plantilla_ecografia"
                                            class="small text-muted mb-1"
                                        >
                                            <i class="fa fa-file-word-o text-success mr-1"></i>
                                            Actual:
                                            <strong>{{ registro.plantilla_ecografia }}</strong>
                                        </div>
                                        <input
                                            type="file"
                                            class="form-control"
                                            id="exampleInputFileEcografia"
                                            aria-describedby="fileHelpEco"
                                            @change="obtenerPlantillaEcografia"
                                            accept=".doc, .docx"
                                        />
                                        <div
                                            v-if="plantillaEcografia"
                                            class="mt-1 small text-success d-flex align-items-center justify-content-between"
                                        >
                                            <span>
                                                <i class="fa fa-check mr-1"></i>
                                                {{ plantillaEcografia.name }} ({{
                                                    (plantillaEcografia.size / 1024).toFixed(1)
                                                }}
                                                KB)
                                            </span>
                                            <button
                                                type="button"
                                                class="btn btn-xs btn-link text-danger p-0 ml-2"
                                                @click="limpiarPlantillaEcografia"
                                                title="Quitar archivo seleccionado"
                                            >
                                                Quitar
                                            </button>
                                        </div>
                                        <small id="fileHelpEco" class="form-text text-muted">
                                            Formatos admitidos: .doc, .docx
                                        </small>
                                    </div>
                                </div>

                                <!-- Columna de catálogo de variables interactivo con copia rápida -->
                                <div class="col-md-8">
                                    <div class="card border mb-3">
                                        <div
                                            class="card-header bg-light d-flex justify-content-between align-items-center py-2 flex-wrap"
                                        >
                                            <h5 class="card-title text-info mb-0 font-weight-bold">
                                                <i class="fa fa-code mr-1"></i> Variables para las
                                                Plantillas
                                            </h5>
                                            <small class="text-muted">
                                                <i class="fa fa-mouse-pointer mr-1"></i>Clic en una
                                                variable para copiarla
                                            </small>
                                        </div>
                                        <div
                                            class="card-body p-3"
                                            style="max-height: 480px; overflow-y: auto"
                                        >
                                            <!-- Paciente -->
                                            <div class="mb-3">
                                                <div
                                                    class="text-primary font-weight-bold mb-1 small"
                                                >
                                                    <i class="fa fa-user-circle mr-1"></i> DATOS DEL
                                                    PACIENTE
                                                </div>
                                                <div class="d-flex flex-wrap">
                                                    <span
                                                        role="button"
                                                        class="badge badge-light border variable-badge m-1 p-2"
                                                        @click="
                                                            copiarVariable('${nombre_paciente}')
                                                        "
                                                        title="Clic para copiar"
                                                    >
                                                        <code>${nombre_paciente}</code>
                                                        <span class="text-muted ml-1"
                                                            >Nombres paciente</span
                                                        >
                                                    </span>
                                                    <span
                                                        role="button"
                                                        class="badge badge-light border variable-badge m-1 p-2"
                                                        @click="copiarVariable('${num_docu}')"
                                                        title="Clic para copiar"
                                                    >
                                                        <code>${num_docu}</code>
                                                        <span class="text-muted ml-1"
                                                            >Documento de identidad</span
                                                        >
                                                    </span>
                                                    <span
                                                        role="button"
                                                        class="badge badge-light border variable-badge m-1 p-2"
                                                        @click="copiarVariable('${fec_naci}')"
                                                        title="Clic para copiar"
                                                    >
                                                        <code>${fec_naci}</code>
                                                        <span class="text-muted ml-1"
                                                            >Fecha de nacimiento</span
                                                        >
                                                    </span>
                                                    <span
                                                        role="button"
                                                        class="badge badge-light border variable-badge m-1 p-2"
                                                        @click="copiarVariable('${sexo}')"
                                                        title="Clic para copiar"
                                                    >
                                                        <code>${sexo}</code>
                                                        <span class="text-muted ml-1">Sexo</span>
                                                    </span>
                                                    <span
                                                        role="button"
                                                        class="badge badge-light border variable-badge m-1 p-2"
                                                        @click="copiarVariable('${edad}')"
                                                        title="Clic para copiar"
                                                    >
                                                        <code>${edad}</code>
                                                        <span class="text-muted ml-1">Edad</span>
                                                    </span>
                                                    <span
                                                        role="button"
                                                        class="badge badge-light border variable-badge m-1 p-2"
                                                        @click="copiarVariable('${dir}')"
                                                        title="Clic para copiar"
                                                    >
                                                        <code>${dir}</code>
                                                        <span class="text-muted ml-1"
                                                            >Dirección de residencia</span
                                                        >
                                                    </span>
                                                    <span
                                                        role="button"
                                                        class="badge badge-light border variable-badge m-1 p-2"
                                                        @click="copiarVariable('${tel}')"
                                                        title="Clic para copiar"
                                                    >
                                                        <code>${tel}</code>
                                                        <span class="text-muted ml-1"
                                                            >Teléfono fijo</span
                                                        >
                                                    </span>
                                                    <span
                                                        role="button"
                                                        class="badge badge-light border variable-badge m-1 p-2"
                                                        @click="copiarVariable('${cel}')"
                                                        title="Clic para copiar"
                                                    >
                                                        <code>${cel}</code>
                                                        <span class="text-muted ml-1">Celular</span>
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Estudio -->
                                            <div class="mb-3">
                                                <div class="text-info font-weight-bold mb-1 small">
                                                    <i class="fa fa-file-text-o mr-1"></i> DATOS DEL
                                                    ESTUDIO
                                                </div>
                                                <div class="d-flex flex-wrap">
                                                    <span
                                                        role="button"
                                                        class="badge badge-light border variable-badge m-1 p-2"
                                                        @click="copiarVariable('${fechor_estudio}')"
                                                        title="Clic para copiar"
                                                    >
                                                        <code>${fechor_estudio}</code>
                                                        <span class="text-muted ml-1"
                                                            >Fecha y hora estudio</span
                                                        >
                                                    </span>
                                                    <span
                                                        role="button"
                                                        class="badge badge-light border variable-badge m-1 p-2"
                                                        @click="copiarVariable('${fechor_lectura}')"
                                                        title="Clic para copiar"
                                                    >
                                                        <code>${fechor_lectura}</code>
                                                        <span class="text-muted ml-1"
                                                            >Fecha y hora lectura</span
                                                        >
                                                    </span>
                                                    <span
                                                        role="button"
                                                        class="badge badge-light border variable-badge m-1 p-2"
                                                        @click="
                                                            copiarVariable(
                                                                '${fechor_transcripcion}'
                                                            )
                                                        "
                                                        title="Clic para copiar"
                                                    >
                                                        <code>${fechor_transcripcion}</code>
                                                        <span class="text-muted ml-1"
                                                            >Fecha y hora transcripción</span
                                                        >
                                                    </span>
                                                    <span
                                                        role="button"
                                                        class="badge badge-light border variable-badge m-1 p-2"
                                                        @click="copiarVariable('${atencion}')"
                                                        title="Clic para copiar"
                                                    >
                                                        <code>${atencion}</code>
                                                        <span class="text-muted ml-1"
                                                            >Atención / Folio</span
                                                        >
                                                    </span>
                                                    <span
                                                        role="button"
                                                        class="badge badge-light border variable-badge m-1 p-2"
                                                        @click="copiarVariable('${cod_producto}')"
                                                        title="Clic para copiar"
                                                    >
                                                        <code>${cod_producto}</code>
                                                        <span class="text-muted ml-1"
                                                            >Código de producto</span
                                                        >
                                                    </span>
                                                    <span
                                                        role="button"
                                                        class="badge badge-light border variable-badge m-1 p-2"
                                                        @click="copiarVariable('${nom_producto}')"
                                                        title="Clic para copiar"
                                                    >
                                                        <code>${nom_producto}</code>
                                                        <span class="text-muted ml-1"
                                                            >Nombre de producto</span
                                                        >
                                                    </span>
                                                    <span
                                                        role="button"
                                                        class="badge badge-light border variable-badge m-1 p-2"
                                                        @click="copiarVariable('${lectura}')"
                                                        title="Clic para copiar"
                                                    >
                                                        <code>${lectura}</code>
                                                        <span class="text-muted ml-1"
                                                            >Texto de lectura radiológica</span
                                                        >
                                                    </span>
                                                    <span
                                                        role="button"
                                                        class="badge badge-light border variable-badge m-1 p-2"
                                                        @click="copiarVariable('${nom_sede}')"
                                                        title="Clic para copiar"
                                                    >
                                                        <code>${nom_sede}</code>
                                                        <span class="text-muted ml-1"
                                                            >Sede de toma</span
                                                        >
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Ecografías -->
                                            <div class="mb-3">
                                                <div
                                                    class="text-success font-weight-bold mb-1 small"
                                                >
                                                    <i class="fa fa-heartbeat mr-1"></i> ESPECÍFICAS
                                                    DE ECOGRAFÍAS
                                                </div>
                                                <div class="d-flex flex-wrap">
                                                    <span
                                                        role="button"
                                                        class="badge badge-light border variable-badge m-1 p-2"
                                                        @click="copiarVariable('${tipo_estudio}')"
                                                        title="Clic para copiar"
                                                    >
                                                        <code>${tipo_estudio}</code>
                                                        <span class="text-muted ml-1"
                                                            >Tipo de estudio</span
                                                        >
                                                    </span>
                                                    <span
                                                        role="button"
                                                        class="badge badge-light border variable-badge m-1 p-2"
                                                        @click="copiarVariable('${opinion}')"
                                                        title="Clic para copiar"
                                                    >
                                                        <code>${opinion}</code>
                                                        <span class="text-muted ml-1"
                                                            >Opinión del estudio</span
                                                        >
                                                    </span>
                                                    <span
                                                        role="button"
                                                        class="badge badge-light border variable-badge m-1 p-2"
                                                        @click="copiarVariable('${productos}')"
                                                        title="Clic para copiar"
                                                    >
                                                        <code>${productos}</code>
                                                        <span class="text-muted ml-1"
                                                            >Productos de la lectura</span
                                                        >
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Administradora de Salud -->
                                            <div class="mb-3">
                                                <div
                                                    class="text-warning font-weight-bold mb-1 small"
                                                >
                                                    <i class="fa fa-institution mr-1"></i>
                                                    ADMINISTRADORA DE SALUD (EPS)
                                                </div>
                                                <div class="d-flex flex-wrap">
                                                    <span
                                                        role="button"
                                                        class="badge badge-light border variable-badge m-1 p-2"
                                                        @click="copiarVariable('${eps}')"
                                                        title="Clic para copiar"
                                                    >
                                                        <code>${eps}</code>
                                                        <span class="text-muted ml-1"
                                                            >Nombre de la EPS</span
                                                        >
                                                    </span>
                                                    <span
                                                        role="button"
                                                        class="badge badge-light border variable-badge m-1 p-2"
                                                        @click="copiarVariable('${contrato}')"
                                                        title="Clic para copiar"
                                                    >
                                                        <code>${contrato}</code>
                                                        <span class="text-muted ml-1"
                                                            >Contrato de vinculación</span
                                                        >
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Médico -->
                                            <div>
                                                <div
                                                    class="text-danger font-weight-bold mb-1 small"
                                                >
                                                    <i class="fa fa-user-md mr-1"></i> DATOS DEL
                                                    MÉDICO RADIÓLOGO
                                                </div>
                                                <div class="d-flex flex-wrap">
                                                    <span
                                                        role="button"
                                                        class="badge badge-light border variable-badge m-1 p-2"
                                                        @click="copiarVariable('${nombres_medico}')"
                                                        title="Clic para copiar"
                                                    >
                                                        <code>${nombres_medico}</code>
                                                        <span class="text-muted ml-1"
                                                            >Nombres del médico</span
                                                        >
                                                    </span>
                                                    <span
                                                        role="button"
                                                        class="badge badge-light border variable-badge m-1 p-2"
                                                        @click="copiarVariable('${reg_medico}')"
                                                        title="Clic para copiar"
                                                    >
                                                        <code>${reg_medico}</code>
                                                        <span class="text-muted ml-1"
                                                            >Registro médico</span
                                                        >
                                                    </span>
                                                    <span
                                                        role="button"
                                                        class="badge badge-light border variable-badge m-1 p-2"
                                                        @click="
                                                            copiarVariable('${num_docu_medico}')
                                                        "
                                                        title="Clic para copiar"
                                                    >
                                                        <code>${num_docu_medico}</code>
                                                        <span class="text-muted ml-1"
                                                            >Documento del médico</span
                                                        >
                                                    </span>
                                                    <span
                                                        role="button"
                                                        class="badge badge-light border variable-badge m-1 p-2"
                                                        @click="copiarVariable('${firma_medico}')"
                                                        title="Clic para copiar"
                                                    >
                                                        <code>${firma_medico}</code>
                                                        <span class="text-muted ml-1"
                                                            >Imagen firma</span
                                                        >
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Estado Activo/Inactivo -->
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <div class="form-group mb-0">
                                        <div class="checkbox checkbox-success">
                                            <input
                                                type="checkbox"
                                                id="estado"
                                                name="estado"
                                                v-model="registro.estado"
                                                :true-value="1"
                                                :false-value="0"
                                            />
                                            <label for="estado" class="font-weight-bold">
                                                Sede Activa en el Sistema
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Botones de Acción del Formulario -->
                            <div class="form-actions mt-4 pt-3 border-top">
                                <button
                                    type="submit"
                                    class="btn btn-success m-r-10"
                                    v-if="!actualizar"
                                    :disabled="loadingGuardar"
                                    aria-label="Guardar sede"
                                    title="Guardar sede (Ctrl+S)"
                                >
                                    <i
                                        class="fa mr-1"
                                        :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-check'"
                                    ></i>
                                    Guardar (Ctrl+S)
                                </button>

                                <button
                                    type="submit"
                                    class="btn btn-warning m-r-10 text-white"
                                    v-if="actualizar"
                                    :disabled="loadingGuardar"
                                    aria-label="Actualizar sede"
                                    title="Actualizar sede (Ctrl+S)"
                                >
                                    <i
                                        class="fa mr-1"
                                        :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-pencil'"
                                    ></i>
                                    Actualizar (Ctrl+S)
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-inverse"
                                    @click="cerrarSidebar()"
                                    aria-label="Cancelar y cerrar panel"
                                    title="Cancelar y cerrar (Esc)"
                                >
                                    <i class="fa fa-times mr-1"></i>
                                    Cancelar (Esc)
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

<script setup>
/**
 * Componente de Gestión y Configuración de Sedes LIDiag.
 *
 * Flujo del proceso:
 * 1. Consulta las sedes configuradas desde /api/config-sedes e inicializa DataTables de forma segura.
 * 2. Visualiza métricas KPI en tarjetas interactivas (Total, Activas, Inactivas).
 * 3. Gestiona los parámetros de conexión para DCM4CHEE (PACS) y el Visor Web Oviyam.
 * 4. Controla la subida de plantillas Word (.doc, .docx) para Rx/Tomografía/Resonancia y Ecografías.
 * 5. Provee catálogo accesible de variables asistenciales con copiado rápido al portapapeles.
 * 6. Soporta atajos de teclado clínicos (Ctrl+S para guardar, Esc para cerrar) y modo oscuro nativo.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-18
 */
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { alertError, alertSuccess, confirmAction, toastMessage } from "@/helpers/alerts.js";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts";
import axios from "axios";

const dt = ref(null);
const primerInput = ref(null);
const registros = ref([]);
const registro = ref({
    id: null,
    nom_sede: "",
    email_repor: "",
    ip_dcm: "",
    bd_dcm: "",
    puerto_dcm: "",
    usuario_dcm: "",
    password_dcm: "",
    usuario_oviyam: "",
    password_oviyam: "",
    url_oviyam: "",
    tap_oviyam: "",
    plantilla: "",
    plantilla_rx: "",
    plantilla_ecografia: "",
    estado: 1,
});
const tituloModal = ref("Nueva sede");
const actualizar = ref(false);
const plantillaRx = ref(null);
const plantillaEcografia = ref(null);
const errores = ref({});
const loadingListar = ref(false);
const loadingGuardar = ref(false);
const loadingEliminarId = ref(null);
const sidebarVisible = ref(false);
const sidebarRef = ref(null);

// Métricas KPI computadas
const totalSedes = computed(() => registros.value.length);
const totalActivas = computed(() => registros.value.filter((r) => Boolean(r.estado)).length);
const totalInactivas = computed(() => registros.value.filter((r) => !r.estado).length);

// Atajos de teclado clínicos (Ctrl+S para guardar, Esc para cerrar)
useKeyboardShortcuts({
    onSave: () => {
        if (sidebarVisible.value) {
            guardarRegistro();
        }
    },
    onEscape: () => {
        if (sidebarVisible.value) {
            cerrarSidebar();
        }
    },
    isActive: () => sidebarVisible.value,
    isLoading: loadingGuardar,
});

function abrirSidebar() {
    sidebarVisible.value = true;
    nextTick(() => {
        if (primerInput.value) {
            primerInput.value.focus();
        } else {
            document.getElementById("nom_sede")?.focus();
        }
    });
}

function cerrarSidebar() {
    sidebarVisible.value = false;
    errores.value = {};
}

async function ListarDatos() {
    if (loadingListar.value) return;
    loadingListar.value = true;
    try {
        safeDestroyDataTable(dt.value || "#tablaSedes");
        const res = await axios.get("/api/config-sedes");
        registros.value = res.data?.data || res.data || [];
        nextTick(() => {
            safeInitDataTable(dt.value || "#tablaSedes", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
                columnDefs: [{ targets: [-1], orderable: false, searchable: false }],
            });
        });
    } catch (error) {
        registros.value = [];
        toastMessage({
            heading: "Error",
            text: error?.response?.data?.message || "No fue posible cargar las sedes.",
            icon: "error",
        });
    } finally {
        loadingListar.value = false;
    }
}

async function guardarRegistro() {
    if (loadingGuardar.value) return;

    // Validación defensiva en frontend
    if (!registro.value.nom_sede || !registro.value.nom_sede.trim()) {
        errores.value = { nom_sede: ["El nombre de la sede es obligatorio."] };
        toastMessage({
            heading: "Validación",
            text: "Por favor complete el nombre de la sede.",
            icon: "warning",
        });
        primerInput.value?.focus();
        return;
    }

    loadingGuardar.value = true;
    try {
        const formData = new FormData();
        const reg = { ...registro.value };
        if (reg.puerto_dcm === "" || reg.puerto_dcm === null || reg.puerto_dcm === undefined) {
            reg.puerto_dcm = null;
        } else {
            reg.puerto_dcm = Number(reg.puerto_dcm);
        }
        formData.append("registro", JSON.stringify(reg));
        if (registro.value && registro.value.id) {
            formData.append("id", String(registro.value.id));
        }
        if (plantillaRx.value) {
            formData.append("plantilla_rx", plantillaRx.value);
        }
        if (plantillaEcografia.value) {
            formData.append("plantilla_ecografia", plantillaEcografia.value);
        }

        if (actualizar.value === false) {
            const res = await axios.post("/api/config-sedes", formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });

            if (res.status >= 200 && res.status < 300) {
                toastMessage({
                    heading: "Completado",
                    text:
                        res.data?.data?.message ||
                        res.data?.message ||
                        "Sede creada correctamente.",
                    icon: "success",
                });

                ListarDatos();
                cerrarSidebar();
                limpiar();
            }
        } else {
            const res = await axios.post("/config/sedes/updateWitchPlantillas", formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });

            if (res.status >= 200 && res.status < 300) {
                toastMessage({
                    heading: "Completado",
                    text:
                        res.data?.data?.message ||
                        res.data?.message ||
                        "Sede actualizada correctamente.",
                    icon: "success",
                });

                ListarDatos();
                cerrarSidebar();
                limpiar();
            }
        }
    } catch (error) {
        errores.value = error?.response?.data?.errors || {};
        if (error?.response?.status !== 422) {
            toastMessage({
                heading: "Error",
                text: error?.response?.data?.message || "No fue posible guardar la sede.",
                icon: "error",
            });
        } else {
            toastMessage({
                heading: "Validación",
                text: error?.response?.data?.message || "Revise los campos requeridos.",
                icon: "warning",
            });
        }
    } finally {
        loadingGuardar.value = false;
    }
}

async function eliminarRegistro(id) {
    const ok = await confirmAction({
        title: "¿Está seguro?",
        text: "¡No podrá recuperar este registro de sede!",
        icon: "warning",
    });
    if (!ok) return;

    try {
        if (loadingEliminarId.value) return;
        loadingEliminarId.value = id;
        const response = await axios.delete(`/api/config-sedes/${id}`);
        alertSuccess(
            "¡Completado!",
            response?.data?.message || "Sede eliminada correctamente.",
            "success"
        );
        ListarDatos();
    } catch (error) {
        alertError(
            "Error",
            error?.response?.data?.message || "No fue posible eliminar la sede.",
            "error"
        );
    } finally {
        loadingEliminarId.value = null;
    }
}

function obtenerPlantillaRx(e) {
    plantillaRx.value = e.target.files[0] || null;
}

function obtenerPlantillaEcografia(e) {
    plantillaEcografia.value = e.target.files[0] || null;
}

function limpiarPlantillaRx() {
    plantillaRx.value = null;
    const input = document.getElementById("exampleInputFileRx");
    if (input) input.value = "";
}

function limpiarPlantillaEcografia() {
    plantillaEcografia.value = null;
    const input = document.getElementById("exampleInputFileEcografia");
    if (input) input.value = "";
}

function resetFileInputs() {
    limpiarPlantillaRx();
    limpiarPlantillaEcografia();
}

async function copiarVariable(token) {
    try {
        if (navigator?.clipboard?.writeText) {
            await navigator.clipboard.writeText(token);
        } else {
            const textarea = document.createElement("textarea");
            textarea.value = token;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand("copy");
            document.body.removeChild(textarea);
        }
        toastMessage({
            heading: "Copiado",
            text: `Variable ${token} copiada al portapapeles.`,
            icon: "info",
        });
    } catch {
        toastMessage({
            heading: "Aviso",
            text: `Seleccione manualmente: ${token}`,
            icon: "warning",
        });
    }
}

function mostrarRegistro(data = {}) {
    resetFileInputs();
    if (actualizar.value) {
        tituloModal.value = `Actualizar sede: ${data.nom_sede || ""}`;
        registro.value.id = data.id || null;
        registro.value.nom_sede = data.nom_sede || "";
        registro.value.email_repor = data.email_repor || "";
        registro.value.ip_dcm = data.ip_dcm || "";
        registro.value.bd_dcm = data.bd_dcm || "";
        registro.value.puerto_dcm = data.puerto_dcm ?? "";
        registro.value.usuario_dcm = data.usuario_dcm || "";
        registro.value.password_dcm = data.password_dcm || "";
        registro.value.usuario_oviyam = data.usuario_oviyam || "";
        registro.value.password_oviyam = data.password_oviyam || "";
        registro.value.url_oviyam = data.url_oviyam || "";
        registro.value.tap_oviyam = data.tap_oviyam || "";
        registro.value.plantilla = data.plantilla_rx || "";
        registro.value.plantilla_rx = data.plantilla_rx || "";
        registro.value.plantilla_ecografia = data.plantilla_ecografia || "";
        registro.value.estado = data.estado !== undefined ? Number(data.estado) : 1;

        abrirSidebar();
        errores.value = {};
    } else {
        actualizar.value = false;
        tituloModal.value = "Nueva sede";
        registro.value.id = 0;
        registro.value.nom_sede = "";
        registro.value.email_repor = "";
        registro.value.ip_dcm = "";
        registro.value.bd_dcm = "";
        registro.value.puerto_dcm = "";
        registro.value.usuario_dcm = "";
        registro.value.password_dcm = "";
        registro.value.usuario_oviyam = "";
        registro.value.password_oviyam = "";
        registro.value.url_oviyam = "";
        registro.value.tap_oviyam = "";
        registro.value.plantilla = "";
        registro.value.plantilla_rx = "";
        registro.value.plantilla_ecografia = "";
        registro.value.estado = 1;

        abrirSidebar();
        errores.value = {};
    }
}

function limpiar() {
    resetFileInputs();
    actualizar.value = false;
    tituloModal.value = "Nueva sede";
    registro.value.id = 0;
    registro.value.nom_sede = "";
    registro.value.email_repor = "";
    registro.value.ip_dcm = "";
    registro.value.bd_dcm = "";
    registro.value.puerto_dcm = "";
    registro.value.usuario_dcm = "";
    registro.value.password_dcm = "";
    registro.value.usuario_oviyam = "";
    registro.value.password_oviyam = "";
    registro.value.url_oviyam = "";
    registro.value.tap_oviyam = "";
    registro.value.plantilla = "";
    registro.value.plantilla_rx = "";
    registro.value.plantilla_ecografia = "";
    registro.value.estado = 1;

    cerrarSidebar();
    errores.value = {};
}

onMounted(() => {
    ListarDatos();
});

onBeforeUnmount(() => {
    safeDestroyDataTable(dt.value || "#tablaSedes");
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
    backdrop-filter: blur(2px);
    z-index: 1090;
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
    box-shadow: -5px 0 25px rgba(0, 0, 0, 0.15);
    transition:
        right 0.35s cubic-bezier(0.16, 1, 0.3, 1),
        visibility 0.35s;
    visibility: hidden;
    pointer-events: none;
    overflow-y: auto;
}

.shw-rside {
    right: 0px !important;
    width: 80% !important;
    visibility: visible !important;
    pointer-events: auto !important;
}

.variable-badge {
    cursor: pointer;
    transition: all 0.2s ease;
    user-select: none;
}

.variable-badge:hover {
    background-color: #e2e8f0 !important;
    transform: translateY(-1px);
}

:global(body.theme-dark) .right-sidebar {
    background: #181a20 !important;
    color: #e2e8f0 !important;
    border-left: 1px solid #2d3343 !important;
}

:global(body.theme-dark) .variable-badge {
    background-color: #1e222d !important;
    border-color: #2d3343 !important;
    color: #e2e8f0 !important;
}

:global(body.theme-dark) .variable-badge:hover {
    background-color: #2b3240 !important;
}

:global(body.theme-dark) .variable-badge code {
    background-color: #12141a;
    color: #38bdf8;
}

@media (max-width: 767px) {
    .right-sidebar {
        width: 100% !important;
        right: -100% !important;
    }
    .shw-rside {
        width: 100% !important;
        right: 0px !important;
    }
}
</style>
