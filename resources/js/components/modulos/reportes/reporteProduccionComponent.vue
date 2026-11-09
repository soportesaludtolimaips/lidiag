<template>
    <div>
        <!-- Bread crumb -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Reportes</h3>
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
                    <li class="breadcrumb-item active">Producción</li>
                </ol>
            </div>
        </div>

        <!-- Indicadores Asistenciales (Nativos AdminWrap) -->
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex p-10 no-block">
                            <div class="align-self-center display-6 m-r-20">
                                <i class="text-success fa fa-list-alt"></i>
                            </div>
                            <div class="align-slef-center">
                                <h2 class="m-b-0">
                                    <span v-if="loadingIndicadores">...</span>
                                    <span v-else>
                                        {{ indicadores.estudiosAsignados?.toLocaleString() || 0 }}
                                    </span>
                                </h2>
                                <h6 class="text-muted m-b-0">Total estudios asignados (30d)</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex p-10 no-block">
                            <div class="align-self-center display-6 m-r-20">
                                <i class="text-primary fa fa-user-md"></i>
                            </div>
                            <div class="align-slef-center">
                                <h2 class="m-b-0">
                                    <span v-if="loadingIndicadores">...</span>
                                    <span v-else>
                                        {{ indicadores.estudiosLeidos?.toLocaleString() || 0 }}
                                    </span>
                                </h2>
                                <h6 class="text-muted m-b-0">Total estudios leídos</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex p-10 no-block">
                            <div class="align-self-center display-6 m-r-20">
                                <i class="text-warning fa fa-microphone"></i>
                            </div>
                            <div class="align-slef-center">
                                <h2 class="m-b-0">
                                    <span v-if="loadingIndicadores">...</span>
                                    <span v-else>
                                        {{
                                            indicadores.estudiosPorTranscribir?.toLocaleString() ||
                                            0
                                        }}
                                    </span>
                                </h2>
                                <h6 class="text-muted m-b-0">Pend. transcripción</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex p-10 no-block">
                            <div class="align-self-center display-6 m-r-20">
                                <i class="text-info fa fa-check-circle"></i>
                            </div>
                            <div class="align-slef-center">
                                <h2 class="m-b-0">
                                    <span v-if="loadingIndicadores">...</span>
                                    <span v-else>
                                        {{ indicadores.operacion?.transcritosHoy || 0 }}
                                    </span>
                                </h2>
                                <h6 class="text-muted m-b-0">Finalizados hoy</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pestañas de Agrupación por Módulo (AdminWrap / Bootstrap 4) -->
        <div class="row m-b-20">
            <div class="col-12">
                <ul class="nav nav-tabs customtab" role="tablist">
                    <li class="nav-item" v-if="tieneAccesoModulo('Rx')">
                        <a
                            class="nav-link"
                            :class="{ active: moduloActivo === 'Rx' }"
                            href="javascript:void(0)"
                            @click="cambiarModulo('Rx')"
                        >
                            <span class="hidden-sm-up"><i class="fa fa-heartbeat"></i></span>
                            <span class="hidden-xs-down"
                                ><i class="fa fa-heartbeat text-info m-r-5"></i> Módulo Rx</span
                            >
                        </a>
                    </li>
                    <li class="nav-item" v-if="tieneAccesoModulo('Ecografias')">
                        <a
                            class="nav-link"
                            :class="{ active: moduloActivo === 'Ecografias' }"
                            href="javascript:void(0)"
                            @click="cambiarModulo('Ecografias')"
                        >
                            <span class="hidden-sm-up"><i class="fa fa-stethoscope"></i></span>
                            <span class="hidden-xs-down"
                                ><i class="fa fa-stethoscope text-primary m-r-5"></i> Módulo
                                Ecografías</span
                            >
                        </a>
                    </li>
                    <li class="nav-item" v-if="tieneAccesoModulo('Oftalmologia')">
                        <a
                            class="nav-link"
                            :class="{ active: moduloActivo === 'Oftalmologia' }"
                            href="javascript:void(0)"
                            @click="cambiarModulo('Oftalmologia')"
                        >
                            <span class="hidden-sm-up"><i class="fa fa-eye"></i></span>
                            <span class="hidden-xs-down"
                                ><i class="fa fa-eye text-success m-r-5"></i> Módulo
                                Oftalmología</span
                            >
                        </a>
                    </li>
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            :class="{ active: moduloActivo === 'Transversal' }"
                            href="javascript:void(0)"
                            @click="cambiarModulo('Transversal')"
                        >
                            <span class="hidden-sm-up"><i class="fa fa-calendar-check-o"></i></span>
                            <span class="hidden-xs-down"
                                ><i class="fa fa-calendar-check-o text-muted m-r-5"></i> Citas y
                                Gestión General</span
                            >
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- 1. GRUPO: MÓDULO RX (Radiología e Imágenes Diagnósticas)        -->
        <!-- ============================================================== -->
        <div class="row" v-if="moduloActivo === 'Rx' && tieneAccesoModulo('Rx')">
            <div class="col-md-3 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-filter"></i> Producción Radiológica
                        </h4>
                        <div class="row">
                            <div class="col-12">
                                <a href="javascript:void(0)" @click="abrirModalProduccion('Rx')">
                                    <i class="fa fa-check text-info"></i>
                                    Producción detallada (RX, Tomografía, Mamografía, Resonancia)
                                </a>
                            </div>
                            <div class="col-12">
                                <a
                                    href="javascript:void(0)"
                                    @click="abrirModalProduccionConsolidada()"
                                >
                                    <i class="fa fa-balance-scale text-warning"></i>
                                    Producción consolidada (Tomados PACS vs Asignados LIDiag)
                                </a>
                            </div>
                            <div class="col-12">
                                <a href="javascript:void(0)" @click="abrirModalConsolidadoRed()">
                                    <i class="fa fa-globe text-info"></i>
                                    Consolidado red hospitalaria (Ranking, Modalidades, TAT)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-database"></i> Estudios tomados en PAC
                        </h4>
                        <div class="row">
                            <div class="col-12">
                                <a href="javascript:void(0)" @click="abrirModalPAC()">
                                    <i class="fa fa-check text-info"></i>
                                    Descargar reporte de estudios tomados en PACS
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-globe"></i> Tele-radiología
                        </h4>
                        <div class="row">
                            <div class="col-12">
                                <a href="javascript:void(0)" @click="abrirModalTeleRadiologia()">
                                    <i class="fa fa-check text-info"></i>
                                    Reporte de órdenes y lecturas externas
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-radiation text-warning"></i> Dosimetría y Radiación
                        </h4>
                        <div class="row">
                            <div class="col-12">
                                <a href="javascript:void(0)" @click="abrirModalDosimetria()">
                                    <i class="fa fa-check text-info"></i>
                                    Reporte de Dosimetría y Niveles NRD (Res. 482)
                                </a>
                            </div>
                            <div
                                class="col-12"
                                v-if="can('dosimetria.dashboard') || can('reportes.detallados')"
                            >
                                <a href="/dosis/dashboard" target="_blank">
                                    <i class="fa fa-external-link text-info"></i>
                                    Tablero interactivo de dosimetría
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-tachometer"></i> Calidad y Oportunidad
                        </h4>
                        <div class="row">
                            <div class="col-12">
                                <a href="javascript:void(0)" @click="abrirModalTAT()">
                                    <i class="fa fa-check text-info"></i>
                                    Tiempos de respuesta y entrega (TAT)
                                </a>
                            </div>
                            <div class="col-12">
                                <a
                                    href="javascript:void(0)"
                                    @click="abrirModalSemaforoRepresados()"
                                >
                                    <i class="fa fa-exclamation-triangle text-warning"></i>
                                    Semáforo de oportunidad y represamiento (>24h y Urgencias)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-money"></i> Financiero / Liquidación
                        </h4>
                        <div class="row">
                            <div
                                class="col-12"
                                v-if="can('liquidacion.exportar') || can('reportes.detallados')"
                            >
                                <a href="javascript:void(0)" @click="abrirModalLiquidacion()">
                                    <i class="fa fa-check text-info"></i>
                                    Liquidación de honorarios a radiólogos
                                </a>
                            </div>
                            <div
                                class="col-12"
                                v-if="can('liquidacion.exportar') || can('reportes.detallados')"
                            >
                                <a href="javascript:void(0)" @click="abrirModalProductividad()">
                                    <i class="fa fa-check text-info"></i>
                                    Productividad médica (Corte mensual)
                                </a>
                            </div>
                            <div class="col-12">
                                <a href="/liquidacion" target="_blank">
                                    <i class="fa fa-external-link text-info"></i>
                                    Ir al módulo de liquidación
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-server"></i> Agentes y Servidores PACS
                        </h4>
                        <div class="row">
                            <div class="col-12">
                                <a href="javascript:void(0)" @click="abrirModalAgentes()">
                                    <i class="fa fa-check text-info"></i>
                                    Salud y telemetría de agentes hospitalarios
                                </a>
                            </div>
                            <div class="col-12">
                                <a href="javascript:void(0)" @click="abrirModalAlmacenamiento()">
                                    <i class="fa fa-hdd-o text-info"></i>
                                    Monitor de almacenamiento y servidores PACS
                                </a>
                            </div>
                            <div
                                class="col-12"
                                v-if="can('seguridad.backups.index') || can('reportes.detallados')"
                            >
                                <a href="/seguridad-backups" target="_blank">
                                    <i class="fa fa-external-link text-info"></i>
                                    Monitor central de backups y sedes
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- 2. GRUPO: MÓDULO ECOGRAFÍAS                                     -->
        <!-- ============================================================== -->
        <div class="row" v-if="moduloActivo === 'Ecografias' && tieneAccesoModulo('Ecografias')">
            <div class="col-md-4 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-stethoscope"></i> Producción de Ecografías
                        </h4>
                        <div class="row">
                            <div class="col-12">
                                <a
                                    href="javascript:void(0)"
                                    @click="abrirModalProduccion('Ecografia')"
                                >
                                    <i class="fa fa-check text-info"></i>
                                    Producción detallada (Ecografías)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-id-card-o"></i> Consentimientos y Formatos
                        </h4>
                        <div class="row">
                            <div class="col-12" v-if="can('ecografias.consentimientos.index')">
                                <a href="/ecografias-consentimientos" target="_blank">
                                    <i class="fa fa-external-link text-info"></i>
                                    Consentimientos informados firmados (PDF)
                                </a>
                            </div>
                            <div
                                class="col-12"
                                v-if="can('ecografias.consentimientos.plantillas.index')"
                            >
                                <a href="/ecografias-consentimientos-plantillas" target="_blank">
                                    <i class="fa fa-external-link text-info"></i>
                                    Plantillas de consentimientos
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-address-book-o"></i> Atención y Procedimientos
                        </h4>
                        <div class="row">
                            <div class="col-12" v-if="can('ecografias.admision')">
                                <a href="/ecografias-admisiones" target="_blank">
                                    <i class="fa fa-external-link text-info"></i>
                                    Admisionar para procedimientos
                                </a>
                            </div>
                            <div class="col-12" v-if="can('ecografias.index')">
                                <a href="/ecografias-adminisionadas" target="_blank">
                                    <i class="fa fa-external-link text-info"></i>
                                    Ecografías admisionadas en sala
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- 3. GRUPO: MÓDULO OFTALMOLOGÍA                                   -->
        <!-- ============================================================== -->
        <div
            class="row"
            v-if="moduloActivo === 'Oftalmologia' && tieneAccesoModulo('Oftalmologia')"
        >
            <div class="col-md-4 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-eye"></i> Producción de Oftalmología
                        </h4>
                        <div class="row">
                            <div class="col-12">
                                <a
                                    href="javascript:void(0)"
                                    @click="abrirModalProduccion('Oftalmologia')"
                                >
                                    <i class="fa fa-check text-info"></i>
                                    Producción detallada (Oftalmología)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-file-text-o"></i> Diagnóstico Ocular
                        </h4>
                        <div class="row">
                            <div class="col-12">
                                <span class="text-muted small">
                                    <i class="fa fa-info-circle text-info"></i>
                                    Exámenes: Campimetrías, Biometrías, Paquimetrías y Tomografías
                                    Oculares (OCT).
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-calendar"></i> Agenda de Oftalmología
                        </h4>
                        <div class="row">
                            <div
                                class="col-12"
                                v-if="can('agendamiento.agendamiento.index') || can('citas.index')"
                            >
                                <a href="/agendamiento" target="_blank">
                                    <i class="fa fa-external-link text-info"></i>
                                    Agenda médica de especialistas
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- 4. GRUPO: TRANSVERSAL Y GESTIÓN DE CITAS                        -->
        <!-- ============================================================== -->
        <div class="row" v-if="moduloActivo === 'Transversal'">
            <div class="col-md-4 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-calendar-check-o"></i> Citas y Agenda
                        </h4>
                        <div class="row">
                            <div class="col-12">
                                <a href="javascript:void(0)" @click="abrirModalAusentismo()">
                                    <i class="fa fa-check text-info"></i>
                                    Ausentismo y cancelaciones (No-Show)
                                </a>
                            </div>
                            <div class="col-12" v-if="can('citas.index')">
                                <a href="/citas" target="_blank">
                                    <i class="fa fa-external-link text-info"></i>
                                    Listado general de citas
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-whatsapp text-success"></i> Canal WhatsApp y Conversión
                        </h4>
                        <div class="row">
                            <div class="col-12">
                                <a href="javascript:void(0)" @click="abrirModalCitasWhatsApp()">
                                    <i class="fa fa-whatsapp text-success"></i>
                                    Estadísticas de citas y canal WhatsApp (Atención Hoy y
                                    Conversión)
                                </a>
                            </div>
                            <div class="col-12" v-if="can('citas.index')">
                                <a href="/citas/revision" target="_blank">
                                    <i class="fa fa-external-link text-info"></i>
                                    Solicitudes de citas por revisar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6" v-if="can('helpdesk.reportes.index')">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-support text-danger"></i> Mesa de Ayuda
                        </h4>
                        <div class="row">
                            <div class="col-12">
                                <a href="/helpdesk-reportes" target="_blank">
                                    <i class="fa fa-external-link text-info"></i>
                                    Reportes de incidentes y Helpdesk
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Producción Detallada -->
        <div
            class="modal fade"
            :class="{ show: modalProduccionVisible }"
            :style="{ display: modalProduccionVisible ? 'block' : 'none' }"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            @click.self="
                resetearFormulario();
                modalProduccionVisible = false;
            "
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">
                            Filtro para el reporte de Producción ({{
                                tipoReporte === "Rx"
                                    ? "RX, Tomografía, Mamografía, Resonancia"
                                    : tipoReporte === "Ecografia"
                                      ? "Ecografías"
                                      : "Oftalmología"
                            }})
                        </h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            @click="
                                resetearFormulario();
                                modalProduccionVisible = false;
                            "
                        >
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="descargarReporte()">
                            <div class="form-group">
                                <label class="control-label" for="prod-sede"
                                    >Sede Hospitalaria</label
                                >
                                <select
                                    class="form-control custom-select"
                                    style="width: 100%; height: 36px"
                                    v-model="datosReporte.sedeId"
                                    id="prod-sede"
                                >
                                    <option value="0">...::: Todas las sedes :::...</option>
                                    <option v-for="sede in sedes" :key="sede.id" :value="sede.id">
                                        {{ sede.nom_sede }}
                                    </option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="prod-quien-asigno"
                                    >Quién Agendó</label
                                >
                                <select
                                    class="form-control custom-select"
                                    style="width: 100%; height: 36px"
                                    v-model="datosReporte.quien_asigno"
                                    id="prod-quien-asigno"
                                >
                                    <option value="0">...::: Todos quienes agendaron :::...</option>
                                    <option
                                        v-for="usuario in quienes_asignaron"
                                        :key="usuario.id"
                                        :value="usuario.id"
                                    >
                                        {{ usuario.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="prod-medico"
                                    >Médico Radiólogo</label
                                >
                                <select
                                    class="form-control custom-select"
                                    style="width: 100%; height: 36px"
                                    v-model="datosReporte.medico"
                                    id="prod-medico"
                                >
                                    <option value="" selected>
                                        ...::: Todos los médicos :::...
                                    </option>
                                    <option
                                        v-for="usuario in medicos"
                                        :key="usuario.id"
                                        :value="usuario.id"
                                    >
                                        {{ usuario.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label
                                            class="control-label font-weight-bold"
                                            for="prod-fechaIni"
                                            >Desde</label
                                        >
                                        <input
                                            class="form-control input-daterange-datepicker"
                                            type="date"
                                            id="prod-fechaIni"
                                            v-model="datosReporte.fechaIni"
                                            @keyup.enter="descargarReporte()"
                                        />
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label
                                            class="control-label font-weight-bold"
                                            for="prod-fechaFin"
                                            >Hasta</label
                                        >
                                        <input
                                            class="form-control input-daterange-datepicker"
                                            type="date"
                                            id="prod-fechaFin"
                                            v-model="datosReporte.fechaFin"
                                            @keyup.enter="descargarReporte()"
                                        />
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-default waves-effect"
                            @click="
                                resetearFormulario();
                                modalProduccionVisible = false;
                            "
                        >
                            Cerrar
                        </button>
                        <button
                            type="button"
                            class="btn btn-info waves-effect waves-light"
                            @click="descargarReporte()"
                            :disabled="loadingDescargarProduccion"
                        >
                            <i v-if="loadingDescargarProduccion" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-download"></i>
                            Descargar reportes
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="modalProduccionVisible" class="modal-backdrop fade show"></div>

        <!-- Modal Producción Consolidada (Tomados PACS vs Asignados LIDiag) -->
        <div
            class="modal fade"
            :class="{ show: modalProduccionConsolidadaVisible }"
            :style="{ display: modalProduccionConsolidadaVisible ? 'block' : 'none' }"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            @click.self="
                resetearFormularioConsolidada();
                modalProduccionConsolidadaVisible = false;
            "
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">
                            <i class="fa fa-balance-scale text-warning"></i> Producción Consolidada
                            (Tomados vs Asignados)
                        </h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            @click="
                                resetearFormularioConsolidada();
                                modalProduccionConsolidadaVisible = false;
                            "
                        >
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="descargarProduccionConsolidada()">
                            <div class="form-group">
                                <label class="control-label" for="cons-sede"
                                    >Sede Hospitalaria</label
                                >
                                <select
                                    class="form-control custom-select"
                                    style="width: 100%; height: 36px"
                                    v-model="datosProduccionConsolidada.sedeId"
                                    id="cons-sede"
                                >
                                    <option value="0">
                                        ...::: Todas las sedes de la red :::...
                                    </option>
                                    <option v-for="sede in sedes" :key="sede.id" :value="sede.id">
                                        {{ sede.nom_sede }}
                                    </option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="cons-modalidad"
                                    >Modalidad Diagnóstica</label
                                >
                                <select
                                    class="form-control custom-select"
                                    style="width: 100%; height: 36px"
                                    v-model="datosProduccionConsolidada.modalidad"
                                    id="cons-modalidad"
                                >
                                    <option value="">...::: Todas las modalidades :::...</option>
                                    <option value="DX">Radiografía Digital (DX / RX)</option>
                                    <option value="CR">Radiografía Computarizada (CR)</option>
                                    <option value="CT">Tomografía Computarizada (CT / TAC)</option>
                                    <option value="MR">Resonancia Magnética (MR / RM)</option>
                                    <option value="MG">Mamografía (MG)</option>
                                    <option value="US">Ecografía / Ultrasonido (US)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="cons-destino"
                                    >Destino del Estudio</label
                                >
                                <select
                                    class="form-control custom-select"
                                    style="width: 100%; height: 36px"
                                    v-model="datosProduccionConsolidada.destino"
                                    id="cons-destino"
                                >
                                    <option value="">
                                        ...::: Todos (Salud Tolima y Tele-Radiología) :::...
                                    </option>
                                    <option value="Salud Tolima">Salud Tolima (Lectura Red)</option>
                                    <option value="Tele Radiologia">
                                        Tele-Radiología (Enviados a Tele)
                                    </option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="control-label" for="cons-fechaIni"
                                            >Fecha Inicial</label
                                        >
                                        <input
                                            type="date"
                                            class="form-control"
                                            id="cons-fechaIni"
                                            v-model="datosProduccionConsolidada.fechaIni"
                                            @keyup.enter="descargarProduccionConsolidada()"
                                        />
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="control-label" for="cons-fechaFin"
                                            >Fecha Final</label
                                        >
                                        <input
                                            type="date"
                                            class="form-control"
                                            id="cons-fechaFin"
                                            v-model="datosProduccionConsolidada.fechaFin"
                                            @keyup.enter="descargarProduccionConsolidada()"
                                        />
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-default waves-effect"
                            @click="
                                resetearFormularioConsolidada();
                                modalProduccionConsolidadaVisible = false;
                            "
                        >
                            Cerrar
                        </button>
                        <button
                            type="button"
                            class="btn btn-warning waves-effect waves-light"
                            @click="descargarProduccionConsolidada()"
                            :disabled="loadingDescargarConsolidada"
                        >
                            <i v-if="loadingDescargarConsolidada" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-download"></i>
                            Descargar Consolidado (.xlsx)
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="modalProduccionConsolidadaVisible" class="modal-backdrop fade show"></div>

        <!-- Modal PAC -->
        <div
            class="modal fade"
            :class="{ show: modalPACVisible }"
            :style="{ display: modalPACVisible ? 'block' : 'none' }"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            @click.self="
                resetearFormularioPAC();
                modalPACVisible = false;
            "
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Filtro para el reporte de estudios tomados</h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            @click="
                                resetearFormularioPAC();
                                modalPACVisible = false;
                            "
                        >
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="descargarReporteEstudiosPAC()">
                            <div class="form-group">
                                <label class="control-label" for="pac-sede"
                                    >Sede Hospitalaria</label
                                >
                                <select
                                    class="form-control custom-select"
                                    style="width: 100%; height: 36px"
                                    v-model="datosPAC.sedeId"
                                    id="pac-sede"
                                >
                                    <option value="0">...::: Seleccione una sede :::...</option>
                                    <option v-for="sede in sedes" :key="sede.id" :value="sede.id">
                                        {{ sede.nom_sede }}
                                    </option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label
                                            class="control-label font-weight-bold"
                                            for="pac-fechaIni"
                                            >Desde</label
                                        >
                                        <input
                                            class="form-control"
                                            type="date"
                                            id="pac-fechaIni"
                                            v-model="datosPAC.fechaIni"
                                            @keyup.enter="descargarReporteEstudiosPAC()"
                                        />
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label
                                            class="control-label font-weight-bold"
                                            for="pac-fechaFin"
                                            >Hasta</label
                                        >
                                        <input
                                            class="form-control"
                                            type="date"
                                            id="pac-fechaFin"
                                            v-model="datosPAC.fechaFin"
                                            @keyup.enter="descargarReporteEstudiosPAC()"
                                        />
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-default waves-effect"
                            @click="
                                resetearFormularioPAC();
                                modalPACVisible = false;
                            "
                        >
                            Cerrar
                        </button>
                        <button
                            type="button"
                            class="btn btn-info waves-effect waves-light"
                            @click="descargarReporteEstudiosPAC()"
                            :disabled="loadingDescargarPAC"
                        >
                            <i v-if="loadingDescargarPAC" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-download"></i>
                            Descargar reporte
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="modalPACVisible" class="modal-backdrop fade show"></div>

        <!-- Modal Dosimetría / Control de Radiación -->
        <div
            class="modal fade"
            :class="{ show: modalDosimetriaVisible }"
            :style="{ display: modalDosimetriaVisible ? 'block' : 'none' }"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            @click.self="
                resetearFormularioDosimetria();
                modalDosimetriaVisible = false;
            "
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">
                            Filtro para reporte de Dosimetría (Res. 482 de 2018)
                        </h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            @click="
                                resetearFormularioDosimetria();
                                modalDosimetriaVisible = false;
                            "
                        >
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="descargarReporteDosimetria()">
                            <div class="form-group">
                                <label class="control-label" for="dosis-sede"
                                    >Sede Hospitalaria</label
                                >
                                <select
                                    class="form-control custom-select"
                                    style="width: 100%; height: 36px"
                                    v-model="datosDosimetria.sedeId"
                                    id="dosis-sede"
                                >
                                    <option value="0">...::: Todas las sedes :::...</option>
                                    <option v-for="sede in sedes" :key="sede.id" :value="sede.id">
                                        {{ sede.nom_sede }}
                                    </option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label
                                            class="control-label font-weight-bold"
                                            for="dosis-fechaIni"
                                            >Desde</label
                                        >
                                        <input
                                            class="form-control"
                                            type="date"
                                            id="dosis-fechaIni"
                                            v-model="datosDosimetria.fechaIni"
                                            @keyup.enter="descargarReporteDosimetria()"
                                        />
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label
                                            class="control-label font-weight-bold"
                                            for="dosis-fechaFin"
                                            >Hasta</label
                                        >
                                        <input
                                            class="form-control"
                                            type="date"
                                            id="dosis-fechaFin"
                                            v-model="datosDosimetria.fechaFin"
                                            @keyup.enter="descargarReporteDosimetria()"
                                        />
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-default waves-effect"
                            @click="
                                resetearFormularioDosimetria();
                                modalDosimetriaVisible = false;
                            "
                        >
                            Cerrar
                        </button>
                        <button
                            type="button"
                            class="btn btn-info waves-effect waves-light"
                            @click="descargarReporteDosimetria()"
                            :disabled="loadingDescargarDosimetria"
                        >
                            <i v-if="loadingDescargarDosimetria" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-download"></i>
                            Descargar reporte
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="modalDosimetriaVisible" class="modal-backdrop fade show"></div>

        <!-- Modal Tele-radiología -->
        <div
            class="modal fade"
            :class="{ show: modalTeleRadiologiaVisible }"
            :style="{ display: modalTeleRadiologiaVisible ? 'block' : 'none' }"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            @click.self="
                resetearFormularioTeleRadiologia();
                modalTeleRadiologiaVisible = false;
            "
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Filtro de Tele-radiología y Lecturas Externas</h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            @click="
                                resetearFormularioTeleRadiologia();
                                modalTeleRadiologiaVisible = false;
                            "
                        >
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="descargarReporteTeleRadiologia()">
                            <div class="form-group">
                                <label class="control-label" for="tele-sede"
                                    >Sede Hospitalaria</label
                                >
                                <select
                                    class="form-control custom-select"
                                    style="width: 100%; height: 36px"
                                    v-model="datosTeleRadiologia.sedeId"
                                    id="tele-sede"
                                >
                                    <option value="0">...::: Todas las sedes :::...</option>
                                    <option v-for="sede in sedes" :key="sede.id" :value="sede.id">
                                        {{ sede.nom_sede }}
                                    </option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label
                                            class="control-label font-weight-bold"
                                            for="tele-fechaIni"
                                            >Desde</label
                                        >
                                        <input
                                            class="form-control"
                                            type="date"
                                            id="tele-fechaIni"
                                            v-model="datosTeleRadiologia.fechaIni"
                                            @keyup.enter="descargarReporteTeleRadiologia()"
                                        />
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label
                                            class="control-label font-weight-bold"
                                            for="tele-fechaFin"
                                            >Hasta</label
                                        >
                                        <input
                                            class="form-control"
                                            type="date"
                                            id="tele-fechaFin"
                                            v-model="datosTeleRadiologia.fechaFin"
                                            @keyup.enter="descargarReporteTeleRadiologia()"
                                        />
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-default waves-effect"
                            @click="
                                resetearFormularioTeleRadiologia();
                                modalTeleRadiologiaVisible = false;
                            "
                        >
                            Cerrar
                        </button>
                        <button
                            type="button"
                            class="btn btn-info waves-effect waves-light"
                            @click="descargarReporteTeleRadiologia()"
                            :disabled="loadingDescargarTeleRadiologia"
                        >
                            <i
                                v-if="loadingDescargarTeleRadiologia"
                                class="fa fa-spinner fa-spin"
                            ></i>
                            <i v-else class="fa fa-download"></i>
                            Descargar reporte
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="modalTeleRadiologiaVisible" class="modal-backdrop fade show"></div>

        <!-- Modal Ausentismo y Cancelaciones de Citas -->
        <div
            class="modal fade"
            :class="{ show: modalAusentismoVisible }"
            :style="{ display: modalAusentismoVisible ? 'block' : 'none' }"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            @click.self="
                resetearFormularioAusentismo();
                modalAusentismoVisible = false;
            "
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Filtro de Ausentismo y Cancelaciones (No-Show)</h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            @click="
                                resetearFormularioAusentismo();
                                modalAusentismoVisible = false;
                            "
                        >
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="descargarReporteAusentismo()">
                            <div class="form-group">
                                <label class="control-label" for="ausentismo-sede"
                                    >Sede Hospitalaria</label
                                >
                                <select
                                    class="form-control custom-select"
                                    style="width: 100%; height: 36px"
                                    v-model="datosAusentismo.sedeId"
                                    id="ausentismo-sede"
                                >
                                    <option value="0">...::: Todas las sedes :::...</option>
                                    <option v-for="sede in sedes" :key="sede.id" :value="sede.id">
                                        {{ sede.nom_sede }}
                                    </option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="ausentismo-estado"
                                    >Estado de Inasistencia / Cancelación</label
                                >
                                <select
                                    class="form-control custom-select"
                                    style="width: 100%; height: 36px"
                                    v-model="datosAusentismo.estado"
                                    id="ausentismo-estado"
                                >
                                    <option value="todos">
                                        ...::: Todos los ausentes y cancelados :::...
                                    </option>
                                    <option value="no_asistio">
                                        Solo No Asistió (Inasistencias)
                                    </option>
                                    <option value="cancelada">Solo Canceladas</option>
                                    <option value="rechazada">Solo Rechazadas</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label
                                            class="control-label font-weight-bold"
                                            for="ausentismo-fechaIni"
                                            >Desde</label
                                        >
                                        <input
                                            class="form-control"
                                            type="date"
                                            id="ausentismo-fechaIni"
                                            v-model="datosAusentismo.fechaIni"
                                            @keyup.enter="descargarReporteAusentismo()"
                                        />
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label
                                            class="control-label font-weight-bold"
                                            for="ausentismo-fechaFin"
                                            >Hasta</label
                                        >
                                        <input
                                            class="form-control"
                                            type="date"
                                            id="ausentismo-fechaFin"
                                            v-model="datosAusentismo.fechaFin"
                                            @keyup.enter="descargarReporteAusentismo()"
                                        />
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-default waves-effect"
                            @click="
                                resetearFormularioAusentismo();
                                modalAusentismoVisible = false;
                            "
                        >
                            Cerrar
                        </button>
                        <button
                            type="button"
                            class="btn btn-info waves-effect waves-light"
                            @click="descargarReporteAusentismo()"
                            :disabled="loadingDescargarAusentismo"
                        >
                            <i v-if="loadingDescargarAusentismo" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-download"></i>
                            Descargar reporte
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="modalAusentismoVisible" class="modal-backdrop fade show"></div>

        <!-- Modal Liquidación de Honorarios -->
        <div
            class="modal fade"
            :class="{ show: modalLiquidacionVisible }"
            :style="{ display: modalLiquidacionVisible ? 'block' : 'none' }"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            @click.self="
                resetearFormularioLiquidacion();
                modalLiquidacionVisible = false;
            "
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Filtro de Liquidación de Honorarios</h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            @click="
                                resetearFormularioLiquidacion();
                                modalLiquidacionVisible = false;
                            "
                        >
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="descargarReporteLiquidacion()">
                            <div class="form-group">
                                <label class="control-label" for="liq-sede"
                                    >Sede Hospitalaria</label
                                >
                                <select
                                    class="form-control custom-select"
                                    style="width: 100%; height: 36px"
                                    v-model="datosLiquidacion.sedeId"
                                    id="liq-sede"
                                >
                                    <option value="0">...::: Seleccione una sede :::...</option>
                                    <option v-for="sede in sedes" :key="sede.id" :value="sede.id">
                                        {{ sede.nom_sede }}
                                    </option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label
                                            class="control-label font-weight-bold"
                                            for="liq-fechaIni"
                                            >Desde</label
                                        >
                                        <input
                                            class="form-control"
                                            type="date"
                                            id="liq-fechaIni"
                                            v-model="datosLiquidacion.fechaIni"
                                            @keyup.enter="descargarReporteLiquidacion()"
                                        />
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label
                                            class="control-label font-weight-bold"
                                            for="liq-fechaFin"
                                            >Hasta</label
                                        >
                                        <input
                                            class="form-control"
                                            type="date"
                                            id="liq-fechaFin"
                                            v-model="datosLiquidacion.fechaFin"
                                            @keyup.enter="descargarReporteLiquidacion()"
                                        />
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-default waves-effect"
                            @click="
                                resetearFormularioLiquidacion();
                                modalLiquidacionVisible = false;
                            "
                        >
                            Cerrar
                        </button>
                        <button
                            type="button"
                            class="btn btn-info waves-effect waves-light"
                            @click="descargarReporteLiquidacion()"
                            :disabled="loadingDescargarLiquidacion"
                        >
                            <i v-if="loadingDescargarLiquidacion" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-download"></i>
                            Descargar reporte
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="modalLiquidacionVisible" class="modal-backdrop fade show"></div>

        <!-- Modal Productividad Médica (Corte Mensual) -->
        <div
            class="modal fade"
            :class="{ show: modalProductividadVisible }"
            :style="{ display: modalProductividadVisible ? 'block' : 'none' }"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            @click.self="
                resetearFormularioProductividad();
                modalProductividadVisible = false;
            "
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Filtro de Productividad Médica Mensual</h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            @click="
                                resetearFormularioProductividad();
                                modalProductividadVisible = false;
                            "
                        >
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="descargarReporteProductividad()">
                            <div class="form-group">
                                <label class="control-label" for="prod-med-sede"
                                    >Sede Hospitalaria</label
                                >
                                <select
                                    class="form-control custom-select"
                                    style="width: 100%; height: 36px"
                                    v-model="datosProductividad.sedeId"
                                    id="prod-med-sede"
                                >
                                    <option value="0">...::: Seleccione una sede :::...</option>
                                    <option v-for="sede in sedes" :key="sede.id" :value="sede.id">
                                        {{ sede.nom_sede }}
                                    </option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="prod-med-periodo"
                                    >Periodo de Corte (Mes)</label
                                >
                                <input
                                    class="form-control"
                                    type="month"
                                    id="prod-med-periodo"
                                    v-model="datosProductividad.periodo"
                                    @keyup.enter="descargarReporteProductividad()"
                                />
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-default waves-effect"
                            @click="
                                resetearFormularioProductividad();
                                modalProductividadVisible = false;
                            "
                        >
                            Cerrar
                        </button>
                        <button
                            type="button"
                            class="btn btn-info waves-effect waves-light"
                            @click="descargarReporteProductividad()"
                            :disabled="loadingDescargarProductividad"
                        >
                            <i
                                v-if="loadingDescargarProductividad"
                                class="fa fa-spinner fa-spin"
                            ></i>
                            <i v-else class="fa fa-download"></i>
                            Descargar reporte
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="modalProductividadVisible" class="modal-backdrop fade show"></div>

        <!-- Modal Calidad y Oportunidad (Turnaround Time - TAT) -->
        <div
            class="modal fade"
            :class="{ show: modalTATVisible }"
            :style="{ display: modalTATVisible ? 'block' : 'none' }"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            @click.self="
                resetearFormularioTAT();
                modalTATVisible = false;
            "
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Filtro de Calidad y Oportunidad (TAT)</h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            @click="
                                resetearFormularioTAT();
                                modalTATVisible = false;
                            "
                        >
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="descargarReporteTAT()">
                            <div class="form-group">
                                <label class="control-label" for="tat-sede"
                                    >Sede Hospitalaria</label
                                >
                                <select
                                    class="form-control custom-select"
                                    style="width: 100%; height: 36px"
                                    v-model="datosTAT.sedeId"
                                    id="tat-sede"
                                >
                                    <option value="0">...::: Todas las sedes :::...</option>
                                    <option v-for="sede in sedes" :key="sede.id" :value="sede.id">
                                        {{ sede.nom_sede }}
                                    </option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="tat-modalidad"
                                    >Modalidad Técnica</label
                                >
                                <select
                                    class="form-control custom-select"
                                    style="width: 100%; height: 36px"
                                    v-model="datosTAT.modalidad"
                                    id="tat-modalidad"
                                >
                                    <option value="todas">
                                        ...::: Todas las modalidades :::...
                                    </option>
                                    <option value="RX">RX — Radiología Convencional</option>
                                    <option value="CT">CT — Tomografía Computarizada</option>
                                    <option value="MR">MR — Resonancia Magnética</option>
                                    <option value="MG">MG — Mamografía</option>
                                    <option value="US">US — Ecografía / Ultrasonido</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label
                                            class="control-label font-weight-bold"
                                            for="tat-fechaIni"
                                            >Desde</label
                                        >
                                        <input
                                            class="form-control"
                                            type="date"
                                            id="tat-fechaIni"
                                            v-model="datosTAT.fechaIni"
                                            @keyup.enter="descargarReporteTAT()"
                                        />
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label
                                            class="control-label font-weight-bold"
                                            for="tat-fechaFin"
                                            >Hasta</label
                                        >
                                        <input
                                            class="form-control"
                                            type="date"
                                            id="tat-fechaFin"
                                            v-model="datosTAT.fechaFin"
                                            @keyup.enter="descargarReporteTAT()"
                                        />
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-default waves-effect"
                            @click="
                                resetearFormularioTAT();
                                modalTATVisible = false;
                            "
                        >
                            Cerrar
                        </button>
                        <button
                            type="button"
                            class="btn btn-info waves-effect waves-light"
                            @click="descargarReporteTAT()"
                            :disabled="loadingDescargarTAT"
                        >
                            <i v-if="loadingDescargarTAT" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-download"></i>
                            Descargar reporte
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="modalTATVisible" class="modal-backdrop fade show"></div>

        <!-- Modal Salud y Telemetría de Agentes Hospitalarios -->
        <div
            class="modal fade"
            :class="{ show: modalAgentesVisible }"
            :style="{ display: modalAgentesVisible ? 'block' : 'none' }"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            @click.self="
                resetearFormularioAgentes();
                modalAgentesVisible = false;
            "
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Filtro de Telemetría y Agentes Hospitalarios</h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            @click="
                                resetearFormularioAgentes();
                                modalAgentesVisible = false;
                            "
                        >
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="descargarReporteAgentes()">
                            <div class="form-group">
                                <label class="control-label" for="agentes-sede"
                                    >Sede Hospitalaria</label
                                >
                                <select
                                    class="form-control custom-select"
                                    style="width: 100%; height: 36px"
                                    v-model="datosAgentes.sedeId"
                                    id="agentes-sede"
                                >
                                    <option value="0">...::: Todas las sedes :::...</option>
                                    <option v-for="sede in sedes" :key="sede.id" :value="sede.id">
                                        {{ sede.nom_sede }}
                                    </option>
                                </select>
                            </div>
                            <div class="alert alert-info py-2 px-3 small mb-0">
                                <i class="fa fa-info-circle mr-1"></i>
                                Consolida el estado operativo de los agentes de base de datos,
                                replicación de imágenes PACS, telemetría de dosimetría y equipos
                                emisores.
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-default waves-effect"
                            @click="
                                resetearFormularioAgentes();
                                modalAgentesVisible = false;
                            "
                        >
                            Cerrar
                        </button>
                        <button
                            type="button"
                            class="btn btn-info waves-effect waves-light"
                            @click="descargarReporteAgentes()"
                            :disabled="loadingDescargarAgentes"
                        >
                            <i v-if="loadingDescargarAgentes" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-download"></i>
                            Descargar reporte
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="modalAgentesVisible" class="modal-backdrop fade show"></div>

        <!-- Modal 10: Consolidado Red Hospitalaria -->
        <div
            class="modal fade"
            :class="{ show: modalConsolidadoRedVisible }"
            :style="{ display: modalConsolidadoRedVisible ? 'block' : 'none' }"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            @click.self="modalConsolidadoRedVisible = false"
        >
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Consolidado de Producción — Red Hospitalaria</h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            @click="modalConsolidadoRedVisible = false"
                        >
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Selector de Período Rápido -->
                        <div class="btn-group btn-group-sm mb-3 w-100" role="group">
                            <button
                                type="button"
                                class="btn"
                                :class="
                                    datosConsolidadoRed.periodo === 'hoy'
                                        ? 'btn-info'
                                        : 'btn-secondary'
                                "
                                @click="cambiarPeriodoConsolidadoRed('hoy')"
                            >
                                Hoy
                            </button>
                            <button
                                type="button"
                                class="btn"
                                :class="
                                    datosConsolidadoRed.periodo === 'ayer'
                                        ? 'btn-info'
                                        : 'btn-secondary'
                                "
                                @click="cambiarPeriodoConsolidadoRed('ayer')"
                            >
                                Ayer
                            </button>
                            <button
                                type="button"
                                class="btn"
                                :class="
                                    datosConsolidadoRed.periodo === 'semana'
                                        ? 'btn-info'
                                        : 'btn-secondary'
                                "
                                @click="cambiarPeriodoConsolidadoRed('semana')"
                            >
                                Últimos 7 días
                            </button>
                            <button
                                type="button"
                                class="btn"
                                :class="
                                    datosConsolidadoRed.periodo === 'mes'
                                        ? 'btn-info'
                                        : 'btn-secondary'
                                "
                                @click="cambiarPeriodoConsolidadoRed('mes')"
                            >
                                Mes Actual
                            </button>
                            <button
                                type="button"
                                class="btn"
                                :class="
                                    datosConsolidadoRed.periodo === 'custom'
                                        ? 'btn-info'
                                        : 'btn-secondary'
                                "
                                @click="cambiarPeriodoConsolidadoRed('custom')"
                            >
                                Rango Manual
                            </button>
                        </div>

                        <!-- Filtro de fechas manual si aplica -->
                        <div v-if="datosConsolidadoRed.periodo === 'custom'" class="row mb-3">
                            <div class="col-5">
                                <label class="control-label" for="red-fechaIni">Desde</label>
                                <input
                                    id="red-fechaIni"
                                    class="form-control form-control-sm"
                                    type="date"
                                    v-model="datosConsolidadoRed.fechaIni"
                                    @keyup.enter="consultarConsolidadoRed()"
                                />
                            </div>
                            <div class="col-5">
                                <label class="control-label" for="red-fechaFin">Hasta</label>
                                <input
                                    id="red-fechaFin"
                                    class="form-control form-control-sm"
                                    type="date"
                                    v-model="datosConsolidadoRed.fechaFin"
                                    @keyup.enter="consultarConsolidadoRed()"
                                />
                            </div>
                            <div class="col-2 d-flex align-items-end">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-info w-100"
                                    @click="consultarConsolidadoRed()"
                                    :disabled="loadingConsolidadoRed"
                                >
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Cargando estado -->
                        <div v-if="loadingConsolidadoRed" class="text-center py-4">
                            <i class="fa fa-spinner fa-spin fa-2x text-info"></i>
                            <p class="text-muted mt-2 mb-0">
                                Consultando producción de toda la red hospitalaria...
                            </p>
                        </div>

                        <!-- Resumen interactivo en vivo -->
                        <div v-else-if="resumenConsolidadoRed">
                            <!-- Métricas destacadas -->
                            <div class="row mb-3 text-center">
                                <div class="col-3">
                                    <div class="card card-body bg-light p-2 mb-0">
                                        <h4 class="mb-0 text-info">
                                            {{ resumenConsolidadoRed.totalProcedimientos }}
                                        </h4>
                                        <small class="text-muted">Procedimientos Red</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="card card-body bg-light p-2 mb-0">
                                        <h4 class="mb-0 text-primary">
                                            {{ resumenConsolidadoRed.totalPacientes }}
                                        </h4>
                                        <small class="text-muted">Pacientes Únicos</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="card card-body bg-light p-2 mb-0">
                                        <h4 class="mb-0">
                                            <span
                                                class="badge"
                                                :class="'badge-' + resumenConsolidadoRed.tatClase"
                                            >
                                                {{ resumenConsolidadoRed.tatTexto }}
                                            </span>
                                        </h4>
                                        <small class="text-muted">TAT Clínico Real</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="card card-body bg-light p-2 mb-0">
                                        <h4 class="mb-0 text-warning">
                                            {{ resumenConsolidadoRed.pendientesRed }}
                                        </h4>
                                        <small class="text-muted">Pendientes en Red</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Ranking de Producción por Sede -->
                            <h5 class="box-title mb-2">
                                <i class="fa fa-hospital-o text-info"></i> Ranking de Producción por
                                Sede Hospitalaria
                            </h5>
                            <div
                                class="table-responsive mb-3"
                                style="max-height: 180px; overflow-y: auto"
                            >
                                <table class="table table-sm table-striped table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Sede Hospitalaria</th>
                                            <th class="text-center">Estudios</th>
                                            <th class="text-center">% Red</th>
                                            <th style="width: 35%">Distribución</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="sede in resumenConsolidadoRed.rankingSedes"
                                            :key="sede.sede_id"
                                        >
                                            <td>
                                                <strong>{{ sede.nom_sede }}</strong>
                                            </td>
                                            <td class="text-center font-weight-bold">
                                                {{ sede.cantidad }}
                                            </td>
                                            <td class="text-center">{{ sede.porcentaje }}%</td>
                                            <td>
                                                <div class="progress" style="height: 10px">
                                                    <div
                                                        class="progress-bar bg-info"
                                                        role="progressbar"
                                                        :style="{ width: sede.porcentaje + '%' }"
                                                    ></div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="!resumenConsolidadoRed.rankingSedes.length">
                                            <td colspan="4" class="text-center text-muted">
                                                Sin lecturas registradas en este período.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Desglose de Modalidades -->
                            <h5 class="box-title mb-2">
                                <i class="fa fa-cubes text-info"></i> Modalidades Diagnósticas
                            </h5>
                            <div class="row">
                                <div
                                    class="col-4 mb-2"
                                    v-for="mod in resumenConsolidadoRed.modalidades"
                                    :key="mod.mod_codigo"
                                >
                                    <div class="d-flex align-items-center p-2 border rounded">
                                        <i :class="mod.icono + ' text-info mr-2 fa-lg'"></i>
                                        <div class="w-100">
                                            <div class="d-flex justify-content-between">
                                                <small class="font-weight-bold">{{
                                                    mod.mod_nombre
                                                }}</small>
                                                <small class="text-muted"
                                                    >{{ mod.cantidad }} ({{
                                                        mod.porcentaje
                                                    }}%)</small
                                                >
                                            </div>
                                            <div class="progress mt-1" style="height: 6px">
                                                <div
                                                    class="progress-bar bg-success"
                                                    :style="{ width: mod.porcentaje + '%' }"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-default waves-effect"
                            @click="modalConsolidadoRedVisible = false"
                        >
                            Cerrar
                        </button>
                        <button
                            type="button"
                            class="btn btn-info waves-effect waves-light"
                            @click="descargarConsolidadoRed()"
                            :disabled="loadingDescargarConsolidadoRed"
                        >
                            <i
                                v-if="loadingDescargarConsolidadoRed"
                                class="fa fa-spinner fa-spin"
                            ></i>
                            <i v-else class="fa fa-download"></i>
                            Descargar reporte
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="modalConsolidadoRedVisible" class="modal-backdrop fade show"></div>

        <!-- Modal 11: Semáforo de Represamiento Asistencial y Urgencias -->
        <div
            class="modal fade"
            :class="{ show: modalSemaforoRepresadosVisible }"
            :style="{ display: modalSemaforoRepresadosVisible ? 'block' : 'none' }"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            @click.self="modalSemaforoRepresadosVisible = false"
        >
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">
                            Semáforo de Represamiento Asistencial y Urgencias
                        </h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            @click="modalSemaforoRepresadosVisible = false"
                        >
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Filtro de Sede -->
                        <div class="row align-items-center mb-3">
                            <div class="col-8">
                                <label class="control-label mb-1" for="semaforo-sede"
                                    >Filtrar por Sede</label
                                >
                                <select
                                    id="semaforo-sede"
                                    class="form-control custom-select form-control-sm"
                                    v-model="datosSemaforoRepresados.sedeId"
                                    @change="consultarSemaforoRepresados()"
                                >
                                    <option value="0">
                                        ...::: Toda la Red Hospitalaria :::...
                                    </option>
                                    <option v-for="sede in sedes" :key="sede.id" :value="sede.id">
                                        {{ sede.nom_sede }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-4 text-right pt-3">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-info"
                                    @click="consultarSemaforoRepresados()"
                                    :disabled="loadingSemaforoRepresados"
                                >
                                    <i
                                        class="fa fa-refresh mr-1"
                                        :class="{ 'fa-spin': loadingSemaforoRepresados }"
                                    ></i>
                                    Actualizar
                                </button>
                            </div>
                        </div>

                        <!-- Cargando estado -->
                        <div v-if="loadingSemaforoRepresados" class="text-center py-4">
                            <i class="fa fa-spinner fa-spin fa-2x text-warning"></i>
                            <p class="text-muted mt-2 mb-0">
                                Evaluando estudios represados y tiempos de urgencias...
                            </p>
                        </div>

                        <!-- Resumen en vivo -->
                        <div v-else-if="resumenSemaforoRepresados">
                            <div
                                class="alert alert-secondary py-1 px-3 small d-flex justify-content-between mb-3"
                            >
                                <span
                                    ><i class="fa fa-clock-o mr-1"></i> Corte en tiempo real:
                                    <strong>{{ resumenSemaforoRepresados.corte }}</strong></span
                                >
                                <span class="text-muted"
                                    >Prioridad: Atender urgencias &gt;30m y estudios en rango rojo
                                    (&gt;72h)</span
                                >
                            </div>

                            <!-- Tarjetas Semáforo de Criticidad -->
                            <div class="row text-center mb-3">
                                <div class="col-3">
                                    <div class="card card-body border-warning p-2 mb-0">
                                        <h3 class="mb-0 text-warning">
                                            {{ resumenSemaforoRepresados.tot24_48 }}
                                        </h3>
                                        <small class="text-muted">🟡 24h a 48h (Preventivo)</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="card card-body border-danger p-2 mb-0">
                                        <h3 class="mb-0 text-danger">
                                            {{ resumenSemaforoRepresados.tot48_72 }}
                                        </h3>
                                        <small class="text-muted">🟠 48h a 72h (Crítico)</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="card card-body bg-danger text-white p-2 mb-0">
                                        <h3 class="mb-0 text-white">
                                            {{ resumenSemaforoRepresados.totMas72 }}
                                        </h3>
                                        <small class="text-white"
                                            >🔴 &gt; 72h (Severo / Glosa)</small
                                        >
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="card card-body border-dark p-2 mb-0">
                                        <h3 class="mb-0 text-dark">
                                            {{ resumenSemaforoRepresados.totalUrgentes }}
                                            <small
                                                v-if="
                                                    resumenSemaforoRepresados.totalUrgentes30m > 0
                                                "
                                                class="text-danger"
                                            >
                                                ({{ resumenSemaforoRepresados.totalUrgentes30m }}
                                                &gt;30m)
                                            </small>
                                        </h3>
                                        <small class="text-muted">🚑 Urgencias Pendientes</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabla de Detalle por Sede -->
                            <h5 class="box-title mb-2">
                                <i class="fa fa-list-alt text-info"></i> Detalle de Represamiento
                                por Sede
                            </h5>
                            <div
                                class="table-responsive"
                                style="max-height: 200px; overflow-y: auto"
                            >
                                <table class="table table-sm table-striped table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Sede Hospitalaria</th>
                                            <th class="text-center text-warning">🟡 24-48h</th>
                                            <th class="text-center text-danger">🟠 48-72h</th>
                                            <th class="text-center font-weight-bold text-danger">
                                                🔴 &gt;72h
                                            </th>
                                            <th class="text-center">Total Represados</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="rep in resumenSemaforoRepresados.represadosPorSede"
                                            :key="rep.sede_id"
                                        >
                                            <td>
                                                <strong>{{ rep.nom_sede }}</strong>
                                            </td>
                                            <td class="text-center">{{ rep.rango_24_48 }}</td>
                                            <td class="text-center">{{ rep.rango_48_72 }}</td>
                                            <td class="text-center font-weight-bold text-danger">
                                                {{ rep.rango_mas_72 }}
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-secondary"
                                                    >{{ rep.cantidad }} ({{
                                                        rep.porcentaje
                                                    }}%)</span
                                                >
                                            </td>
                                        </tr>
                                        <tr
                                            v-if="
                                                !resumenSemaforoRepresados.represadosPorSede.length
                                            "
                                        >
                                            <td colspan="5" class="text-center text-success py-2">
                                                <i class="fa fa-check-circle mr-1"></i> No se
                                                registran estudios con retraso mayor a 24 horas.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-default waves-effect"
                            @click="modalSemaforoRepresadosVisible = false"
                        >
                            Cerrar
                        </button>
                        <button
                            type="button"
                            class="btn btn-info waves-effect waves-light"
                            @click="descargarSemaforoRepresados()"
                            :disabled="loadingDescargarSemaforoRepresados"
                        >
                            <i
                                v-if="loadingDescargarSemaforoRepresados"
                                class="fa fa-spinner fa-spin"
                            ></i>
                            <i v-else class="fa fa-download"></i>
                            Descargar reporte
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="modalSemaforoRepresadosVisible" class="modal-backdrop fade show"></div>

        <!-- Modal 12: Estadísticas de Citas y Canal WhatsApp -->
        <div
            class="modal fade"
            :class="{ show: modalCitasWhatsAppVisible }"
            :style="{ display: modalCitasWhatsAppVisible ? 'block' : 'none' }"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            @click.self="modalCitasWhatsAppVisible = false"
        >
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Estadísticas de Citas y Canal WhatsApp</h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            @click="modalCitasWhatsAppVisible = false"
                        >
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Filtros: Período y Sede -->
                        <div class="row mb-3">
                            <div class="col-7">
                                <label class="control-label mb-1">Período de Solicitudes</label>
                                <div class="btn-group btn-group-sm w-100" role="group">
                                    <button
                                        type="button"
                                        class="btn"
                                        :class="
                                            datosCitasWhatsApp.periodo === 'hoy'
                                                ? 'btn-info'
                                                : 'btn-secondary'
                                        "
                                        @click="cambiarPeriodoCitasWhatsApp('hoy')"
                                    >
                                        Hoy
                                    </button>
                                    <button
                                        type="button"
                                        class="btn"
                                        :class="
                                            datosCitasWhatsApp.periodo === 'semana'
                                                ? 'btn-info'
                                                : 'btn-secondary'
                                        "
                                        @click="cambiarPeriodoCitasWhatsApp('semana')"
                                    >
                                        7 Días
                                    </button>
                                    <button
                                        type="button"
                                        class="btn"
                                        :class="
                                            datosCitasWhatsApp.periodo === 'mes'
                                                ? 'btn-info'
                                                : 'btn-secondary'
                                        "
                                        @click="cambiarPeriodoCitasWhatsApp('mes')"
                                    >
                                        Este Mes
                                    </button>
                                    <button
                                        type="button"
                                        class="btn"
                                        :class="
                                            datosCitasWhatsApp.periodo === 'custom'
                                                ? 'btn-info'
                                                : 'btn-secondary'
                                        "
                                        @click="cambiarPeriodoCitasWhatsApp('custom')"
                                    >
                                        Manual
                                    </button>
                                </div>
                            </div>
                            <div class="col-5">
                                <label class="control-label mb-1" for="citas-sede">Sede</label>
                                <select
                                    id="citas-sede"
                                    class="form-control custom-select form-control-sm"
                                    v-model="datosCitasWhatsApp.sedeId"
                                    @change="consultarCitasWhatsApp()"
                                >
                                    <option value="0">...::: Todas las sedes :::...</option>
                                    <option v-for="sede in sedes" :key="sede.id" :value="sede.id">
                                        {{ sede.nom_sede }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Rango de fechas manual si aplica -->
                        <div v-if="datosCitasWhatsApp.periodo === 'custom'" class="row mb-3">
                            <div class="col-5">
                                <label class="control-label" for="citas-fechaIni">Desde</label>
                                <input
                                    id="citas-fechaIni"
                                    class="form-control form-control-sm"
                                    type="date"
                                    v-model="datosCitasWhatsApp.fechaIni"
                                    @keyup.enter="consultarCitasWhatsApp()"
                                />
                            </div>
                            <div class="col-5">
                                <label class="control-label" for="citas-fechaFin">Hasta</label>
                                <input
                                    id="citas-fechaFin"
                                    class="form-control form-control-sm"
                                    type="date"
                                    v-model="datosCitasWhatsApp.fechaFin"
                                    @keyup.enter="consultarCitasWhatsApp()"
                                />
                            </div>
                            <div class="col-2 d-flex align-items-end">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-info w-100"
                                    @click="consultarCitasWhatsApp()"
                                    :disabled="loadingCitasWhatsApp"
                                >
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Cargando estado -->
                        <div v-if="loadingCitasWhatsApp" class="text-center py-4">
                            <i class="fa fa-spinner fa-spin fa-2x text-success"></i>
                            <p class="text-muted mt-2 mb-0">
                                Consultando métricas de citas y canal WhatsApp...
                            </p>
                        </div>

                        <!-- Resumen en vivo -->
                        <div v-else-if="resumenCitasWhatsApp">
                            <!-- Bloque 1: Citas del Día -->
                            <div class="card border-info mb-3">
                                <div class="card-header bg-info text-white py-1 px-3">
                                    <i class="fa fa-calendar-check-o mr-1"></i> Citas Programadas
                                    para Atención HOY
                                </div>
                                <div class="card-body p-2">
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <h4 class="mb-0 text-info">
                                                {{ resumenCitasWhatsApp.citasHoy.total }}
                                            </h4>
                                            <small class="text-muted">Total Citas Hoy</small>
                                        </div>
                                        <div class="col-4">
                                            <h4 class="mb-0 text-success">
                                                {{ resumenCitasWhatsApp.citasHoy.confirmadas }}
                                                <small
                                                    >({{
                                                        resumenCitasWhatsApp.citasHoy.pctAsistencia
                                                    }}%)</small
                                                >
                                            </h4>
                                            <small class="text-muted"
                                                >Confirmadas / Asignadas</small
                                            >
                                        </div>
                                        <div class="col-4">
                                            <h4 class="mb-0 text-danger">
                                                {{ resumenCitasWhatsApp.citasHoy.canceladas }}
                                            </h4>
                                            <small class="text-muted"
                                                >Canceladas / Rechazadas</small
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bloque 2: Tráfico WhatsApp en el período -->
                            <div class="card border-success mb-3">
                                <div class="card-header bg-success text-white py-1 px-3">
                                    <i class="fa fa-whatsapp mr-1"></i> Tráfico Canal WhatsApp en el
                                    Período ({{ resumenCitasWhatsApp.etiquetaPeriodo }})
                                </div>
                                <div class="card-body p-2">
                                    <div class="row text-center">
                                        <div class="col-3">
                                            <h4 class="mb-0 text-dark">
                                                {{
                                                    resumenCitasWhatsApp.whatsappPeriodo
                                                        .totalSolicitudes
                                                }}
                                            </h4>
                                            <small class="text-muted">Solicitudes Recibidas</small>
                                        </div>
                                        <div class="col-3">
                                            <h4 class="mb-0 text-success">
                                                {{ resumenCitasWhatsApp.whatsappPeriodo.aprobadas }}
                                            </h4>
                                            <small class="text-muted">Aprobadas / Agendadas</small>
                                        </div>
                                        <div class="col-3">
                                            <h4 class="mb-0 text-info">
                                                {{
                                                    resumenCitasWhatsApp.whatsappPeriodo
                                                        .tasaConversion
                                                }}%
                                            </h4>
                                            <small class="text-muted">Tasa de Conversión</small>
                                        </div>
                                        <div class="col-3">
                                            <h4 class="mb-0 text-warning">
                                                {{
                                                    resumenCitasWhatsApp.whatsappPeriodo
                                                        .pendientesRevision
                                                }}
                                            </h4>
                                            <small class="text-muted">Pendientes por Revisar</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Desglose de Estados -->
                            <h6 class="box-title mb-2">Desglose de Estados de Gestión</h6>
                            <div class="row">
                                <div
                                    class="col-4 mb-2"
                                    v-for="st in resumenCitasWhatsApp.desgloseEstados"
                                    :key="st.estado"
                                >
                                    <div class="d-flex justify-content-between p-2 border rounded">
                                        <span>{{ st.estado }}</span>
                                        <strong>{{ st.cantidad }} ({{ st.porcentaje }}%)</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-default waves-effect"
                            @click="modalCitasWhatsAppVisible = false"
                        >
                            Cerrar
                        </button>
                        <button
                            type="button"
                            class="btn btn-info waves-effect waves-light"
                            @click="descargarCitasWhatsApp()"
                            :disabled="loadingDescargarCitasWhatsApp"
                        >
                            <i
                                v-if="loadingDescargarCitasWhatsApp"
                                class="fa fa-spinner fa-spin"
                            ></i>
                            <i v-else class="fa fa-download"></i>
                            Descargar reporte
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="modalCitasWhatsAppVisible" class="modal-backdrop fade show"></div>

        <!-- Modal 13: Monitor de Almacenamiento y Servidores PACS -->
        <div
            class="modal fade"
            :class="{ show: modalAlmacenamientoVisible }"
            :style="{ display: modalAlmacenamientoVisible ? 'block' : 'none' }"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            @click.self="modalAlmacenamientoVisible = false"
        >
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Monitor de Almacenamiento y Servidores PACS</h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            @click="modalAlmacenamientoVisible = false"
                        >
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Filtro de Sede y botón actualizar -->
                        <div class="row align-items-center mb-3">
                            <div class="col-8">
                                <label class="control-label mb-1" for="almacen-sede"
                                    >Filtrar Sede</label
                                >
                                <select
                                    id="almacen-sede"
                                    class="form-control custom-select form-control-sm"
                                    v-model="datosAlmacenamiento.sedeId"
                                    @change="consultarAlmacenamiento()"
                                >
                                    <option value="0">...::: Todas las sedes :::...</option>
                                    <option v-for="sede in sedes" :key="sede.id" :value="sede.id">
                                        {{ sede.nom_sede }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-4 text-right pt-3">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-info"
                                    @click="consultarAlmacenamiento()"
                                    :disabled="loadingAlmacenamiento"
                                >
                                    <i
                                        class="fa fa-refresh mr-1"
                                        :class="{ 'fa-spin': loadingAlmacenamiento }"
                                    ></i>
                                    Actualizar
                                </button>
                            </div>
                        </div>

                        <!-- Cargando estado -->
                        <div v-if="loadingAlmacenamiento" class="text-center py-4">
                            <i class="fa fa-spinner fa-spin fa-2x text-info"></i>
                            <p class="text-muted mt-2 mb-0">
                                Consultando unidades de disco y estado de servidores...
                            </p>
                        </div>

                        <!-- Resumen en vivo -->
                        <div v-else-if="resumenAlmacenamiento">
                            <!-- Servidor Central -->
                            <div class="card border-secondary mb-3">
                                <div
                                    class="card-header bg-dark text-white py-1 px-3 d-flex justify-content-between align-items-center"
                                >
                                    <span
                                        ><i class="fa fa-server mr-1"></i> Servidor Central (LIDiag
                                        Cloud Web)</span
                                    >
                                    <span
                                        class="badge"
                                        :class="
                                            'badge-' +
                                            resumenAlmacenamiento.servidorCentral.iconoClase
                                        "
                                    >
                                        {{ resumenAlmacenamiento.servidorCentral.freeGb }} GB Libres
                                        ({{ resumenAlmacenamiento.servidorCentral.freePct }}%)
                                    </span>
                                </div>
                                <div class="card-body p-2">
                                    <div
                                        class="d-flex justify-content-between small text-muted mb-1"
                                    >
                                        <span
                                            >Usado:
                                            <strong
                                                >{{
                                                    resumenAlmacenamiento.servidorCentral.usedGb
                                                }}
                                                GB</strong
                                            >
                                            ({{
                                                resumenAlmacenamiento.servidorCentral.usedPct
                                            }}%)</span
                                        >
                                        <span
                                            >Capacidad Total:
                                            <strong
                                                >{{
                                                    resumenAlmacenamiento.servidorCentral.totalGb
                                                }}
                                                GB</strong
                                            ></span
                                        >
                                    </div>
                                    <div class="progress" style="height: 12px">
                                        <div
                                            class="progress-bar"
                                            :class="
                                                'bg-' +
                                                resumenAlmacenamiento.servidorCentral.iconoClase
                                            "
                                            role="progressbar"
                                            :style="{
                                                width:
                                                    resumenAlmacenamiento.servidorCentral.usedPct +
                                                    '%',
                                            }"
                                        ></div>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        <i class="fa fa-info-circle mr-1"></i> Umbrales PACS: 🟡
                                        Advertencia &lt;15% o 50GB | 🔴 Crítico &lt;5% o 20GB.
                                    </small>
                                </div>
                            </div>

                            <!-- Sedes y Agentes Edge -->
                            <h5 class="box-title mb-2">
                                <i class="fa fa-cubes text-info"></i> Salud de Agentes Edge y
                                Respaldos en Sedes
                            </h5>
                            <div
                                class="table-responsive"
                                style="max-height: 200px; overflow-y: auto"
                            >
                                <table class="table table-sm table-striped table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Sede Hospitalaria</th>
                                            <th class="text-center">IP DCM</th>
                                            <th class="text-center">Respaldos DB</th>
                                            <th class="text-center">Sync PACS</th>
                                            <th class="text-center">Dosimetría</th>
                                            <th>Último Backup</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="sede in resumenAlmacenamiento.sedes"
                                            :key="sede.id"
                                        >
                                            <td>
                                                <strong>{{ sede.nom_sede }}</strong>
                                            </td>
                                            <td class="text-center small">
                                                {{ sede.ip_dcm || "N/D" }}
                                            </td>
                                            <td class="text-center">
                                                <span
                                                    class="badge"
                                                    :class="'badge-' + sede.backup_clase"
                                                    >{{ sede.backup_estado }}</span
                                                >
                                            </td>
                                            <td class="text-center">
                                                <span
                                                    class="badge"
                                                    :class="'badge-' + sede.sync_clase"
                                                    >{{ sede.sync_estado }}</span
                                                >
                                            </td>
                                            <td class="text-center">
                                                <span
                                                    class="badge"
                                                    :class="'badge-' + sede.dosimetria_clase"
                                                    >{{ sede.dosimetria_estado }}</span
                                                >
                                            </td>
                                            <td class="small">{{ sede.backup_tiempo }}</td>
                                        </tr>
                                        <tr v-if="!resumenAlmacenamiento.sedes.length">
                                            <td colspan="6" class="text-center text-muted">
                                                No se registran sedes activas configuradas.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-default waves-effect"
                            @click="modalAlmacenamientoVisible = false"
                        >
                            Cerrar
                        </button>
                        <button
                            type="button"
                            class="btn btn-info waves-effect waves-light"
                            @click="descargarAlmacenamiento()"
                            :disabled="loadingDescargarAlmacenamiento"
                        >
                            <i
                                v-if="loadingDescargarAlmacenamiento"
                                class="fa fa-spinner fa-spin"
                            ></i>
                            <i v-else class="fa fa-download"></i>
                            Descargar reporte
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="modalAlmacenamientoVisible" class="modal-backdrop fade show"></div>
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref } from "vue";

import { listarUsuariosYSedes } from "../../../helpers/usuarios";
import { toastMessage } from "@/helpers/alerts.js";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts.js";
import usePermissions from "@/helpers/permissions.js";
import axios from "axios";

const MAX_DATE_RANGE_DAYS = 365;

const props = defineProps({
    usuarioactual: { type: Object, default: () => ({}) },
    permisos: { type: Array, default: () => [] },
});

const { can } = usePermissions(props.permisos);

// ── Control de Acceso y Agrupación por Módulos Asistenciales (Rx, Ecografias, Oftalmologia) ──
const moduloActivo = ref("Rx");

const modulosDisponibles = computed(() => {
    const roles = props.usuarioactual?.roles || [];
    const esAdmin = roles.some(
        (r) =>
            r.name === "Administrador" ||
            r.name === "Super Administrador" ||
            r.name === "Reportes" ||
            r.name === "Estadistico"
    );
    if (esAdmin) {
        return ["Rx", "Ecografias", "Oftalmologia"];
    }

    const mods = (props.usuarioactual?.modulos || []).map((m) => m.modulo);
    if (mods.length === 0) {
        return ["Rx"];
    }
    return mods;
});

function tieneAccesoModulo(moduloNombre) {
    const norm = (s) =>
        String(s || "")
            .toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "");
    const target = norm(moduloNombre);
    return modulosDisponibles.value.some((m) => norm(m) === target);
}

function cambiarModulo(modulo) {
    if (modulo === "Transversal" || tieneAccesoModulo(modulo)) {
        moduloActivo.value = modulo;
    }
}

const sedes = ref([]);
const datosReporte = ref({ sedeId: 0, quien_asigno: "", medico: "", fechaIni: "", fechaFin: "" });
const datosProduccionConsolidada = ref({
    sedeId: 0,
    modalidad: "",
    destino: "",
    fechaIni: "",
    fechaFin: "",
});
const datosPAC = ref({ sedeId: 0, fechaIni: "", fechaFin: "" });
const datosDosimetria = ref({ sedeId: 0, fechaIni: "", fechaFin: "" });
const datosTeleRadiologia = ref({ sedeId: 0, fechaIni: "", fechaFin: "" });
const datosAusentismo = ref({ sedeId: 0, fechaIni: "", fechaFin: "", estado: "todos" });
const datosLiquidacion = ref({ sedeId: 0, fechaIni: "", fechaFin: "" });
const datosProductividad = ref({ sedeId: 0, periodo: "" });
const datosTAT = ref({ sedeId: 0, fechaIni: "", fechaFin: "", modalidad: "todas" });
const datosAgentes = ref({ sedeId: 0 });
const datosConsolidadoRed = ref({ periodo: "hoy", fechaIni: "", fechaFin: "" });
const datosSemaforoRepresados = ref({ sedeId: 0 });
const datosCitasWhatsApp = ref({ periodo: "hoy", sedeId: 0, fechaIni: "", fechaFin: "" });
const datosAlmacenamiento = ref({ sedeId: 0 });
const quienes_asignaron = ref([]);
const medicos = ref([]);
const tipoReporte = ref("Rx");
const loadingSedes = ref(false);
const loadingIndicadores = ref(false);
const loadingDescargarProduccion = ref(false);
const loadingDescargarConsolidada = ref(false);
const loadingDescargarPAC = ref(false);
const loadingDescargarDosimetria = ref(false);
const loadingDescargarTeleRadiologia = ref(false);
const loadingDescargarAusentismo = ref(false);
const loadingDescargarLiquidacion = ref(false);
const loadingDescargarProductividad = ref(false);
const loadingDescargarTAT = ref(false);
const loadingDescargarAgentes = ref(false);
const loadingDescargarConsolidadoRed = ref(false);
const loadingDescargarSemaforoRepresados = ref(false);
const loadingDescargarCitasWhatsApp = ref(false);
const loadingDescargarAlmacenamiento = ref(false);
const loadingConsolidadoRed = ref(false);
const loadingSemaforoRepresados = ref(false);
const loadingCitasWhatsApp = ref(false);
const loadingAlmacenamiento = ref(false);
const modalProduccionVisible = ref(false);
const modalProduccionConsolidadaVisible = ref(false);
const modalPACVisible = ref(false);
const modalDosimetriaVisible = ref(false);
const modalTeleRadiologiaVisible = ref(false);
const modalAusentismoVisible = ref(false);
const modalLiquidacionVisible = ref(false);
const modalProductividadVisible = ref(false);
const modalTATVisible = ref(false);
const modalAgentesVisible = ref(false);
const modalConsolidadoRedVisible = ref(false);
const modalSemaforoRepresadosVisible = ref(false);
const modalCitasWhatsAppVisible = ref(false);
const modalAlmacenamientoVisible = ref(false);

const resumenConsolidadoRed = ref(null);
const resumenSemaforoRepresados = ref(null);
const resumenCitasWhatsApp = ref(null);
const resumenAlmacenamiento = ref(null);

const indicadores = ref({
    estudiosAsignados: 0,
    estudiosLeidos: 0,
    estudiosPorTranscribir: 0,
    operacion: {
        transcritosHoy: 0,
        leidosHoy: 0,
    },
});

function resetearFormulario() {
    datosReporte.value = { sedeId: 0, quien_asigno: "", medico: "", fechaIni: "", fechaFin: "" };
}

function resetearFormularioConsolidada() {
    datosProduccionConsolidada.value = {
        sedeId: 0,
        modalidad: "",
        destino: "",
        fechaIni: new Date().toISOString().slice(0, 7) + "-01",
        fechaFin: new Date().toISOString().slice(0, 10),
    };
}

function resetearFormularioPAC() {
    datosPAC.value = { sedeId: 0, fechaIni: "", fechaFin: "" };
}

function resetearFormularioDosimetria() {
    datosDosimetria.value = { sedeId: 0, fechaIni: "", fechaFin: "" };
}

function resetearFormularioTeleRadiologia() {
    datosTeleRadiologia.value = { sedeId: 0, fechaIni: "", fechaFin: "" };
}

function resetearFormularioAusentismo() {
    datosAusentismo.value = { sedeId: 0, fechaIni: "", fechaFin: "", estado: "todos" };
}

function resetearFormularioLiquidacion() {
    datosLiquidacion.value = { sedeId: 0, fechaIni: "", fechaFin: "" };
}

function resetearFormularioProductividad() {
    datosProductividad.value = { sedeId: 0, periodo: "" };
}

function resetearFormularioTAT() {
    datosTAT.value = { sedeId: 0, fechaIni: "", fechaFin: "", modalidad: "todas" };
}

function resetearFormularioAgentes() {
    datosAgentes.value = { sedeId: 0 };
}

function validarFechas(fechaIni, fechaFin) {
    if (!fechaIni || !fechaFin) {
        toastMessage({
            heading: "Atención",
            text: "Debes seleccionar fecha inicio y fecha fin.",
            icon: "warning",
        });
        return false;
    }
    if (fechaIni > fechaFin) {
        toastMessage({
            heading: "Atención",
            text: "La fecha inicio no puede ser mayor a la fecha fin.",
            icon: "warning",
        });
        return false;
    }
    const ini = new Date(fechaIni);
    const fin = new Date(fechaFin);
    const diffDays = Math.ceil((fin - ini) / (1000 * 60 * 60 * 24));
    if (diffDays > MAX_DATE_RANGE_DAYS) {
        toastMessage({
            heading: "Atención",
            text: `El rango de fechas no puede superar ${MAX_DATE_RANGE_DAYS} días.`,
            icon: "warning",
        });
        return false;
    }
    return true;
}

/**
 * Carga las sedes activas y los catálogos de usuarios (quienes asignaron, médicos).
 */
async function listarSedes() {
    if (loadingSedes.value) return;
    loadingSedes.value = true;
    try {
        await listarUsuariosYSedes({
            set quienes_asignaron(v) {
                quienes_asignaron.value = v;
            },
            set medicos(v) {
                medicos.value = v;
            },
        });

        const res = await axios.get("/api/config-sedes/listar/sedes/activas");
        sedes.value = res?.data?.data || res?.data || [];
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar las sedes o usuarios.",
            icon: "error",
        });
    } finally {
        loadingSedes.value = false;
    }
}

/**
 * Descarga el reporte de producción (RX, Ecografía) como archivo Excel.
 */
async function descargarReporte() {
    if (loadingDescargarProduccion.value) return;
    loadingDescargarProduccion.value = true;
    try {
        if (!validarFechas(datosReporte.value.fechaIni, datosReporte.value.fechaFin)) {
            return;
        }

        const params = new URLSearchParams({
            tipo: String(tipoReporte.value || "Rx"),
            sedeId: String(datosReporte.value.sedeId ?? 0),
            quien_asigno: String(datosReporte.value.quien_asigno ?? 0),
            medico: String(datosReporte.value.medico ?? 0),
            fechaIni: String(datosReporte.value.fechaIni),
            fechaFin: String(datosReporte.value.fechaFin),
        });

        const url = `/descarga-reportes-produccion?${params.toString()}`;
        const newWindow = window.open(url, "_blank", "noopener,noreferrer");

        if (!newWindow) {
            toastMessage({
                heading: "Atención",
                text: "El navegador bloqueó la apertura de la ventana. Permita pop-ups para este sitio.",
                icon: "warning",
            });
        }
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible generar el reporte de producción.",
            icon: "error",
        });
    } finally {
        setTimeout(() => {
            loadingDescargarProduccion.value = false;
        }, 700);
    }
}

