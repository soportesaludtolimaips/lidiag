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
                    <li class="breadcrumb-item active">Lectura de ecografias</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-info waves-effect waves-light m-r-10"
                    @click="listarPendiestePorLectura()"
                    :disabled="isBuscandoPendientes"
                    title="Actualizar lista de pendientes"
                >
                    <i
                        class="fa mr-1"
                        :class="isBuscandoPendientes ? 'fa-spinner fa-spin' : 'fa-refresh'"
                    ></i>
                    Refrescar
                </button>
                <i
                    class="fa fa-life-ring model_img img-responsive text-info"
                    @click="modalAyudaVisible = true"
                    role="button"
                    title="Comandos de voz"
                    tabindex="0"
                ></i>
            </div>
            <!-- Modal de comandos de voz reutilizable -->
            <VoiceModal v-model="modalAyudaVisible" />
        </div>
        <!-- ============================================================== -->
        <!-- End Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Start Page Content -->
        <!-- ============================================================== -->
        <div class="row">
            <div class="col-12 table-responsive">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h4 class="card-title mb-0">Listado de atenciones disponibles.</h4>
                                <h6 class="card-subtitle text-info mb-0">
                                    Asigne los médicos para su respectiva lectura.
                                </h6>
                            </div>
                            <div>
                                <button
                                    type="button"
                                    class="btn btn-sm"
                                    :class="isDarkMode ? 'btn-warning' : 'btn-outline-dark'"
                                    @click="toggleDarkMode"
                                    :title="
                                        isDarkMode
                                            ? 'Cambiar a Modo Claro'
                                            : 'Cambiar a Modo Oscuro / Ecografía'
                                    "
                                >
                                    <i :class="isDarkMode ? 'fa fa-sun-o' : 'fa fa-moon-o'"></i>
                                    {{ isDarkMode ? "Modo Claro" : "Modo Oscuro" }}
                                </button>
                            </div>
                        </div>
                        <div v-if="!busqueda.medico_id" class="alert alert-warning mt-3">
                            <i class="fa fa-exclamation-triangle"></i>
                            Seleccione un médico desde el menú superior (Módulo → Médico) para
                            listar las ecografías pendientes.
                        </div>
                        <table-skeleton-loader
                            :visible="isBuscandoPendientes"
                            :rows="6"
                            :columns="10"
                        />
                        <empty-state-component
                            v-if="
                                !isBuscandoPendientes &&
                                busqueda.medico_id &&
                                registros.length === 0
                            "
                            icon="fa fa-stethoscope"
                            title="No hay ecografías pendientes"
                            description="No se encontraron ecografías pendientes de lectura para el médico seleccionado."
                            action-text="Actualizar lista"
                            action-icon="fa fa-refresh"
                            @action="listarPendiestePorLectura()"
                        />
                        <div
                            class="table-responsive"
                            v-show="!isBuscandoPendientes && registros.length > 0"
                        >
                            <table
                                id="tablaEcografiasLectura"
                                ref="dt"
                                class="table table-hover table-bordered table-custom-compact"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 11%">Atención / Fecha</th>
                                        <th style="width: 20%">Paciente</th>
                                        <th style="width: 25%">Examen / Procedimiento</th>
                                        <th style="width: 20%">Aseguramiento / EPS</th>
                                        <th style="width: 14%">Ubicación & Cama</th>
                                        <th style="width: 10%" class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(item, index) in registros"
                                        :key="item.ecografia_producto_id || item.id || index"
                                    >
                                        <!-- 1. Atención / Fecha -->
                                        <td class="align-middle">
                                            <div
                                                class="font-weight-bold text-dark"
                                                style="font-size: 13px"
                                            >
                                                <i class="fa fa-hashtag text-muted mr-1"></i>
                                                {{ item.atencion_id || "Directo" }}
                                            </div>
                                            <small
                                                v-if="item.created_at"
                                                class="text-muted d-block"
                                                style="font-size: 11px"
                                            >
                                                <i class="fa fa-calendar-o mr-1"></i
                                                >{{ formatearFecha(item.created_at) }}
                                            </small>
                                        </td>

                                        <!-- 2. Paciente + Documento -->
                                        <td class="align-middle">
                                            <div class="paciente-nombre font-weight-bold text-dark">
                                                {{ item.paciente?.nombres }}
                                                {{ item.paciente?.seg_nom }}
                                                {{ item.paciente?.pri_ape }}
                                                {{ item.paciente?.seg_ape }}
                                            </div>
                                            <div
                                                class="text-primary mt-1"
                                                style="font-size: 12px; font-weight: 600"
                                            >
                                                <i class="fa fa-id-card-o mr-1"></i>
                                                {{ item.paciente?.num_docu }}
                                            </div>
                                        </td>

                                        <!-- 3. Examen / Procedimiento + Consentimiento -->
                                        <td class="align-middle">
                                            <div
                                                class="examen-titulo font-weight-bold text-dark"
                                                :title="item.nom_produc"
                                            >
                                                {{ item.nom_produc }}
                                            </div>
                                            <div v-if="item.cod_cups" class="mt-1">
                                                <small class="text-muted" style="font-size: 11px">
                                                    Cód: {{ item.cod_cups }}
                                                </small>
                                            </div>
                                            <div class="mt-1">
                                                <small
                                                    v-if="
                                                        item.consentimiento_estado ===
                                                        'PENDIENTE_PACIENTE'
                                                    "
                                                    class="badge badge-danger"
                                                >
                                                    Firma paciente pendiente
                                                </small>
                                                <small
                                                    v-else-if="
                                                        item.consentimiento_estado ===
                                                        'PENDIENTE_MEDICO'
                                                    "
                                                    class="badge badge-warning text-white"
                                                >
                                                    Firma médico pendiente
                                                </small>
                                                <small
                                                    v-else-if="
                                                        item.consentimiento_estado === 'COMPLETO'
                                                    "
                                                    class="badge badge-success"
                                                >
                                                    Consentimiento completo
                                                </small>
                                            </div>
                                        </td>

                                        <!-- 4. Aseguramiento / EPS + Contrato + Plan -->
                                        <td class="align-middle">
                                            <div
                                                class="font-weight-bold text-dark text-truncate"
                                                style="max-width: 240px"
                                                :title="
                                                    item.admin_salud?.nom_admin_salud ||
                                                    'Particular / Directo'
                                                "
                                            >
                                                {{
                                                    item.admin_salud?.nom_admin_salud ||
                                                    (item.tipo_recaudo_id == 2
                                                        ? "PARTICULAR"
                                                        : item.tipo_recaudo_id == 3
                                                          ? "CORTESÍA"
                                                          : "Sin EPS")
                                                }}
                                            </div>
                                            <div
                                                v-if="item.contrato || item.plan"
                                                class="small text-muted mt-1"
                                                style="font-size: 11px; line-height: 1.3"
                                            >
                                                <div
                                                    v-if="item.contrato"
                                                    class="text-truncate"
                                                    style="max-width: 240px"
                                                    :title="'Contrato: ' + item.contrato"
                                                >
                                                    <strong>C:</strong> {{ item.contrato }}
                                                </div>
                                                <div
                                                    v-if="item.plan"
                                                    class="text-truncate"
                                                    style="max-width: 240px"
                                                    :title="'Plan: ' + item.plan"
                                                >
                                                    <strong>P:</strong> {{ item.plan }}
                                                </div>
                                            </div>
                                        </td>

                                        <!-- 5. Ubicación & Cama -->
                                        <td class="align-middle">
                                            <div
                                                class="text-truncate"
                                                style="max-width: 160px"
                                                :title="item.ubicacion || 'Sin ubicación'"
                                            >
                                                <i class="fa fa-hospital-o text-muted mr-1"></i>
                                                <span style="font-size: 12px">{{
                                                    item.ubicacion || "Sin ubicación"
                                                }}</span>
                                            </div>
                                            <div class="mt-1">
                                                <span
                                                    v-if="item.cama"
                                                    class="badge badge-light border text-dark font-weight-bold"
                                                    style="font-size: 10px"
                                                >
                                                    Cama: {{ item.cama }}
                                                </span>
                                                <span
                                                    v-else
                                                    class="text-muted font-italic"
                                                    style="font-size: 10px"
                                                >
                                                    Sin cama
                                                </span>
                                            </div>
                                        </td>

                                        <!-- 6. Acciones -->
                                        <td class="text-center align-middle text-nowrap">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm mr-1"
                                                :disabled="
                                                    bloqueoLectura ||
                                                    (item.tiene_plantilla_consentimiento &&
                                                        (!item.consentimiento_estado ||
                                                            item.consentimiento_estado ===
                                                                'PENDIENTE_PACIENTE'))
                                                "
                                                @click="
                                                    actualizar = true;
                                                    mostrarRegistro(item, index);
                                                "
                                                title="Realizar lectura"
                                                aria-label="Realizar lectura"
                                            >
                                                <i class="fa fa-check mr-1"></i> Leer
                                            </button>

                                            <button
                                                v-if="
                                                    can(
                                                        'ecografias.consentimientos.firmar_medico'
                                                    ) &&
                                                    item.consentimiento_id &&
                                                    item.consentimiento_estado ===
                                                        'PENDIENTE_MEDICO'
                                                "
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-success btn-sm"
                                                :disabled="
                                                    isFirmandoConsentimiento[item.consentimiento_id]
                                                "
                                                @click="firmarConsentimientoMedico(item)"
                                                title="Firmar consentimiento"
                                                aria-label="Firmar consentimiento"
                                            >
                                                <i
                                                    class="fa"
                                                    :class="
                                                        isFirmandoConsentimiento[
                                                            item.consentimiento_id
                                                        ]
                                                            ? 'fa-spinner fa-spin'
                                                            : 'fa-pencil'
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

        <!-- ============================================================== -->
        <!-- Right sidebar -->
        <!-- ============================================================== -->
        <!-- Backdrop para cerrar al hacer clic y atenuar fondo -->
        <transition name="fade">
            <div
                v-if="sidebarVisible && !isZenMode"
                class="sidebar-backdrop"
                @click="cerrarSidebar()"
                aria-hidden="true"
            ></div>
        </transition>

        <!-- .right-sidebar -->
        <div
            id="drawerLecturaEcografia"
            class="right-sidebar sidebar-scroll"
            :class="{ 'shw-rside': sidebarVisible, 'dark-mode': isDarkMode, 'zen-mode': isZenMode }"
        >
            <div class="slimscrollright">
                <div class="rpanel-title d-flex justify-content-between align-items-center">
                    <div>
                        {{ tituloAtencion }}
                        <br />
                        {{ tituloModal }}
                        <br />
                        {{ subTituloModal }}
                    </div>
                    <div class="d-flex align-items-center">
                        <button
                            type="button"
                            class="btn btn-sm btn-link p-0 mr-3"
                            style="color: inherit; text-decoration: none"
                            @click="toggleZenMode"
                            :title="
                                isZenMode
                                    ? 'Salir de Pantalla Completa / Modo Zen'
                                    : 'Modo Pantalla Completa / Modo Zen (100% Pantalla)'
                            "
                        >
                            <i :class="isZenMode ? 'fa fa-compress' : 'fa fa-expand'"></i>
                            <span class="ml-1 d-none d-sm-inline">{{
                                isZenMode ? "Normal" : "Zen"
                            }}</span>
                        </button>
                        <button
                            type="button"
                            class="btn btn-sm btn-link p-0 mr-3"
                            style="color: inherit; text-decoration: none"
                            @click="toggleDarkMode"
                            :title="
                                isDarkMode
                                    ? 'Cambiar a Modo Claro'
                                    : 'Cambiar a Modo Oscuro / Ecografía'
                            "
                        >
                            <i :class="isDarkMode ? 'fa fa-sun-o' : 'fa fa-moon-o'"></i>
                            <span class="ml-1 d-none d-sm-inline">{{
                                isDarkMode ? "Claro" : "Oscuro"
                            }}</span>
                        </button>
                        <button
                            type="button"
                            class="right-side-toggle"
                            @click="btnCerrarModalForm()"
                            aria-label="Cerrar panel"
                        >
                            <i class="ti-close" id="btnCerrarModalForm"></i>
                        </button>
                    </div>
                </div>
                <div class="r-panel-body">
                    <!-- ============================================================== -->
                    <!-- Aqui va el contenido de los formularios -->
                    <!-- ============================================================== -->
                    <form action="#">
                        <div class="form-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h5 class="card-title text-info">
                                                <i class="fa fa-edit"></i> Lectura
                                            </h5>
                                        </div>
                                        <div class="ml-auto">
                                            <ul class="list-inline text-center">
                                                <li>
                                                    <button
                                                        type="button"
                                                        class="btn-mic"
                                                        @click="toggleSpeechRecognition"
                                                        :aria-label="
                                                            voiceState === 'active'
                                                                ? 'Detener grabación de voz'
                                                                : 'Iniciar grabación de voz'
                                                        "
                                                        :aria-pressed="
                                                            String(voiceState === 'active')
                                                        "
                                                        style="
                                                            width: 44px;
                                                            height: 44px;
                                                            padding: 0;
                                                            border: none;
                                                            background: none;
                                                            cursor: pointer;
                                                        "
                                                    >
                                                        <img title="Grabadora" :src="microfono" />
                                                    </button>
                                                </li>
                                                <li>
                                                    <i
                                                        class="fa fa-file-pdf-o text-danger fa-2x model_img img-responsive"
                                                        :class="{
                                                            'opacity-50': isGenerandoReporte,
                                                        }"
                                                        @click="
                                                            !isGenerandoReporte && generarReporte()
                                                        "
                                                        title="Generar e imprimir reporte PDF"
                                                    />
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr />
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label font-weight-bold"
                                        >Tipos de plantillas</label
                                    >
                                    <Select2
                                        ref="selTipoPlantilla"
                                        :value="registro.tipo_plantilla_id"
                                        :options="tiposPlantillasOptions"
                                        width="100%"
                                        :allow-clear="true"
                                        :label-fields="['nom_tipo']"
                                        placeholder="Elija el tipo de plantilla"
                                        dropdown-parent-selector="#drawerLecturaEcografia"
                                        @input="onTipoPlantillaSelect"
                                    />
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label class="control-label font-weight-bold">Plantilla</label>
                                    <Select2
                                        ref="selPlantilla"
                                        :value="registro.plantilla_id"
                                        width="100%"
                                        :allow-clear="true"
                                        :options="plantillasOptions"
                                        :label-fields="['nom_plantilla']"
                                        placeholder="Elija la plantilla"
                                        dropdown-parent-selector="#drawerLecturaEcografia"
                                        @input="onPlantillaSelect"
                                    />
                                </div>
                            </div>
                        </div>
                        <hr />
                        <div class="row">
                            <div class="col-md-8">
                                <h5 class="card-title text-info">
                                    <i class="fa fa-pencil"></i> Lectura
                                </h5>
                                <div class="form-group">
                                    <WysiwygEditor
                                        v-model="registro.lectura"
                                        placeholder="Ingrese aquí la lectura del procedimiento"
                                        :rows="17"
                                    />
                                    <span class="text-danger" v-if="errores.plantilla">{{
                                        errores.plantilla[0]
                                    }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <h5 class="card-title text-info">
                                        <i class="fa fa-info"></i> Opinión
                                    </h5>
                                    <WysiwygEditor
                                        v-model="registro.opinion"
                                        placeholder="Ingrese aquí la opinión del procedimiento"
                                        :rows="17"
                                    />
                                    <span class="text-danger" v-if="errores.opinion">{{
                                        errores.opinion[0]
                                    }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="form-actions mt-3">
                            <button
                                type="button"
                                class="btn btn-success waves-effect waves-light m-r-5"
                                @click="guardarRegistro()"
                                :disabled="
                                    isGuardandoLectura ||
                                    (sedeInterface && registro.estado_envio_sahi === 'ENVIANDO')
                                "
                                title="Guardar informe ecográfico"
                            >
                                <i
                                    class="fa mr-1"
                                    :class="isGuardandoLectura ? 'fa-spinner fa-spin' : 'fa-check'"
                                ></i>
                                {{ isGuardandoLectura ? "Guardando..." : "Guardar" }}
                            </button>

                            <button
                                v-if="sedeInterface && registro.ecografia_producto_id"
                                type="button"
                                class="btn btn-info waves-effect waves-light m-r-5"
                                @click="consultarEstadoSahi(false)"
                                :disabled="isConsultando"
                            >
                                <i
                                    class="fa mr-1"
                                    :class="isConsultando ? 'fa-spinner fa-spin' : 'fa-refresh'"
                                ></i>
                                {{ isConsultando ? "Consultando..." : "Actualizar SAHI" }}
                            </button>

                            <button
                                v-if="sedeInterface && registro.estado_envio_sahi === 'ERROR'"
                                type="button"
                                class="btn btn-warning waves-effect waves-light m-r-5"
                                @click="enviarASahiEcografiaProducto()"
                                :disabled="isEnviando || registro.estado_envio_sahi === 'ENVIANDO'"
                            >
                                <i class="fa fa-refresh mr-1"></i> Reintentar SAHI
                            </button>

                            <span
                                v-if="sedeInterface && registro.estado_envio_sahi"
                                class="label m-r-5"
                                :class="{
                                    'label-success': registro.estado_envio_sahi === 'ENVIADO',
                                    'label-warning':
                                        registro.estado_envio_sahi === 'ENVIANDO' ||
                                        registro.estado_envio_sahi === 'PENDIENTE',
                                    'label-danger': registro.estado_envio_sahi === 'ERROR',
                                }"
                            >
                                SAHI: {{ registro.estado_envio_sahi }}
                            </span>
                            <small
                                v-if="
                                    sedeInterface &&
                                    registro.estado_envio_sahi === 'ERROR' &&
                                    registro.error_envio_sahi
                                "
                                class="text-danger d-block"
                            >
                                {{ registro.error_envio_sahi }}
                            </small>

                            <!-- Indicador de Autoguardado -->
                            <span
                                v-if="estadoAutoguardado.visible"
                                class="badge badge-light border text-muted ml-2 mr-2"
                                style="font-size: 11px; font-weight: normal"
                            >
                                <i
                                    class="fa mr-1"
                                    :class="
                                        estadoAutoguardado.guardando
                                            ? 'fa-spinner fa-spin text-info'
                                            : estadoAutoguardado.error
                                              ? 'fa-exclamation-triangle text-danger'
                                              : 'fa-check text-success'
                                    "
                                ></i>
                                {{ estadoAutoguardado.texto }}
                            </span>

                            <button
                                type="button"
                                class="btn btn-inverse waves-effect waves-light"
                                @click="btnCerrarModalForm()"
                                title="Cerrar panel"
                            >
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- Floating Dictation Capsule (Widget flotante de dictado por voz)-->
            <!-- ============================================================== -->
            <transition name="fade-slide">
                <div
                    v-if="sidebarVisible"
                    class="floating-dictation-pill"
                    :class="{
                        'is-recording': voiceState === 'active',
                        'is-paused': voiceState === 'paused',
                        'is-idle': voiceState === 'inactive',
                        'is-dark': isDarkMode,
                    }"
                >
                    <div class="pill-indicator">
                        <span class="status-dot"></span>
                    </div>

                    <div class="pill-waveform" v-if="voiceState === 'active'">
                        <span class="wave-bar bar-1"></span>
                        <span class="wave-bar bar-2"></span>
                        <span class="wave-bar bar-3"></span>
                        <span class="wave-bar bar-4"></span>
                    </div>

                    <div class="pill-info">
                        <span class="pill-status-text">
                            {{
                                voiceState === "active"
                                    ? "Grabando Audio"
                                    : voiceState === "paused"
                                      ? "Dictado en Pausa"
                                      : "Dictado Inactivo"
                            }}
                        </span>
                        <span class="pill-shortcut text-muted">Voz STT</span>
                    </div>

                    <button
                        type="button"
                        class="btn btn-circle pill-btn"
                        :class="
                            voiceState === 'active'
                                ? 'btn-danger'
                                : voiceState === 'paused'
                                  ? 'btn-warning'
                                  : 'btn-info'
                        "
                        @click="toggleSpeechRecognition()"
                        :title="
                            voiceState === 'active'
                                ? 'Pausar dictado por voz'
                                : voiceState === 'paused'
                                  ? 'Reanudar dictado por voz'
                                  : 'Iniciar dictado por voz'
                        "
                    >
                        <i
                            :class="
                                voiceState === 'active'
                                    ? 'fa fa-pause'
                                    : voiceState === 'paused'
                                      ? 'fa fa-play'
                                      : 'fa fa-microphone'
                            "
                        ></i>
                    </button>
                </div>
            </transition>
        </div>
        <!-- ============================================================== -->
        <!-- End Right sidebar -->
        <!-- ============================================================== -->

        <!-- sample modal content -->
        <div
            v-if="modalReporteVisible"
            class="modal fade show bs-example-modal-lg-impri-repor"
            tabindex="-1"
            role="dialog"
            aria-labelledby="myLargeModalLabel-impri-repor"
            aria-hidden="true"
            style="display: block; background: rgba(0, 0, 0, 0.6); z-index: 3000"
            @click.self="cerrarModalReporte()"
        >
            <div class="modal-dialog modal-xl" style="max-width: 80%">
                <div class="modal-content shadow-lg">
                    <div class="modal-header">
                        <h4 class="modal-title" id="myLargeModalLabel-impri-repor">
                            Reporte de lectura
                        </h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            @click="cerrarModalReporte()"
                        >
                            ×
                        </button>
                    </div>
                    <div class="modal-body p-0">
                        <iframe
                            v-if="urlReportePdf"
                            :src="urlReportePdf"
                            width="100%"
                            height="650px"
                            id="ifrReporteEcografia"
                            frameborder="0"
                            style="border: 0; outline: none; width: 100%"
                            scrolling="auto"
                        ></iframe>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-danger waves-effect text-left"
                            @click="cerrarModalReporte()"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
        <!-- /.modal -->

        <div
            id="modal-pendientes-consentimientos"
            class="modal fade"
            tabindex="-1"
            role="dialog"
            aria-labelledby="modal-pendientes-consentimientos-title"
            aria-hidden="true"
            v-show="modalPendientesConsentimientosVisible"
            :style="{ display: modalPendientesConsentimientosVisible ? 'block' : 'none' }"
        >
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="modal-pendientes-consentimientos-title">
                            Consentimientos pendientes por firmar (día anterior)
                        </h4>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            Debe firmar los consentimientos pendientes del día anterior para poder
                            realizar lecturas.
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Paciente</th>
                                        <th>Documento</th>
                                        <th>Examen</th>
                                        <th>Firma paciente</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="row in pendientesDiaAnterior"
                                        :key="row.consentimiento_id"
                                    >
                                        <td>{{ row.paciente?.nombres || "—" }}</td>
                                        <td>{{ row.paciente?.num_docu || "—" }}</td>
                                        <td>{{ row.nom_produc }}</td>
                                        <td>{{ row.consentimiento_firma_paciente_at || "—" }}</td>
                                        <td class="text-nowrap">
                                            <button
                                                type="button"
                                                class="btn btn-success btn-sm"
                                                :disabled="
                                                    !row.consentimiento_id ||
                                                    isFirmandoConsentimiento[row.consentimiento_id]
                                                "
                                                @click="firmarConsentimientoMedico(row)"
                                            >
                                                <i
                                                    class="fa"
                                                    :class="
                                                        isFirmandoConsentimiento[
                                                            row.consentimiento_id
                                                        ]
                                                            ? 'fa-spinner fa-spin'
                                                            : 'fa-pencil'
                                                    "
                                                ></i>
                                                Firmar
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="pendientesDiaAnterior.length === 0">
                                        <td colspan="5" class="text-center text-muted">
                                            Sin pendientes.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-default waves-effect"
                            data-dismiss="modal"
                            :disabled="pendientesDiaAnterior.length > 0"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, getCurrentInstance } from "vue";
