<template>
    <div>
        <!-- Bread crumb -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Reportes</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Reportes</a>
                    </li>
                    <li class="breadcrumb-item active">Producción</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary btn-action-clinical m-r-5"
                    :disabled="loadingSedes"
                    title="Actualizar sedes y catálogos asistenciales (F2)"
                    @click="listarSedes()"
                >
                    <i class="fa fa-refresh mr-1" :class="{ 'fa-spin': loadingSedes }"></i>
                    Refrescar <span class="badge badge-light border ml-1">F2</span>
                </button>
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

        <!-- Page Content -->
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-filter"></i> Producción
                        </h4>

                        <div class="row">
                            <div class="col-12">
                                <a href="javascript:void(0)" @click="abrirModalProduccion('Rx')">
                                    <i class="fa fa-check text-info"></i>
                                    Producción detallada (RX, Tomografía, Mamografía, Resonancia)
                                </a>
                            </div>
                            <div class="col-12" v-if="can('ecografias.reporte')">
                                <a
                                    href="javascript:void(0)"
                                    @click="abrirModalProduccion('Ecografia')"
                                >
                                    <i class="fa fa-check text-info"></i>
                                    Producción detallada (Ecografias)
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
                            <i class="fa fa-filter"></i> Estudios tomados en PAC
                        </h4>

                        <div class="row">
                            <div class="col-12">
                                <a href="javascript:void(0)" @click="abrirModalPAC()">
                                    <i class="fa fa-check text-info"></i>
                                    Descargar reporte de estudios tomados
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
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
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
        </div>

        <!-- Modal Producción Detallada -->
        <div
            v-if="modalProduccionVisible"
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-hidden="false"
            style="background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(2px); z-index: 1050"
            @click.self="
                resetearFormulario();
                modalProduccionVisible = false;
            "
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Filtro para el reporte ({{ tipoReporte }})</h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            title="Cerrar (Esc)"
                            @click="
                                resetearFormulario();
                                modalProduccionVisible = false;
                            "
                        >
                            &times;
                        </button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="form-group">
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
                                <select
                                    class="form-control custom-select"
                                    style="width: 100%; height: 36px"
                                    v-model="datosReporte.medico"
                                    id="prod-medico"
                                >
                                    <option value="" selected>
                                        ...::: Todos los medicos :::...
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
                                        <div class="example">
                                            <h5 class="box-title m-t-30">Desde</h5>
                                            <input
                                                class="form-control input-daterange-datepicker"
                                                type="date"
                                                id="prod-fechaIni"
                                                v-model="datosReporte.fechaIni"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <div class="example">
                                            <h5 class="box-title m-t-30">Hasta</h5>
                                            <input
                                                class="form-control input-daterange-datepicker"
                                                type="date"
                                                id="prod-fechaFin"
                                                v-model="datosReporte.fechaFin"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-inverse btn-action-clinical waves-effect"
                            title="Cerrar (Esc)"
                            @click="
                                resetearFormulario();
                                modalProduccionVisible = false;
                            "
                        >
                            Cerrar
                            <span class="badge badge-secondary ml-1">Esc</span>
                        </button>
                        <button
                            type="button"
                            class="btn btn-info btn-action-clinical waves-effect waves-light"
                            title="Descargar reporte en Excel (Ctrl+S)"
                            @click="descargarReporte()"
                            :disabled="loadingDescargarProduccion"
                        >
                            <i
                                v-if="loadingDescargarProduccion"
                                class="fa fa-spinner fa-spin mr-1"
                            ></i>
                            <i v-else class="fa fa-download mr-1"></i>
                            Descargar reportes
                            <span class="badge badge-light text-dark ml-1">Ctrl+S</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal PAC -->
        <div
            v-if="modalPACVisible"
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-hidden="false"
            style="background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(2px); z-index: 1050"
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
                            title="Cerrar (Esc)"
                            @click="
                                resetearFormularioPAC();
                                modalPACVisible = false;
                            "
                        >
                            &times;
                        </button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="form-group">
                                <label class="control-label">Sede</label>
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
                                        <div class="example">
                                            <h5 class="box-title m-t-30">Desde</h5>
                                            <input
                                                class="form-control"
                                                type="date"
                                                id="pac-fechaIni"
                                                v-model="datosPAC.fechaIni"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <div class="example">
                                            <h5 class="box-title m-t-30">Hasta</h5>
                                            <input
                                                class="form-control"
                                                type="date"
                                                id="pac-fechaFin"
                                                v-model="datosPAC.fechaFin"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-inverse btn-action-clinical waves-effect"
                            title="Cerrar (Esc)"
                            @click="
                                resetearFormularioPAC();
                                modalPACVisible = false;
                            "
                        >
                            Cerrar
                            <span class="badge badge-secondary ml-1">Esc</span>
                        </button>
                        <button
                            type="button"
                            class="btn btn-info btn-action-clinical waves-effect waves-light"
                            title="Descargar reporte en Excel (Ctrl+S)"
                            @click="descargarReporteEstudiosPAC()"
                            :disabled="loadingDescargarPAC"
                        >
                            <i v-if="loadingDescargarPAC" class="fa fa-spinner fa-spin mr-1"></i>
                            <i v-else class="fa fa-download mr-1"></i>
                            Descargar reporte
                            <span class="badge badge-light text-dark ml-1">Ctrl+S</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Dosimetría / Control de Radiación -->
        <div
            v-if="modalDosimetriaVisible"
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-hidden="false"
            style="background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(2px); z-index: 1050"
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
                            title="Cerrar (Esc)"
                            @click="
                                resetearFormularioDosimetria();
                                modalDosimetriaVisible = false;
                            "
                        >
                            &times;
                        </button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="form-group">
                                <label class="control-label">Sede Hospitalaria</label>
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
                                        <div class="example">
                                            <h5 class="box-title m-t-30">Desde</h5>
                                            <input
                                                class="form-control"
                                                type="date"
                                                id="dosis-fechaIni"
                                                v-model="datosDosimetria.fechaIni"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <div class="example">
                                            <h5 class="box-title m-t-30">Hasta</h5>
                                            <input
                                                class="form-control"
                                                type="date"
                                                id="dosis-fechaFin"
                                                v-model="datosDosimetria.fechaFin"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-inverse btn-action-clinical waves-effect"
                            title="Cerrar (Esc)"
                            @click="
                                resetearFormularioDosimetria();
                                modalDosimetriaVisible = false;
                            "
                        >
                            Cerrar
                            <span class="badge badge-secondary ml-1">Esc</span>
                        </button>
                        <button
                            type="button"
                            class="btn btn-info btn-action-clinical waves-effect waves-light"
                            title="Descargar reporte en Excel (Ctrl+S)"
                            @click="descargarReporteDosimetria()"
                            :disabled="loadingDescargarDosimetria"
                        >
                            <i
                                v-if="loadingDescargarDosimetria"
                                class="fa fa-spinner fa-spin mr-1"
                            ></i>
                            <i v-else class="fa fa-download mr-1"></i>
                            Descargar reporte
                            <span class="badge badge-light text-dark ml-1">Ctrl+S</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Tele-radiología -->
        <div
            v-if="modalTeleRadiologiaVisible"
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-hidden="false"
            style="background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(2px); z-index: 1050"
            @click.self="
                resetearFormularioTeleRadiologia();
                modalTeleRadiologiaVisible = false;
            "
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title font-bold">
                            Filtro de Tele-radiología y Lecturas Externas
                        </h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            title="Cerrar (Esc)"
                            @click="
                                resetearFormularioTeleRadiologia();
                                modalTeleRadiologiaVisible = false;
                            "
                        >
                            &times;
                        </button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="form-group">
                                <label class="control-label font-weight-bold"
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
                                        <div class="example">
                                            <h5 class="box-title m-t-30">Desde</h5>
                                            <input
                                                class="form-control"
                                                type="date"
                                                id="tele-fechaIni"
                                                v-model="datosTeleRadiologia.fechaIni"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <div class="example">
                                            <h5 class="box-title m-t-30">Hasta</h5>
                                            <input
                                                class="form-control"
                                                type="date"
                                                id="tele-fechaFin"
                                                v-model="datosTeleRadiologia.fechaFin"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-inverse btn-action-clinical waves-effect"
                            title="Cerrar (Esc)"
                            @click="
                                resetearFormularioTeleRadiologia();
                                modalTeleRadiologiaVisible = false;
                            "
                        >
                            Cerrar
                            <span class="badge badge-secondary ml-1">Esc</span>
                        </button>
                        <button
                            type="button"
                            class="btn btn-info btn-action-clinical waves-effect waves-light"
                            title="Descargar reporte en Excel (Ctrl+S)"
                            @click="descargarReporteTeleRadiologia()"
                            :disabled="loadingDescargarTeleRadiologia"
                        >
                            <i
                                v-if="loadingDescargarTeleRadiologia"
                                class="fa fa-spinner fa-spin mr-1"
                            ></i>
                            <i v-else class="fa fa-download mr-1"></i>
                            Descargar reporte
                            <span class="badge badge-light text-dark ml-1">Ctrl+S</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Ausentismo y Cancelaciones de Citas -->
        <div
            v-if="modalAusentismoVisible"
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-hidden="false"
            style="background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(2px); z-index: 1050"
            @click.self="
                resetearFormularioAusentismo();
                modalAusentismoVisible = false;
            "
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title font-bold">
                            Filtro de Ausentismo y Cancelaciones (No-Show)
                        </h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            title="Cerrar (Esc)"
                            @click="
                                resetearFormularioAusentismo();
                                modalAusentismoVisible = false;
                            "
                        >
                            &times;
                        </button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="form-group">
                                <label class="control-label font-weight-bold"
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
                                <label class="control-label font-weight-bold"
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
                                        <div class="example">
                                            <h5 class="box-title m-t-30">Desde</h5>
                                            <input
                                                class="form-control"
                                                type="date"
                                                id="ausentismo-fechaIni"
                                                v-model="datosAusentismo.fechaIni"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <div class="example">
                                            <h5 class="box-title m-t-30">Hasta</h5>
                                            <input
                                                class="form-control"
                                                type="date"
                                                id="ausentismo-fechaFin"
                                                v-model="datosAusentismo.fechaFin"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-inverse btn-action-clinical waves-effect"
                            title="Cerrar (Esc)"
                            @click="
                                resetearFormularioAusentismo();
                                modalAusentismoVisible = false;
                            "
                        >
                            Cerrar
                            <span class="badge badge-secondary ml-1">Esc</span>
                        </button>
                        <button
                            type="button"
                            class="btn btn-info btn-action-clinical waves-effect waves-light"
                            title="Descargar reporte en Excel (Ctrl+S)"
                            @click="descargarReporteAusentismo()"
                            :disabled="loadingDescargarAusentismo"
                        >
                            <i
                                v-if="loadingDescargarAusentismo"
                                class="fa fa-spinner fa-spin mr-1"
                            ></i>
                            <i v-else class="fa fa-download mr-1"></i>
                            Descargar reporte
                            <span class="badge badge-light text-dark ml-1">Ctrl+S</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Liquidación de Honorarios -->
        <div
            v-if="modalLiquidacionVisible"
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-hidden="false"
            style="background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(2px); z-index: 1050"
            @click.self="
                resetearFormularioLiquidacion();
                modalLiquidacionVisible = false;
            "
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title font-bold">Filtro de Liquidación de Honorarios</h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            title="Cerrar (Esc)"
                            @click="
                                resetearFormularioLiquidacion();
                                modalLiquidacionVisible = false;
                            "
                        >
                            &times;
                        </button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="form-group">
                                <label class="control-label font-weight-bold"
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
                                        <div class="example">
                                            <h5 class="box-title m-t-30">Desde</h5>
                                            <input
                                                class="form-control"
                                                type="date"
                                                id="liq-fechaIni"
                                                v-model="datosLiquidacion.fechaIni"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <div class="example">
                                            <h5 class="box-title m-t-30">Hasta</h5>
                                            <input
                                                class="form-control"
                                                type="date"
                                                id="liq-fechaFin"
                                                v-model="datosLiquidacion.fechaFin"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-inverse btn-action-clinical waves-effect"
                            title="Cerrar (Esc)"
                            @click="
                                resetearFormularioLiquidacion();
                                modalLiquidacionVisible = false;
                            "
                        >
                            Cerrar
                            <span class="badge badge-secondary ml-1">Esc</span>
                        </button>
                        <button
                            type="button"
                            class="btn btn-info btn-action-clinical waves-effect waves-light"
                            title="Descargar reporte en Excel (Ctrl+S)"
                            @click="descargarReporteLiquidacion()"
                            :disabled="loadingDescargarLiquidacion"
                        >
                            <i
                                v-if="loadingDescargarLiquidacion"
                                class="fa fa-spinner fa-spin mr-1"
                            ></i>
                            <i v-else class="fa fa-download mr-1"></i>
                            Descargar reporte
                            <span class="badge badge-light text-dark ml-1">Ctrl+S</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Productividad Médica (Corte Mensual) -->
        <div
            v-if="modalProductividadVisible"
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-hidden="false"
            style="background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(2px); z-index: 1050"
            @click.self="
                resetearFormularioProductividad();
                modalProductividadVisible = false;
            "
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title font-bold">
                            Filtro de Productividad Médica Mensual
                        </h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            title="Cerrar (Esc)"
                            @click="
                                resetearFormularioProductividad();
                                modalProductividadVisible = false;
                            "
                        >
                            &times;
                        </button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="form-group">
                                <label class="control-label font-weight-bold"
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
                                <label class="control-label font-weight-bold"
                                    >Periodo de Corte (Mes)</label
                                >
                                <input
                                    class="form-control"
                                    type="month"
                                    id="prod-med-periodo"
                                    v-model="datosProductividad.periodo"
                                />
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-inverse btn-action-clinical waves-effect"
                            title="Cerrar (Esc)"
                            @click="
                                resetearFormularioProductividad();
                                modalProductividadVisible = false;
                            "
                        >
                            Cerrar
                            <span class="badge badge-secondary ml-1">Esc</span>
                        </button>
                        <button
                            type="button"
                            class="btn btn-info btn-action-clinical waves-effect waves-light"
                            title="Descargar reporte en Excel (Ctrl+S)"
                            @click="descargarReporteProductividad()"
                            :disabled="loadingDescargarProductividad"
                        >
                            <i
                                v-if="loadingDescargarProductividad"
                                class="fa fa-spinner fa-spin mr-1"
                            ></i>
                            <i v-else class="fa fa-download mr-1"></i>
                            Descargar reporte
                            <span class="badge badge-light text-dark ml-1">Ctrl+S</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Calidad y Oportunidad (Turnaround Time - TAT) -->
        <div
            v-if="modalTATVisible"
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-hidden="false"
            style="background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(2px); z-index: 1050"
            @click.self="
                resetearFormularioTAT();
                modalTATVisible = false;
            "
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title font-bold">Filtro de Calidad y Oportunidad (TAT)</h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            title="Cerrar (Esc)"
                            @click="
                                resetearFormularioTAT();
                                modalTATVisible = false;
                            "
                        >
                            &times;
                        </button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="form-group">
                                <label class="control-label font-weight-bold"
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
                                <label class="control-label font-weight-bold"
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
                                        <div class="example">
                                            <h5 class="box-title m-t-30">Desde</h5>
                                            <input
                                                class="form-control"
                                                type="date"
                                                id="tat-fechaIni"
                                                v-model="datosTAT.fechaIni"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <div class="example">
                                            <h5 class="box-title m-t-30">Hasta</h5>
                                            <input
                                                class="form-control"
                                                type="date"
                                                id="tat-fechaFin"
                                                v-model="datosTAT.fechaFin"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-inverse btn-action-clinical waves-effect"
                            title="Cerrar (Esc)"
                            @click="
                                resetearFormularioTAT();
                                modalTATVisible = false;
                            "
                        >
                            Cerrar
                            <span class="badge badge-secondary ml-1">Esc</span>
                        </button>
                        <button
                            type="button"
                            class="btn btn-info btn-action-clinical waves-effect waves-light"
                            title="Descargar reporte en Excel (Ctrl+S)"
                            @click="descargarReporteTAT()"
                            :disabled="loadingDescargarTAT"
                        >
                            <i v-if="loadingDescargarTAT" class="fa fa-spinner fa-spin mr-1"></i>
                            <i v-else class="fa fa-download mr-1"></i>
                            Descargar reporte
                            <span class="badge badge-light text-dark ml-1">Ctrl+S</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { nextTick, onMounted, ref } from "vue";

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