/**
 * Descarga el reporte de producción consolidada (Estudios Tomados PACS vs Asignados LIDiag) en Excel.
 */
async function descargarProduccionConsolidada() {
    if (loadingDescargarConsolidada.value) return;
    loadingDescargarConsolidada.value = true;
    try {
        if (
            !validarFechas(
                datosProduccionConsolidada.value.fechaIni,
                datosProduccionConsolidada.value.fechaFin
            )
        ) {
            return;
        }

        const params = new URLSearchParams({
            sedeId: String(datosProduccionConsolidada.value.sedeId ?? 0),
            modalidad: String(datosProduccionConsolidada.value.modalidad ?? ""),
            destino: String(datosProduccionConsolidada.value.destino ?? ""),
            fechaIni: String(datosProduccionConsolidada.value.fechaIni),
            fechaFin: String(datosProduccionConsolidada.value.fechaFin),
        });

        const url = `/descarga-reportes-produccion-consolidada?${params.toString()}`;
        const newWindow = window.open(url, "_blank", "noopener,noreferrer");

        if (!newWindow) {
            toastMessage({
                heading: "Atención",
                text: "El navegador bloqueó la apertura de la ventana. Permita pop-ups para este sitio.",
                icon: "warning",
            });
        }
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible generar el reporte de producción consolidada.",
            icon: "error",
        });
    } finally {
        setTimeout(() => {
            loadingDescargarConsolidada.value = false;
        }, 700);
    }
}

