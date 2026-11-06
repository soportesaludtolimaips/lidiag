<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles mb-3">
            <div class="col-md-6 align-self-center">
                <h3 class="text-themecolor mb-1">
                    <i class="fa fa-stethoscope mr-1"></i> Transcripción Tele-radiología
                </h3>
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
                    <li class="breadcrumb-item active">Tele-radiología Transcribir</li>
                </ol>
            </div>
            <div
                class="col-md-6 align-self-center text-right d-none d-md-flex justify-content-end align-items-center"
            >
                <button
                    type="button"
                    class="btn btn-sm btn-rounded mr-2"
                    :class="isDarkMode ? 'btn-warning' : 'btn-outline-secondary'"
                    @click="toggleDarkMode"
                    :title="isDarkMode ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro'"
                >
                    <i :class="isDarkMode ? 'fa fa-sun-o' : 'fa fa-moon-o'"></i>
                    {{ isDarkMode ? "Modo Claro" : "Modo Oscuro" }}
                </button>
                <button
                    type="button"
                    class="btn btn-sm btn-outline-info btn-rounded"
                    :disabled="loadingBuscar"
                    title="Actualizar listado de estudios"
                    @click="listarPendientesTrascribir()"
                >
                    <i class="fa fa-refresh mr-1" :class="{ 'fa-spin': loadingBuscar }"></i>
                    Refrescar
                </button>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- KPI Summary Cards -->
        <!-- ============================================================== -->
        <div class="row m-b-20" v-if="todosLosRegistros.length > 0">
            <!-- Card 1: Total Pendientes -->
            <div class="col-lg-3 col-md-6 col-sm-6 m-b-15">
                <div
                    class="card kpi-card border-0 shadow-sm h-100 cursor-pointer"
                    :class="{ 'kpi-card-active active-total': filtroRapido === 'todos' }"
                    @click="aplicarFiltroRapido('todos')"
                    title="Ver todos los estudios pendientes"
                >
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-uppercase font-weight-bold font-12"
                                >Total Estudios</span
                            >
                            <h2 class="font-weight-bold text-dark mt-1 mb-0">{{ kpiTotal }}</h2>
                            <small class="text-muted">En bandeja de trabajo</small>
                        </div>
                        <div class="kpi-icon-pill bg-light-primary text-primary">
                            <i class="fa fa-folder-open-o fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Con Lectura TLIMAGEN -->
            <div class="col-lg-3 col-md-6 col-sm-6 m-b-15">
                <div
                    class="card kpi-card border-0 shadow-sm h-100 cursor-pointer"
                    :class="{ 'kpi-card-active active-ready': filtroRapido === 'con_lectura' }"
                    @click="aplicarFiltroRapido('con_lectura')"
                    title="Ver sólo estudios con lectura recibida de TLIMAGEN"
                >
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-success text-uppercase font-weight-bold font-12"
                                >Listos Para Transcribir</span
                            >
                            <h2 class="font-weight-bold text-success mt-1 mb-0">
                                {{ kpiConLectura }}
                            </h2>
                            <small class="text-success font-weight-bold"
                                >Con lectura recibida</small
                            >
                        </div>
                        <div class="kpi-icon-pill bg-light-success text-success">
                            <i class="fa fa-check-circle-o fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Sin Lectura / En Espera -->
            <div class="col-lg-3 col-md-6 col-sm-6 m-b-15">
                <div
                    class="card kpi-card border-0 shadow-sm h-100 cursor-pointer"
                    :class="{ 'kpi-card-active active-pending': filtroRapido === 'sin_lectura' }"
                    @click="aplicarFiltroRapido('sin_lectura')"
                    title="Ver estudios en espera de lectura en TLIMAGEN"
                >
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-warning text-uppercase font-weight-bold font-12"
                                >En Espera de Tele</span
                            >
                            <h2 class="font-weight-bold text-warning mt-1 mb-0">
                                {{ kpiSinLectura }}
                            </h2>
                            <small class="text-muted">Pendientes de reporte</small>
                        </div>
                        <div class="kpi-icon-pill bg-light-warning text-warning">
                            <i class="fa fa-clock-o fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 4: Urgencias / Alta Prioridad -->
            <div class="col-lg-3 col-md-6 col-sm-6 m-b-15">
                <div
                    class="card kpi-card border-0 shadow-sm h-100 cursor-pointer"
                    :class="{ 'kpi-card-active active-urgent': filtroRapido === 'urgente' }"
                    @click="aplicarFiltroRapido('urgente')"
                    title="Ver estudios con prioridad Alta"
                >
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-danger text-uppercase font-weight-bold font-12"
                                >Prioridad Alta</span
                            >
                            <h2 class="font-weight-bold text-danger mt-1 mb-0">
                                {{ kpiUrgentes }}
                            </h2>
                            <small class="text-danger font-weight-bold">Atención prioritaria</small>
                        </div>
                        <div class="kpi-icon-pill bg-light-danger text-danger">
                            <i class="fa fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- Filtros de Búsqueda -->
        <!-- ============================================================== -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title text-themecolor mb-0 font-16">
                                <i class="fa fa-filter mr-1"></i> Filtros de Búsqueda
                            </h4>
                            <div class="btn-group btn-group-sm">
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    @click="setFiltroFecha('hoy')"
                                    title="Estudios de hoy"
                                >
                                    Hoy
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    @click="setFiltroFecha('7dias')"
                                    title="Últimos 7 días"
                                >
                                    Últimos 7 días
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    @click="setFiltroFecha('mes')"
                                    title="Este mes"
                                >
                                    Este mes
                                </button>
                            </div>
                        </div>

                        <div class="form-body">
                            <div class="row">
                                <div class="col-lg-3 col-md-6 mb-2">
                                    <div class="form-group mb-1">
                                        <label
                                            class="control-label font-weight-bold small text-muted mb-1"
                                        >
                                            <i class="fa fa-user-plus mr-1"></i> Quien Asignó
                                        </label>
                                        <select
                                            v-model="busqueda.quien_asigno_id"
                                            name="quien_asigno_id"
                                            id="quien_asigno_id"
                                            class="form-control custom-select form-control-sm"
                                        >
                                            <option value="">
                                                ...::: Todos los usuarios :::...
                                            </option>
                                            <option
                                                v-for="item in quienes_asignaron"
                                                :key="item.id"
                                                :value="item.id"
                                            >
                                                {{ item.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6 mb-2">
                                    <div class="form-group mb-1">
                                        <label
                                            class="control-label font-weight-bold small text-muted mb-1"
                                        >
                                            <i class="fa fa-user mr-1"></i> Paciente / Documento
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <input
                                                type="text"
                                                id="bus_nom_num_docu"
                                                name="bus_nom_num_docu"
                                                v-model="busqueda.bus_nom_num_docu"
                                                class="form-control"
                                                placeholder="Nombres o documento..."
                                                @keyup.enter="listarPendientesTrascribir()"
                                            />
                                            <div
                                                class="input-group-append"
                                                v-if="busqueda.bus_nom_num_docu"
                                            >
                                                <button
                                                    class="btn btn-outline-secondary"
                                                    type="button"
                                                    @click="
                                                        busqueda.bus_nom_num_docu = '';
                                                        listarPendientesTrascribir();
                                                    "
                                                    title="Limpiar búsqueda rápida"
                                                >
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-6 mb-2">
                                    <div class="form-group mb-1">
                                        <label
                                            class="control-label font-weight-bold small text-muted mb-1"
                                        >
                                            <i class="fa fa-calendar mr-1"></i> Fecha Inicio
                                        </label>
                                        <input
                                            type="date"
                                            id="bus_fech_ini"
                                            name="bus_fech_ini"
                                            v-model="busqueda.bus_fech_ini"
                                            class="form-control form-control-sm"
                                        />
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-6 mb-2">
                                    <div class="form-group mb-1">
                                        <label
                                            class="control-label font-weight-bold small text-muted mb-1"
                                        >
                                            <i class="fa fa-calendar mr-1"></i> Fecha Fin
                                        </label>
                                        <input
                                            type="date"
                                            id="bus_fecha_fin"
                                            name="bus_fecha_fin"
                                            v-model="busqueda.bus_fecha_fin"
                                            class="form-control form-control-sm"
                                        />
                                    </div>
                                </div>
                                <div class="col-lg-1 col-md-12 mb-2 d-flex align-items-end">
                                    <div class="btn-group w-100">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-success"
                                            :disabled="loadingBuscar"
                                            @click="listarPendientesTrascribir()"
                                            title="Buscar estudios"
                                        >
                                            <i
                                                v-if="loadingBuscar"
                                                class="fa fa-spinner fa-spin"
                                            ></i>
                                            <i v-else class="fa fa-search"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-secondary"
                                            @click="btnLimpiar()"
                                            title="Limpiar filtros"
                                        >
                                            <i class="fa fa-eraser"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- Tabla de Resultados -->
        <!-- ============================================================== -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <!-- Header de la tabla con chips de estado -->
                        <div
                            class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom"
                        >
                            <div class="mb-2 mb-md-0">
                                <h4 class="card-title mb-0 font-weight-bold">
                                    <i class="fa fa-list-alt text-primary mr-1"></i> Estudios
                                    Pendientes de Transcripción
                                </h4>
                                <small class="text-muted">
                                    Revise la lectura enviada desde TLIMAGEN y complete la
                                    transcripción oficial.
                                </small>
                            </div>

                            <!-- Chips de filtro rápido -->
                            <div
                                class="quick-filter-chips btn-group btn-group-sm"
                                v-if="todosLosRegistros.length > 0"
                            >
                                <button
                                    type="button"
                                    class="btn"
                                    :class="
                                        filtroRapido === 'todos'
                                            ? 'btn-secondary text-white'
                                            : 'btn-outline-secondary'
                                    "
                                    @click="aplicarFiltroRapido('todos')"
                                >
                                    Todos <span class="badge badge-light ml-1">{{ kpiTotal }}</span>
                                </button>
                                <button
                                    type="button"
                                    class="btn"
                                    :class="
                                        filtroRapido === 'con_lectura'
                                            ? 'btn-success text-white'
                                            : 'btn-outline-success'
                                    "
                                    @click="aplicarFiltroRapido('con_lectura')"
                                >
                                    <i class="fa fa-check-circle mr-1"></i> Listos
                                    <span class="badge badge-light ml-1">{{ kpiConLectura }}</span>
                                </button>
                                <button
                                    type="button"
                                    class="btn"
                                    :class="
                                        filtroRapido === 'sin_lectura'
                                            ? 'btn-warning text-dark font-weight-bold'
                                            : 'btn-outline-warning text-dark'
                                    "
                                    @click="aplicarFiltroRapido('sin_lectura')"
                                >
                                    <i class="fa fa-clock-o mr-1"></i> En Espera
                                    <span class="badge badge-light ml-1">{{ kpiSinLectura }}</span>
                                </button>
                                <button
                                    type="button"
                                    class="btn"
                                    :class="
                                        filtroRapido === 'urgente'
                                            ? 'btn-danger text-white'
                                            : 'btn-outline-danger'
                                    "
                                    @click="aplicarFiltroRapido('urgente')"
                                >
                                    <i class="fa fa-fire mr-1"></i> Urgentes
                                    <span class="badge badge-light ml-1">{{ kpiUrgentes }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Skeleton Loader -->
                        <table-skeleton-loader v-if="loadingBuscar" :rows="6" :columns="10" />

                        <!-- Empty State -->
                        <div v-else-if="registros.length === 0" class="m-t-20 py-4">
                            <empty-state-component
                                title="No hay estudios pendientes"
                                description="No se encontraron estudios para transcribir con los filtros seleccionados."
                                icon="fa-file-text-o"
                            />
                        </div>

                        <!-- Tabla Principal -->
                        <div v-else class="table-responsive m-t-10">
                            <table
                                id="tablaTeleRadiologiaTranscribir"
                                ref="dt"
                                class="table table-hover table-bordered table-striped"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 75px" class="text-center">Prioridad</th>
                                        <th style="width: 130px">Estado TL</th>
                                        <th>Paciente</th>
                                        <th>Estudio / Procedimiento</th>
                                        <th>Sede / Toma</th>
                                        <th>Asignación</th>
                                        <th>Médico</th>
                                        <th style="width: 140px" class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in registros"
                                        :key="item.estudio_producto_id"
                                        :id="'row-' + item.estudio_producto_id"
                                        :class="{ 'row-ready': item.fechor_lectura != null }"
                                    >
                                        <!-- Prioridad -->
                                        <td class="text-center align-middle">
                                            <span
                                                v-if="item.priori_id == 1"
                                                class="badge badge-danger text-uppercase px-2 py-1 font-weight-bold"
                                                title="Prioridad Alta / Urgente"
                                            >
                                                <i class="fa fa-fire mr-1"></i> Alta
                                            </span>
                                            <span
                                                v-else-if="item.priori_id == 2"
                                                class="badge badge-warning text-dark text-uppercase px-2 py-1 font-weight-bold"
                                                title="Prioridad Media"
                                            >
                                                <i class="fa fa-clock-o mr-1"></i> Media
                                            </span>
                                            <span
                                                v-else-if="item.priori_id == 3"
                                                class="badge badge-info text-uppercase px-2 py-1"
                                                title="Prioridad Baja"
                                            >
                                                <i class="fa fa-info-circle mr-1"></i> Baja
                                            </span>
                                            <span
                                                v-else
                                                class="badge badge-light text-muted text-uppercase px-2 py-1 border"
                                                title="Prioridad Normal"
                                            >
                                                Normal
                                            </span>
                                        </td>

                                        <!-- Estado Lectura TLIMAGEN -->
                                        <td class="align-middle">
                                            <div v-if="item.fechor_lectura != null">
                                                <span
                                                    class="badge badge-success px-2 py-1 d-inline-flex align-items-center"
                                                >
                                                    <i class="fa fa-check-circle mr-1"></i> Con
                                                    Lectura
                                                </span>
                                                <small class="d-block text-muted font-11 mt-1">
                                                    <i class="fa fa-calendar-check-o mr-1"></i
                                                    >{{ item.fechor_lectura }}
                                                </small>
                                            </div>
                                            <div v-else>
                                                <span
                                                    class="badge badge-warning text-dark px-2 py-1 d-inline-flex align-items-center"
                                                >
                                                    <i class="fa fa-hourglass-half mr-1"></i> En
                                                    Espera
                                                </span>
                                                <small class="d-block text-muted font-11 mt-1">
                                                    Sin reporte de Tele
                                                </small>
                                            </div>
                                        </td>

                                        <!-- Paciente -->
                                        <td class="align-middle">
                                            <div class="font-weight-bold text-dark font-14">
                                                {{ item.nom_pacien }}
                                            </div>
                                            <div class="text-muted small mt-1">
                                                <span
                                                    class="badge badge-light border text-muted mr-1"
                                                >
                                                    <i class="fa fa-id-card-o mr-1"></i
                                                    >{{ item.num_docu }}
                                                </span>
                                                <span
                                                    v-if="item.sexo || item.pat_sex"
                                                    class="badge badge-light border text-muted"
                                                >
                                                    {{ item.sexo || item.pat_sex }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Estudio / Detalle -->
                                        <td class="align-middle">
                                            <div class="font-weight-bold text-primary font-13 mb-1">
                                                {{ item.nom_produc }}
                                            </div>
                                            <div class="small text-muted">
                                                <span
                                                    v-if="item.cod_cups"
                                                    class="badge badge-light border mr-1 font-weight-normal"
                                                >
                                                    CUPS: {{ item.cod_cups }}
                                                </span>
                                                <span
                                                    v-if="item.accession_no"
                                                    class="badge badge-light border mr-1 font-weight-normal"
                                                >
                                                    Acc: {{ item.accession_no }}
                                                </span>
                                            </div>
                                            <small
                                                v-if="item.study_desc"
                                                class="d-block text-muted mt-1 text-truncate"
                                                style="max-width: 250px"
                                            >
                                                {{ item.study_desc }}
                                            </small>
                                        </td>

                                        <!-- Sede y Toma -->
                                        <td class="align-middle small">
                                            <div class="font-weight-bold text-dark mb-1">
                                                <i class="fa fa-hospital-o text-muted mr-1"></i
                                                >{{ item.nom_sede }}
                                            </div>
                                            <div class="text-muted">
                                                <i class="fa fa-calendar-o mr-1"></i
                                                >{{ item.fec_estudio }}
                                            </div>
                                        </td>

                                        <!-- Asignación -->
                                        <td class="align-middle small">
                                            <div class="font-weight-bold text-dark mb-1">
                                                <i class="fa fa-user-plus text-muted mr-1"></i
                                                >{{ item.quien_registro }}
                                            </div>
                                            <div class="text-muted font-11">
                                                {{ formatDate(item.created_at) }}
                                            </div>
                                        </td>

                                        <!-- Médico -->
                                        <td class="align-middle small">
                                            <div
                                                v-if="item.nombres_medico"
                                                class="text-dark font-weight-bold"
                                            >
                                                <i class="fa fa-user-md text-info mr-1"></i
                                                >{{ item.nombres_medico }}
                                            </div>
                                            <div v-else class="text-muted font-italic">
                                                Por asignar tras lectura
                                            </div>
                                        </td>

                                        <!-- Acciones -->
                                        <td class="text-center align-middle text-nowrap">
                                            <!-- Botón Transcribir -->
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-rounded m-r-5 shadow-sm"
                                                :class="
                                                    item.fechor_lectura != null
                                                        ? 'btn-success'
                                                        : 'btn-info'
                                                "
                                                :disabled="
                                                    loadingAbrirId === item.estudio_producto_id
                                                "
                                                title="Abrir editor de transcripción"
                                                @click="
                                                    actualizar = true;
                                                    mostrarRegistro(item);
                                                "
                                            >
                                                <i
                                                    v-if="
                                                        loadingAbrirId === item.estudio_producto_id
                                                    "
                                                    class="fa fa-spinner fa-spin"
                                                ></i>
                                                <template v-else>
                                                    <i class="fa fa-pencil mr-1"></i> Transcribir
                                                </template>
                                            </button>

                                            <!-- Botón Visor DICOM Directo -->
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary btn-rounded m-r-5"
                                                title="Abrir visor DICOM de imágenes"
                                                @click="verImagenDirecta(item)"
                                            >
                                                <i class="fa fa-file-image-o"></i>
                                            </button>

                                            <!-- Botón Anular Asignación -->
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger btn-rounded"
                                                :disabled="
                                                    loadingAnularId === item.estudio_producto_id
                                                "
                                                title="Anular asignación de este estudio"
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
                                                <i v-else class="fa fa-trash-o"></i>
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
        <!-- Backdrop para cerrar al hacer clic y atenuar fondo -->
        <!-- ============================================================== -->
        <transition name="fade">
            <div
                v-if="sidebarVisible"
                class="sidebar-backdrop"
                @click="btnCerrarModal()"
                aria-hidden="true"
            ></div>
        </transition>

        <!-- ============================================================== -->
        <!-- Right Sidebar - Panel Deslizante de Transcripción -->
        <!-- ============================================================== -->
        <div
            class="right-sidebar"
            :class="{ 'shw-rside': sidebarVisible, 'dark-mode': isDarkMode, 'zen-mode': isZenMode }"
        >
            <div class="slimscrollright d-flex flex-column h-100">
                <!-- Header del Panel -->
                <div
                    class="rpanel-title d-flex justify-content-between align-items-center px-4 py-3 border-bottom shadow-sm"
                >
                    <div class="d-flex align-items-center text-truncate mr-3">
                        <i class="fa fa-stethoscope text-white fa-lg mr-2"></i>
                        <span class="font-weight-bold font-16 text-white text-truncate">{{
                            tituloModal
                        }}</span>
                    </div>
                    <div class="d-flex align-items-center flex-shrink-0">
                        <!-- Botón Visor DICOM -->
                        <button
                            type="button"
                            class="btn btn-xs btn-outline-light mr-2 font-weight-bold"
                            :disabled="loadingVerImagen"
                            @click="verImagen()"
                            title="Abrir visor de imágenes PACS / DICOM"
                            style="padding: 3px 10px; font-size: 11px; border-radius: 12px"
                        >
                            <i v-if="loadingVerImagen" class="fa fa-spinner fa-spin mr-1"></i>
                            <i v-else class="fa fa-file-image-o mr-1"></i>
                            Visor DICOM
                        </button>

                        <!-- Botón Zen / Pantalla Completa -->
                        <button
                            type="button"
                            class="btn btn-xs btn-outline-light mr-2"
                            @click="toggleZenMode"
                            :title="
                                isZenMode
                                    ? 'Salir de Pantalla Completa / Modo Zen'
                                    : 'Modo Pantalla Completa / Modo Zen'
                            "
                            style="padding: 3px 10px; font-size: 11px; border-radius: 12px"
                        >
                            <i :class="isZenMode ? 'fa fa-compress' : 'fa fa-expand'"></i>
                            {{ isZenMode ? "Normal" : "Zen" }}
                        </button>

                        <!-- Botón Modo Oscuro -->
                        <button
                            type="button"
                            class="btn btn-xs btn-outline-light mr-3"
                            @click="toggleDarkMode"
                            :title="isDarkMode ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro'"
                            style="padding: 3px 10px; font-size: 11px; border-radius: 12px"
                        >
                            <i
                                :class="isDarkMode ? 'fa fa-sun-o text-warning' : 'fa fa-moon-o'"
                            ></i>
                            {{ isDarkMode ? "Claro" : "Oscuro" }}
                        </button>

                        <!-- Botón Cerrar -->
                        <button
                            type="button"
                            class="right-side-toggle text-white border-0 bg-transparent p-0 cursor-pointer"
                            @click="btnCerrarModal()"
                            aria-label="Cerrar panel"
                            title="Cerrar panel (Esc)"
                        >
                            <i class="ti-close font-16"></i>
                        </button>
                    </div>
                </div>

                <!-- Cuerpo del Panel Deslizante -->
                <div class="r-panel-body p-4 flex-grow-1 overflow-auto">
                    <form @submit.prevent>
                        <div class="row">
                            <!-- Columna Izquierda: Datos Clínicos y del Paciente -->
                            <div class="col-lg-5 col-md-12 mb-4">
                                <!-- Componente de Información del Paciente -->
                                <form-paciente :registro="registro" :errores="errores" />

                                <!-- Card Resumen del Estudio -->
                                <div class="card border mt-3 shadow-none clinical-card">
                                    <div
                                        class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center"
                                    >
                                        <h6 class="mb-0 font-weight-bold text-dark font-13">
                                            <i class="fa fa-info-circle text-info mr-1"></i>
                                            Detalles del Estudio
                                        </h6>
                                        <span
                                            v-if="registro.fec_lectura"
                                            class="badge badge-success"
                                        >
                                            Lectura recibida
                                        </span>
                                        <span v-else class="badge badge-warning text-dark">
                                            En espera de lectura
                                        </span>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row">
                                            <div class="col-sm-6 mb-2">
                                                <small class="text-muted font-weight-bold d-block"
                                                    >Procedimiento / Producto:</small
                                                >
                                                <span class="font-weight-bold text-primary">{{
                                                    registro.nom_produc || "N/A"
                                                }}</span>
                                            </div>
                                            <div class="col-sm-6 mb-2">
                                                <small class="text-muted font-weight-bold d-block"
                                                    >CUPS:</small
                                                >
                                                <span>{{ registro.cod_cups || "N/A" }}</span>
                                            </div>
                                            <div class="col-sm-6 mb-2">
                                                <small class="text-muted font-weight-bold d-block"
                                                    >Accession Number:</small
                                                >
                                                <code>{{ registro.accession_no || "N/A" }}</code>
                                            </div>
                                            <div class="col-sm-6 mb-2">
                                                <small class="text-muted font-weight-bold d-block"
                                                    >Fecha del Estudio:</small
                                                >
                                                <span>{{ registro.fec_estudio || "N/A" }}</span>
                                            </div>
                                            <div class="col-12 mb-2" v-if="registro.study_desc">
                                                <small class="text-muted font-weight-bold d-block"
                                                    >Descripción / Región:</small
                                                >
                                                <span class="text-muted">{{
                                                    registro.study_desc
                                                }}</span>
                                            </div>
                                        </div>

                                        <!-- Observaciones del Estudio -->
                                        <div
                                            v-if="registro.observaciones"
                                            class="alert alert-warning py-2 px-3 mt-2 mb-0 small"
                                        >
                                            <strong class="d-block"
                                                ><i class="fa fa-commenting-o mr-1"></i>
                                                Observaciones clínicas:</strong
                                            >
                                            {{ registro.observaciones }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Email para Reporte -->
                                <div class="form-group mt-3">
                                    <label class="control-label font-weight-bold small text-muted">
                                        <i class="fa fa-envelope-o mr-1"></i> Reportar copia al
                                        Email:
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"
                                                ><i class="fa fa-at"></i
                                            ></span>
                                        </div>
                                        <input
                                            type="email"
                                            id="email_reportar"
                                            name="email_reportar"
                                            v-model="registro.email_reportar"
                                            class="form-control"
                                            placeholder="correo@ejemplo.com"
                                        />
                                    </div>
                                    <small class="form-text text-muted"
                                        >Si se especifica, se enviará una notificación con el
                                        resultado.</small
                                    >
                                </div>
                            </div>

                            <!-- Columna Derecha: Editor de Lectura y Transcripción -->
                            <div class="col-lg-7 col-md-12">
                                <div class="card border shadow-none editor-card">
                                    <!-- Toolbar superior del Editor -->
                                    <div
                                        class="card-header bg-light py-2 px-3 d-flex flex-wrap justify-content-between align-items-center"
                                    >
                                        <div class="d-flex align-items-center">
                                            <h5
                                                class="mb-0 font-weight-bold text-dark font-14 mr-3"
                                            >
                                                <i class="fa fa-edit text-primary mr-1"></i> Informe
                                                de Lectura
                                            </h5>
                                            <span
                                                class="badge badge-light border text-muted small mr-2"
                                            >
                                                {{ conteoPalabras }} palabras
                                            </span>
                                            <span class="badge badge-light border text-muted small">
                                                {{ conteoCaracteres }} caracteres
                                            </span>
                                        </div>
                                        <div class="btn-group btn-group-sm mt-1 mt-sm-0">
                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary"
                                                @click="copiarLectura"
                                                title="Copiar contenido de la lectura al portapapeles"
                                            >
                                                <i
                                                    class="fa"
                                                    :class="
                                                        copiadoExitoso
                                                            ? 'fa-check text-success'
                                                            : 'fa-clipboard'
                                                    "
                                                ></i>
                                                {{ copiadoExitoso ? "Copiado!" : "Copiar" }}
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary"
                                                @click="limpiarLectura"
                                                title="Limpiar texto del informe"
                                            >
                                                <i class="fa fa-eraser"></i> Limpiar
                                            </button>
                                        </div>
                                    </div>

                                    <div class="card-body p-3">
                                        <!-- Error de validación -->
                                        <div
                                            v-if="errores.lectura"
                                            class="alert alert-danger py-2 px-3 small mb-2"
                                        >
                                            <i class="fa fa-exclamation-triangle mr-1"></i>
                                            {{ errores.lectura[0] }}
                                        </div>

                                        <!-- Identificación del Médico Informante -->
                                        <div
                                            class="mb-2 text-muted small d-flex justify-content-between align-items-center"
                                        >
                                            <span>
                                                <i class="fa fa-user-md text-info mr-1"></i>
                                                <strong>{{
                                                    registro.nombres_medico ||
                                                    "Médico no especificado"
                                                }}</strong>
                                            </span>
                                            <span
                                                v-if="registro.fec_lectura"
                                                class="text-success font-weight-bold"
                                            >
                                                <i class="fa fa-clock-o mr-1"></i> Recibido:
                                                {{ registro.fec_lectura }}
                                            </span>
                                        </div>

                                        <!-- Textarea Principal de Transcripción -->
                                        <div class="form-group mb-0">
                                            <textarea
                                                v-model="registro.lectura"
                                                class="form-control clinical-textarea"
                                                rows="18"
                                                id="lectura"
                                                placeholder="Ingrese aquí la lectura del estudio o verifique el texto enviado por el radiólogo..."
                                                spellcheck="true"
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tabs de Archivos Adjuntos y Audios de Dictado -->
                                <div class="card border shadow-none mt-3">
                                    <div class="card-header bg-light p-0 border-bottom">
                                        <ul
                                            class="nav nav-tabs customtab border-bottom-0"
                                            role="tablist"
                                        >
                                            <li class="nav-item">
                                                <a
                                                    class="nav-link active font-weight-bold py-2 px-3"
                                                    data-toggle="tab"
                                                    href="#tabAdjuntos"
                                                    role="tab"
                                                >
                                                    <i class="fa fa-paperclip text-info mr-1"></i>
                                                    Adjuntos de Historia Clínica
                                                    <span class="badge badge-info ml-1">{{
                                                        soportesHC.length
                                                    }}</span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a
                                                    class="nav-link font-weight-bold py-2 px-3"
                                                    data-toggle="tab"
                                                    href="#tabAudios"
                                                    role="tab"
                                                >
                                                    <i class="fa fa-microphone text-info mr-1"></i>
                                                    Audios de Dictado
                                                    <span class="badge badge-info ml-1">{{
                                                        audiosDeEstudio.length
                                                    }}</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="card-body p-3">
                                        <div class="tab-content">
                                            <!-- Tab Adjuntos -->
                                            <div
                                                class="tab-pane active"
                                                id="tabAdjuntos"
                                                role="tabpanel"
                                            >
                                                <div
                                                    v-if="soportesHC.length > 0"
                                                    class="list-group list-group-flush"
                                                >
                                                    <div
                                                        v-for="itemArchivo in soportesHC"
                                                        :key="itemArchivo.archivo_encrip"
                                                        class="list-group-item px-2 py-2 d-flex justify-content-between align-items-center"
                                                    >
                                                        <div
                                                            class="d-flex align-items-center text-truncate mr-2"
                                                        >
                                                            <i
                                                                class="fa fa-file-pdf-o text-danger mr-2 fa-lg"
                                                            ></i>
                                                            <span class="text-truncate font-13">{{
                                                                itemArchivo.archivo_original
                                                            }}</span>
                                                        </div>
                                                        <button
                                                            type="button"
                                                            class="btn btn-xs btn-outline-primary btn-rounded flex-shrink-0"
                                                            :disabled="
                                                                loadingDescargaSoporte ===
                                                                itemArchivo.archivo_encrip
                                                            "
                                                            @click="
                                                                descargaSoportte(
                                                                    itemArchivo.archivo_original,
                                                                    itemArchivo.archivo_encrip
                                                                )
                                                            "
                                                            title="Descargar documento adjunto"
                                                        >
                                                            <i
                                                                v-if="
                                                                    loadingDescargaSoporte ===
                                                                    itemArchivo.archivo_encrip
                                                                "
                                                                class="fa fa-spinner fa-spin mr-1"
                                                            ></i>
                                                            <i
                                                                v-else
                                                                class="fa fa-download mr-1"
                                                            ></i>
                                                            Descargar
                                                        </button>
                                                    </div>
                                                </div>
                                                <div
                                                    v-else
                                                    class="text-center text-muted py-3 small"
                                                >
                                                    <i
                                                        class="fa fa-paperclip fa-2x text-muted mb-2 d-block"
                                                    ></i>
                                                    No hay archivos adjuntos para este estudio.
                                                </div>
                                            </div>

                                            <!-- Tab Audios -->
                                            <div class="tab-pane" id="tabAudios" role="tabpanel">
                                                <div v-if="audiosDeEstudio.length > 0">
                                                    <div
                                                        v-for="(item, idx) in audiosDeEstudio"
                                                        :key="item.id || idx"
                                                        class="audio-player-wrapper p-2 mb-2 bg-light rounded border"
                                                    >
                                                        <div class="d-flex align-items-center mb-1">
                                                            <i
                                                                class="fa fa-volume-up text-primary mr-2"
                                                            ></i>
                                                            <small
                                                                class="font-weight-bold text-dark"
                                                                >Audio #{{ idx + 1 }}</small
                                                            >
                                                        </div>
                                                        <audio
                                                            :src="item.url"
                                                            controls
                                                            class="w-100"
                                                            style="height: 36px"
                                                        ></audio>
                                                    </div>
                                                </div>
                                                <div
                                                    v-else
                                                    class="text-center text-muted py-3 small"
                                                >
                                                    <i
                                                        class="fa fa-microphone-slash fa-2x text-muted mb-2 d-block"
                                                    ></i>
                                                    No hay grabaciones de audio asociadas a este
                                                    estudio.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Barra de Acciones Inferior Sticky -->
                        <div
                            class="drawer-footer-actions mt-4 pt-3 border-top d-flex justify-content-between align-items-center"
                        >
                            <button
                                type="button"
                                class="btn btn-outline-secondary px-3"
                                @click="btnCerrarModal()"
                                title="Cerrar panel (Esc)"
                            >
                                <i class="fa fa-times mr-1"></i> Cancelar (Esc)
                            </button>

                            <div class="d-flex align-items-center">
                                <button
                                    type="button"
                                    class="btn btn-success btn-lg px-4 shadow font-weight-bold"
                                    @click="guardarRegistro()"
                                    :disabled="loadingGuardar"
                                    :title="
                                        (actualizar ? 'Actualizar' : 'Guardar') +
                                        ' transcripción (Ctrl+S)'
                                    "
                                >
                                    <i v-if="loadingGuardar" class="fa fa-spinner fa-spin mr-1"></i>
                                    <i v-else class="fa fa-check mr-1"></i>
                                    {{
                                        actualizar
                                            ? "Actualizar Transcripción"
                                            : "Guardar Transcripción"
                                    }}
                                    <kbd
                                        class="ml-2 small bg-dark text-white font-weight-normal border-0"
                                        >Ctrl+S</kbd
                                    >
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- Fin Right Sidebar -->
        <!-- ============================================================== -->
    </div>
</template>

<script setup>
import { computed, getCurrentInstance, nextTick, onBeforeUnmount, onMounted, ref } from "vue";

import FormPaciente from "@/components/comunes/formPacienteComponent.vue";
import { listarUsuariosYSedes } from "../../../helpers/usuarios";
import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import timezone from "dayjs/plugin/timezone";
dayjs.extend(utc);
dayjs.extend(timezone);
import axios from "axios";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables";
import { confirmAction, getUserSafeError, toastMessage } from "@/helpers/alerts";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts.js";

const { appContext } = getCurrentInstance();
const emitter = appContext.config.globalProperties.emitter;

const DESTINO_TELE_RADIOLOGIA = "Tele Radiologia";

defineProps({
    usuarioactual: { type: Object, default: () => ({}) },
});

const dt = ref(null);
const actualizar = ref(false);
const sidebarVisible = ref(false);

const isDarkMode = ref(localStorage.getItem("lidiag_dark_mode") === "true");

function toggleDarkMode() {
    isDarkMode.value = !isDarkMode.value;
    localStorage.setItem("lidiag_dark_mode", isDarkMode.value ? "true" : "false");
}

const isZenMode = ref(false);

function toggleZenMode() {
    isZenMode.value = !isZenMode.value;
}

const soportesHC = ref([]);
const todosLosRegistros = ref([]);
const registros = ref([]);
const filtroRapido = ref("todos");
const copiadoExitoso = ref(false);
const tituloModal = ref("Transcribir estudio");

const registro = ref({
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
    destino_estudio: DESTINO_TELE_RADIOLOGIA,
});

const busqueda = ref({
    estudioParaTranscibir: "si",
    quien_asigno_id: "",
    medico_id: "",
    sede_id: "",
    bus_nom_num_docu: "",
    bus_fech_ini: "",
    bus_fecha_fin: "",
    destino_estudio: DESTINO_TELE_RADIOLOGIA,
});

const datosImagen = ref({ urlOviyam: "", patientId: "", studyUID: "", serverName: "" });
const audiosDeEstudio = ref([]);
const quienes_asignaron = ref([]);
const medicos = ref([]);
const errores = ref({});
const id = ref(null);
const idRow = ref(null);

const loadingBuscar = ref(false);
const loadingGuardar = ref(false);
const loadingAbrirId = ref(null);
const loadingAnularId = ref(null);
const loadingDescargaSoporte = ref(null);
const loadingVerImagen = ref(false);

// KPIs calculados
const kpiTotal = computed(() => todosLosRegistros.value.length);
const kpiConLectura = computed(
    () => todosLosRegistros.value.filter((x) => x.fechor_lectura != null).length
);
const kpiSinLectura = computed(
    () => todosLosRegistros.value.filter((x) => x.fechor_lectura === null).length
);
const kpiUrgentes = computed(() => todosLosRegistros.value.filter((x) => x.priori_id == 1).length);

// Contadores de texto para el editor
const conteoCaracteres = computed(() => (registro.value.lectura || "").length);
const conteoPalabras = computed(() => {
    const txt = (registro.value.lectura || "").trim();
    if (!txt) return 0;
    return txt.split(/\s+/).length;
});

function initDataTable() {
    safeInitDataTable(dt.value || "#tablaTeleRadiologiaTranscribir", {
        dom: "Bfrtip",
        buttons: ["copy", "csv", "excel", "pdf", "print"],
        retrieve: true,
    });
}

function destroyDataTable() {
    safeDestroyDataTable(dt.value || "#tablaTeleRadiologiaTranscribir");
}

function aplicarFiltroRapido(tipo = "todos") {
    filtroRapido.value = tipo;
    destroyDataTable();

    if (tipo === "con_lectura") {
        registros.value = todosLosRegistros.value.filter((x) => x.fechor_lectura != null);
    } else if (tipo === "sin_lectura") {
        registros.value = todosLosRegistros.value.filter((x) => x.fechor_lectura === null);
    } else if (tipo === "urgente") {
        registros.value = todosLosRegistros.value.filter((x) => x.priori_id == 1);
    } else {
        registros.value = [...todosLosRegistros.value];
    }

    nextTick(() => {
        initDataTable();
    });
}

function setFiltroFecha(tipo) {
    const hoy = dayjs().format("YYYY-MM-DD");
    if (tipo === "hoy") {
        busqueda.value.bus_fech_ini = hoy;
        busqueda.value.bus_fecha_fin = hoy;
    } else if (tipo === "7dias") {
        busqueda.value.bus_fech_ini = dayjs().subtract(7, "day").format("YYYY-MM-DD");
        busqueda.value.bus_fecha_fin = hoy;
    } else if (tipo === "mes") {
        busqueda.value.bus_fech_ini = dayjs().startOf("month").format("YYYY-MM-DD");
        busqueda.value.bus_fecha_fin = hoy;
    }
    listarPendientesTrascribir();
}

function btnLimpiar() {
    busqueda.value.quien_asigno_id = "";
    busqueda.value.medico_id = "";
    busqueda.value.bus_nom_num_docu = "";
    busqueda.value.bus_fech_ini = "";
    busqueda.value.bus_fecha_fin = "";
    errores.value = {};
    destroyDataTable();
    todosLosRegistros.value = [];
    registros.value = [];
    filtroRapido.value = "todos";
    btnCerrarModal();
}

async function listarPendientesTrascribir() {
    if (loadingBuscar.value) return;
    loadingBuscar.value = true;
    try {
        if (!sessionStorage.getItem("ST-sede")) {
            toastMessage({
                heading: "Upsss!!!",
                text: "Debes establecer la sede en la cual vas a trabajar.",
                icon: "warning",
            });
            return;
        }

        destroyDataTable();

        const res = await axios.post("/api/estudio-listarPendientesTrascribir", {
            sede_id: sessionStorage.getItem("ST-sede"),
            quien_asigno_id: busqueda.value.quien_asigno_id || undefined,
            medico_id: busqueda.value.medico_id || undefined,
            bus_nom_num_docu: busqueda.value.bus_nom_num_docu || undefined,
            bus_fech_ini: busqueda.value.bus_fech_ini || undefined,
            bus_fecha_fin: busqueda.value.bus_fecha_fin || undefined,
            destino_estudio: DESTINO_TELE_RADIOLOGIA,
            estudioParaTranscibir: busqueda.value.estudioParaTranscibir,
        });

        todosLosRegistros.value = res.data.data || [];
        aplicarFiltroRapido(filtroRapido.value);
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: getUserSafeError(error, "No fue posible listar los pendientes."),
            icon: "error",
        });
    } finally {
        loadingBuscar.value = false;
    }
}

async function guardarRegistro() {
    if (loadingGuardar.value) return;
    loadingGuardar.value = true;
    try {
        toastMessage({
            heading: "Ok!!!",
            text: "Enviando lectura, por favor espere",
            icon: "info",
        });

        const payload = {
            estudio_producto_id: registro.value.estudio_producto_id,
            id_estudio: registro.value.id_estudio,
            lectura: registro.value.lectura,
            num_docu: registro.value.num_docu,
            nom_pacien: registro.value.nom_pacien,
            email: registro.value.email || undefined,
            tel: registro.value.tel || undefined,
            fec_naci: registro.value.fec_naci || undefined,
            fec_estudio: registro.value.fec_estudio || undefined,
            fec_lectura: registro.value.fec_lectura || undefined,
            nombres_medico: registro.value.nombres_medico || undefined,
            reg_medico: registro.value.reg_medico || undefined,
            num_docu_medico: registro.value.num_docu_medico || undefined,
            firma_medico: registro.value.firma_medico || undefined,
            sede_id: registro.value.sede_id,
            nom_sede: registro.value.nom_sede || undefined,
            cod_cups: registro.value.cod_cups,
            nom_produc: registro.value.nom_produc,
            medico_id: registro.value.medico_id || undefined,
            url_oviyam: registro.value.url_oviyam || undefined,
            study_iuid: registro.value.study_iuid || undefined,
            tap_oviyam: registro.value.tap_oviyam || undefined,
            destino_estudio: DESTINO_TELE_RADIOLOGIA,
            email_reportar: registro.value.email_reportar || undefined,
            sexo: registro.value.sexo || undefined,
            direccion: registro.value.direccion || undefined,
        };

        const res = await axios.post("/api/estudio-guardarTranscripcion", payload);

        if (res.status == 200) {
            const idxTodos = todosLosRegistros.value.findIndex(
                (x) => x.estudio_producto_id == idRow.value
            );
            if (idxTodos >= 0) {
                todosLosRegistros.value.splice(idxTodos, 1);
            }

            const estudioEliminar = registros.value.findIndex(
                (x) => x.estudio_producto_id == idRow.value
            );
            if (estudioEliminar >= 0) {
                registros.value.splice(estudioEliminar, 1);
                nextTick(() => {
                    destroyDataTable();
                    initDataTable();
                });
            }

            toastMessage({
                heading: "Ok!!!",
                text: res.data.data.message,
                icon: "success",
            });

            btnCerrarModal();
            errores.value = {};
        } else {
            toastMessage({
                heading: "Upss!!!",
                text: res.data.data.message,
                icon: "error",
            });
        }
    } catch (error) {
        const status = error?.response?.status;
        if (status == 400 && error?.response?.data && typeof error.response.data === "object") {
            errores.value = error.response.data;
        }
        if (status == 402) {
            toastMessage({
                heading: "Upss!!!",
                text:
                    error?.response?.data?.message ||
                    "La plantilla no está configurada para esta sede.",
                icon: "error",
            });
        } else if (status !== 400) {
            toastMessage({
                heading: "Upss!!!",
                text: getUserSafeError(error, "No fue posible guardar la transcripción."),
                icon: "error",
            });
        }
    } finally {
        loadingGuardar.value = false;
    }
}

function descargaSoportte(nomArchivoOriginal, nomArchivoEncriptado) {
    if (loadingDescargaSoporte.value) return;
    loadingDescargaSoporte.value = nomArchivoEncriptado;
    const url = `/api/descarga-soportes-hc/${nomArchivoEncriptado}`;
    let blobUrl = null;
    let link = null;
    axios
        .get(url, { responseType: "blob" })
        .then((response) => {
            blobUrl = window.URL.createObjectURL(new Blob([response.data]));
            link = document.createElement("a");
            link.href = blobUrl;
            link.setAttribute("download", nomArchivoOriginal || nomArchivoEncriptado);
            document.body.appendChild(link);
            link.click();
        })
        .catch((error) => {
            toastMessage({
                heading: "Error",
                text: getUserSafeError(error, "No fue posible descargar el archivo."),
                icon: "error",
            });
        })
        .finally(() => {
            if (link && document.body.contains(link)) {
                document.body.removeChild(link);
            }
            if (blobUrl) {
                setTimeout(() => window.URL.revokeObjectURL(blobUrl), 1000);
            }
            loadingDescargaSoporte.value = null;
        });
}

async function anularAsignacion(idProductoEstudio, paciente, producto) {
    const ok = await confirmAction({
        title: `¿Estás seguro de anular la asignación de ${paciente}?`,
        text: `Producto: ${producto}. Esta acción no se puede deshacer.`,
        icon: "warning",
    });
    if (!ok) return;
    if (loadingAnularId.value) return;

    loadingAnularId.value = idProductoEstudio;
    try {
        const res = await axios.post("/api/estudio-anular-asignacion", {
            id: idProductoEstudio,
        });
        if (res.status === 200) {
            const idxTodos = todosLosRegistros.value.findIndex(
                (x) => x.estudio_producto_id == idProductoEstudio
            );
            if (idxTodos >= 0) {
                todosLosRegistros.value.splice(idxTodos, 1);
            }

            const idx = registros.value.findIndex(
                (x) => x.estudio_producto_id == idProductoEstudio
            );
            if (idx >= 0) {
                registros.value.splice(idx, 1);
                nextTick(() => {
                    destroyDataTable();
                    initDataTable();
                });
            }
            toastMessage({
                heading: "Anulado!",
                text: "El producto se anuló correctamente.",
                icon: "success",
            });
        } else {
            toastMessage({
                heading: "Ups!",
                text: "No fue posible anular el producto.",
                icon: "error",
            });
        }
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: getUserSafeError(error, "No fue posible anular el producto."),
            icon: "error",
        });
    } finally {
        loadingAnularId.value = null;
    }
}

function verImagen() {
    if (loadingVerImagen.value) return;
    const base = String(datosImagen.value.urlOviyam ?? "").trim();
    if (!base || base === "undefined" || base === "null") {
        toastMessage({
            heading: "Upsss!!!",
            text: "No hay visor configurado para esta sede.",
            icon: "warning",
        });
        return;
    }
    loadingVerImagen.value = true;
    const rawServer = datosImagen.value.serverName;
    const cleanServer =
        !rawServer || rawServer === "undefined" || rawServer === "null"
            ? ""
            : String(rawServer).trim();

    const queryObj = {
        patientID: String(datosImagen.value.patientId ?? "").trim(),
        studyUID: String(datosImagen.value.studyUID ?? "").trim(),
    };
    if (cleanServer) {
        queryObj.serverName = cleanServer;
    }

    const params = new URLSearchParams(queryObj);
    const separator = base.includes("?") ? "&" : "?";
    try {
        window.open(`${base}${separator}${params.toString()}`, "_blank", "noopener,noreferrer");
    } catch {
        toastMessage({
            heading: "Error",
            text: "No fue posible abrir el visor de imágenes.",
            icon: "error",
        });
    } finally {
        setTimeout(() => {
            loadingVerImagen.value = false;
        }, 500);
    }
}

function verImagenDirecta(item) {
    datosImagen.value.urlOviyam = item.url_oviyam;
    datosImagen.value.patientId = item.num_docu;
    datosImagen.value.studyUID = item.study_iuid;
    datosImagen.value.serverName = item.tap_oviyam;
    verImagen();
}

function copiarLectura() {
    if (!registro.value.lectura) {
        toastMessage({
            heading: "Aviso",
            text: "No hay texto para copiar.",
            icon: "warning",
        });
        return;
    }
    navigator.clipboard.writeText(registro.value.lectura).then(() => {
        copiadoExitoso.value = true;
        toastMessage({
            heading: "Copiado",
            text: "Texto del informe copiado al portapapeles.",
            icon: "success",
        });
        setTimeout(() => {
            copiadoExitoso.value = false;
        }, 2000);
    });
}

function limpiarLectura() {
    registro.value.lectura = "";
}

async function listarAudios() {
    if (!id.value) return;
    try {
        const res = await axios.get("/api/listar-audios-estudio/" + id.value);
        audiosDeEstudio.value = res.data.data || [];
    } catch {
        audiosDeEstudio.value = [];
    }
}

async function listarMedicos() {
    try {
        const moduloId = sessionStorage.getItem("ST-modulo") || "1";
        const res = await axios.get(`/api/user.listarUsuarios/Medico/${moduloId}`);
        medicos.value = res.data.data || [];
    } catch {
        medicos.value = [];
    }
}

function establecerSede() {
    busqueda.value.sede_id = sessionStorage.getItem("ST-sede");
    registros.value = [];
    todosLosRegistros.value = [];
}

/**
 * Maneja el evento global sedeSeleccionada actualizando la sede activa
 * y limpiando resultados previos para evitar discrepancias de datos.
 *
 * @param {Object} data - Datos del evento con idSede y nomSede.
 * @returns {void}
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-16
 */
function onSedeSeleccionada(data) {
    const idSede = data?.idSede ?? sessionStorage.getItem("ST-sede");
    busqueda.value.sede_id = idSede;
    destroyDataTable();
    todosLosRegistros.value = [];
    registros.value = [];
    btnCerrarModal();
}

function formatDate(timestamp) {
    if (!timestamp) return "";
    return dayjs.utc(timestamp).local().format("YYYY-MM-DD HH:mm:ss");
}

async function mostrarRegistro(data = {}) {
    if (loadingAbrirId.value !== null) return;
    loadingAbrirId.value = data.estudio_producto_id;
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

    tituloModal.value = "Transcripción: " + (data.nom_pacien || "Estudio");
    id.value = data.id;
    registro.value.id_estudio = data.id;
    idRow.value = data.estudio_producto_id;
    registro.value.estudio_producto_id = data.estudio_producto_id;
    registro.value.fec_estudio = data.fec_estudio;
    registro.value.fec_lectura = data.fechor_lectura;
    registro.value.accession_no = data.accession_no;
    registro.value.study_desc = data.study_desc;
    registro.value.email_reportar = data.email_reportar;
    registro.value.atencion = data.atencion;
    registro.value.medico_id = data.medico_id;
    registro.value.num_docu = data.num_docu;
    registro.value.nom_pacien = data.nom_pacien;
    registro.value.direccion = data.direccion;
    registro.value.email = data.email;
    registro.value.tel = data.tel;
    registro.value.sexo = data.sexo ?? data.pat_sex;
    registro.value.fec_naci = data.fec_naci ?? data.pat_birthdate;
    registro.value.cod_cups = data.cod_cups;
    registro.value.nom_produc = data.nom_produc;
    registro.value.num_docu_medico = data.num_docu_medico;
    registro.value.reg_medico = data.reg_medico;
    registro.value.nombres_medico = data.nombres_medico ? `Médico: ${data.nombres_medico}` : "";
    registro.value.firma_medico = data.firma_medico;

    // Si ya trae lectura previa o de Tele-radiología
    const lecturaTexto = data.lectura || "";
    if (lecturaTexto.trim()) {
        registro.value.lectura = `Atención: ${data.atencion || ""}\nFecha y Hora del estudio: ${data.fec_estudio || ""}\nFecha y Hora de transcripción: ${fechaActual}\n\n${data.nom_produc || ""}\n\n${lecturaTexto}`;
    } else {
        registro.value.lectura = `Atención: ${data.atencion || ""}\nFecha y Hora del estudio: ${data.fec_estudio || ""}\nFecha y Hora de transcripción: ${fechaActual}\n\n${data.nom_produc || ""}\n\n`;
    }

    datosImagen.value.urlOviyam = data.url_oviyam;
    datosImagen.value.patientId = data.num_docu;
    datosImagen.value.studyUID = data.study_iuid;
    datosImagen.value.serverName = data.tap_oviyam;

    registro.value.sede_id = data.sede_id;
    registro.value.nom_sede = data.nom_sede;

    registro.value.url_oviyam = data.url_oviyam;
    registro.value.study_iuid = data.study_iuid;
    registro.value.tap_oviyam = data.tap_oviyam;

    try {
        const resSoportesHC = await axios.get("/api/listar-soportes-hc-estudio?id=" + id.value);
        soportesHC.value = resSoportesHC.data.data || [];
    } catch {
        soportesHC.value = [];
    }

    audiosDeEstudio.value = [];
    listarAudios();
    errores.value = {};

    sidebarVisible.value = true;
    loadingAbrirId.value = null;
}

function btnCerrarModal() {
    sidebarVisible.value = false;
    audiosDeEstudio.value = [];
    errores.value = {};
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
            listarPendientesTrascribir();
        }
    },
});