const { proxy } = getCurrentInstance();
const emitter = proxy.emitter;

const isDarkMode = ref(localStorage.getItem("lidiag_dark_mode") === "true");

function toggleDarkMode() {
    isDarkMode.value = !isDarkMode.value;
    try {
        localStorage.setItem("lidiag_dark_mode", String(isDarkMode.value));
    } catch {
        // Fallback silencioso si localStorage está restringido
    }
}

const isZenMode = ref(false);

function toggleZenMode() {
    isZenMode.value = !isZenMode.value;
}

import usePermissions from "@/helpers/permissions.js";
import { toastMessage } from "@/helpers/alerts";
import VoiceModal from "@/components/comunes/VoiceModal.vue";
import { createVoiceSession } from "@/helpers/voice/voiceSession";
import medicalReplacements from "@/data/medical_replacements.json";
import medicalTermsRaw from "@/data/terminos_medicos.txt?raw";
import { useSede } from "@/composables/useSede.js";
import { useSahiInterface } from "@/composables/useSahiInterface.js";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts";
import WysiwygEditor from "@/components/shared/WysiwygEditor.vue";
import axios from "axios";

const props = defineProps({
    usuarioactual: { type: Object, default: () => ({}) },
    permisos: { type: Array, default: () => [] },
    rolesusuario: { type: String, default: "" },
});
const { can } = usePermissions(props.permisos);
const { sedeInterface, sedeNombre, establecerSede: establecerSedeComposable } = useSede();
const { consultarEstado, enviarEcografiaProducto, startPolling, stopPolling } = useSahiInterface();