/**
 * Descarga el reporte de estudios tomados en PAC como archivo Excel.
 */
async function descargarReporteEstudiosPAC() {
    if (loadingDescargarPAC.value) return;
    loadingDescargarPAC.value = true;
    try {
        if (!datosPAC.value.sedeId || datosPAC.value.sedeId === 0) {
            toastMessage({
                heading: "Atención",
                text: "Debes seleccionar una sede.",
                icon: "warning",
            });
            return;
        }
        if (!validarFechas(datosPAC.value.fechaIni, datosPAC.value.fechaFin)) {
            return;
        }

        const params = new URLSearchParams({
            sedeId: String(datosPAC.value.sedeId),
            fechaIni: String(datosPAC.value.fechaIni),
            fechaFin: String(datosPAC.value.fechaFin),
        });

        const url = `/descarga-reportes-estudios-pac?${params.toString()}`;
        const newWindow = window.open(url, "_blank", "noopener,noreferrer");

        if (!newWindow) {
            toastMessage({
                heading: "Atención",
                text: "El navegador bloqueó la apertura de la ventana. Permita pop-ups para este sitio.",
                icon: "warning",
            });
        }
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible generar el reporte de estudios PAC.",
            icon: "error",
        });
    } finally {
        setTimeout(() => {
            loadingDescargarPAC.value = false;
        }, 700);
    }
}

