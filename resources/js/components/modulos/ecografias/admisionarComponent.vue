<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Ecografias</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <button type="button" class="btn btn-link p-0 breadcrumb-btn">
                            Inicio
                        </button>
                    </li>
                    <li class="breadcrumb-item">
                        <button type="button" class="btn btn-link p-0 breadcrumb-btn">
                            Ecografias
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
                                        :disabled="isBuscandoAtenciones || isGuardandoEcografia"
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
                        <h4 class="card-title">Listado de atenciones disponibles.</h4>
                        <h6 class="card-subtitle text-info">
                            Asigne los médicos para su respectiva lectura.
                        </h6>
                        <table-skeleton-loader
                            :visible="isBuscandoAtenciones"
                            :rows="6"
                            :columns="12"
                        />
                        <empty-state-component
                            v-if="!isBuscandoAtenciones && registros.length === 0"
                            icon="fa fa-stethoscope"
                            title="No hay atenciones para mostrar"
                            description="No se encontraron atenciones con los criterios de búsqueda seleccionados."
                            action-text="Limpiar filtros"
                            action-icon="fa fa-refresh"
                            @action="btnLimpiar()"
                        />
                        <div
                            class="table-responsive"
                            v-show="!isBuscandoAtenciones && registros.length > 0"
                        >
                            <table
                                id="tablaAdmisiones"
                                ref="dt"
                                class="table table-hover table-bordered table-custom-compact"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 10%">Fecha / Ingreso</th>
                                        <th style="width: 18%">Paciente</th>
                                        <th style="width: 22%">Examen / Procedimiento</th>
                                        <th style="width: 6%" class="text-center">Cant.</th>
                                        <th style="width: 18%">Aseguramiento / EPS</th>
                                        <th style="width: 11%">Ubicación & Cama</th>
                                        <th style="width: 5%" class="text-center">Estado</th>
                                        <th style="width: 5%" class="text-center">SAHI</th>
                                        <th style="width: 5%" class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in registros" :key="item.id || index">
                                        <!-- 1. Fecha / Ingreso -->
                                        <td class="align-middle">
                                            <div
                                                class="font-weight-bold text-dark"
                                                style="font-size: 13px"
                                            >
                                                <i class="fa fa-calendar-o text-muted mr-1"></i>
                                                {{ formatearFecha(item.FecIngreso) }}
                                            </div>
                                            <small
                                                class="text-muted d-block"
                                                style="font-size: 11px"
                                            >
                                                <i class="fa fa-clock-o mr-1"></i
                                                >{{ formatearHora(item.FecIngreso) }}
                                            </small>
                                        </td>

                                        <!-- 2. Paciente + Documento -->
                                        <td class="align-middle">
                                            <div class="paciente-nombre font-weight-bold text-dark">
                                                {{ item.PrimerNombre }} {{ item.SegundoNombre }}
                                                {{ item.PrimerApellido }} {{ item.SegundoApellido }}
                                            </div>
                                            <div
                                                class="text-primary mt-1"
                                                style="font-size: 12px; font-weight: 600"
                                            >
                                                <i class="fa fa-id-card-o mr-1"></i>
                                                {{ item.NumDocumento }}
                                            </div>
                                        </td>

                                        <!-- 3. Examen / Procedimiento + Valor -->
                                        <td class="align-middle">
                                            <div
                                                class="examen-titulo font-weight-bold text-dark"
                                                :title="item.NomProducto"
                                            >
                                                {{ item.NomProducto }}
                                            </div>
                                            <div class="mt-1 d-flex flex-wrap align-items-center">
                                                <span
                                                    class="badge badge-light border text-muted mr-2"
                                                    style="font-size: 11px"
                                                >
                                                    Valor: ${{ formatoMoneda(item.ValProduc) }}
                                                </span>
                                                <small
                                                    v-if="item.CodProducto"
                                                    class="text-muted mr-2"
                                                    style="font-size: 11px"
                                                >
                                                    Cód: {{ item.CodProducto }}
                                                </small>
                                            </div>
                                            <div class="mt-1">
                                                <small
                                                    v-if="
                                                        item.tiene_plantilla_consentimiento &&
                                                        item.consentimiento_estado ===
                                                            'PENDIENTE_PACIENTE'
                                                    "
                                                    class="badge badge-danger"
                                                >
                                                    Firma paciente pendiente
                                                </small>
                                                <small
                                                    v-else-if="
                                                        item.tiene_plantilla_consentimiento &&
                                                        item.consentimiento_estado ===
                                                            'PENDIENTE_MEDICO'
                                                    "
                                                    class="badge badge-warning text-white"
                                                >
                                                    Firma médico pendiente
                                                </small>
                                                <small
                                                    v-else-if="
                                                        item.tiene_plantilla_consentimiento &&
                                                        item.consentimiento_estado === 'COMPLETO'
                                                    "
                                                    class="badge badge-success"
                                                >
                                                    Consentimiento firmado
                                                </small>
                                            </div>
                                        </td>

                                        <!-- 4. Cantidad -->
                                        <td
                                            class="text-center align-middle font-weight-bold"
                                            style="font-size: 13px"
                                        >
                                            {{ item.Cantidad }}
                                        </td>

                                        <!-- 5. Aseguramiento / EPS + Contrato + Plan -->
                                        <td class="align-middle">
                                            <div
                                                class="font-weight-bold text-dark text-truncate"
                                                style="max-width: 220px"
                                                :title="item.NomTercero || 'Particular / Directo'"
                                            >
                                                {{ item.NomTercero || "Sin Administradora" }}
                                            </div>
                                            <div
                                                v-if="item.DesContrato || item.DesPlan"
                                                class="small text-muted mt-1"
                                                style="font-size: 11px; line-height: 1.3"
                                            >
                                                <div
                                                    v-if="item.DesContrato"
                                                    class="text-truncate"
                                                    style="max-width: 220px"
                                                    :title="'Contrato: ' + item.DesContrato"
                                                >
                                                    <strong>C:</strong> {{ item.DesContrato }}
                                                </div>
                                                <div
                                                    v-if="item.DesPlan"
                                                    class="text-truncate"
                                                    style="max-width: 220px"
                                                    :title="'Plan: ' + item.DesPlan"
                                                >
                                                    <strong>P:</strong> {{ item.DesPlan }}
                                                </div>
                                            </div>
                                        </td>

                                        <!-- 6. Ubicación & Cama + Ordenó -->
                                        <td class="align-middle">
                                            <div
                                                class="text-truncate"
                                                style="max-width: 140px"
                                                :title="
                                                    item.NomUbicacion ||
                                                    item.ubicacion ||
                                                    'Sin ubicación'
                                                "
                                            >
                                                <i class="fa fa-hospital-o text-muted mr-1"></i>
                                                <span style="font-size: 12px">{{
                                                    item.NomUbicacion ||
                                                    item.ubicacion ||
                                                    "Sin ubicación"
                                                }}</span>
                                            </div>
                                            <div class="mt-1">
                                                <span
                                                    v-if="item.Cama || item.cama"
                                                    class="badge badge-light border text-dark font-weight-bold"
                                                    style="font-size: 10px"
                                                >
                                                    Cama: {{ item.Cama || item.cama }}
                                                </span>
                                                <span
                                                    v-else
                                                    class="text-muted font-italic"
                                                    style="font-size: 10px"
                                                >
                                                    Sin cama
                                                </span>
                                            </div>
                                            <div
                                                v-if="item.quien_ordeno"
                                                class="small text-muted mt-1 text-truncate"
                                                style="max-width: 140px; font-size: 10px"
                                                :title="'Ordenó: ' + item.quien_ordeno"
                                            >
                                                <i class="fa fa-user-md text-info mr-1"></i
                                                >{{ item.quien_ordeno }}
                                            </div>
                                        </td>

                                        <!-- 7. Estado -->
                                        <td class="text-center align-middle">
                                            <span
                                                class="badge badge-info"
                                                v-if="item.estado_ecografia === 'EN PROCESO'"
                                                >{{ item.estado_ecografia }}</span
                                            >
                                            <span
                                                class="badge badge-success"
                                                v-if="item.estado_ecografia === 'LEIDO'"
                                                >{{ item.estado_ecografia }}</span
                                            >
                                            <span
                                                class="badge badge-danger"
                                                v-if="item.estado_ecografia === 'ANULADO'"
                                                >{{ item.estado_ecografia }}</span
                                            >
                                        </td>

                                        <!-- 8. SAHI -->
                                        <td class="text-center align-middle">
                                            <template
                                                v-if="sedeInterface && item.ecografia_producto_id"
                                            >
                                                <span
                                                    v-if="item.estado_envio_sahi"
                                                    class="badge"
                                                    :class="{
                                                        'badge-success':
                                                            item.estado_envio_sahi === 'ENVIADO',
                                                        'badge-warning text-white':
                                                            item.estado_envio_sahi === 'ENVIANDO' ||
                                                            item.estado_envio_sahi === 'PENDIENTE',
                                                        'badge-danger':
                                                            item.estado_envio_sahi === 'ERROR',
                                                        'badge-secondary': !item.estado_envio_sahi,
                                                    }"
                                                >
                                                    {{ item.estado_envio_sahi }}
                                                </span>
                                                <span v-else class="text-muted">—</span>

                                                <div class="mt-1">
                                                    <button
                                                        type="button"
                                                        class="btn btn-info btn-xs mr-1"
                                                        @click="consultarEstadoSahi(item)"
                                                        :disabled="
                                                            isConsultandoSahi[
                                                                item.ecografia_producto_id
                                                            ]
                                                        "
                                                        title="Consultar estado SAHI"
                                                        aria-label="Consultar estado SAHI"
                                                    >
                                                        <i
                                                            class="fa"
                                                            :class="
                                                                isConsultandoSahi[
                                                                    item.ecografia_producto_id
                                                                ]
                                                                    ? 'fa-spinner fa-spin'
                                                                    : 'fa-refresh'
                                                            "
                                                        ></i>
                                                    </button>

                                                    <button
                                                        v-if="item.estado_envio_sahi === 'ERROR'"
                                                        type="button"
                                                        class="btn btn-warning btn-xs"
                                                        @click="reenviarASahi(item)"
                                                        :disabled="
                                                            isEnviandoSahi[
                                                                item.ecografia_producto_id
                                                            ] ||
                                                            item.estado_envio_sahi === 'ENVIANDO'
                                                        "
                                                        title="Reenviar a SAHI"
                                                        aria-label="Reenviar a SAHI"
                                                    >
                                                        <i
                                                            class="fa"
                                                            :class="
                                                                isEnviandoSahi[
                                                                    item.ecografia_producto_id
                                                                ]
                                                                    ? 'fa-spinner fa-spin'
                                                                    : 'fa-repeat'
                                                            "
                                                        ></i>
                                                    </button>
                                                </div>

                                                <small
                                                    v-if="
                                                        item.estado_envio_sahi === 'ERROR' &&
                                                        item.error_envio_sahi
                                                    "
                                                    class="text-danger d-block mt-1"
                                                    style="
                                                        font-size: 10px;
                                                        max-width: 90px;
                                                        overflow: hidden;
                                                        text-overflow: ellipsis;
                                                        white-space: nowrap;
                                                    "
                                                    :title="item.error_envio_sahi"
                                                >
                                                    {{ item.error_envio_sahi }}
                                                </small>
                                            </template>
                                            <template v-else>
                                                <span class="text-muted">—</span>
                                            </template>
                                        </td>

                                        <!-- 9. Acciones -->
                                        <td class="text-center align-middle text-nowrap">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm mr-1"
                                                @click="
                                                    actualizar = false;
                                                    registroParaAnular(item);
                                                "
                                                title="Anular agenda"
                                                alt="default"
                                                data-toggle="modal"
                                                data-target="#responsive-modal"
                                                v-if="item.estado_ecografia === 'EN PROCESO'"
                                                aria-label="Anular agenda"
                                            >
                                                <i class="fa fa-trash"></i>
                                            </button>

                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm mr-1"
                                                @click="
                                                    actualizar = true;
                                                    mostrarRegistro(item);
                                                "
                                                title="Agendar ecografia"
                                                aria-label="Agendar ecografia"
                                            >
                                                <i class="fa fa-check"></i>
                                            </button>

                                            <button
                                                v-if="
                                                    can(
                                                        'ecografias.consentimientos.capturar_firma'
                                                    ) &&
                                                    item.tiene_plantilla_consentimiento &&
                                                    item.ecografia_producto_id &&
                                                    (!item.consentimiento_estado ||
                                                        item.consentimiento_estado ===
                                                            'PENDIENTE_PACIENTE')
                                                "
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-success btn-sm"
                                                title="Capturar firma (paciente/acompañante)"
                                                data-toggle="modal"
                                                data-target="#modal-consentimiento"
                                                @click="abrirModalConsentimiento(item)"
                                                aria-label="Capturar firma (paciente/acompañante)"
                                            >
                                                <i class="fa fa-pencil"></i>
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
        <!-- Backdrop para cerrar al hacer clic y atenuar fondo -->
        <transition name="fade">
            <div
                v-if="sidebarVisible"
                class="sidebar-backdrop"
                @click="cerrarSidebar()"
                aria-hidden="true"
            ></div>
        </transition>

        <!-- .right-sidebar -->
        <div
            id="drawerAdmisionarEcografia"
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
                    <!-- ============================================================== -->
                    <!-- Aqui va el contenido de los formularios -->
                    <!-- ============================================================== -->
                    <form action="#">
                        <div class="form-body">
                            <!-- Resumen informativo de EPS, Contrato, Plan y Atención (solo aplica cuando proviene de una atención institucional) -->
                            <div
                                v-if="
                                    !esParticular &&
                                    !esCortesia &&
                                    (registro.admin_salud ||
                                        registro.contrato ||
                                        registro.plan ||
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
                                    <div class="col-md-3" v-if="registro.admin_salud">
                                        <strong
                                            ><i class="fa fa-hospital-o mr-1"></i>EPS /
                                            Tercero:</strong
                                        >
                                        <div class="text-dark font-weight-bold">
                                            {{ registro.admin_salud }}
                                        </div>
                                    </div>
                                    <div class="col-md-3" v-if="registro.contrato">
                                        <strong
                                            ><i class="fa fa-file-text-o mr-1"></i>Contrato:</strong
                                        >
                                        <div class="text-dark font-weight-bold">
                                            {{ registro.contrato }}
                                        </div>
                                    </div>
                                    <div class="col-md-3" v-if="registro.plan">
                                        <strong><i class="fa fa-bookmark-o mr-1"></i>Plan:</strong>
                                        <div class="text-dark font-weight-bold">
                                            {{ registro.plan }}
                                        </div>
                                    </div>
                                    <div class="col-md-3" v-if="registro.quien_ordeno">
                                        <strong
                                            ><i class="fa fa-user-md mr-1"></i>Médico que
                                            ordenó:</strong
                                        >
                                        <div class="text-dark font-weight-bold">
                                            {{ registro.quien_ordeno }}
                                        </div>
                                    </div>
                                    <div class="col-md-3" v-if="registro.ubicacion">
                                        <strong
                                            ><i class="fa fa-building-o mr-1"></i>Servicio /
                                            Ubicación:</strong
                                        >
                                        <div class="text-dark font-weight-bold">
                                            {{ registro.ubicacion }}
                                        </div>
                                    </div>
                                    <div class="col-md-3" v-if="registro.cama">
                                        <strong><i class="fa fa-bed mr-1"></i>Cama:</strong>
                                        <div class="text-dark font-weight-bold">
                                            {{ registro.cama }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> Datos del Paciente
                                    </h5>
                                    <hr />
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label
                                                    class="control-label font-weight-bold"
                                                    for="selTipoDoc"
                                                    >Tipo de documento</label
                                                >
                                                <Select2
                                                    id="selTipoDoc"
                                                    ref="selTipoDoc"
                                                    :value="registro.tipo_documento_id"
                                                    :options="tipoDocumentoOptions"
                                                    width="100%"
                                                    :allow-clear="true"
                                                    :label-fields="['codigo', 'tipo_documento']"
                                                    dropdown-parent-selector="#drawerAdmisionarEcografia"
                                                    @input="onTipoDocumentoSelect"
                                                />

                                                <span
                                                    class="text-danger"
                                                    v-if="errores.tipo_documento_id"
                                                >
                                                    {{ errores.tipo_documento_id[0] }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label" for="num_docu"
                                                    ># Documento</label
                                                >
                                                <input
                                                    type="text"
                                                    id="num_docu"
                                                    name="num_docu"
                                                    v-model="registro.num_docu"
                                                    class="form-control"
                                                    placeholder="# de Documento"
                                                    @blur="buscarPacientePorDocumento()"
                                                    @keyup.enter="buscarPacientePorDocumento()"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label" for="Primer nombre"
                                                    >Primer Nombre</label
                                                >
                                                <input
                                                    type="text"
                                                    id="nombres"
                                                    name="nombres"
                                                    v-model="registro.nombres"
                                                    class="form-control"
                                                    placeholder="Primer nombre"
                                                />
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label" for="Segundo nombres"
                                                    >Segundo Nombre</label
                                                >
                                                <input
                                                    type="text"
                                                    id="seg_nom"
                                                    name="seg_nom"
                                                    v-model="registro.seg_nom"
                                                    class="form-control"
                                                    placeholder="Segundo nombre"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label" for="pri_ape"
                                                    >Primer Apellido</label
                                                >
                                                <input
                                                    type="text"
                                                    id="pri_ape"
                                                    name="pri_ape"
                                                    v-model="registro.pri_ape"
                                                    class="form-control"
                                                    placeholder="Primer apellido"
                                                />
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label" for="seg_ape"
                                                    >Segundo Apellido</label
                                                >
                                                <input
                                                    type="text"
                                                    id="seg_ape"
                                                    name="seg_ape"
                                                    v-model="registro.seg_ape"
                                                    class="form-control"
                                                    placeholder="Segundo apaellido"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label" for="sexo">Sexo</label>
                                                <select
                                                    id="sexo"
                                                    name="sexo"
                                                    v-model="registro.sexo"
                                                    class="form-control"
                                                >
                                                    <option value="">—</option>
                                                    <option value="M">M</option>
                                                    <option value="F">F</option>
                                                    <option value="O">Otro</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label" for="fec_naci"
                                                    >Fec. Nacimi</label
                                                >
                                                <input
                                                    type="date"
                                                    id="fec_naci"
                                                    name="fec_naci"
                                                    v-model="registro.fec_naci"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-7">
                                            <div class="form-group">
                                                <label class="control-label" for="direccion"
                                                    >Dirección</label
                                                >
                                                <input
                                                    type="text"
                                                    id="direccion"
                                                    name="direccion"
                                                    v-model="registro.direccion"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label" for="tel"
                                                    >Télefono</label
                                                >
                                                <input
                                                    type="number"
                                                    id="tel"
                                                    name="tel"
                                                    v-model="registro.tel"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-7">
                                            <div class="form-group">
                                                <label class="control-label" for="email"
                                                    >E - Mail</label
                                                >
                                                <input
                                                    type="email"
                                                    id="email"
                                                    name="email"
                                                    v-model="registro.email"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> Datos de la Asignación
                                    </h5>
                                    <hr />
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label
                                                    class="control-label font-weight-bold"
                                                    for="selTipoRecaudo"
                                                >
                                                    Tipo de Ecografía / Recaudo
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select
                                                    id="selTipoRecaudo"
                                                    class="form-control"
                                                    v-model="registro.tipo_recaudo_id"
                                                    @change="onTipoRecaudoChange"
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
                                                <small
                                                    class="form-text"
                                                    :class="
                                                        esCortesia
                                                            ? 'text-danger font-weight-bold'
                                                            : 'text-muted'
                                                    "
                                                >
                                                    <i class="fa fa-info-circle mr-1"></i>
                                                    {{
                                                        esCortesia
                                                            ? "Las ecografías de cortesía no generan ningún recaudo y se transfieren únicamente al portal del paciente."
                                                            : esParticular
                                                              ? "Las ecografías particulares recaudan directamente y se transfieren al portal del paciente (no se envían a SAHI)."
                                                              : "Procedimiento institucional facturado por atención SAHI."
                                                    }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label
                                                    class="control-label font-weight-bold"
                                                    for="selMedico"
                                                    >Médico</label
                                                >
                                                <Select2
                                                    id="selMedico"
                                                    ref="selMedico"
                                                    :value="registro.medico_id"
                                                    :options="medicoOptions"
                                                    width="100%"
                                                    :allow-clear="true"
                                                    :label-fields="['name']"
                                                    dropdown-parent-selector="#drawerAdmisionarEcografia"
                                                    @input="onMedicoSelect"
                                                />

                                                <span class="text-danger" v-if="errores.medico_id">
                                                    {{ errores.medico_id[0] }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-7">
                                            <div class="form-group">
                                                <label
                                                    class="control-label font-weight-bold"
                                                    for="selProducto"
                                                    >Producto</label
                                                >
                                                <Select2
                                                    id="selProducto"
                                                    ref="selProducto"
                                                    :value="productoSelecciondo"
                                                    :options="productoOptions"
                                                    width="100%"
                                                    :allow-clear="true"
                                                    dropdown-parent-selector="#drawerAdmisionarEcografia"
                                                    @input="onProductoSelect"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-product-hunt"></i> Productos
                                    </h5>
                                    <hr />
                                    <div class="row">
                                        <div class="col-md-12 table-responsive">
                                            <table
                                                id="myTable"
                                                class="table table-bordered table-striped"
                                            >
                                                <thead>
                                                    <tr>
                                                        <th>Código</th>
                                                        <th>Producto</th>
                                                        <th style="width: 120px">Cantidad</th>
                                                        <th style="width: 160px">Valor</th>
                                                        <th style="width: 50px">Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr
                                                        v-for="item in registro.productosAgenda"
                                                        :key="item.id"
                                                    >
                                                        <td>{{ item.CodLegal }}</td>
                                                        <td>{{ item.NomProducto }}</td>
                                                        <td>
                                                            <input
                                                                type="number"
                                                                class="form-control form-control-sm text-right"
                                                                min="1"
                                                                step="1"
                                                                v-model.number="item.Cantidad"
                                                                placeholder="Cant."
                                                                aria-label="Cantidad del producto"
                                                            />
                                                        </td>
                                                        <td>
                                                            <div class="input-group input-group-sm">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"
                                                                        >$</span
                                                                    >
                                                                </div>
                                                                <input
                                                                    type="number"
                                                                    class="form-control form-control-sm text-right"
                                                                    min="0"
                                                                    step="any"
                                                                    v-model.number="item.ValProduc"
                                                                    :disabled="esCortesia"
                                                                    placeholder="0"
                                                                    aria-label="Precio del producto"
                                                                />
                                                            </div>
                                                            <small
                                                                v-if="esCortesia"
                                                                class="text-success font-weight-bold d-block mt-1"
                                                            >
                                                                <i class="fa fa-gift mr-1"></i>
                                                                Cortesía ($0)
                                                            </small>
                                                        </td>
                                                        <td class="text-nowrap text-center">
                                                            <button
                                                                type="button"
                                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                                @click="
                                                                    quitarProductoAgenda(
                                                                        item.CodLegal
                                                                    )
                                                                "
                                                                aria-label="Quitar producto"
                                                            >
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions mt-3">
                                <button
                                    type="button"
                                    class="btn btn-success waves-effect waves-light m-r-5"
                                    @click="guardarRegistro()"
                                    :disabled="isGuardandoEcografia"
                                    title="Guardar admisión"
                                >
                                    <i
                                        class="fa mr-1"
                                        :class="
                                            isGuardandoEcografia ? 'fa-spinner fa-spin' : 'fa-check'
                                        "
                                    ></i>
                                    {{ isGuardandoEcografia ? "Guardando..." : "Guardar" }}
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-inverse waves-effect waves-light"
                                    @click="cerrarSidebar()"
                                    :disabled="isGuardandoEcografia"
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
        <!-- ============================================================== -->
        <!-- End Right sidebar -->
        <!-- ============================================================== -->

        <div
            id="responsive-modal"
            class="modal fade"
            tabindex="-1"
            role="dialog"
            aria-labelledby="myModalLabel"
            aria-hidden="true"
            style="display: none"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="myModalLabel">Anular Agenda</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="form-group">
                                <label for="message-text" class="control-label"
                                    >Motivo de anulación:</label
                                >
                                <textarea
                                    class="form-control"
                                    id="message-text"
                                    v-model="registroAnular.motivo_anula"
                                ></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-default waves-effect"
                            data-dismiss="modal"
                            id="btnCerrarModalAunular"
                        >
                            Close
                        </button>
                        <button
                            type="button"
                            class="btn btn-danger waves-effect waves-light"
                            @click="anularEcografia()"
                            :disabled="isAnulandoEcografia"
                        >
                            <i
                                class="fa"
                                :class="isAnulandoEcografia ? 'fa-spinner fa-spin' : 'fa-trash'"
                            ></i>
                            {{ isAnulandoEcografia ? "Anulando..." : "Anular" }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div
            id="modal-consentimiento"
            class="modal fade"
            tabindex="-1"
            role="dialog"
            aria-labelledby="modal-consentimiento-title"
            aria-hidden="true"
            style="display: none"
        >
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="modal-consentimiento-title">
                            Capturar firma consentimiento
                        </h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <div v-if="consentimientoItem">
                            <div class="mb-2">
                                <small class="text-muted">
                                    {{ consentimientoItem.NomProducto }} ·
                                    {{ consentimientoItem.NumDocumento }}
                                </small>
                            </div>

                            <div class="form-group">
                                <label class="control-label" for="consentimiento-firmante-tipo"
                                    >¿Quién firma?</label
                                >
                                <select
                                    class="form-control"
                                    id="consentimiento-firmante-tipo"
                                    v-model="consentimientoFirmanteTipo"
                                >
                                    <option value="PACIENTE">Paciente</option>
                                    <option value="ACOMPANANTE">Acompañante</option>
                                </select>
                            </div>

                            <div v-if="consentimientoFirmanteTipo === 'ACOMPANANTE'" class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label" for="acompanante-cedula"
                                            >Cédula</label
                                        >
                                        <input
                                            id="acompanante-cedula"
                                            class="form-control"
                                            v-model="acompanante.cedula"
                                        />
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="control-label" for="acompanante-nombres"
                                            >Nombres</label
                                        >
                                        <input
                                            id="acompanante-nombres"
                                            class="form-control"
                                            v-model="acompanante.nombres"
                                        />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label" for="acompanante-email"
                                            >Email</label
                                        >
                                        <input
                                            id="acompanante-email"
                                            class="form-control"
                                            v-model="acompanante.email"
                                        />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label" for="acompanante-telefono"
                                            >Teléfono</label
                                        >
                                        <input
                                            id="acompanante-telefono"
                                            class="form-control"
                                            v-model="acompanante.telefono"
                                        />
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label" for="acompanante-direccion"
                                            >Dirección</label
                                        >
                                        <input
                                            id="acompanante-direccion"
                                            class="form-control"
                                            v-model="acompanante.direccion"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="mt-2">
                                <button
                                    type="button"
                                    class="btn btn-success"
                                    @click="generarLinkFirma()"
                                    :disabled="isGenerandoLinkFirma"
                                >
                                    <i
                                        class="fa"
                                        :class="
                                            isGenerandoLinkFirma ? 'fa-spinner fa-spin' : 'fa-link'
                                        "
                                    ></i>
                                    {{
                                        isGenerandoLinkFirma
                                            ? "Generando…"
                                            : "Generar link de firma (10 min)"
                                    }}
                                </button>
                            </div>

                            <div v-if="consentimientoUrl" class="alert alert-info mt-3">
                                <div><strong>Link de firma</strong></div>
                                <div class="small" style="word-break: break-all">
                                    {{ consentimientoUrl }}
                                </div>
                                <div class="mt-2">
                                    <button
                                        class="btn btn-sm btn-primary"
                                        type="button"
                                        @click="abrirLinkFirma()"
                                    >
                                        Abrir
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-muted">Seleccione un registro.</div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-default waves-effect"
                            data-dismiss="modal"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- Modal Enviar a Worklist (Dispositivos / Ecógrafo) -->
        <!-- ============================================================== -->
        <div
            class="modal fade"
            :class="{ show: modalWorklistVisible, 'd-block': modalWorklistVisible }"
            tabindex="-1"
            role="dialog"
            v-if="modalWorklistVisible"
            style="background: rgba(0, 0, 0, 0.5)"
        >
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title font-weight-bold">
                            <i class="fa fa-television text-info mr-2"></i> Enviar Ecografía a
                            Worklist
                        </h4>
                        <button
                            type="button"
                            class="close"
                            @click="cerrarModalWorklist()"
                            :disabled="loadingWorklist"
                            aria-label="Cerrar"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="card card-outline-info p-3 mb-3 bg-light">
                            <h5 class="card-title text-info mb-1 font-weight-bold">
                                <i class="fa fa-user mr-1"></i> {{ worklistData.pacienteNombre }}
                            </h5>
                            <div class="text-muted small">
                                <strong># Documento:</strong>
                                <span class="text-primary font-weight-bold ml-1">
                                    <i class="fa fa-id-card-o mr-1"></i>{{ worklistData.numDocu }}
                                </span>
                                <span class="mx-2">|</span>
                                <strong>Atención:</strong> {{ worklistData.atencionId }}
                            </div>
                        </div>

                        <form action="#" @submit.prevent="confirmarEnvioWorklist()">
                            <div class="form-group">
                                <label class="control-label font-weight-bold" for="wl_eco_producto">
                                    Examen / Procedimiento <span class="text-danger">*</span>
                                </label>
                                <select
                                    id="wl_eco_producto"
                                    v-model="worklistForm.producto_id"
                                    class="form-control"
                                    required
                                >
                                    <option :value="null" disabled>-- Seleccione Examen --</option>
                                    <option
                                        v-for="prod in worklistData.productos"
                                        :key="prod.id"
                                        :value="prod.id"
                                    >
                                        {{ prod.nom_produc }} (CUPS: {{ prod.cod_cups }})
                                    </option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="control-label font-weight-bold" for="wl_eco_acc">
                                    Accession Number <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input
                                        type="text"
                                        id="wl_eco_acc"
                                        v-model="worklistForm.accession_no"
                                        class="form-control font-weight-bold"
                                        required
                                    />
                                    <div class="input-group-append">
                                        <button
                                            type="button"
                                            class="btn btn-secondary"
                                            @click="generarAccessionSugerido()"
                                            title="Regenerar sugerido"
                                        >
                                            <i class="fa fa-refresh"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">
                                    Identificador único DICOM generado automáticamente.
                                </small>
                            </div>

                            <div class="form-group">
                                <label
                                    class="control-label font-weight-bold"
                                    for="wl_eco_dispositivo"
                                >
                                    Ecógrafo / Dispositivo de Destino
                                    <span class="text-danger">*</span>
                                </label>
                                <div v-if="loadingDispositivos" class="py-2 text-muted">
                                    <i class="fa fa-spinner fa-spin mr-1"></i> Cargando dispositivos
                                    configurados...
                                </div>
                                <select
                                    v-else-if="dispositivosWorklist.length > 0"
                                    id="wl_eco_dispositivo"
                                    v-model="worklistForm.station_aet"
                                    class="form-control"
                                    required
                                >
                                    <option value="" disabled>
                                        -- Seleccione el ecógrafo / sala --
                                    </option>
                                    <option
                                        v-for="disp in dispositivosWorklist"
                                        :key="disp.aet"
                                        :value="disp.aet"
                                    >
                                        {{ disp.nombre }}
                                    </option>
                                </select>
                                <div v-else>
                                    <input
                                        type="text"
                                        id="wl_eco_dispositivo"
                                        v-model="worklistForm.station_aet"
                                        class="form-control"
                                        placeholder="AE Title del equipo (Ej: VOLUSON, LOGIQ)"
                                        required
                                    />
                                    <small class="text-warning d-block mt-1">
                                        <i class="fa fa-exclamation-triangle mr-1"></i>
                                        No hay ecógrafos activos registrados en esta sede. Ingrese
                                        el AE Title manualmente.
                                    </small>
                                </div>
                            </div>

                            <div class="form-group mb-0">
                                <label class="control-label" for="wl_eco_obs"
                                    >Observaciones (Opcional)</label
                                >
                                <textarea
                                    id="wl_eco_obs"
                                    v-model="worklistForm.observaciones"
                                    class="form-control"
                                    rows="2"
                                    placeholder="Indicaciones para el ecógrafo o consola..."
                                ></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-inverse"
                            @click="cerrarModalWorklist()"
                            :disabled="loadingWorklist"
                        >
                            Omitir
                        </button>
                        <button
                            type="button"
                            class="btn btn-success"
                            @click="confirmarEnvioWorklist()"
                            :disabled="
                                loadingWorklist ||
                                !worklistForm.producto_id ||
                                !worklistForm.accession_no ||
                                !worklistForm.station_aet
                            "
                        >
                            <i
                                class="fa mr-1"
                                :class="loadingWorklist ? 'fa-spinner fa-spin' : 'fa-send'"
                            ></i>
                            {{ loadingWorklist ? "Enviando a Worklist..." : "Enviar a Worklist" }}
                        </button>
                    </div>
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
import { formatoMoneda } from "@/helpers/funciones.js";
import { alertError, alertSuccess, confirmAction, toastMessage } from "@/helpers/alerts.js";
import { useSede } from "@/composables/useSede.js";
import { useSahiInterface } from "@/composables/useSahiInterface.js";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts";
import axios from "axios";

const props = defineProps({
    usuarioactual: { type: Object, default: () => ({}) },
    permisos: { type: Array, default: () => [] },
});
const { can } = usePermissions(props.permisos);
const { sedeActual, sedeInterface, establecerSede: establecerSedeComposable } = useSede();
const { consultarEstado, enviarEcografiaProducto } = useSahiInterface();

const dt = ref(null);
const actualizar = ref(false);

const tituloModal = ref("Nueva admisión");
const busqueda = ref({
    sede_id: sedeActual,
    medico_id: null,
    bus_nom_num_docu: "",
    bus_fech_ini: "",
    bus_fecha_fin: "",
});
const registros = ref([]);
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
    quien_registro_id: props.usuarioactual.id,
    cel: "",
    atencion_id: null,
    tipo_recaudo_id: 1, // Por defecto: 1 = Por Atención
    productosAgenda: [],
    contrato: null,
    plan: null,
    admin_salud: null,
    quien_ordeno: null,
    ubicacion: "",
    cama: "",
});
const registroAnular = ref({
    ecografia_id: "",
    atencion_id: "",
    quien_anulo_id: props.usuarioactual.id,
    motivo_anula: "",
});
const errores = ref({});
const erroresBusqueda = ref({});
const medicos = ref([]);
const tipoDocumentos = ref([]);
const tiposRecaudo = ref([]);
const medicoActual = ref(
    localStorage.getItem("ST-ecografia-medico") || sessionStorage.getItem("ST-medico")
);
const productoSelecciondo = ref(null);
const productos = ref([]);
const isBuscandoAtenciones = ref(false);
const isGuardandoEcografia = ref(false);
const isAnulandoEcografia = ref(false);
const isConsultandoSahi = ref({});
const isEnviandoSahi = ref({});
const consentimientoItem = ref(null);
const consentimientoFirmanteTipo = ref("PACIENTE");
const acompanante = ref({ cedula: "", nombres: "", email: "", direccion: "", telefono: "" });
const isGenerandoLinkFirma = ref(false);
const consentimientoUrl = ref("");
const sidebarVisible = ref(false);
const selTipoDoc = ref(null);
const selMedico = ref(null);
const selProducto = ref(null);

// Estado y variables para modal de Enviar a Worklist
const modalWorklistVisible = ref(false);
const loadingWorklist = ref(false);
const loadingDispositivos = ref(false);
const dispositivosWorklist = ref([]);
const worklistData = ref({
    pacienteId: null,
    pacienteNombre: "",
    numDocu: "",
    atencionId: "",
    ecografiaId: null,
    ecografiaProductoId: null,
    productos: [],
});
const worklistForm = ref({
    paciente_id: null,
    sede_id: null,
    producto_id: null,
    accession_no: "",
    modality: "US",
    station_aet: "",
    ecografia_id: null,
    ecografia_producto_id: null,
    observaciones: "",
});

const tipoRecaudoActual = computed(() => {
    return (
        tiposRecaudo.value.find((tr) => Number(tr.id) === Number(registro.value.tipo_recaudo_id)) ||
        null
    );
});

const esCortesia = computed(() => {
    return (
        tipoRecaudoActual.value?.codigo === "CORTESIA" ||
        (!tipoRecaudoActual.value?.aplica_recaudo && tipoRecaudoActual.value !== null)
    );
});

const esParticular = computed(() => {
    return tipoRecaudoActual.value?.codigo === "PARTICULAR";
});

function onTipoRecaudoChange() {
    if (esCortesia.value) {
        // En cortesía, todo producto asignado tiene valor 0
        (registro.value.productosAgenda || []).forEach((p) => {
            p.ValProduc = 0;
        });
    }
}

useKeyboardShortcuts({
    onSearch: () => {
        if (!sidebarVisible.value) {
            listarAtenciones();
        }
    },
    onSave: guardarRegistro,
    onEscape: cerrarSidebar,
    isActive: () => true,
    isLoading: isGuardandoEcografia,
});

/**
 * Opciones derivadas de médicos para el componente Select2 local.
 *
 * @returns {Array}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
const medicoOptions = computed(() => {
    // Opciones para el Select2 local de medicos.
    return (medicos.value || []).map((p) => ({ id: String(p.id), name: p.name }));
});
/**
 * Opciones derivadas de tipos de documento para el componente Select2 local.
 *
 * @returns {Array}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
const tipoDocumentoOptions = computed(() => {
    // Opciones para el Select2 local de medicos.
    return (tipoDocumentos.value || []).map((p) => ({
        id: String(p.id),
        codigo: p.codigo,
        tipo_documento: p.tipo_documento,
    }));
});
/**
 * Opciones derivadas de productos para el componente Select2 local.
 *
 * @returns {Array}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
const productoOptions = computed(() => {
    // Opciones para el Select2 local de productos.
    return (productos.value || []).map((p) => ({
        id: String(p.id),
        cod_cups: p.cod_cups,
        nom_produc: p.nom_produc,
    }));
});

/**
 * Ejecuta la búsqueda de atenciones disparada desde el input (tecla Enter).
 *
 * @returns {any}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function buscarStudy() {
    // Soporte para Enter en el input de búsqueda
    return listarAtenciones();
}
/**
 * Limpia los criterios de búsqueda y el formulario de admisión.
 *
 * @returns {any}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function btnLimpiar() {
    // Limpia criterios de búsqueda (mantiene sede/medico del contexto)
    busqueda.value.bus_nom_num_docu = "";
    busqueda.value.bus_fech_ini = "";
    busqueda.value.bus_fecha_fin = "";
    erroresBusqueda.value = {};

    safeDestroyDataTable(dt.value || "#tablaAdmisiones");
    registros.value = [];
    limpiarFormulario();
}

/**
 * Formatea una fecha ISO a DD/MM/YYYY.
 *
 * @param {string} fecha Fecha en formato ISO o string.
 * @returns {string} Fecha formateada o guion si no existe.
 */
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

/**
 * Formatea una fecha ISO para obtener la hora en formato HH:mm (12h o 24h).
 *
 * @param {string} fecha Fecha en formato ISO o string.
 * @returns {string} Hora formateada o guion.
 */
function formatearHora(fecha) {
    if (!fecha) return "";
    try {
        const d = new Date(fecha);
        if (isNaN(d.getTime())) return "";
        let horas = d.getHours();
        const minutos = String(d.getMinutes()).padStart(2, "0");
        const ampm = horas >= 12 ? "PM" : "AM";
        horas = horas % 12;
        horas = horas ? horas : 12; // Hora '0' se muestra como '12'
        return `${String(horas).padStart(2, "0")}:${minutos} ${ampm}`;
    } catch {
        return "";
    }
}
/**
 * Destruye de forma segura la instancia de DataTable si existe.
 *
 * @param {string} selector Selector jQuery de la tabla a destruir.
 *
 * @returns {any}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function safeDestroyDataTable(selector = "#tablaAdmisiones") {
    if (typeof window === "undefined" || !window.$ || !window.$.fn || !window.$.fn.DataTable)
        return;
    const $t = window.$(selector);
    if (!$t.length) return;
    if (window.$.fn.DataTable.isDataTable($t)) {
        $t.DataTable().clear().destroy();
    }
}
/**
 * Inicializa de forma segura la instancia de DataTable con botones de exportación.
 *
 * @param {string} selector Selector jQuery de la tabla a inicializar.
 *
 * @returns {any}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function safeInitDataTable(selector = "#tablaAdmisiones") {
    if (typeof window === "undefined" || !window.$ || !window.$.fn || !window.$.fn.DataTable)
        return;
    nextTick(() => {
        const $t = window.$(selector);
        if (!$t.length) return;
        if (window.$.fn.DataTable.isDataTable($t)) return;
        $t.DataTable({
            dom: "Bfrtip",
            buttons: ["copy", "csv", "excel", "pdf", "print"],
            language: {
                emptyTable: "No se encontraron registros",
                zeroRecords: "No se encontraron registros",
            },
        });
    });
}
/**
 * Consulta y lista las atenciones disponibles según los filtros de búsqueda.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
async function listarAtenciones() {
    try {
        isBuscandoAtenciones.value = true;

        const res = await axios.post(
            "/api/interface-sahi-ecografias/listar/productos/atencion",
            busqueda.value
        );

        if (res.status == 200) {
            safeDestroyDataTable(dt.value || "#tablaAdmisiones");

            registros.value = res.data.data;

            safeInitDataTable(dt.value || "#tablaAdmisiones");
        }
    } catch (error) {
        erroresBusqueda.value = error?.response?.data?.errors || {};
        toastMessage({
            heading: "Error",
            text: error?.response?.data?.message || "No fue posible consultar las atenciones.",
            icon: "error",
        });
    } finally {
        isBuscandoAtenciones.value = false;
    }
}
/**
 * Prepara y abre el modal de captura de firma de consentimiento para un registro.
 *
 * @param {Object} item Registro de atención seleccionado.
 *
 * @returns {any}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function abrirModalConsentimiento(item) {
    consentimientoItem.value = item;
    consentimientoFirmanteTipo.value = "PACIENTE";
    acompanante.value = { cedula: "", nombres: "", email: "", direccion: "", telefono: "" };
    consentimientoUrl.value = "";
}
/**
 * Genera un enlace temporal (10 min) para la firma del consentimiento y refresca su estado.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
async function generarLinkFirma() {
    if (!consentimientoItem.value?.ecografia_producto_id) return;
    try {
        isGenerandoLinkFirma.value = true;
        const payload = {
            ecografia_producto_id: consentimientoItem.value.ecografia_producto_id,
            sede_id: busqueda.value?.sede_id || sessionStorage.getItem("ST-sede") || null,
            firmante_tipo: consentimientoFirmanteTipo.value,
        };
        if (consentimientoFirmanteTipo.value === "ACOMPANANTE") {
            payload.acompanante = { ...acompanante.value };
        }
        const res = await axios.post("/api/ecografias-consentimientos/token", payload);
        consentimientoUrl.value = res?.data?.url || "";
        // refrescar estado del consentimiento en el listado
        await refrescarConsentimientoItem();
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: error.response?.data?.message || "No fue posible generar el link de firma.",
            icon: "error",
        });
    } finally {
        isGenerandoLinkFirma.value = false;
    }
}
/**
 * Abre en una nueva pestaña el enlace de firma del consentimiento generado.
 *
 * @returns {any}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function abrirLinkFirma() {
    if (!consentimientoUrl.value) return;
    window.open(consentimientoUrl.value, "_blank", "noopener,noreferrer");
}
/**
 * Consulta el consentimiento vigente y actualiza el estado en el registro local.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
async function refrescarConsentimientoItem() {
    if (!consentimientoItem.value?.ecografia_producto_id) return;
    try {
        const res = await axios.get(
            `/api/ecografias-consentimientos/vigente/${consentimientoItem.value.ecografia_producto_id}`
        );
        const c = res?.data;
        consentimientoItem.value.consentimiento_id = c?.id ?? null;
        consentimientoItem.value.consentimiento_estado = c?.estado ?? null;
        consentimientoItem.value.consentimiento_bloquea =
            consentimientoItem.value.tiene_plantilla_consentimiento &&
            c?.estado === "PENDIENTE_PACIENTE";
    } catch (_) {
        // best-effort
    }
}
/**
 * Guarda la admisión de la ecografía y sus productos vía API.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
async function guardarRegistro() {
    // Si no es particular ni cortesía, la atención previa de SAHI es obligatoria
    if (!esParticular.value && !esCortesia.value && !registro.value.atencion_id) {
        toastMessage({
            heading: "¡Atención!",
            text: "Para este tipo de recaudo es obligatorio seleccionar una atención previa proveniente de SAHI.",
            icon: "warning",
        });
        return;
    }

    try {
        isGuardandoEcografia.value = true;

        const ecografiaRes = await axios.post("/api/ecografias", registro.value);

        if (ecografiaRes.status === 200 || ecografiaRes.status === 201) {
            // Mostrar notificación de éxito
            toastMessage({
                heading: "¡Éxito!",
                text: "Agenda guardada correctamente.",
                icon: "success",
            });

            // Actualizar la lista de datos y limpiar el formulario
            listarAtenciones();
            btnCerrarModalForm();

            // Desplegar modal para enviar a Worklist con los datos de la ecografía creada
            const ecoCreada = ecografiaRes.data?.data;
            prepararModalWorklist(ecoCreada, registro.value);
            limpiarFormulario();
        } else {
            toastMessage({
                heading: "Error",
                text: ecografiaRes.data?.message || "No se pudo guardar la agenda.",
                icon: "error",
            });
        }
    } catch (error) {
        // Mostrar errores específicos en el formulario
        if (error.response?.data?.errors) {
            errores.value = error.response.data.errors;
        }
        toastMessage({
            heading: "Error",
            text: error.response?.data?.message || "No fue posible guardar la ecografía.",
            icon: "error",
        });
    } finally {
        isGuardandoEcografia.value = false;
    }
}
/**
 * Carga los datos del registro que se va a anular en el modal correspondiente.
 *
 * @param {Object} data Datos de la atención a anular.
 *
 * @returns {any}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function registroParaAnular(data) {
    registroAnular.value.ecografia_id = data.id;
    registroAnular.value.atencion_id = data?.atencion_id ?? data?.IdAtencion ?? "";
}
/**
 * Anula la ecografía previa confirmación, enviando el motivo al backend.
 *
 * @returns {any}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function anularEcografia() {
    if (registroAnular.value.motivo_anula === "") {
        toastMessage({
            heading: "¡Atención!",
            text: "Debe ingresar el motivo de anulación.",
            icon: "warning",
        });
        return;
    }

    confirmAction({
        title: "Estas seguro de anular la ecografia?",
        text: "No podrás recuperar este registro!",
        icon: "warning",
    }).then(async (ok) => {
        if (!ok) return;
        if (isAnulandoEcografia.value) return;
        isAnulandoEcografia.value = true;
        try {
            const response = await axios.post(
                `/api/ecografia-anular/${registroAnular.value.ecografia_id}`,
                registroAnular.value
            );
            alertSuccess("Ok!", response?.data?.message || "Ecografía anulada.", "success");
            const cerrarModal = document.getElementById("btnCerrarModalAunular");
            if (cerrarModal) cerrarModal.click();
            await listarAtenciones();
        } catch {
            alertError("Error", "No fue posible anular la ecografía.", "error");
        } finally {
            isAnulandoEcografia.value = false;
        }
    });
}

/**
 * Prepara y abre el modal de envío a Worklist para la ecografía admitida.
 *
 * @param {Object} ecoData Datos retornados de la ecografía creada.
 * @param {Object} formOriginal Datos originales del formulario de admisión.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-17
 */
async function prepararModalWorklist(ecoData = {}, formOriginal = {}) {
    const sedeIdActual =
        formOriginal.sede_id ||
        sessionStorage.getItem("ST-sede") ||
        localStorage.getItem("ST-sede") ||
        busqueda.value.sede_id ||
        null;

    const pacId = ecoData.paciente_id || ecoData.paciente?.id || formOriginal.paciente_id;
    const nombres =
        `${formOriginal.nombres || ""} ${formOriginal.seg_nom || ""} ${formOriginal.pri_ape || ""} ${formOriginal.seg_ape || ""}`.trim();

    // Extraer productos admitidos
    let prods = [];
    if (ecoData.ecografia_productos && Array.isArray(ecoData.ecografia_productos)) {
        prods = ecoData.ecografia_productos.map((ep) => ({
            id: ep.producto_id || ep.config_producto?.id,
            nom_produc: ep.config_producto?.nom_produc || ep.nom_produc,
            cod_cups: ep.config_producto?.cod_cups || ep.cod_cups,
            ecografia_producto_id: ep.id,
        }));
    } else if (formOriginal.productosAgenda && Array.isArray(formOriginal.productosAgenda)) {
        prods = formOriginal.productosAgenda.map((p) => ({
            id: p.IdProducto || p.id,
            nom_produc: p.NomProducto || p.nom_produc,
            cod_cups: p.CodLegal || p.cod_cups,
            ecografia_producto_id: null,
        }));
    }

    const primerProd = prods[0] || null;

    worklistData.value = {
        pacienteId: pacId,
        pacienteNombre: nombres || ecoData.paciente?.nombres || "Paciente",
        numDocu: formOriginal.num_docu || ecoData.paciente?.num_docu || "",
        atencionId: formOriginal.atencion_id || ecoData.atencion_id || "",
        ecografiaId: ecoData.id || null,
        ecografiaProductoId: primerProd?.ecografia_producto_id || null,
        productos: prods,
    };

    worklistForm.value = {
        paciente_id: pacId,
        sede_id: sedeIdActual ? Number(sedeIdActual) : null,
        producto_id: primerProd?.id || null,
        accession_no: "",
        modality: "US",
        station_aet: "",
        ecografia_id: ecoData.id || null,
        ecografia_producto_id: primerProd?.ecografia_producto_id || null,
        observaciones: "",
    };

    modalWorklistVisible.value = true;

    await Promise.all([generarAccessionSugerido(), cargarDispositivosWorklist(sedeIdActual)]);
}

/**
 * Consulta los dispositivos configurados para el módulo de ecografías en la sede activa.
 *
 * @param {number|string} sedeId Identificador de la sede.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-17
 */
async function cargarDispositivosWorklist(sedeId) {
    loadingDispositivos.value = true;
    dispositivosWorklist.value = [];
    try {
        const idSede =
            sedeId || sessionStorage.getItem("ST-sede") || localStorage.getItem("ST-sede");

        if (!idSede) return;

        // Módulo 2 = Ecografias
        const res = await axios.get("/api/config-worklist-dispositivos", {
            params: { sede_id: idSede, modulo_id: 2 },
        });

        const items = res.data?.data || res.data || [];
        if (Array.isArray(items) && items.length > 0) {
            dispositivosWorklist.value = items
                .filter((d) => d.estado)
                .map((d) => ({
                    aet: d.ae_title || d.codigo,
                    nombre: `${d.nombre} (${d.ae_title || d.codigo}) — ${d.ubicacion || "Consultorio"}`,
                    station_name: d.nombre,
                    ip: d.ip_address,
                }));

            if (dispositivosWorklist.value.length > 0) {
                worklistForm.value.station_aet = dispositivosWorklist.value[0].aet;
            }
        } else {
            // Fallback: consultar estaciones del PACS directamente si no hay dispositivos en la tabla local
            const resPacs = await axios.get("/api/pacientes/worklist-estaciones", {
                params: { sede_id: idSede, modality: "US" },
            });
            if (resPacs.data?.status === 200 && Array.isArray(resPacs.data?.data)) {
                dispositivosWorklist.value = resPacs.data.data.map((e) => ({
                    aet: e.aet,
                    nombre: e.nombre || e.aet,
                    station_name: e.station_name || e.aet,
                }));
                if (dispositivosWorklist.value.length > 0) {
                    worklistForm.value.station_aet = dispositivosWorklist.value[0].aet;
                }
            }
        }
    } catch {
        dispositivosWorklist.value = [];
    } finally {
        loadingDispositivos.value = false;
    }
}

/**
 * Genera el Accession Number sugerido para la modalidad de ecografía (US).
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-17
 */
async function generarAccessionSugerido() {
    try {
        const idSede =
            worklistForm.value.sede_id ||
            sessionStorage.getItem("ST-sede") ||
            localStorage.getItem("ST-sede");

        const res = await axios.get("/api/pacientes/accession-sugerido", {
            params: { sede_id: idSede, modality: "US" },
        });

        if (res.data?.data?.accession_no) {
            worklistForm.value.accession_no = res.data.data.accession_no;
        }
    } catch {
        const hoy = new Date().toISOString().slice(0, 10).replace(/-/g, "");
        worklistForm.value.accession_no = `US-${hoy}-0001`;
    }
}

/**
 * Cierra el modal de envío a Worklist.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-17
 */
function cerrarModalWorklist() {
    modalWorklistVisible.value = false;
}

/**
 * Envía la orden de ecografía a la Modality Worklist DICOM de la sede.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-17
 */
async function confirmarEnvioWorklist() {
    if (loadingWorklist.value) return;
    loadingWorklist.value = true;

    try {
        const prodSeleccionado = worklistData.value.productos.find(
            (p) => String(p.id) === String(worklistForm.value.producto_id)
        );

        const payload = {
            paciente_id: worklistForm.value.paciente_id,
            sede_id: worklistForm.value.sede_id,
            producto_id: worklistForm.value.producto_id,
            accession_no: worklistForm.value.accession_no,
            modality: "US",
            station_aet: worklistForm.value.station_aet,
            ecografia_id: worklistForm.value.ecografia_id,
            ecografia_producto_id:
                prodSeleccionado?.ecografia_producto_id ||
                worklistForm.value.ecografia_producto_id ||
                null,
            observaciones: worklistForm.value.observaciones || null,
        };

        const res = await axios.post("/api/pacientes/enviar-worklist", payload);

        toastMessage({
            heading: "¡Enviado a Worklist!",
            text:
                res.data?.message ||
                "Paciente y procedimiento cargados en la consola del ecógrafo exitosamente.",
            icon: "success",
        });

        cerrarModalWorklist();
    } catch (err) {
        toastMessage({
            heading: "Error en Worklist",
            text:
                err.response?.data?.message ||
                "No fue posible enviar la orden a la consola del ecógrafo. Verifique la conexión con el PACS.",
            icon: "error",
        });
    } finally {
        loadingWorklist.value = false;
    }
}
/**
 * Inicializa la sede actual desde sesión y limpia la lista de registros.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
async function initSede() {
    const sede = sessionStorage.getItem("ST-sede") || localStorage.getItem("ST-sede");
    registro.value.sede_id = sede || null;
    busqueda.value.sede_id = sede || null;
    if (sede && !sessionStorage.getItem("ST-sede")) {
        sessionStorage.setItem("ST-sede", String(sede));
    }
    await establecerSedeComposable();
    registros.value = [];
}
/**
 * Establece el médico y sede actuales desde almacenamiento y limpia registros.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
async function establecerMedico() {
    const sede = sessionStorage.getItem("ST-sede") || localStorage.getItem("ST-sede");
    registro.value.sede_id = sede || null;
    busqueda.value.sede_id = sede || null;
    medicoActual.value =
        localStorage.getItem("ST-ecografia-medico") ||
        localStorage.getItem("ST-medico") ||
        sessionStorage.getItem("ST-medico");

    await establecerSedeComposable();
    registros.value = [];
}
/**
 * Consulta el estado de envío a SAHI de un producto de ecografía.
 *
 * @param {Object} item Registro de atención con el producto de ecografía.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
async function consultarEstadoSahi(item) {
    if (!sedeInterface.value) return;
    if (!item?.ecografia_producto_id) return;

    const id = item.ecografia_producto_id;
    try {
        isConsultandoSahi.value[id] = true;
        const estado = await consultarEstado(id);

        item.estado_envio_sahi = estado.estado_envio_sahi ?? item.estado_envio_sahi;
        item.fechor_envio_sahi = estado.fechor_envio_sahi ?? item.fechor_envio_sahi;
        item.error_envio_sahi = estado.error_envio_sahi ?? null;
        item.sahi_esquema_149 = estado.sahi_esquema_149 ?? item.sahi_esquema_149;
        item.sahi_esquema_150 = estado.sahi_esquema_150 ?? item.sahi_esquema_150;
    } catch (error) {
        toastMessage({
            heading: "SAHI",
            text: error.response?.data?.message || "No se pudo consultar el estado SAHI.",
            icon: "warning",
        });
    } finally {
        isConsultandoSahi.value[id] = false;
    }
}
/**
 * Reenvía un producto de ecografía a SAHI marcándolo como en proceso de envío.
 *
 * @param {Object} item Registro de atención con el producto de ecografía.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
async function reenviarASahi(item) {
    if (!sedeInterface.value) return;
    if (!item?.ecografia_producto_id) return;

    const id = item.ecografia_producto_id;
    try {
        isEnviandoSahi.value[id] = true;
        item.estado_envio_sahi = "ENVIANDO";
        item.error_envio_sahi = null;

        await enviarEcografiaProducto(id);

        toastMessage({ heading: "SAHI", text: "Envío a SAHI encolado.", icon: "info" });
    } catch (error) {
        item.estado_envio_sahi = "ERROR";
        item.error_envio_sahi = error.response?.data?.message || "No se pudo reenviar a SAHI.";
        toastMessage({
            heading: "SAHI",
            text: item.error_envio_sahi,
            icon: "warning",
            hideAfter: 4500,
        });
    } finally {
        isEnviandoSahi.value[id] = false;
    }
}
/**
 * Lista los usuarios con rol de médico para el módulo de ecografías.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
async function listarMedicos() {
    try {
        const res = await axios.get(`/api/user.listarUsuarios/Medico/2`);
        medicos.value = res.data.data;
    } catch {
        medicos.value = [];
    }
}
/**
 * Lista los tipos de documento activos para el formulario de paciente.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
async function listarTipoDocumentos() {
    try {
        const res = await axios.get("/api/config-tipos-documentos/listar/activos");
        tipoDocumentos.value = res.data.data;
    } catch {
        tipoDocumentos.value = [];
    }
}
/**
 * Lista los tipos de recaudo activos (Atención, Particular, Cortesía).
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-17
 */
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
/**
 * Lista los productos de configuración disponibles para la agenda.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
async function listarProductos() {
    try {
        const res = await axios.get("/api/config-productos");
        productos.value = res.data.data;
    } catch {
        productos.value = [];
    }
}
/**
 * Asigna el médico seleccionado al registro desde el componente Select2.
 *
 * @param {any} value Valor seleccionado en el Select2 (objeto o arreglo).
 *
 * @returns {any}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
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
 * Asigna el tipo de documento seleccionado al registro desde el Select2.
 *
 * @param {any} value Valor seleccionado en el Select2 (objeto o arreglo).
 *
 * @returns {any}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function onTipoDocumentoSelect(value) {
    let selectedId = null;
    if (Array.isArray(value) && value.length > 0) {
        const v = value[0];
        selectedId = String(typeof v === "object" && v && v.id != null ? v.id : v);
    } else if (value && typeof value === "object" && value.id != null) {
        selectedId = String(value.id);
    } else if (value != null) {
        selectedId = String(value);
    }
    registro.value.tipo_documento_id = selectedId || "";
}
/**
 * Agrega el producto seleccionado a la agenda de la ecografía y limpia la selección.
 *
 * @param {any} value Identificador del producto seleccionado.
 *
 * @returns {any}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function onProductoSelect(value) {
    let selectedId = value || null;
    if (selectedId != null) {
        var prod = productos.value.find(function (p) {
            return String(p.id) === String(selectedId);
        });
        if (prod) {
            const yaExiste = registro.value.productosAgenda.some(
                (p) => (p.IdProducto || p.id) == prod.id
            );
            if (!yaExiste) {
                registro.value.productosAgenda.push({
                    id: prod.id,
                    IdProducto: prod.id,
                    CodLegal: prod.cod_cups,
                    CodProducto: prod.cod_cups,
                    cod_cups: prod.cod_cups,
                    NomProducto: prod.nom_produc,
                    nom_produc: prod.nom_produc,
                    Cantidad: 1,
                    ValProduc: esCortesia.value ? 0 : prod.val_produc,
                });
            }

            // Limpiar selección
            productoSelecciondo.value = "";
        }
    }
}
/**
 * Cierra la visualización de la barra lateral del formulario de admisión.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2024-04-04
 */
function cerrarSidebar() {
    sidebarVisible.value = false;
}

const btnCerrarModalForm = cerrarSidebar;

/**
 * Abre el panel lateral, reconstruye componentes dependientes y aplica foco accesible.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-19
 */
async function abrirModal() {
    sidebarVisible.value = true;

    if (!medicos.value || medicos.value.length === 0) {
        await listarMedicos();
    }
    if (!productos.value || productos.value.length === 0) {
        await listarProductos();
    }
    if (!tipoDocumentos.value || tipoDocumentos.value.length === 0) {
        await listarTipoDocumentos();
    }
    if (!tiposRecaudo.value || tiposRecaudo.value.length === 0) {
        await listarTiposRecaudo();
    }

    nextTick(() => {
        setTimeout(() => {
            selTipoDoc.value?.rebuild();
            selMedico.value?.rebuild();
            selProducto.value?.rebuild();
        }, 50);
        document.getElementById("num_docu")?.focus();
    });
}

/**
 * Carga los datos de la atención seleccionada en el formulario de admisión.
 *
 * @param {Object} data Datos de la atención a mostrar en el formulario.
 *
 * @returns {any}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function mostrarRegistro(data = {}) {
    tituloModal.value = `Admisionar al paciente: ${data.PrimerNombre} ${data.SegundoNombre}`;

    registro.value.paciente_id = data.paciente_id;
    registro.value.num_docu = data.NumDocumento;
    registro.value.nombres = data.PrimerNombre;
    registro.value.seg_nom = data.SegundoNombre;
    registro.value.pri_ape = data.PrimerApellido;
    registro.value.seg_ape = data.SegundoApellido;
    // Homologar sexo (SAHI: 1 = Masculino, 2 = Femenino, o códigos textuales)
    const rawSexo = String(data.IdSexo ?? data.sexo ?? "")
        .trim()
        .toUpperCase();
    if (rawSexo === "1" || rawSexo === "M" || rawSexo === "MASCULINO") {
        registro.value.sexo = "M";
    } else if (rawSexo === "2" || rawSexo === "F" || rawSexo === "FEMENINO") {
        registro.value.sexo = "F";
    } else if (rawSexo === "O" || rawSexo === "OTRO" || rawSexo === "3") {
        registro.value.sexo = "O";
    } else {
        registro.value.sexo = data.IdSexo ?? "";
    }
    registro.value.fec_naci = data.FecNacimiento;
    registro.value.direccion = data.DirCasa;
    registro.value.tel = data.TelCasa;
    registro.value.cel = data.TelCelular || data.TelCasa;
    registro.value.atencion_id = data?.IdAtencion ?? data?.atencion_id ?? null;
    registro.value.contrato = data.DesContrato;
    registro.value.plan = data.DesPlan;
    registro.value.admin_salud = data.NomTercero;
    registro.value.quien_ordeno = data.quien_ordeno;
    registro.value.ubicacion = data.NomUbicacion || data.ubicacion || "";
    registro.value.cama = data.Cama || data.cama || "";

    registro.value.productosAgenda = [data];
    // Homologar tipo de documento
    const codigo = data.CodTipoDoc;
    const tipoHomologado = tipoDocumentoOptions.value.find((t) => t.codigo === codigo);

    if (tipoHomologado) {
        registro.value.tipo_documento_id = tipoHomologado.id;
    }

    abrirModal();
}
/**
 * Abre directamente el formulario de admisión para Particular o Cortesía sin requerir atención previa.
 *
 * @param {'PARTICULAR'|'CORTESIA'} tipoCodigo Código del tipo de recaudo.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-18
 */
async function abrirAdmisionDirecta(tipoCodigo = "PARTICULAR") {
    limpiarFormulario();

    if (!tiposRecaudo.value || tiposRecaudo.value.length === 0) {
        await listarTiposRecaudo();
    }

    const trEncontrado = tiposRecaudo.value.find((tr) => tr.codigo === tipoCodigo);
    if (trEncontrado) {
        registro.value.tipo_recaudo_id = trEncontrado.id;
    } else {
        registro.value.tipo_recaudo_id = tipoCodigo === "CORTESIA" ? 3 : 2;
    }

    registro.value.atencion_id = "";
    tituloModal.value =
        tipoCodigo === "CORTESIA"
            ? "Nueva Admisión de Cortesía (Directa)"
            : "Nueva Admisión Particular (Directa)";

    await abrirModal();
}

/**
 * Consulta un paciente registrado en el sistema local o externo por su número de documento para autocompletar.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-18
 */
async function buscarPacientePorDocumento() {
    const doc = String(registro.value.num_docu || "").trim();
    if (!doc || doc.length < 3) return;

    try {
        const sedeId =
            registro.value.sede_id ||
            sessionStorage.getItem("ST-sede") ||
            localStorage.getItem("ST-sede") ||
            1;

        // 1. Búsqueda Local en LIDiag
        const res = await axios.post("/api/pacientes/listar", {
            bus_documento: doc,
            sede_id: sedeId,
        });

        const lista = res.data?.data?.data || res.data?.data || [];
        const encontradoLocal = Array.isArray(lista)
            ? lista.find((p) => String(p.num_docu || "").trim() === doc) || lista[0]
            : null;

        if (
            encontradoLocal &&
            String(encontradoLocal.num_docu || "")
                .trim()
                .includes(doc)
        ) {
            registro.value.paciente_id = encontradoLocal.id;
            registro.value.nombres = encontradoLocal.nombres || registro.value.nombres;
            registro.value.seg_nom = encontradoLocal.seg_nom || registro.value.seg_nom;
            registro.value.pri_ape = encontradoLocal.pri_ape || registro.value.pri_ape;
            registro.value.seg_ape = encontradoLocal.seg_ape || registro.value.seg_ape;
            if (encontradoLocal.sexo) {
                const s = String(encontradoLocal.sexo).trim().toUpperCase();
                registro.value.sexo =
                    s === "1" || s === "M" ? "M" : s === "2" || s === "F" ? "F" : s;
            }
            registro.value.fec_naci = encontradoLocal.fec_naci || registro.value.fec_naci;
            registro.value.direccion = encontradoLocal.direccion || registro.value.direccion;
            registro.value.tel = encontradoLocal.tel || registro.value.tel;
            registro.value.cel = encontradoLocal.cel || registro.value.cel;
            registro.value.email = encontradoLocal.email || registro.value.email;
            if (encontradoLocal.tipo_documento_id) {
                registro.value.tipo_documento_id = encontradoLocal.tipo_documento_id;
                selTipoDoc.value?.rebuild();
            }
            return;
        }

        // 2. Si no está local, buscar en SAHI si la sede cuenta con interfaz
        try {
            const resSahi = await axios.get(
                `/api/interface-sahi/buscar-paciente/${encodeURIComponent(doc)}`
            );
            if (resSahi.status === 200 && resSahi.data?.data) {
                const c = resSahi.data.data;
                registro.value.nombres = c.PrimerNombre || registro.value.nombres;
                registro.value.seg_nom = c.SegundoNombre || "";
                registro.value.pri_ape = c.PrimerApellido || registro.value.pri_ape;
                registro.value.seg_ape = c.SegundoApellido || "";
                if (c.FecNacimiento) {
                    registro.value.fec_naci = c.FecNacimiento;
                }
                if (c.IdSexo) {
                    const s = String(c.IdSexo).trim().toUpperCase();
                    registro.value.sexo =
                        s === "1" || s === "M" ? "M" : s === "2" || s === "F" ? "F" : s;
                }
                if (c.DirCasa) registro.value.direccion = c.DirCasa;
                if (c.TelCasa) registro.value.tel = c.TelCasa;
                if (c.CodTipoDoc && tipoDocumentoOptions.value?.length) {
                    const tipoHomologado = tipoDocumentoOptions.value.find(
                        (t) => t.codigo === c.CodTipoDoc
                    );
                    if (tipoHomologado) {
                        registro.value.tipo_documento_id = tipoHomologado.id;
                        selTipoDoc.value?.rebuild();
                    }
                }
            }
        } catch (_) {
            // No existe en SAHI o falló la conexión
        }
    } catch (_) {
        // Búsqueda auxiliar no bloqueante
    }
}

/**
 * Reinicia los campos del formulario de admisión de la ecografía.
 *
 * @returns {any}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function limpiarFormulario() {
    tituloModal.value = "";

    registro.value.paciente_id = "";
    registro.value.num_docu = "";
    registro.value.nombres = "";
    registro.value.seg_nom = "";
    registro.value.pri_ape = "";
    registro.value.seg_ape = "";
    registro.value.sexo = "";
    registro.value.fec_naci = "";
    registro.value.direccion = "";
    registro.value.tel = "";
    registro.value.cel = "";
    registro.value.atencion_id = "";
    registro.value.contrato = "";
    registro.value.plan = "";
    registro.value.admin_salud = "";
    registro.value.quien_ordeno = "";
    registro.value.ubicacion = "";
    registro.value.cama = "";

    registro.value.productosAgenda = [];
}
/**
 * Elimina un producto de la agenda de la ecografía por su código legal.
 *
 * @param {string} codProduc Código legal del producto a quitar.
 *
 * @returns {any}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function quitarProductoAgenda(codProduc) {
    registro.value.productosAgenda = registro.value.productosAgenda.filter(
        (x) => x.CodLegal !== codProduc
    );
}

/**
 * Hook de montaje: valida sede, carga catálogos y escucha cambios de sede.
 *
 * @returns {any}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
async function onSedeSeleccionada(data) {
    const sedeId =
        data?.idSede || sessionStorage.getItem("ST-sede") || localStorage.getItem("ST-sede");
    if (sedeId) {
        sessionStorage.setItem("ST-sede", String(sedeId));
        localStorage.setItem("ST-sede", String(sedeId));
    }
    await establecerMedico();
}

/**
 * Hook de montaje: valida sede, carga catálogos y escucha cambios de sede.
 *
 * @returns {any}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
onMounted(() => {
    listarMedicos();
    listarTipoDocumentos();
    listarProductos();
    listarTiposRecaudo();

    emitter.on("sedeSeleccionada", onSedeSeleccionada);

    const sedeActual = sessionStorage.getItem("ST-sede") || localStorage.getItem("ST-sede");

    if (!sedeActual) {
        if (emitter) {
            emitter.emit("solicitarSedeActiva");
        }
        return;
    }

    if (!sessionStorage.getItem("ST-sede")) {
        sessionStorage.setItem("ST-sede", String(sedeActual));
    }

    initSede();
});

onBeforeUnmount(() => {
    emitter.off("sedeSeleccionada", onSedeSeleccionada);
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
        width: 100% !important;
    }
}

.audio {
    width: 50px;
    height: 5px;
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

/* ============================================================== */
/* Estilos compactos para tabla de atenciones disponibles         */
/* ============================================================== */
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
    line-height: 1.3;
    max-width: 280px;
    word-break: break-word;
    color: #1a202c;
}

.table-hover tbody tr:hover {
    background-color: #f1f7ff;
}
</style>