const dt = ref(null);
const actualizar = ref(false);

const tituloModal = ref("Nueva admisión");
const subTituloModal = ref("");
const tituloAtencion = ref("");
const busqueda = ref({
    sede_id: "",
    medico_id: "",
    bus_nom_num_docu: "",
    bus_fech_ini: "",
    bus_fecha_fin: "",
});
const registros = ref([]);
const registro = ref({
    ecografia_id: "",
    ecografia_producto_id: "",
    producto_id: "",
    sede_id: "",
    medico_id: "",
    quien_registro_id: props.usuarioactual.id,
    atencion_id: null,
    lectura: null,
    opinion: null,
    tipo_estudio: null,
    tipo_plantilla_id: "",
    plantilla_id: "",
    nom_pacien: null,
    estado_envio_sahi: null,
    fechor_envio_sahi: null,
    error_envio_sahi: null,
    sahi_esquema_149: null,
    sahi_esquema_150: null,
});
const errores = ref({});
const accion = ref(null);
const tiposPlantillas = ref([]);
const plantillas = ref([]);
const url = ref("");
const microfono = ref("admin-wrap/assets/images/mic.gif");
const voiceSession = ref(null);
const voiceState = ref("inactive");
const modalAyudaVisible = ref(false);
const uppercase = ref(false);
const capitalizeNext = ref(false);
const medicalReplCompiled = ref(null);
const medicalVocabularyMap = ref({});
const medicalVocabularyKeys = ref([]);
const medicalVocabularyIndex = ref({});
const isGuardandoLectura = ref(false);
const isBuscandoPendientes = ref(false);
const isConsultando = ref(false);
const isEnviando = ref(false);
const isGenerandoReporte = ref(false);
const bloqueoLectura = ref(false);
const pendientesDiaAnterior = ref([]);
const isFirmandoConsentimiento = ref({});
const sidebarVisible = ref(false);
const selTipoPlantilla = ref(null);
const selPlantilla = ref(null);
const modalPendientesConsentimientosVisible = ref(false);
const modalReporteVisible = ref(false);
const urlReportePdf = ref("");

// Variables de estado para el Autoguardado de borrador (cada 1 min)
const timerAutoguardado = ref(null);
const ultimoContenidoGuardado = ref("");
const estadoAutoguardado = ref({
    visible: false,
    guardando: false,
    error: false,
    texto: "",
});

function cerrarSidebar() {
    if (sidebarVisible.value) {
        sidebarVisible.value = false;
        detenerAutoguardado();
        stopSahiPolling();
    }
}

useKeyboardShortcuts({
    onSearch: () => {
        if (!sidebarVisible.value) {
            listarPendiestePorLectura();
        }
    },
    onSave: guardarRegistro,
    onEscape: cerrarSidebar,
    onDictate: toggleSpeechRecognition,
    isActive: () => true,
    isLoading: isGuardandoLectura,
});

/**
 * Compila las opciones de tipos de plantillas para el Select2 local a partir de `tiposPlantillas`.
 *
 * @returns {Array} Array de objetos con las propiedades `id` y `nom_tipo`.
 * @author Jhon Javer Lozano Arce
 * @date 2024-06-10
 */
const tiposPlantillasOptions = computed(() => {
    return (tiposPlantillas.value || []).map((p) => ({
        id: String(p.id),
        nom_tipo: p.nom_tipo,
    }));
});

/**
 * Compila las opciones de plantillas para el Select2 local a partir de `plantillas`.
 *
 * @returns {Array} Array de objetos con las propiedades `id`, `nom_plantilla`, `plantilla` y `opinion`.
 * @author Jhon Javer Lozano Arce
 * @date 2024-06-10
 */
const plantillasOptions = computed(() => {
    const opts = (plantillas.value || []).map((p) => ({
        id: String(p.id),
        nom_plantilla: p.nom_plantilla,
        plantilla: p.plantilla,
        opinion: p.opinion,
    }));
    return opts;
});

/**
 * Compila las reglas de reemplazo médico desde el archivo JSON y las almacena en `medicalReplCompiled`.
 *
 * @returns {Array} Array de reglas compiladas, cada una como un array con un RegExp y un string de reemplazo.
 * @author Jhon Javer Lozano Arce
 * @date 2024-06-10
 */
function getMedicalReplacementsCompiled() {
    if (medicalReplCompiled.value) return medicalReplCompiled.value;
    const compiled = [];
    medicalReplacements.forEach((rule) => {
        if (!Array.isArray(rule) || rule.length < 3) return;
        const [regexString, flags, replacement] = rule;
        try {
            compiled.push([
                new RegExp(String(regexString), String(flags || "")),
                String(replacement),
            ]);
        } catch {
            // ignore regla inválida
        }
    });
    medicalReplCompiled.value = compiled;
    return compiled;
}

/**
 * Aplica las reglas de reemplazo médico al texto dado.
 *
 * @returns {string} Texto con los reemplazos aplicados.
 * @author Jhon Javer Lozano Arce
 * @date 2024-06-10
 */
function applyMedicalReplacements(text) {
    let out = String(text ?? "");
    const compiled = getMedicalReplacementsCompiled();
    compiled.forEach(([re, rep]) => {
        out = out.replace(re, rep);
    });
    return out;
}

/**
 * Elimina los diacríticos (acentos) de un texto dado.
 *
 * @param {string} text - El texto del cual se eliminarán los diacríticos.
 * @returns {string} El texto sin diacríticos.
 * @author Jhon Javer Lozano Arce
 * @date 2024-06-10
 */
function stripDiacritics(text) {
    try {
        return String(text)
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "");
    } catch {
        return String(text);
    }
}
/**
 * Escapa los caracteres especiales de una cadena para que pueda usarse de forma segura dentro de una expresión regular.
 *
 * @param {string} text Texto del cual se escaparán los caracteres especiales.
 *
 * @returns {string}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
function escapeRegExp(text) {
    return String(text).replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}
/**
 * Normaliza una palabra eliminando diacríticos, pasándola a minúsculas y removiendo caracteres no alfanuméricos.
 *
 * @param {string} text Texto de la palabra a normalizar.
 *
 * @returns {string}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
function normalizeWord(text) {
    return stripDiacritics(String(text))
        .toLowerCase()
        .replace(/[^a-z0-9ñ]+/gi, "");
}

/**
 * Calcula la distancia de Levenshtein entre dos cadenas, con un límite máximo de distancia.
 * Si la distancia excede `maxDist`, la función devuelve `maxDist + 1` para indicar que las cadenas son demasiado diferentes.
 *
 * @param {string} a - La primera cadena.
 * @param {string} b - La segunda cadena.
 * @param {number} [maxDist=2] - La distancia máxima permitida antes de devolver un valor que indica que las cadenas son demasiado diferentes.
 * @returns {number} La distancia de Levenshtein entre las dos cadenas, o `maxDist + 1` si la distancia excede `maxDist`.
 * @author Jhon Javer Lozano Arce
 * @date 2024-06-10
 */