/**
 * Descarga el reporte de dosimetría y control de radiación (Res. 482 de 2018) en Excel.
 */
async function descargarReporteDosimetria() {
    if (loadingDescargarDosimetria.value) return;
    loadingDescargarDosimetria.value = true;
    try {
        if (!validarFechas(datosDosimetria.value.fechaIni, datosDosimetria.value.fechaFin)) {
            return;
        }

        const params = new URLSearchParams({
            sedeId: String(datosDosimetria.value.sedeId ?? 0),
            fechaIni: String(datosDosimetria.value.fechaIni),
            fechaFin: String(datosDosimetria.value.fechaFin),
        });

        const url = `/descarga-reportes-dosimetria?${params.toString()}`;
        const newWindow = window.open(url, "_blank", "noopener,noreferrer");

        if (!newWindow) {
            toastMessage({
                heading: "Atención",
                text: "El navegador bloqueó la apertura de la ventana. Permita pop-ups para este sitio.",
                icon: "warning",
            });
        }
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible generar el reporte de dosimetría.",
            icon: "error",
        });
    } finally {
        setTimeout(() => {
            loadingDescargarDosimetria.value = false;
        }, 700);
    }
}

/**
 * Descarga el reporte de tele-radiología y lecturas externas en Excel.
 */