const sedes = ref([]);
const datosReporte = ref({ sedeId: 0, quien_asigno: "", medico: "", fechaIni: "", fechaFin: "" });
const datosPAC = ref({ sedeId: 0, fechaIni: "", fechaFin: "" });
const datosDosimetria = ref({ sedeId: 0, fechaIni: "", fechaFin: "" });
const datosTeleRadiologia = ref({ sedeId: 0, fechaIni: "", fechaFin: "" });
const datosAusentismo = ref({ sedeId: 0, fechaIni: "", fechaFin: "", estado: "todos" });
const datosLiquidacion = ref({ sedeId: 0, fechaIni: "", fechaFin: "" });
const datosProductividad = ref({ sedeId: 0, periodo: "" });
const datosTAT = ref({ sedeId: 0, fechaIni: "", fechaFin: "", modalidad: "todas" });
const quienes_asignaron = ref([]);
const medicos = ref([]);
const tipoReporte = ref("Rx");
const loadingSedes = ref(false);
const loadingIndicadores = ref(false);
const loadingDescargarProduccion = ref(false);
const loadingDescargarPAC = ref(false);
const loadingDescargarDosimetria = ref(false);
const loadingDescargarTeleRadiologia = ref(false);
const loadingDescargarAusentismo = ref(false);
const loadingDescargarLiquidacion = ref(false);
const loadingDescargarProductividad = ref(false);
const loadingDescargarTAT = ref(false);
const modalProduccionVisible = ref(false);
const modalPACVisible = ref(false);
const modalDosimetriaVisible = ref(false);
const modalTeleRadiologiaVisible = ref(false);
const modalAusentismoVisible = ref(false);
const modalLiquidacionVisible = ref(false);
const modalProductividadVisible = ref(false);
const modalTATVisible = ref(false);

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

function abrirModalProduccion(tipo) {
    tipoReporte.value = tipo;
    modalProduccionVisible.value = true;
    nextTick(() => {
        const el = document.getElementById("prod-sede");
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

onMounted(() => {
    listarSedes();
    cargarIndicadores();
});

useKeyboardShortcuts({
    onSearch: () => {
        if (
            !modalProduccionVisible.value &&
            !modalPACVisible.value &&
            !modalDosimetriaVisible.value &&
            !modalTeleRadiologiaVisible.value &&
            !modalAusentismoVisible.value &&
            !modalLiquidacionVisible.value &&
            !modalProductividadVisible.value &&
            !modalTATVisible.value &&
            !loadingSedes.value
        ) {
            refrescarTodo();
        }
    },
    onSave: () => {
        if (modalProduccionVisible.value && !loadingDescargarProduccion.value) {
            descargarReporte();
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
        }
    },
    onEscape: () => {
        if (modalProduccionVisible.value) {
            resetearFormulario();
            modalProduccionVisible.value = false;
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
        }
    },
});
</script>

<style scoped>
.btn-action-clinical {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.btn-action-clinical:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow:
        0 4px 6px -1px rgba(0, 0, 0, 0.1),
        0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
</style>