function levenshteinMax(a, b, maxDist = 2) {
    if (a === b) return 0;
    if (!a || !b) return Math.max(a ? a.length : 0, b ? b.length : 0);
    const la = a.length;
    const lb = b.length;
    if (Math.abs(la - lb) > maxDist) return maxDist + 1;

    let prev = new Array(lb + 1);
    let curr = new Array(lb + 1);
    for (let j = 0; j <= lb; j++) prev[j] = j;

    for (let i = 1; i <= la; i++) {
        curr[0] = i;
        let minInRow = curr[0];
        const ca = a.charCodeAt(i - 1);
        for (let j = 1; j <= lb; j++) {
            const cost = ca === b.charCodeAt(j - 1) ? 0 : 1;
            const v = Math.min(prev[j] + 1, curr[j - 1] + 1, prev[j - 1] + cost);
            curr[j] = v;
            if (v < minInRow) minInRow = v;
        }
        if (minInRow > maxDist) return maxDist + 1;
        const tmp = prev;
        prev = curr;
        curr = tmp;
    }
    return prev[lb];
}

/**
 * Reconstruye el vocabulario médico a partir de un archivo de términos médicos y lo almacena en `medicalVocabularyMap`, `medicalVocabularyKeys` y `medicalVocabularyIndex`.
 *
 * @returns {void}
 * @author Jhon Javer Lozano Arce
 * @date 2024-06-10
 */
function rebuildMedicalVocabulary() {
    const map = {};
    const addPhrase = (phrase) => {
        if (!phrase) return;
        const s = String(phrase).trim();
        if (!s) return;

        const nFull = normalizeWord(s);
        if (nFull) map[nFull] = s;

        const tokens = s.split(/[s/,-]+/g).filter(Boolean);
        tokens.forEach((t) => {
            const nt = normalizeWord(t);
            if (nt && nt.length >= 4 && !map[nt]) map[nt] = t;
        });
    };

    // Fuente principal: archivo grande de términos
    medicalTermsRaw.split("\n").forEach(addPhrase);

    medicalVocabularyMap.value = map;
    const keys = Object.keys(map);
    medicalVocabularyKeys.value = keys;

    const index = {};
    keys.forEach((k) => {
        if (!k) return;
        const first = k[0];
        const len = k.length;
        if (!index[first]) index[first] = {};
        if (!index[first][len]) index[first][len] = [];
        index[first][len].push(k);
    });
    medicalVocabularyIndex.value = index;
}

/**
 * Aplica correcciones médicas al texto dado, incluyendo normalizaciones y correcciones basadas en un vocabulario médico indexado.
 *
 * @param {string} text - El texto al que aplicar las correcciones.
 * @returns {string} - El texto corregido.
 * @author Jhon Javer Lozano Arce
 * @date 2024-06-10
 */
function applyMedicalCorrections(text) {
    // 1) Normalizaciones (regex)
    let out = applyMedicalReplacements(text);

    // 2) Corrección por vocabulario (exact + fuzzy indexado)
    const vocabMap = medicalVocabularyMap.value || {};
    const vocabIndex = medicalVocabularyIndex.value || {};
    if (!vocabMap || !Object.keys(vocabMap).length) return out;

    out = String(out).replace(/[\p{L}\p{N}]+/gu, (token) => {
        if (/^\d+$/u.test(token)) return token;
        if (token.length < 4) return token;
        const n = normalizeWord(token);
        if (!n) return token;

        if (vocabMap[n]) return String(vocabMap[n]);

        const first = n[0];
        const bucketByLen = vocabIndex[first] || null;
        if (!bucketByLen) return token;

        let best = null;
        let bestDist = 3;
        for (let L = n.length - 2; L <= n.length + 2; L++) {
            const arr = bucketByLen[L];
            if (!arr || !arr.length) continue;
            for (let i = 0; i < arr.length; i++) {
                const k = arr[i];
                const d = levenshteinMax(n, k, 2);
                if (d < bestDist) {
                    bestDist = d;
                    best = k;
                    if (bestDist === 1) break;
                }
            }
            if (bestDist === 1) break;
        }
        if (best && bestDist <= 2 && vocabMap[best]) return String(vocabMap[best]);
        return token;
    });

    return out;
}
/**
 * Reemplaza en el texto los comandos de voz reconocidos por su resultado, invocando una función por cada coincidencia.
 *
 * @param {string} text Texto original sobre el cual se aplican los comandos.
 * @param {Object} commandMap Mapa de comandos con su función o valor de reemplazo.
 * @param {Object} options Opciones con la función onEachMatch que se ejecuta por cada coincidencia.
 *
 * @returns {string}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
function applyCommandMap(text, commandMap, { onEachMatch } = {}) {
    if (!commandMap) return text;
    const keys = Object.keys(commandMap).sort((a, b) => b.length - a.length);
    keys.forEach((command) => {
        const escaped = escapeRegExp(command);
        const re = new RegExp(`(^|[^\\p{L}\\p{N}_])(${escaped})(?=$|[^\\p{L}\\p{N}_])`, "giu");
        text = text.replace(re, (match, prefix) => {
            if (typeof onEachMatch === "function") {
                try {
                    onEachMatch(command);
                } catch {
                    /* noop */
                }
            }
            const fn = commandMap[command];
            const replacement = typeof fn === "function" ? fn() : String(fn);
            return `${prefix}${replacement}`;
        });
    });
    return text;
}
/**
 * Retorna el mapa de comandos de voz que activan o desactivan el modo de mayúsculas sostenidas.
 *
 * @returns {Object}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
function getUppercaseCommands() {
    return {
        "iniciar mayúscula sostenida": () => {
            uppercase.value = true;
            return "";
        },
        "iniciar mayúsculas sostenida": () => {
            uppercase.value = true;
            return "";
        },
        "iniciar mayúsculas": () => {
            uppercase.value = true;
            return "";
        },
        "iniciar mayúscula": () => {
            uppercase.value = true;
            return "";
        },
        "mayúsculas sostenida": () => {
            uppercase.value = true;
            return "";
        },
        "mayúscula sostenida": () => {
            uppercase.value = true;
            return "";
        },
        "activar mayúsculas": () => {
            uppercase.value = true;
            return "";
        },
        "activar mayúscula": () => {
            uppercase.value = true;
            return "";
        },
        "habilitar mayúsculas": () => {
            uppercase.value = true;
            return "";
        },
        "habilitar mayúscula": () => {
            uppercase.value = true;
            return "";
        },
        "desactivar mayúsculas": () => {
            uppercase.value = false;
            return "";
        },
        "desactivar mayúscula": () => {
            uppercase.value = false;
            return "";
        },
        "terminar mayúsculas": () => {
            uppercase.value = false;
            return "";
        },
        "terminar mayúscula": () => {
            uppercase.value = false;
            return "";
        },
        "fin mayúsculas": () => {
            uppercase.value = false;
            return "";
        },
        "fin mayúscula": () => {
            uppercase.value = false;
            return "";
        },
        "finaliza mayúsculas": () => {
            uppercase.value = false;
            return "";
        },
        "finaliza mayúscula": () => {
            uppercase.value = false;
            return "";
        },
        "finalizar mayúsculas": () => {
            uppercase.value = false;
            return "";
        },
        "finalizar mayúscula": () => {
            uppercase.value = false;
            return "";
        },
    };
}
/**
 * Retorna el mapa de comandos de voz que insertan saltos de línea en el texto dictado.
 *
 * @returns {Object}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
function getLineBreakCommands() {
    return {
        "punto aparte": () => ".\n\n",
        "punto a parte": () => ".\n\n",
        "punto y aparte": () => ".\n\n",
        "nuevo párrafo": () => "\n\n",
        "nueva sección": () => "\n\n---\n\n",
        separador: () => "\n\n---\n\n",
        "salto de línea": () => "\n",
        "salto de linea": () => "\n",
        "doble salto de línea": () => "\n\n",
        "doble salto de linea": () => "\n\n",
        enter: () => "\n",
        "doble enter": () => "\n\n",
        "línea en blanco": () => "\n\n",
        "linea en blanco": () => "\n\n",
        "doble línea en blanco": () => "\n\n\n\n",
        "doble linea en blanco": () => "\n\n\n\n",
    };
}
/**
 * Retorna el mapa de comandos de voz que insertan puntuación, espacios y símbolos en el texto dictado.
 *
 * @returns {Object}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
function getPunctuationCommands() {
    return {
        punto: () => {
            capitalizeNext.value = true;
            return ".";
        },
        "punto y seguido": () => {
            capitalizeNext.value = true;
            return ". ";
        },
        "punto seguido": () => {
            capitalizeNext.value = true;
            return ". ";
        },
        coma: () => ",",
        "dos puntos": () => {
            capitalizeNext.value = true;
            return ": ";
        },
        "dos puntos aparte": () => {
            capitalizeNext.value = true;
            return ":\n\n";
        },
        "punto y coma": () => "; ",
        "dar espacio": () => " ",
        "un espacio": () => " ",
        "dos espacios": () => "  ",
        "dos espacio": () => "  ",
        "doble espacio": () => "  ",
        "abrir paréntesis": () => "(",
        "abrir parentesis": () => "(",
        "cerrar paréntesis": () => ")",
        "cerrar parentesis": () => ")",
        "abrir corchetes": () => "[",
        "cerrar corchetes": () => "]",
        "abrir llaves": () => "{",
        "cerrar llaves": () => "}",
        "abrir comillas": () => '"',
        "cerrar comillas": () => '"',
        "abrir comillas simples": () => "'",
        "cerrar comillas simples": () => "'",
        guion: () => "-",
        "guion medio": () => "-",
        "guion bajo": () => "_",
        "barra diagonal": () => "/",
        barra: () => "/",
        "barra invertida": () => "\\",
        "nueva oración": () => {
            capitalizeNext.value = true;
            return ". ";
        },
        "nueva oracion": () => {
            capitalizeNext.value = true;
            return ". ";
        },
        "por ciento": () => "%",
        porciento: () => "%",
        menos: () => "-",
        más: () => "+",
        mas: () => "+",
        igual: () => "=",
        multiplicar: () => "×",
        dividir: () => "÷",
        asterisco: () => "*",
        numeral: () => "#",
        hashtag: () => "#",
        ampersand: () => "&",
        arroba: () => "@",
        dólar: () => "$",
        dolar: () => "$",
        pesos: () => "$",
        euro: () => "€",
    };
}
/**
 * Construye y retorna el conjunto completo de comandos de voz (mayúsculas, saltos y puntuación) incluyendo variantes sin tildes.
 *
 * @returns {Object}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
function getAllVoiceCommands() {
    // Agrega claves sin tildes para tolerar dictado sin acentos
    const uppercaseCommands = getUppercaseCommands();
    const lineBreakCommands = getLineBreakCommands();
    const punctuationCommands = getPunctuationCommands();

    const addNormalizedKeys = (obj) => {
        const keys = Object.keys(obj);
        keys.forEach((k) => {
            const normalized = stripDiacritics(k).toLowerCase();
            if (!Object.prototype.hasOwnProperty.call(obj, normalized)) {
                obj[normalized] = obj[k];
            }
        });
    };
    addNormalizedKeys(uppercaseCommands);
    addNormalizedKeys(lineBreakCommands);
    addNormalizedKeys(punctuationCommands);
    return { uppercaseCommands, lineBreakCommands, punctuationCommands };
}
/**
 * Normaliza dígitos dictados ante palabras de comandos (ej. '2 puntos' a 'dos puntos').
 *
 * @param {string} texto Texto a normalizar.
 *
 * @returns {string}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-23
 */