async function descargarReporteTeleRadiologia() {
    if (loadingDescargarTeleRadiologia.value) return;
    loadingDescargarTeleRadiologia.value = true;
    try {
        if (
            !validarFechas(datosTeleRadiologia.value.fechaIni, datosTeleRadiologia.value.fechaFin)
        ) {
            return;
        }

        const params = new URLSearchParams({
            sedeId: String(datosTeleRadiologia.value.sedeId ?? 0),
            fechaIni: String(datosTeleRadiologia.value.fechaIni),
            fechaFin: String(datosTeleRadiologia.value.fechaFin),
        });

        const url = `/descarga-reportes-teleradiologia?${params.toString()}`;
        const newWindow = window.open(url, "_blank", "noopener,noreferrer");

        if (!newWindow) {
            toastMessage({
                heading: "Atención",
                text: "El navegador bloqueó la apertura de la ventana. Permita pop-ups para este sitio.",
                icon: "warning",
            });
        }
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible generar el reporte de tele-radiología.",
            icon: "error",
        });
    } finally {
        setTimeout(() => {
            loadingDescargarTeleRadiologia.value = false;
        }, 700);
    }
}

/**
 * Descarga el reporte de ausentismo y cancelaciones de citas en Excel.
 */
async function descargarReporteAusentismo() {
    if (loadingDescargarAusentismo.value) return;
    loadingDescargarAusentismo.value = true;
    try {
        if (!validarFechas(datosAusentismo.value.fechaIni, datosAusentismo.value.fechaFin)) {
            return;
        }

        const params = new URLSearchParams({
            sedeId: String(datosAusentismo.value.sedeId ?? 0),
            fechaIni: String(datosAusentismo.value.fechaIni),
            fechaFin: String(datosAusentismo.value.fechaFin),
            estado: String(datosAusentismo.value.estado || "todos"),
        });

        const url = `/descarga-reportes-ausentismo-citas?${params.toString()}`;
        const newWindow = window.open(url, "_blank", "noopener,noreferrer");

        if (!newWindow) {
            toastMessage({
                heading: "Atención",
                text: "El navegador bloqueó la apertura de la ventana. Permita pop-ups para este sitio.",
                icon: "warning",
            });
        }
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible generar el reporte de ausentismo de citas.",
            icon: "error",
        });
    } finally {
        setTimeout(() => {
            loadingDescargarAusentismo.value = false;
        }, 700);
    }
}