onMounted(async () => {
    listarMedicos();
    establecerSede();
    emitter.on("sedeSeleccionada", onSedeSeleccionada);
    await listarUsuariosYSedes({
        set quienes_asignaron(v) {
            quienes_asignaron.value = v;
        },
        set medicos(v) {
            medicos.value = v;
        },
    });
});

onBeforeUnmount(() => {
    emitter.off("sedeSeleccionada", onSedeSeleccionada);
    destroyDataTable();
});
</script>

<style scoped>
/* ============================================================== */
/* KPI Metric Cards                                               */
/* ============================================================== */
.kpi-card {
    border-radius: 8px;
    background: #ffffff;
    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease,
        border-color 0.2s ease;
    border-left: 4px solid #cfd8dc !important;
}

.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08) !important;
}

.kpi-card-active {
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12) !important;
    background: #f8fafc;
}

.kpi-card.active-total {
    border-left-color: #0288d1 !important;
}

.kpi-card.active-ready {
    border-left-color: #00c292 !important;
}

.kpi-card.active-pending {
    border-left-color: #fec107 !important;
}

.kpi-card.active-urgent {
    border-left-color: #e53935 !important;
}

.kpi-icon-pill {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.bg-light-primary {
    background-color: #e1f5fe !important;
}

.bg-light-success {
    background-color: #e8f8f5 !important;
}

.bg-light-warning {
    background-color: #fff8e1 !important;
}

.bg-light-danger {
    background-color: #ffebee !important;
}

/* ============================================================== */
/* Tabla y Filas                                                  */
/* ============================================================== */
.row-ready {
    background-color: rgba(40, 167, 69, 0.03) !important;
}

.thead-light th {
    background-color: #f8fafc;
    color: #475569;
    font-weight: 600;
    font-size: 13px;
    border-top: none;
}

/* ============================================================== */
/* Panel Lateral (Right Sidebar)                                  */
/* ============================================================== */
.right-sidebar {
    position: fixed;
    right: -95%;
    width: 95%;
    z-index: 1100;
    background: #ffffff;
    top: 0px;
    height: 100%;
    box-shadow: -5px 0 35px rgba(0, 0, 0, 0.18);
    transition:
        right 0.35s cubic-bezier(0.16, 1, 0.3, 1),
        visibility 0.35s;
    visibility: hidden;
    pointer-events: none;
}

.shw-rside {
    right: 0px !important;
    width: 95%;
    visibility: visible;
    pointer-events: auto;
}

.sidebar-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.55);
    z-index: 1050;
    backdrop-filter: blur(2px);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.rpanel-title {
    background: linear-gradient(135deg, #1e88e5 0%, #1565c0 100%);
}

.clinical-textarea {
    font-family: "Consolas", "Courier New", monospace;
    font-size: 13.5px;
    line-height: 1.6;
    border-radius: 4px;
    border: 1px solid #cbd5e1;
    resize: vertical;
    padding: 12px;
}

.clinical-textarea:focus {
    border-color: #0288d1;
    box-shadow: 0 0 0 3px rgba(2, 136, 209, 0.15);
}

.audio-player-wrapper {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}

/* ============================================================== */
/* Modo Zen / Pantalla Completa                                   */
/* ============================================================== */
.right-sidebar.zen-mode {
    width: 100% !important;
    right: 0 !important;
    height: 100% !important;
    z-index: 2500 !important;
}

/* ============================================================== */
/* Modo Oscuro / Radiología                                       */
/* ============================================================== */
.right-sidebar.dark-mode {
    background: #181a20 !important;
    color: #e2e8f0 !important;
}

.right-sidebar.dark-mode .rpanel-title {
    background: #0f1117 !important;
    border-bottom: 1px solid #2d3343 !important;
}

.right-sidebar.dark-mode .r-panel-body {
    background: #181a20 !important;
}

.right-sidebar.dark-mode .card,
.right-sidebar.dark-mode .clinical-card,
.right-sidebar.dark-mode .editor-card {
    background-color: #1e222b !important;
    border-color: #2d3343 !important;
}

.right-sidebar.dark-mode .card-header {
    background-color: #242934 !important;
    border-color: #2d3343 !important;
    color: #e2e8f0 !important;
}

.right-sidebar.dark-mode .card-header h5,
.right-sidebar.dark-mode .card-header h6 {
    color: #f1f5f9 !important;
}

.right-sidebar.dark-mode .form-control,
.right-sidebar.dark-mode .clinical-textarea {
    background-color: #222632 !important;
    color: #f8fafc !important;
    border-color: #3b4254 !important;
}

.right-sidebar.dark-mode .clinical-textarea:focus {
    background-color: #262b38 !important;
    border-color: #38bdf8 !important;
}

.right-sidebar.dark-mode .input-group-text {
    background-color: #242934 !important;
    border-color: #3b4254 !important;
    color: #94a3b8 !important;
}

.right-sidebar.dark-mode .list-group-item {
    background-color: #1e222b !important;
    border-color: #2d3343 !important;
    color: #e2e8f0 !important;
}

.right-sidebar.dark-mode .audio-player-wrapper {
    background-color: #242934 !important;
    border-color: #2d3343 !important;
}

.right-sidebar.dark-mode .drawer-footer-actions {
    border-top-color: #2d3343 !important;
}
</style>