function normalizarDigitosComando(texto) {
    const mapa = { 1: "un", 2: "dos" };
    return String(texto || "").replace(/\b(\d{1,2})\s+(puntos?|espacios?)\b/gi, (m, n, resto) => {
        const palabra = mapa[parseInt(n, 10)];
        return palabra ? `${palabra} ${resto}` : m;
    });
}
/**
 * Procesa el texto dictado aplicando los comandos de voz, mayúsculas sostenidas y capitalización de nuevas oraciones.
 *
 * @param {string} text Texto reconocido por voz a procesar.
 *
 * @returns {string}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
function processCommands(text) {
    const { uppercaseCommands, lineBreakCommands, punctuationCommands } = getAllVoiceCommands();
    let out = normalizarDigitosComando(String(text ?? ""));
    out = applyCommandMap(out, uppercaseCommands);
    out = applyCommandMap(out, lineBreakCommands, {
        onEachMatch: () => {
            capitalizeNext.value = true;
        },
    });
    out = applyCommandMap(out, punctuationCommands);
    out = uppercase.value ? out.toUpperCase() : out;
    if (capitalizeNext.value) {
        out = out.replace(/\n\n(\s*)([a-záéíóúüñ])/i, (m, spaces, first) => {
            capitalizeNext.value = false;
            return `\n\n${spaces}${first.toUpperCase()}`;
        });
        if (capitalizeNext.value) {
            out = out.replace(/(^|\. |\.\n)(\s*)([a-záéíóúüñ])/i, (m, start, spaces, first) => {
                capitalizeNext.value = false;
                return `${start}${spaces}${first.toUpperCase()}`;
            });
        }
        if (capitalizeNext.value) {
            out = out.replace(/(^|\n)(\s*)([a-záéíóúüñ])/i, (m, start, spaces, first) => {
                capitalizeNext.value = false;
                return `${start}${spaces}${first.toUpperCase()}`;
            });
        }
    }
    return out;
}
/**
 * Crea y retorna la sesión de reconocimiento de voz configurada para dictado, gramática de comandos y manejo de errores.
 *
 * @returns {Object}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
function ensureVoiceSession() {
    if (voiceSession.value) return voiceSession.value;
    const { uppercaseCommands, lineBreakCommands, punctuationCommands } = getAllVoiceCommands();
    const grammarCommands = [
        ...Object.keys(uppercaseCommands),
        ...Object.keys(lineBreakCommands),
        ...Object.keys(punctuationCommands),
    ];

    voiceSession.value = createVoiceSession({
        enableAudio: false, // Ecografías: solo dictado (no grabación)
        enableDictation: true,
        lang: "es-ES",
        grammarCommands,
        maxNoSpeechRetries: 6,
        onFinalText: (raw) => {
            let text = String(raw || "").trim();
            if (!text) return;
            text = processCommands(text);
            text = applyMedicalCorrections(text);
            if (!text) return;

            // Añadir a lectura (mantener comportamiento: espacio si no termina en espacio/salto)
            const suffix = /[ \n]$/.test(text) ? "" : " ";
            registro.value.lectura = String(registro.value.lectura || "") + text + suffix;
        },
        onPermissionDenied: () => {
            voiceState.value = "inactive";
            microfono.value = "admin-wrap/assets/images/mic.gif";
            toastMessage({
                heading: "Permiso requerido",
                text: "El navegador bloqueó el micrófono. Permite el acceso al micrófono para usar dictado.",
                icon: "warning",
                hideAfter: 6000,
            });
        },
        onError: (e) => {
            const err = e && e.error ? String(e.error) : "";
            if (e && e.message === "Silencio repetido") {
                voiceState.value = "inactive";
                microfono.value = "admin-wrap/assets/images/mic.gif";
                toastMessage({
                    heading: "Sin voz detectada",
                    text: "No se detecta voz (silencio repetido). Verifica micrófono/entrada y vuelve a iniciar el dictado.",
                    icon: "warning",
                    hideAfter: 6000,
                });
            } else if (err.includes("not-allowed")) {
                voiceState.value = "inactive";
                microfono.value = "admin-wrap/assets/images/mic.gif";
                toastMessage({
                    heading: "Micrófono bloqueado",
                    text: "Permite el acceso al micrófono en la configuración del navegador.",
                    icon: "error",
                    hideAfter: 6000,
                });
            } else if (err.includes("aborted")) {
                if (voiceState.value === "active" && voiceSession.value) {
                    try {
                        const dict = voiceSession.value.getDictation?.();
                        if (dict) dict.resume();
                    } catch {}
                }
            }
        },
    });
    return voiceSession.value;
}
/**
 * Alterna el estado del reconocimiento de voz (iniciar, pausar o reanudar) y actualiza el icono del micrófono.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
async function toggleSpeechRecognition() {
    // Mantener el ícono/animación actual, sin tocar template.
    try {
        const vs = ensureVoiceSession();
        if (voiceState.value === "inactive") {
            await vs.start();
            voiceState.value = "active";
            microfono.value = "admin-wrap/assets/images/mic-animate.gif";
        } else if (voiceState.value === "active") {
            vs.pause();
            voiceState.value = "paused";
            microfono.value = "admin-wrap/assets/images/mic.gif";
        } else {
            // paused -> resume
            vs.resume();
            voiceState.value = "active";
            microfono.value = "admin-wrap/assets/images/mic-animate.gif";
        }
    } catch (error) {
        voiceState.value = "inactive";
        microfono.value = "admin-wrap/assets/images/mic.gif";
        toastMessage({
            heading: "Error",
            text: error && error.message ? error.message : "No fue posible iniciar el dictado.",
            icon: "error",
            hideAfter: 5000,
        });
    }
}
/**
 * Destruye la sesión de reconocimiento de voz activa y restablece el estado e icono del micrófono.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
function stopVoice() {
    if (voiceSession.value) {
        try {
            voiceSession.value.destroy();
        } catch {
            /* noop */
        }
        voiceSession.value = null;
    }
    voiceState.value = "inactive";
    microfono.value = "admin-wrap/assets/images/mic.gif";
}
/**
 * Reanuda el reconocimiento de voz si la pestaña vuelve a estar visible y el dictado sigue activo.
 *
 * Flujo del proceso:
 * 1. Verifica si el estado del dictado es activo y existe una sesión de voz.
 * 2. Comprueba si el dictador de voz se encuentra inactivo.
 * 3. Ejecuta resume() para restaurar la escucha sin intervención del usuario.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-23
 */
function handleVoiceVisibilityChange() {
    if (voiceState.value !== "active" || !voiceSession.value) return;
    try {
        const dict = voiceSession.value.getDictation?.();
        if (dict && !dict.getState().active) {
            dict.resume();
        }
    } catch {}
}
/**
 * Reanuda el reconocimiento de voz cuando la ventana del navegador recupera el foco.
 *
 * Flujo del proceso:
 * 1. Verifica si el dictado está marcado como activo en el componente.
 * 2. Comprueba si la instancia de dictado subyacente se detuvo.
 * 3. Invoca resume() en la sesión de voz para reanudar el flujo continuo.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-23
 */
function handleVoiceWindowFocus() {
    if (voiceState.value !== "active" || !voiceSession.value) return;
    try {
        const dict = voiceSession.value.getDictation?.();
        if (dict && !dict.getState().active) {
            dict.resume();
        }
    } catch {}
}
/**
 * Inicia el sondeo periódico del estado de envío a SAHI para la ecografía producto actual.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
function startSahiPolling() {
    if (!sedeInterface.value) return;
    if (!registro.value?.ecografia_producto_id) return;

    const id = registro.value.ecografia_producto_id;
    const onUpdate = (estado) => {
        if (estado.estado_envio_sahi != null)
            registro.value.estado_envio_sahi = estado.estado_envio_sahi;
        registro.value.fechor_envio_sahi =
            estado.fechor_envio_sahi ?? registro.value.fechor_envio_sahi;
        registro.value.error_envio_sahi = estado.error_envio_sahi ?? null;
        registro.value.sahi_esquema_149 =
            estado.sahi_esquema_149 ?? registro.value.sahi_esquema_149;
        registro.value.sahi_esquema_150 =
            estado.sahi_esquema_150 ?? registro.value.sahi_esquema_150;
    };
    const onError = (error) => {
        registro.value.estado_envio_sahi = "ERROR";
        registro.value.error_envio_sahi =
            error.response?.data?.message || "Error consultando SAHI.";
    };
    startPolling(id, onUpdate, onError);
}
/**
 * Detiene el sondeo periódico del estado de envío a SAHI.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
function stopSahiPolling() {
    stopPolling();
}

/**
 * Consulta el estado de envío a SAHI para la ecografía actual y actualiza el registro con la información obtenida.
 * @param {any} silent=false - Si es true, no muestra mensajes de toast al usuario.
 * @returns {any}
 * @author Jhon Javer Lozano Arce
 * @date 2024-06-10
 */