/**
 * Descarga el reporte de liquidación de honorarios médicos en Excel.
 */
async function descargarReporteLiquidacion() {
    if (loadingDescargarLiquidacion.value) return;
    loadingDescargarLiquidacion.value = true;
    try {
        if (!datosLiquidacion.value.sedeId || datosLiquidacion.value.sedeId === 0) {
            toastMessage({
                heading: "Atención",
                text: "Debes seleccionar una sede para la liquidación.",
                icon: "warning",
            });
            return;
        }
        if (!validarFechas(datosLiquidacion.value.fechaIni, datosLiquidacion.value.fechaFin)) {
            return;
        }

        const params = new URLSearchParams({
            sede_id: String(datosLiquidacion.value.sedeId),
            fecha_inicio: String(datosLiquidacion.value.fechaIni),
            fecha_fin: String(datosLiquidacion.value.fechaFin),
        });

        const url = `/descarga-reportes-liquidacion?${params.toString()}`;
        const newWindow = window.open(url, "_blank", "noopener,noreferrer");

        if (!newWindow) {
            toastMessage({
                heading: "Atención",
                text: "El navegador bloqueó la apertura de la ventana. Permita pop-ups para este sitio.",
                icon: "warning",
            });
        }
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible generar el reporte de liquidación.",
            icon: "error",
        });
    } finally {
        setTimeout(() => {
            loadingDescargarLiquidacion.value = false;
        }, 700);
    }
}