async function consultarEstadoSahi(silent = false) {
    if (!sedeInterface.value) return;
    const id = registro.value.ecografia_producto_id;
    if (!id) return;

    try {
        const estado = await consultarEstado(id);
        if (estado.estado_envio_sahi != null)
            registro.value.estado_envio_sahi = estado.estado_envio_sahi;
        registro.value.fechor_envio_sahi =
            estado.fechor_envio_sahi ?? registro.value.fechor_envio_sahi;
        registro.value.error_envio_sahi = estado.error_envio_sahi ?? null;
        registro.value.sahi_esquema_149 =
            estado.sahi_esquema_149 ?? registro.value.sahi_esquema_149;
        registro.value.sahi_esquema_150 =
            estado.sahi_esquema_150 ?? registro.value.sahi_esquema_150;

        if (registro.value.estado_envio_sahi !== "ENVIANDO") {
            stopPolling();
        }

        if (!silent) {
            toastMessage({
                heading: "SAHI",
                text: "Estado actualizado.",
                icon: "info",
                hideAfter: 2500,
            });
        }
    } catch (error) {
        if (!silent) {
            toastMessage({
                heading: "SAHI",
                text: error.response?.data?.message || "No se pudo consultar el estado en SAHI.",
                icon: "warning",
            });
        }
    }
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
 * Destruye de manera segura un DataTable en el selector dado, si existe y está inicializado.
 * @param {string} selector - Selector del DataTable a destruir (por defecto: "#tablaEcografiasLectura").
 * @author Jhon Javer Lozano Arce
 * @date 2024-06-10
 */
function safeDestroyDataTable(selector = "#tablaEcografiasLectura") {
    if (typeof window === "undefined" || !window.$ || !window.$.fn || !window.$.fn.DataTable)
        return;
    const $t = window.$(selector);
    if (!$t.length) return;
    if (window.$.fn.DataTable.isDataTable($t)) {
        $t.DataTable().clear().destroy();
    }
}

/**
 * Inicializa de manera segura un DataTable en el selector dado, si no está ya inicializado.
 * @param {string} selector - Selector del DataTable a inicializar (por defecto: "#tablaEcografiasLectura").
 * @author Jhon Javer Lozano Arce
 * @date 2024-06-10
 */
function safeInitDataTable(selector = "#tablaEcografiasLectura") {
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
 * Lista las ecografías pendientes de lectura para el médico actual y evalúa consentimientos pendientes del día anterior.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
async function listarPendiestePorLectura() {
    // Evita llamar al backend con "null"/vacío (causa 404 "El medico no se encontro")
    const medicoId = String(busqueda.value?.medico_id ?? "").trim();
    if (!medicoId || medicoId === "null" || medicoId === "undefined" || isNaN(Number(medicoId))) {
        registros.value = [];
        return;
    }
    try {
        isBuscandoPendientes.value = true;

        const res = await axios.post("/api/ecografia-listarPendientesPorLectura/" + medicoId);
        if (res.status == 200) {
            safeDestroyDataTable(dt.value || "#tablaEcografiasLectura");

            registros.value = res.data.data.ecografias;

            safeInitDataTable(dt.value || "#tablaEcografiasLectura");

            evaluarPendientesConsentimientosDiaAnterior();
        }
    } catch {
        registros.value = [];
    } finally {
        isBuscandoPendientes.value = false;
    }
}
/**
 * Determina los consentimientos firmados por el paciente el día anterior pendientes de firma del médico y bloquea la lectura si existen.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
function evaluarPendientesConsentimientosDiaAnterior() {
    const todayStart = new Date();
    todayStart.setHours(0, 0, 0, 0);

    const pendientes = (registros.value || []).filter((r) => {
        if (r?.consentimiento_estado !== "PENDIENTE_MEDICO") return false;
        if (!r?.consentimiento_firma_paciente_at) return false;
        const ts = new Date(r.consentimiento_firma_paciente_at).getTime();
        if (!Number.isFinite(ts)) return false;
        return ts < todayStart.getTime();
    });

    pendientesDiaAnterior.value = pendientes;
    bloqueoLectura.value = pendientes.length > 0;

    if (bloqueoLectura.value) {
        modalPendientesConsentimientosVisible.value = true;
    } else if (!bloqueoLectura.value) {
        modalPendientesConsentimientosVisible.value = false;
    }
}
/**
 * Envía la firma del médico sobre un consentimiento pendiente y actualiza su estado a COMPLETO.
 *
 * @param {Object} item Registro del consentimiento que el médico firma.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
async function firmarConsentimientoMedico(item) {
    const id = item?.consentimiento_id;
    if (!id) return;
    if (isFirmandoConsentimiento.value[id]) return;
    try {
        isFirmandoConsentimiento.value[id] = true;
        await axios.post("/api/ecografias-consentimientos/firmar-medico", {
            consentimiento_id: id,
        });

        item.consentimiento_estado = "COMPLETO";
        item.consentimiento_firma_medico_at = new Date()
            .toISOString()
            .slice(0, 19)
            .replace("T", " ");

        evaluarPendientesConsentimientosDiaAnterior();
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: error.response?.data?.message || "No fue posible firmar el consentimiento.",
            icon: "error",
        });
    } finally {
        isFirmandoConsentimiento.value[id] = false;
    }
}
/**
 * Envía al backend el guardado de la lectura de la ecografía actual y retorna si la operación fue exitosa.
 *
 * @returns {Promise<boolean>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
async function generarLectura() {
    try {
        // Enviar solicitud para guardar la ecografía
        const res = await axios.put("/api/ecografia-lectura/" + registro.value.ecografia_id, {
            accion: accion.value,
            registro: registro.value,
        });

        if (res.status === 200 || res.status === 201) {
            // Mostrar notificación de éxito
            toastMessage({
                heading: "¡Éxito!",
                text: "Lectura guardada correctamente.",
                icon: "success",
            });

            return true;
        } else {
            toastMessage({
                heading: "Error",
                text:
                    res.data?.message ||
                    res.data?.data?.message ||
                    "No se pudo guardar la lectura.",
                icon: "error",
            });

            return false;
        }
    } catch (error) {
        // Mostrar errores específicos en el formulario
        if (error.response?.data?.errors) {
            errores.value = error.response.data.errors;
        }
        toastMessage({
            heading: "Error",
            text: error.response?.data?.message || "Ocurrió un error al guardar la lectura.",
            icon: "error",
        });

        return false;
    }
}
/**
 * Envía la ecografía producto a SAHI, gestiona el estado de envío y, de ser necesario, inicia el sondeo de estado.
 *
 * @returns {Promise<boolean>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
async function enviarASahiEcografiaProducto() {
    if (!sedeInterface.value) return true;

    const id = registro.value.ecografia_producto_id;
    if (!id) {
        toastMessage({
            heading: "Advertencia",
            text: "No se encontró ecografia_producto_id para enviar a SAHI.",
            icon: "warning",
        });
        return false;
    }

    try {
        registro.value.estado_envio_sahi = "ENVIANDO";
        const res = await enviarEcografiaProducto(id);

        const encolado = res.status === 202;
        toastMessage({
            heading: "SAHI",
            text:
                res.data?.message ||
                (encolado ? "Envío a SAHI encolado." : "Enviado a SAHI correctamente."),
            icon: encolado ? "info" : "success",
        });

        registro.value.estado_envio_sahi = encolado ? "ENVIANDO" : "ENVIADO";
        registro.value.fechor_envio_sahi = new Date().toISOString();
        registro.value.error_envio_sahi = null;
        if (!encolado && res.data?.data) {
            registro.value.sahi_esquema_149 =
                res.data.data.IdEsquemaEncabezado149 ?? registro.value.sahi_esquema_149;
            registro.value.sahi_esquema_150 =
                res.data.data.IdEsquemaEncabezado150 ?? registro.value.sahi_esquema_150;
        }

        if (encolado) {
            const onUpdate = (estado) => {
                if (estado.estado_envio_sahi != null)
                    registro.value.estado_envio_sahi = estado.estado_envio_sahi;
                registro.value.fechor_envio_sahi =
                    estado.fechor_envio_sahi ?? registro.value.fechor_envio_sahi;
                registro.value.error_envio_sahi = estado.error_envio_sahi ?? null;
                registro.value.sahi_esquema_149 =
                    estado.sahi_esquema_149 ?? registro.value.sahi_esquema_149;
                registro.value.sahi_esquema_150 =
                    estado.sahi_esquema_150 ?? registro.value.sahi_esquema_150;
            };
            const onError = (error) => {
                registro.value.estado_envio_sahi = "ERROR";
                registro.value.error_envio_sahi =
                    error.response?.data?.message || "Error consultando SAHI.";
            };
            startPolling(id, onUpdate, onError);
        }

        return true;
    } catch (error) {
        toastMessage({
            heading: "SAHI",
            text: error.response?.data?.message || "No se pudo enviar a SAHI.",
            icon: "warning",
            hideAfter: 4500,
        });
        registro.value.estado_envio_sahi = "ERROR";
        registro.value.error_envio_sahi =
            error.response?.data?.message || "No se pudo enviar a SAHI.";
        return false;
    }
}

/**
 * Guarda el registro de lectura actual y, si corresponde, envía la información a SAHI.
 * Después de guardar, actualiza la lista de pendientes y limpia el formulario.
 *
 * @returns {Promise<boolean>} - Una promesa que resuelve a true si el registro se guardó correctamente, false en caso contrario.
 * @author Jhon Javer Lozano Arce
 * @date 2024-06-10
 */
async function guardarRegistro() {
    const lecturaTexto = (registro.value.lectura || "").replace(/<[^>]*>/g, "").trim();
    if (!lecturaTexto) {
        toastMessage({
            heading: "Atención",
            text: "El informe de la ecografía no puede estar vacío antes de guardar.",
            icon: "warning",
        });
        return;
    }

    const accionActual = "TERMINAR_LECTURA";
    accion.value = accionActual;

    isGuardandoLectura.value = true;
    const guardadoOk = await generarLectura();
    accion.value = null;
    isGuardandoLectura.value = false;

    if (!guardadoOk) return;

    // Enviar a SAHI solo si la sede tiene interface y la acción fue TERMINAR_LECTURA
    if (sedeInterface.value && accionActual === "TERMINAR_LECTURA") {
        const sahiOk = await enviarASahiEcografiaProducto();
        if (!sahiOk) {
            listarPendiestePorLectura();
            return;
        }
    }

    listarPendiestePorLectura();
    limpiarFormulario();
    btnCerrarModalForm();
}

/**
 * Genera un reporte de la lectura actual y lo muestra en un iframe.
 * Después de generar el reporte, actualiza la lista de pendientes.
 *
 * @returns {Promise<boolean>} - Una promesa que resuelve a true si el reporte se generó correctamente, false en caso contrario.
 * @author Jhon Javer Lozano Arce
 * @date 2024-06-10
 */
async function generarReporte() {
    if (isGenerandoReporte.value) return;
    isGenerandoReporte.value = true;
    try {
        accion.value = "IMPRIMIR_LECTURA";
        const guardadoOk = await generarLectura();
        accion.value = null;

        if (!guardadoOk) return;

        urlReportePdf.value = "";

        const res = await axios.post(
            "/api/ecografia-generar-reporte/" + registro.value.ecografia_id,
            registro.value
        );
        const pathReporte = res.data?.data?.plantilla || res.data?.plantilla;

        if (pathReporte) {
            urlReportePdf.value = pathReporte;
            modalReporteVisible.value = true;
        } else {
            toastMessage({
                heading: "Reporte",
                text: "No se recibió la ruta del archivo PDF generado.",
                icon: "warning",
            });
        }

        listarPendiestePorLectura();

        return true;
    } catch (error) {
        if (error.response?.data?.errors) {
            errores.value = error.response.data.errors;
        }
        toastMessage({
            heading: "Error",
            text: error.response?.data?.message || "No fue posible generar el reporte.",
            icon: "error",
        });
        modalReporteVisible.value = false;
    } finally {
        isGenerandoReporte.value = false;
    }
}

/**
 * Cierra la visualización del modal de reporte de lectura.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-15
 */
function cerrarModalReporte() {
    modalReporteVisible.value = false;
    urlReportePdf.value = "";
}

/**
 * Inicializa la sede actual del usuario a partir del almacenamiento de sesión y establece la sede en el composable correspondiente.
 * @returns {Promise<void>} - Una promesa que se resuelve cuando la sede ha sido establecida correctamente.
 * @author Jhon Javer Lozano Arce
 * @date 2024-06-10
 */
async function initSede() {
    const sede = sessionStorage.getItem("ST-sede") || localStorage.getItem("ST-sede");
    registro.value.sede_id = sede || null;
    busqueda.value.sede_id = sede || null;
    if (sede && !sessionStorage.getItem("ST-sede")) {
        sessionStorage.setItem("ST-sede", String(sede));
    }
    await establecerSedeComposable();
}

/**
 * Establece el médico actual a partir del almacenamiento local o del usuario actual y actualiza los registros pendientes de lectura.
 * @returns {void}
 * @author Jhon Javer Lozano Arce
 * @date 2024-06-10
 */
function establecerMedico() {
    const medicoStorage =
        sessionStorage.getItem("ST-ecografia-medico") ||
        localStorage.getItem("ST-ecografia-medico") ||
        sessionStorage.getItem("ST-medico") ||
        localStorage.getItem("ST-medico") ||
        props.usuarioactual?.id;

    if (medicoStorage && !isNaN(Number(medicoStorage))) {
        busqueda.value.medico_id = String(medicoStorage);
        registro.value.medico_id = String(medicoStorage);
    }

    listarPendiestePorLectura();
}

/**
 * Lista los tipos de plantillas activas desde el backend y actualiza la variable `tiposPlantillas`.
 * Si hay tipos de plantillas disponibles, selecciona automáticamente el primer tipo y carga sus plantillas correspondientes.
 *
 * @returns {Promise<void>} - Una promesa que se resuelve cuando la operación de listado ha finalizado.
 * @author Jhon Javer Lozano Arce
 * @date 2024-06-10
 */
async function listarTiposPlantillas(medicoId = null) {
    try {
        const idMed =
            medicoId ||
            busqueda.value.medico_id ||
            sessionStorage.getItem("ST-ecografia-medico") ||
            localStorage.getItem("ST-ecografia-medico") ||
            sessionStorage.getItem("ST-medico") ||
            localStorage.getItem("ST-medico") ||
            "";

        const url = idMed
            ? `/api/config-tipos-plantillas/listar/activos?medico_id=${idMed}`
            : "/api/config-tipos-plantillas/listar/activos";

        const res = await axios.get(url);
        tiposPlantillas.value = res.data.data;
        nextTick(() => {
            selTipoPlantilla.value?.rebuild();
        });
    } catch {
        tiposPlantillas.value = [];
    }
}

/**
 * Maneja la selección de un tipo de plantilla, cargando las plantillas correspondientes desde el backend.
 * Si no se selecciona un tipo válido, limpia la lista de plantillas y el campo `tipo_estudio` del registro.
 *
 * @param {string|null} value - El ID del tipo de plantilla seleccionado. Si es null o vacío, se considera que no se ha seleccionado un tipo válido.
 * @returns {Promise<void>} - Una promesa que se resuelve cuando la operación de carga de plantillas ha finalizado.
 * @author Jhon Javer Lozano Arce
 * @date 2024-06-10
 */
async function onTipoPlantillaSelect(value) {
    let selectedId = null;
    if (value != null && value !== "" && value !== "0" && value !== 0) {
        selectedId = String(value);
    }
    registro.value.tipo_plantilla_id = selectedId || "";
    registro.value.plantilla_id = "";
    registro.value.lectura = null;
    registro.value.opinion = null;

    if (!selectedId) {
        plantillas.value = [];
        registro.value.tipo_estudio = "";
        return;
    }

    try {
        const medicoIdPlantilla =
            registro.value.medico_id ||
            busqueda.value.medico_id ||
            sessionStorage.getItem("ST-ecografia-medico") ||
            localStorage.getItem("ST-ecografia-medico") ||
            sessionStorage.getItem("ST-medico") ||
            localStorage.getItem("ST-medico") ||
            props.usuarioactual?.id ||
            0;

        const url = `/api/config-plantillas/listar/activos/${selectedId}/${medicoIdPlantilla}`;
        const res = await axios.get(url);

        plantillas.value = res.data.data || [];
        var tipo = tiposPlantillasOptions.value.find(function (t) {
            return String(t.id) === String(selectedId);
        });
        registro.value.tipo_estudio = (tipo && tipo.nom_tipo) || "";

        nextTick(() => {
            selPlantilla.value?.rebuild();
        });
    } catch {
        plantillas.value = [];
        nextTick(() => {
            selPlantilla.value?.rebuild();
        });
        toastMessage({
            heading: "Plantillas",
            text: "No se pudieron cargar las plantillas para este tipo.",
            icon: "warning",
        });
    }
}

/**
 * Maneja la selección de una plantilla específica, actualizando los campos `lectura` y `opinion` del registro con los valores correspondientes de la plantilla seleccionada.
 * Si no se encuentra la plantilla seleccionada, se establecen los campos `lectura` y `opinion` como null.
 *
 * @param {string} value - El ID de la plantilla seleccionada.
 * @returns {void}
 * @author Jhon Javer Lozano Arce
 * @date 2024-06-10
 */
function onPlantillaSelect(value) {
    let selectedId = null;
    if (value != null && value !== "" && value !== "0" && value !== 0) {
        selectedId = String(value);
    }
    registro.value.plantilla_id = selectedId || "";

    if (!selectedId) {
        registro.value.lectura = null;
        registro.value.opinion = null;
        return;
    }

    var plantilla = plantillasOptions.value.find(function (p) {
        return String(p.id) === String(selectedId);
    });
    registro.value.lectura = (plantilla && plantilla.plantilla) || null;
    registro.value.opinion = (plantilla && plantilla.opinion) || null;
}
/**
 * Inicia el temporizador de autoguardado en segundo plano (cada 60 segundos).
 *
 * @returns {void}
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-18
 */
function iniciarAutoguardado() {
    detenerAutoguardado();
    ultimoContenidoGuardado.value = `${registro.value.lectura || ""}|${registro.value.opinion || ""}`;
    estadoAutoguardado.value = {
        visible: false,
        guardando: false,
        error: false,
        texto: "",
    };

    timerAutoguardado.value = setInterval(async () => {
        if (!sidebarVisible.value || !registro.value.ecografia_id) return;
        if (isGuardandoLectura.value || isGenerandoReporte.value) return;

        const contenidoActual = `${registro.value.lectura || ""}|${registro.value.opinion || ""}`;
        const textoLimpio = (registro.value.lectura || "").replace(/<[^>]*>/g, "").trim();

        // Si no hay texto significativo o no ha cambiado desde el último guardado, omitir
        if (!textoLimpio || contenidoActual === ultimoContenidoGuardado.value) return;

        try {
            estadoAutoguardado.value = {
                visible: true,
                guardando: true,
                error: false,
                texto: "Guardando borrador...",
            };

            const res = await axios.put(`/api/ecografia-lectura/${registro.value.ecografia_id}`, {
                accion: "GUARDAR_BORRADOR",
                registro: registro.value,
            });

            if (res.status === 200 || res.status === 201) {
                ultimoContenidoGuardado.value = contenidoActual;
                const ahora = new Date();
                const horaStr = ahora.toLocaleTimeString([], {
                    hour: "2-digit",
                    minute: "2-digit",
                    second: "2-digit",
                });
                estadoAutoguardado.value = {
                    visible: true,
                    guardando: false,
                    error: false,
                    texto: `Borrador guardado a las ${horaStr}`,
                };
            }
        } catch {
            estadoAutoguardado.value = {
                visible: true,
                guardando: false,
                error: true,
                texto: "Error al autoguardar borrador",
            };
        }
    }, 60000); // 1 minuto
}

/**
 * Detiene y limpia el temporizador de autoguardado.
 *
 * @returns {void}
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-18
 */
function detenerAutoguardado() {
    if (timerAutoguardado.value) {
        clearInterval(timerAutoguardado.value);
        timerAutoguardado.value = null;
    }
}

/**
 * Cierra/despliega el panel lateral del formulario de lectura y detiene el sondeo SAHI.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
function btnCerrarModalForm() {
    sidebarVisible.value = !sidebarVisible.value;
    if (sidebarVisible.value) {
        // El panel nace oculto (display:none): Select2 se niega a inicializar
        // dentro de contenedores ocultos, así que se reconstruye al abrir.
        nextTick(() => {
            selTipoPlantilla.value?.rebuild();
            selPlantilla.value?.rebuild();
        });
        iniciarAutoguardado();
    } else {
        detenerAutoguardado();
    }
    // al cerrar, detenemos polling para evitar fugas
    stopSahiPolling();
}
/**
 * Carga los datos de la ecografía seleccionada en el formulario de lectura y prepara el panel lateral.
 *
 * @param {Object} data Datos de la atención/ecografía seleccionada a mostrar.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
function mostrarRegistro(data = {}) {
    const ubicacionInfo = data.ubicacion ? ` — Servicio: ${data.ubicacion}` : "";
    const camaInfo = data.cama ? ` — Cama: ${data.cama}` : "";
    tituloAtencion.value = `Atención N°: ${data.atencion_id}${ubicacionInfo}${camaInfo}`;
    tituloModal.value = `Paciente: ${data.paciente.num_docu} - Nombres: ${data.paciente.nombres} ${data.paciente.seg_nom} ${data.paciente.pri_ape} ${data.paciente.seg_ape} `;
    subTituloModal.value = `Examen: ${data.nom_produc} - Valor: ${data.ValProduc}`;

    registro.value.ecografia_id = data.id;
    registro.value.ecografia_producto_id = data.ecografia_producto_id;
    registro.value.producto_id = data.producto_id;
    registro.value.atencion_id = data.atencion_id;
    registro.value.paciente_id = data.paciente_id;
    registro.value.num_docu = data.paciente.num_docu;
    registro.value.nom_pacien =
        `${data.paciente.nombres || ""} ${data.paciente.seg_nom || ""} ${data.paciente.pri_ape || ""} ${data.paciente.seg_ape || ""}`.trim();
    registro.value.seg_nom = data.paciente.seg_nom;
    registro.value.pri_ape = data.paciente.pri_ape;
    registro.value.seg_ape = data.paciente.seg_ape;
    registro.value.sexo = data.paciente.sexo;
    registro.value.fec_naci = data.paciente.fec_naci;
    registro.value.direccion = data.paciente.direccion;
    registro.value.dir = data.paciente.direccion;
    registro.value.tel = data.paciente.tel;
    registro.value.cel = data.paciente.cel;
    registro.value.contrato = data.contrato;
    registro.value.plan = data.plan;
    registro.value.admin_salud = data.admin_salud?.nom_admin_salud || "";
    registro.value.ubicacion = data.ubicacion || "";
    registro.value.cama = data.cama || "";
    registro.value.quien_ordena = data.quien_ordena || "";

    registro.value.fec_estudio = data.fec_cita_sahi || "";
    registro.value.nom_produc = data.nom_produc || "";
    registro.value.cod_cups = data.cod_cups || "";
    registro.value.nom_sede = sedeNombre.value || "";
    registro.value.atencion = String(data.atencion_id || "");
    registro.value.tipo_estudio = data.tipo_estudio || "";

    if (data.paciente.fec_naci) {
        const partes = String(data.paciente.fec_naci).substring(0, 10).split("-");
        if (partes.length === 3) {
            const anio = parseInt(partes[0], 10);
            const mes = parseInt(partes[1], 10) - 1;
            const dia = parseInt(partes[2], 10);
            const hoy = new Date();
            let edad = hoy.getFullYear() - anio;
            const mesDiff = hoy.getMonth() - mes;
            if (mesDiff < 0 || (mesDiff === 0 && hoy.getDate() < dia)) {
                edad--;
            }
            registro.value.edad = String(edad);
        } else {
            registro.value.edad = "";
        }
    } else {
        registro.value.edad = "";
    }

    registro.value.lectura = data.lectura;
    registro.value.opinion = data.opinion;
    registro.value.tipo_plantilla_id = "";
    registro.value.plantilla_id = "";
    plantillas.value = [];

    // El médico seleccionado se obtiene prioritariamente del storage (header/sesión del médico seleccionado)
    const medicoStorage =
        sessionStorage.getItem("ST-ecografia-medico") ||
        localStorage.getItem("ST-ecografia-medico") ||
        sessionStorage.getItem("ST-medico") ||
        localStorage.getItem("ST-medico") ||
        busqueda.value.medico_id ||
        props.usuarioactual?.id;

    if (medicoStorage) {
        registro.value.medico_id = String(medicoStorage);
    } else if (data.medico_id) {
        registro.value.medico_id = String(data.medico_id);
    }

    registro.value.estado_envio_sahi = data.estado_envio_sahi || null;
    registro.value.fechor_envio_sahi = data.fechor_envio_sahi || null;
    registro.value.error_envio_sahi = data.error_envio_sahi || null;
    registro.value.sahi_esquema_149 = data.sahi_esquema_149 || null;
    registro.value.sahi_esquema_150 = data.sahi_esquema_150 || null;

    if (sedeInterface.value && registro.value.estado_envio_sahi === "ENVIANDO") {
        startSahiPolling();
    } else {
        stopSahiPolling();
    }

    btnCerrarModalForm();
}
/**
 * Reinicia los campos del formulario de lectura y detiene el sondeo SAHI.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
function limpiarFormulario() {
    tituloModal.value = "";
    detenerAutoguardado();
    estadoAutoguardado.value = { visible: false, guardando: false, error: false, texto: "" };
    stopSahiPolling();

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
    registro.value.quien_ordena = "";
    registro.value.ubicacion = "";
    registro.value.cama = "";
    registro.value.lectura = "";
    registro.value.opinion = "";
    errores.value = {};
}

/**
 * Hook de ciclo de vida que inicializa sede, tipos de plantillas, médico, vocabulario médico y suscripciones a eventos del emitter.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
function onSedeSeleccionada(data) {
    const nuevaSede =
        data?.idSede ?? sessionStorage.getItem("ST-sede") ?? localStorage.getItem("ST-sede");
    if (!nuevaSede) return;
    registro.value.sede_id = nuevaSede;
    busqueda.value.sede_id = nuevaSede;
    initSede();
}

function onMedicoSeleccionado(data) {
    if (data?.idMedico) {
        registro.value.medico_id = data.idMedico;
        busqueda.value.medico_id = String(data.idMedico);
    }
    establecerMedico();
    listarTiposPlantillas(data?.idMedico);
}

/**
 * Hook de ciclo de vida que inicializa sede, tipos de plantillas, médico, vocabulario médico y suscripciones a eventos del emitter.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
function handleKeydown(e) {
    if (e.key === "F2") {
        e.preventDefault();
        toggleSpeechRecognition();
    }
}

onMounted(() => {
    initSede();
    listarTiposPlantillas();
    establecerMedico();
    rebuildMedicalVocabulary();

    emitter.on("sedeSeleccionada", onSedeSeleccionada);
    emitter.on("medicoSeleccionado", onMedicoSeleccionado);
    if (!registro.value.sede_id && emitter) {
        emitter.emit("solicitarSedeActiva");
    }
    window.addEventListener("keydown", handleKeydown);
    document.addEventListener("visibilitychange", handleVoiceVisibilityChange);
    window.addEventListener("focus", handleVoiceWindowFocus);

    url.value = window.location.pathname;
});

onBeforeUnmount(() => {
    window.removeEventListener("keydown", handleKeydown);
    document.removeEventListener("visibilitychange", handleVoiceVisibilityChange);
    window.removeEventListener("focus", handleVoiceWindowFocus);
    emitter.off("sedeSeleccionada", onSedeSeleccionada);
    emitter.off("medicoSeleccionado", onMedicoSeleccionado);
    detenerAutoguardado();
    stopSahiPolling();
    try {
        stopVoice();
    } catch {
        /* noop */
    }
});
</script>
<style scoped>
/* ============================================================== */
/* Floating Dictation Pill Widget                                 */
/* ============================================================== */
.floating-dictation-pill {
    position: fixed;
    bottom: 30px;
    right: 40px;
    z-index: 1200;
    display: flex;
    align-items: center;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    padding: 6px 10px 6px 14px;
    border-radius: 50px;
    box-shadow:
        0 10px 25px -5px rgba(0, 0, 0, 0.2),
        0 8px 10px -6px rgba(0, 0, 0, 0.1);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    user-select: none;
}