/**
 * Descarga el reporte de productividad médica mensual en Excel.
 */
async function descargarReporteProductividad() {
    if (loadingDescargarProductividad.value) return;
    loadingDescargarProductividad.value = true;
    try {
        if (!datosProductividad.value.sedeId || datosProductividad.value.sedeId === 0) {
            toastMessage({
                heading: "Atención",
                text: "Debes seleccionar una sede para el corte de productividad.",
                icon: "warning",
            });
            return;
        }
        if (!datosProductividad.value.periodo) {
            toastMessage({
                heading: "Atención",
                text: "Debes seleccionar el periodo mensual (Año-Mes).",
                icon: "warning",
            });
            return;
        }

        const params = new URLSearchParams({
            sede_id: String(datosProductividad.value.sedeId),
            periodo: String(datosProductividad.value.periodo),
        });

        const url = `/descarga-reportes-productividad?${params.toString()}`;
        const newWindow = window.open(url, "_blank", "noopener,noreferrer");

        if (!newWindow) {
            toastMessage({
                heading: "Atención",
                text: "El navegador bloqueó la apertura de la ventana. Permita pop-ups para este sitio.",
                icon: "warning",
            });
        }
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible generar el reporte de productividad médica.",
            icon: "error",
        });
    } finally {
        setTimeout(() => {
            loadingDescargarProductividad.value = false;
        }, 700);
    }
}

/**
 * Descarga el reporte de calidad y oportunidad (TAT) en Excel.
 */
async function descargarReporteTAT() {
    if (loadingDescargarTAT.value) return;
    loadingDescargarTAT.value = true;
    try {
        if (!validarFechas(datosTAT.value.fechaIni, datosTAT.value.fechaFin)) {
            return;
        }

        const params = new URLSearchParams({
            sedeId: String(datosTAT.value.sedeId ?? 0),
            fechaIni: String(datosTAT.value.fechaIni),
            fechaFin: String(datosTAT.value.fechaFin),
            modalidad: String(datosTAT.value.modalidad || "todas"),
        });

        const url = `/descarga-reportes-tat?${params.toString()}`;
        const newWindow = window.open(url, "_blank", "noopener,noreferrer");

        if (!newWindow) {
            toastMessage({
                heading: "Atención",
                text: "El navegador bloqueó la apertura de la ventana. Permita pop-ups para este sitio.",
                icon: "warning",
            });
        }
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible generar el reporte de tiempos TAT.",
            icon: "error",
        });
    } finally {
        setTimeout(() => {
            loadingDescargarTAT.value = false;
        }, 700);
    }
}

/**
 * Descarga el reporte de salud y telemetría de agentes hospitalarios en Excel.
 */
async function descargarReporteAgentes() {
    if (loadingDescargarAgentes.value) return;
    loadingDescargarAgentes.value = true;
    try {
        const params = new URLSearchParams({
            sedeId: String(datosAgentes.value.sedeId ?? 0),
        });

        const url = `/descarga-reportes-agentes?${params.toString()}`;
        const newWindow = window.open(url, "_blank", "noopener,noreferrer");

        if (!newWindow) {
            toastMessage({
                heading: "Atención",
                text: "El navegador bloqueó la apertura de la ventana. Permita pop-ups para este sitio.",
                icon: "warning",
            });
        }
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible generar el reporte de agentes y telemetría.",
            icon: "error",
        });
    } finally {
        setTimeout(() => {
            loadingDescargarAgentes.value = false;
        }, 700);
    }
}

/**
 * Carga indicadores asistenciales en tiempo real desde el servicio de Dashboard.
 */
async function cargarIndicadores() {
    if (loadingIndicadores.value) return;
    loadingIndicadores.value = true;
    try {
        const res = await axios.get("/api/reportes.dashboard?days=30");
        if (res?.data?.data) {
            indicadores.value = {
                ...indicadores.value,
                ...res.data.data,
            };
        }
    } catch (_error) {
        // Mantiene valores por defecto ante microcortes
    } finally {
        loadingIndicadores.value = false;
    }
}

function refrescarTodo() {
    listarSedes();
    cargarIndicadores();
}

async function abrirModalProduccion(tipo) {
    tipoReporte.value = tipo;
    modalProduccionVisible.value = true;

    let moduloId = 1;
    if (tipo === "Ecografia") moduloId = 2;
    if (tipo === "Oftalmologia") moduloId = 3;

    try {
        await listarUsuariosYSedes(
            {
                set quienes_asignaron(v) {
                    quienes_asignaron.value = v;
                },
                set medicos(v) {
                    medicos.value = v;
                },
            },
            { moduloId: String(moduloId) }
        );
    } catch (_e) {
        // Fallback silencioso
    }

    nextTick(() => {
        const el = document.getElementById("prod-sede");
        if (el) el.focus();
    });
}

function abrirModalProduccionConsolidada() {
    resetearFormularioConsolidada();
    modalProduccionConsolidadaVisible.value = true;
    nextTick(() => {
        const el = document.getElementById("cons-sede");
        if (el) el.focus();
    });
}

function abrirModalPAC() {
    modalPACVisible.value = true;
    nextTick(() => {
        const el = document.getElementById("pac-sede");
        if (el) el.focus();
    });
}

function abrirModalDosimetria() {
    modalDosimetriaVisible.value = true;
    nextTick(() => {
        const el = document.getElementById("dosis-sede");
        if (el) el.focus();
    });
}

function abrirModalTeleRadiologia() {
    modalTeleRadiologiaVisible.value = true;
    nextTick(() => {
        const el = document.getElementById("tele-sede");
        if (el) el.focus();
    });
}