.floating-dictation-pill.is-dark {
    background: #1e222b;
    border-color: #3b4254;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.6);
}

.floating-dictation-pill .status-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: #94a3b8;
    display: inline-block;
    margin-right: 10px;
    transition: background-color 0.3s ease;
}

.floating-dictation-pill.is-recording .status-dot {
    background-color: #ef4444;
    box-shadow: 0 0 10px #ef4444;
    animation: pulse 1.2s infinite;
}

.floating-dictation-pill.is-paused .status-dot {
    background-color: #f59e0b;
    box-shadow: 0 0 8px #f59e0b;
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
    }
}

.pill-waveform {
    display: flex;
    align-items: center;
    height: 16px;
    margin-right: 10px;
    gap: 3px;
}

.wave-bar {
    width: 3px;
    background-color: #ef4444;
    border-radius: 2px;
    animation: soundWave 0.8s ease-in-out infinite alternate;
}

.wave-bar.bar-1 {
    height: 6px;
    animation-delay: 0.1s;
}
.wave-bar.bar-2 {
    height: 14px;
    animation-delay: 0.3s;
}
.wave-bar.bar-3 {
    height: 10px;
    animation-delay: 0.2s;
}
.wave-bar.bar-4 {
    height: 16px;
    animation-delay: 0.4s;
}