function abrirModalAusentismo() {
    modalAusentismoVisible.value = true;
    nextTick(() => {
        const el = document.getElementById("ausentismo-sede");
        if (el) el.focus();
    });
}

function abrirModalLiquidacion() {
    modalLiquidacionVisible.value = true;
    nextTick(() => {
        const el = document.getElementById("liq-sede");
        if (el) el.focus();
    });
}

function abrirModalProductividad() {
    modalProductividadVisible.value = true;
    nextTick(() => {
        const el = document.getElementById("prod-med-sede");
        if (el) el.focus();
    });
}

function abrirModalTAT() {
    modalTATVisible.value = true;
    nextTick(() => {
        const el = document.getElementById("tat-sede");
        if (el) el.focus();
    });
}

function abrirModalAgentes() {
    modalAgentesVisible.value = true;
    nextTick(() => {
        const el = document.getElementById("agentes-sede");
        if (el) el.focus();
    });
}

function abrirModalConsolidadoRed() {
    modalConsolidadoRedVisible.value = true;
    if (!resumenConsolidadoRed.value) {
        consultarConsolidadoRed();
    }
}

function cambiarPeriodoConsolidadoRed(nuevoPeriodo) {
    datosConsolidadoRed.value.periodo = nuevoPeriodo;
    if (nuevoPeriodo !== "custom") {
        consultarConsolidadoRed();
    }
}

async function consultarConsolidadoRed() {
    if (loadingConsolidadoRed.value) return;
    loadingConsolidadoRed.value = true;
    try {
        const params = new URLSearchParams({
            periodo: datosConsolidadoRed.value.periodo,
            fechaIni: datosConsolidadoRed.value.fechaIni || "",
            fechaFin: datosConsolidadoRed.value.fechaFin || "",
        });
        const res = await axios.get(`/api/reportes-resumen-red?${params.toString()}`);
        resumenConsolidadoRed.value = res.data;
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible consultar el consolidado de la red.",
            icon: "error",
        });
    } finally {
        loadingConsolidadoRed.value = false;
    }
}

async function descargarConsolidadoRed() {
    if (loadingDescargarConsolidadoRed.value) return;
    loadingDescargarConsolidadoRed.value = true;
    try {
        const params = new URLSearchParams({
            periodo: datosConsolidadoRed.value.periodo,
            fechaIni: datosConsolidadoRed.value.fechaIni || "",
            fechaFin: datosConsolidadoRed.value.fechaFin || "",
        });
        const url = `/descarga-reportes-consolidado-red?${params.toString()}`;
        const newWindow = window.open(url, "_blank", "noopener,noreferrer");
        if (!newWindow) {
            toastMessage({
                heading: "Atención",
                text: "El navegador bloqueó la apertura de la ventana. Permita pop-ups para este sitio.",
                icon: "warning",
            });
        }
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible descargar el consolidado de la red.",
            icon: "error",
        });
    } finally {
        setTimeout(() => {
            loadingDescargarConsolidadoRed.value = false;
        }, 700);
    }
}

function abrirModalSemaforoRepresados() {
    modalSemaforoRepresadosVisible.value = true;
    consultarSemaforoRepresados();
}

async function consultarSemaforoRepresados() {
    if (loadingSemaforoRepresados.value) return;
    loadingSemaforoRepresados.value = true;
    try {
        const params = new URLSearchParams({
            sedeId: String(datosSemaforoRepresados.value.sedeId || 0),
        });
        const res = await axios.get(`/api/reportes-semaforo-represados?${params.toString()}`);
        resumenSemaforoRepresados.value = res.data;
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible consultar el semáforo de represamientos.",
            icon: "error",
        });
    } finally {
        loadingSemaforoRepresados.value = false;
    }
}

async function descargarSemaforoRepresados() {
    if (loadingDescargarSemaforoRepresados.value) return;
    loadingDescargarSemaforoRepresados.value = true;
    try {
        const params = new URLSearchParams({
            sedeId: String(datosSemaforoRepresados.value.sedeId || 0),
        });
        const url = `/descarga-reportes-represados?${params.toString()}`;
        const newWindow = window.open(url, "_blank", "noopener,noreferrer");
        if (!newWindow) {
            toastMessage({
                heading: "Atención",
                text: "El navegador bloqueó la apertura de la ventana. Permita pop-ups para este sitio.",
                icon: "warning",
            });
        }
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible descargar el reporte de represamientos.",
            icon: "error",
        });
    } finally {
        setTimeout(() => {
            loadingDescargarSemaforoRepresados.value = false;
        }, 700);
    }
}

function abrirModalCitasWhatsApp() {
    modalCitasWhatsAppVisible.value = true;
    if (!resumenCitasWhatsApp.value) {
        consultarCitasWhatsApp();
    }
}

function cambiarPeriodoCitasWhatsApp(nuevoPeriodo) {
    datosCitasWhatsApp.value.periodo = nuevoPeriodo;
    if (nuevoPeriodo !== "custom") {
        consultarCitasWhatsApp();
    }
}

async function consultarCitasWhatsApp() {
    if (loadingCitasWhatsApp.value) return;
    loadingCitasWhatsApp.value = true;
    try {
        const params = new URLSearchParams({
            periodo: datosCitasWhatsApp.value.periodo,
            sedeId: String(datosCitasWhatsApp.value.sedeId || 0),
            fechaIni: datosCitasWhatsApp.value.fechaIni || "",
            fechaFin: datosCitasWhatsApp.value.fechaFin || "",
        });
        const res = await axios.get(`/api/reportes-citas-whatsapp?${params.toString()}`);
        resumenCitasWhatsApp.value = res.data;
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible consultar estadísticas de citas y WhatsApp.",
            icon: "error",
        });
    } finally {
        loadingCitasWhatsApp.value = false;
    }
}

async function descargarCitasWhatsApp() {
    if (loadingDescargarCitasWhatsApp.value) return;
    loadingDescargarCitasWhatsApp.value = true;
    try {
        const params = new URLSearchParams({
            periodo: datosCitasWhatsApp.value.periodo,
            sedeId: String(datosCitasWhatsApp.value.sedeId || 0),
            fechaIni: datosCitasWhatsApp.value.fechaIni || "",
            fechaFin: datosCitasWhatsApp.value.fechaFin || "",
        });
        const url = `/descarga-reportes-citas-whatsapp?${params.toString()}`;
        const newWindow = window.open(url, "_blank", "noopener,noreferrer");
        if (!newWindow) {
            toastMessage({
                heading: "Atención",
                text: "El navegador bloqueó la apertura de la ventana. Permita pop-ups para este sitio.",
                icon: "warning",
            });
        }
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible descargar el reporte de citas WhatsApp.",
            icon: "error",
        });
    } finally {
        setTimeout(() => {
            loadingDescargarCitasWhatsApp.value = false;
        }, 700);
    }
}

function abrirModalAlmacenamiento() {
    modalAlmacenamientoVisible.value = true;
    consultarAlmacenamiento();
}

async function consultarAlmacenamiento() {
    if (loadingAlmacenamiento.value) return;
    loadingAlmacenamiento.value = true;
    try {
        const params = new URLSearchParams({
            sedeId: String(datosAlmacenamiento.value.sedeId || 0),
        });
        const res = await axios.get(`/api/reportes-almacenamiento?${params.toString()}`);
        resumenAlmacenamiento.value = res.data;
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible consultar el almacenamiento de servidores.",
            icon: "error",
        });
    } finally {
        loadingAlmacenamiento.value = false;
    }
}

async function descargarAlmacenamiento() {
    if (loadingDescargarAlmacenamiento.value) return;
    loadingDescargarAlmacenamiento.value = true;
    try {
        const params = new URLSearchParams({
            sedeId: String(datosAlmacenamiento.value.sedeId || 0),
        });
        const url = `/descarga-reportes-almacenamiento?${params.toString()}`;
        const newWindow = window.open(url, "_blank", "noopener,noreferrer");
        if (!newWindow) {
            toastMessage({
                heading: "Atención",
                text: "El navegador bloqueó la apertura de la ventana. Permita pop-ups para este sitio.",
                icon: "warning",
            });
        }
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible descargar el reporte de almacenamiento.",
            icon: "error",
        });
    } finally {
        setTimeout(() => {
            loadingDescargarAlmacenamiento.value = false;
        }, 700);
    }
}

onMounted(() => {
    listarSedes();
    cargarIndicadores();

    if (tieneAccesoModulo("Rx")) {
        moduloActivo.value = "Rx";
    } else if (tieneAccesoModulo("Ecografias")) {
        moduloActivo.value = "Ecografias";
    } else if (tieneAccesoModulo("Oftalmologia")) {
        moduloActivo.value = "Oftalmologia";
    } else {
        moduloActivo.value = "Transversal";
    }
});

useKeyboardShortcuts({
    onSearch: () => {
        if (
            !modalProduccionVisible.value &&
            !modalProduccionConsolidadaVisible.value &&
            !modalPACVisible.value &&
            !modalDosimetriaVisible.value &&
            !modalTeleRadiologiaVisible.value &&
            !modalAusentismoVisible.value &&
            !modalLiquidacionVisible.value &&
            !modalProductividadVisible.value &&
            !modalTATVisible.value &&
            !modalAgentesVisible.value &&
            !modalConsolidadoRedVisible.value &&
            !modalSemaforoRepresadosVisible.value &&
            !modalCitasWhatsAppVisible.value &&
            !modalAlmacenamientoVisible.value &&
            !loadingSedes.value
        ) {
            refrescarTodo();
        }
    },
    onSave: () => {
        if (modalProduccionVisible.value && !loadingDescargarProduccion.value) {
            descargarReporte();
        } else if (modalProduccionConsolidadaVisible.value && !loadingDescargarConsolidada.value) {
            descargarProduccionConsolidada();
        } else if (modalPACVisible.value && !loadingDescargarPAC.value) {
            descargarReporteEstudiosPAC();
        } else if (modalDosimetriaVisible.value && !loadingDescargarDosimetria.value) {
            descargarReporteDosimetria();
        } else if (modalTeleRadiologiaVisible.value && !loadingDescargarTeleRadiologia.value) {
            descargarReporteTeleRadiologia();
        } else if (modalAusentismoVisible.value && !loadingDescargarAusentismo.value) {
            descargarReporteAusentismo();
        } else if (modalLiquidacionVisible.value && !loadingDescargarLiquidacion.value) {
            descargarReporteLiquidacion();
        } else if (modalProductividadVisible.value && !loadingDescargarProductividad.value) {
            descargarReporteProductividad();
        } else if (modalTATVisible.value && !loadingDescargarTAT.value) {
            descargarReporteTAT();
        } else if (modalAgentesVisible.value && !loadingDescargarAgentes.value) {
            descargarReporteAgentes();
        } else if (modalConsolidadoRedVisible.value && !loadingDescargarConsolidadoRed.value) {
            descargarConsolidadoRed();
        } else if (
            modalSemaforoRepresadosVisible.value &&
            !loadingDescargarSemaforoRepresados.value
        ) {
            descargarSemaforoRepresados();
        } else if (modalCitasWhatsAppVisible.value && !loadingDescargarCitasWhatsApp.value) {
            descargarCitasWhatsApp();
        } else if (modalAlmacenamientoVisible.value && !loadingDescargarAlmacenamiento.value) {
            descargarAlmacenamiento();
        }
    },
    onEscape: () => {
        if (modalProduccionVisible.value) {
            resetearFormulario();
            modalProduccionVisible.value = false;
        } else if (modalProduccionConsolidadaVisible.value) {
            resetearFormularioConsolidada();
            modalProduccionConsolidadaVisible.value = false;
        } else if (modalPACVisible.value) {
            resetearFormularioPAC();
            modalPACVisible.value = false;
        } else if (modalDosimetriaVisible.value) {
            resetearFormularioDosimetria();
            modalDosimetriaVisible.value = false;
        } else if (modalTeleRadiologiaVisible.value) {
            resetearFormularioTeleRadiologia();
            modalTeleRadiologiaVisible.value = false;
        } else if (modalAusentismoVisible.value) {
            resetearFormularioAusentismo();
            modalAusentismoVisible.value = false;
        } else if (modalLiquidacionVisible.value) {
            resetearFormularioLiquidacion();
            modalLiquidacionVisible.value = false;
        } else if (modalProductividadVisible.value) {
            resetearFormularioProductividad();
            modalProductividadVisible.value = false;
        } else if (modalTATVisible.value) {
            resetearFormularioTAT();
            modalTATVisible.value = false;
        } else if (modalAgentesVisible.value) {
            resetearFormularioAgentes();
            modalAgentesVisible.value = false;
        } else if (modalConsolidadoRedVisible.value) {
            modalConsolidadoRedVisible.value = false;
        } else if (modalSemaforoRepresadosVisible.value) {
            modalSemaforoRepresadosVisible.value = false;
        } else if (modalCitasWhatsAppVisible.value) {
            modalCitasWhatsAppVisible.value = false;
        } else if (modalAlmacenamientoVisible.value) {
            modalAlmacenamientoVisible.value = false;
        }
    },
});
</script>