@keyframes soundWave {
    0% {
        height: 4px;
    }
    100% {
        height: 18px;
    }
}

.pill-info {
    display: flex;
    flex-direction: column;
    margin-right: 12px;
    min-width: 110px;
}

.pill-status-text {
    font-size: 12px;
    font-weight: 600;
    line-height: 1.2;
    color: #1e293b;
}

.floating-dictation-pill.is-dark .pill-status-text {
    color: #f8fafc;
}

.pill-shortcut {
    font-size: 10px;
    line-height: 1;
}

.pill-btn {
    width: 38px;
    height: 38px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    transition: transform 0.15s ease;
}

.pill-btn:hover {
    transform: scale(1.08);
}

.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.3s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(15px);
}

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
    right: -96%;
    width: 96%;
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
    width: 96%;
    visibility: visible;
    pointer-events: auto;
}

@media (max-width: 767px) {
    .shw-rside {
        width: 100%;
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

/* ============================================================== */
/* Modo Zen / Pantalla Completa (100% viewport)                   */
/* ============================================================== */
.right-sidebar.zen-mode {
    width: 100% !important;
    right: 0 !important;
    height: 100% !important;
    z-index: 2500 !important;
}

.breadcrumb-btn {
    vertical-align: baseline;
}

.mic-link {
    width: 30px;
    height: 30px;
    display: inline-block;
}

/* ============================================================== */
/* Modo Oscuro / Radiología (Dark Mode para entorno Ecografías)   */
/* ============================================================== */
.right-sidebar.dark-mode {
    background: #181a20 !important;
    color: #e2e8f0 !important;
}

.right-sidebar.dark-mode .rpanel-title {
    background: #12141a !important;
    color: #f1f5f9 !important;
    border-bottom: 1px solid #2d3343 !important;
}

.right-sidebar.dark-mode .r-panel-body {
    background: #181a20 !important;
}

.right-sidebar.dark-mode hr {
    border-top-color: #2d3343 !important;
}

.right-sidebar.dark-mode dt,
.right-sidebar.dark-mode label {
    color: #cbd5e1 !important;
}

.right-sidebar.dark-mode dd,
.right-sidebar.dark-mode p,
.right-sidebar.dark-mode span,
.right-sidebar.dark-mode li {
    color: #e2e8f0;
}

.right-sidebar.dark-mode .form-control {
    background-color: #222632 !important;
    color: #f8fafc !important;
    border-color: #3b4254 !important;
}

.right-sidebar.dark-mode .form-control:focus {
    background-color: #262b38 !important;
    border-color: #00bcd4 !important;
    color: #ffffff !important;
}

.right-sidebar.dark-mode .nav-tabs {
    border-bottom-color: #2d3343 !important;
}

.right-sidebar.dark-mode .nav-tabs .nav-link {
    color: #94a3b8 !important;
    background-color: transparent !important;
    border-color: transparent !important;
}

.right-sidebar.dark-mode .nav-tabs .nav-link.active {
    color: #38bdf8 !important;
    background-color: #222632 !important;
    border-color: #2d3343 #2d3343 transparent !important;
}

.right-sidebar.dark-mode .tab-content {
    background: #1f232d !important;
    border-color: #2d3343 !important;
}

.right-sidebar.dark-mode .list-group-item {
    background-color: #222632 !important;
    border-color: #2d3343 !important;
    color: #e2e8f0 !important;
}

.right-sidebar.dark-mode .table {
    color: #e2e8f0 !important;
}

.right-sidebar.dark-mode .table-bordered,
.right-sidebar.dark-mode .table-bordered th,
.right-sidebar.dark-mode .table-bordered td {
    border-color: #2d3343 !important;
}

.right-sidebar.dark-mode :deep(.wysihtml5-toolbar) {
    background: #222632 !important;
    border-color: #3b4254 !important;
    padding: 6px !important;
    border-radius: 4px 4px 0 0 !important;
}

.right-sidebar.dark-mode :deep(.wysihtml5-toolbar .btn) {
    background-color: #2d3343 !important;
    border-color: #3b4254 !important;
    color: #f1f5f9 !important;
}

.right-sidebar.dark-mode :deep(.wysihtml5-toolbar .btn:hover) {
    background-color: #3b4254 !important;
    color: #38bdf8 !important;
}

.right-sidebar.dark-mode :deep(.wysihtml5-sandbox) {
    border-color: #3b4254 !important;
    background-color: #1e222b !important;
}

:deep(.select2-dropdown) {
    z-index: 9999 !important;
}

/* ============================================================== */
/* Estilos compactos para tabla de ecografías pendientes          */
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
    max-width: 300px;
    word-break: break-word;
    color: #1a202c;
}

.table-hover tbody tr:hover {
    background-color: #f1f7ff;
}
</style>
