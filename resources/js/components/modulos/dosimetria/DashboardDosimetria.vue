<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb y Acciones Rápidas (Nativo AdminWrap) -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Dosimetría y Control de Radiación</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item active">Dosimetría (Res. 482 / ISO 27001)</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-sm btn-outline-success m-r-5"
                    :disabled="loading || exportando"
                    title="Exportar informe institucional a Excel (.xlsx)"
                    @click="exportarExcel"
                >
                    <i class="fa fa-file-excel-o m-r-5"></i> Exportar Excel
                </button>
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary m-r-5"
                    :disabled="loading"
                    title="Actualizar datos del dashboard"
                    @click="cargarDatos"
                >
                    <i class="fa fa-refresh m-r-5" :class="{ 'fa-spin': loading }"></i> Refrescar
                </button>
                <button
                    type="button"
                    class="btn btn-sm btn-info"
                    title="Ver catálogo de Niveles de Referencia para Diagnóstico"
                    @click="mostrarModalUmbrales = true"
                >
                    <i class="fa fa-sliders m-r-5"></i> Catálogo NRD
                </button>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- 1. Info Boxes (Nativo AdminWrap) -->
        <!-- ============================================================== -->
        <div class="row">
            <!-- 1.1 Total Estudios Monitoreados -->
            <div class="col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex p-10 no-block">
                            <div class="align-self-center display-6 m-r-20">
                                <i class="text-success icon-Target-Market"></i>
                            </div>
                            <div class="align-slef-center">
                                <h2 class="m-b-0">
                                    <span v-if="loading">...</span>
                                    <span v-else>{{
                                        kpis.total_estudios?.toLocaleString() || 0
                                    }}</span>
                                </h2>
                                <h6 class="text-muted m-b-0">Estudios Monitoreados</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 1.2 Equipos Emisores Activos -->
            <div class="col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex p-10 no-block">
                            <div class="align-self-center display-6 m-r-20">
                                <i class="text-info icon-Desktop-Monitor"></i>
                            </div>
                            <div class="align-slef-center">
                                <h2 class="m-b-0">
                                    <span v-if="loading">...</span>
                                    <span v-else>{{ kpis.total_equipos || 0 }}</span>
                                </h2>
                                <h6 class="text-muted m-b-0">Equipos Emisores</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 1.3 Superaciones NRD (Resolución 482) -->
            <div class="col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex p-10 no-block">
                            <div class="align-self-center display-6 m-r-20">
                                <i
                                    class="icon-Contrast"
                                    :class="
                                        (kpis.total_alertas || 0) > 0
                                            ? 'text-danger'
                                            : 'text-success'
                                    "
                                ></i>
                            </div>
                            <div class="align-slef-center">
                                <h2
                                    class="m-b-0"
                                    :class="
                                        (kpis.total_alertas || 0) > 0
                                            ? 'text-danger'
                                            : 'text-success'
                                    "
                                >
                                    <span v-if="loading">...</span>
                                    <span v-else>{{ kpis.total_alertas || 0 }}</span>
                                </h2>
                                <h6 class="text-muted m-b-0">Superaciones NRD</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 1.4 Dosis Efectiva y Pacientes (ICRP 103 / ISO 27001) -->
            <div class="col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex p-10 no-block">
                            <div class="align-self-center display-6 m-r-20">
                                <i class="text-primary icon-Pulse"></i>
                            </div>
                            <div class="align-slef-center">
                                <h2 class="m-b-0">
                                    <span v-if="loading">...</span>
                                    <span v-else>
                                        {{ kpis.dosis_efectiva_promedio || "0.00" }}
                                        <small class="font-14 text-muted">mSv</small>
                                    </span>
                                </h2>
                                <h6 class="text-muted m-b-0">Dosis Efectiva Media (E)</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- 2. Monitor de Latidos Edge (Dead Man's Switch por Sede) -->
        <!-- ============================================================== -->
        <div class="card m-b-20">
            <div class="card-body p-20">
                <div class="d-flex flex-wrap align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <i class="fa fa-heartbeat text-danger font-20 m-r-10"></i>
                        <div>
                            <h6 class="card-title m-b-0 font-medium">
                                Monitor de Latidos Edge (Dead Man's Switch)
                            </h6>
                            <small class="text-muted">
                                {{ kpis.sedes_online || 0 }} de
                                {{ kpis.sedes_total || sedes.length }} sedes hospitalarias
                                reportando telemetría en tiempo real
                            </small>
                        </div>
                    </div>
                    <div class="m-t-10 m-sm-t-0 d-flex flex-wrap align-items-center">
                        <span
                            v-for="s in sedes"
                            :key="'hb-' + s.id"
                            class="label label-rounded m-r-5 m-b-5"
                            :class="getSedeConexionLabelClass(s.estado_conexion)"
                            :title="getSedeConexionTooltip(s)"
                        >
                            <i
                                class="fa fa-circle font-10 m-r-5"
                                :class="getSedeConexionDotColor(s.estado_conexion)"
                            ></i>
                            {{ s.nom_sede }}
                            <small
                                v-if="s.minutos_sin_contacto !== null"
                                class="m-l-5 font-10 text-muted"
                                >({{ s.minutos_sin_contacto }}m)</small
                            >
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- 3. Alertas Clínicas (NRD y Deriva Técnica) (Nativo AdminWrap) -->
        <!-- ============================================================== -->
        <!-- 3.1 Alertas NRD Críticas -->
        <div v-if="alertas.length > 0" class="alert alert-danger m-b-20">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="card-title text-danger m-b-5">
                <i class="fa fa-exclamation-triangle m-r-10"></i>Alerta de Radioprotección — Se
                detectaron promedios superiores a los Niveles de Referencia para Diagnóstico (NRD)
            </h4>
            <p class="font-14 text-danger m-b-10">
                De acuerdo con la Resolución 482 de 2018, la institución debe revisar los protocolos
                técnicos de adquisición y calibración de los equipos:
            </p>
            <div class="d-flex flex-wrap">
                <span
                    v-for="(al, idx) in alertas"
                    :key="'alert-' + idx"
                    class="label label-danger m-r-5 m-b-5"
                >
                    <strong>{{ al.nom_sede }}</strong> | {{ al.modalidad }} ({{ al.tipo_metrica }}):
                    Promedio {{ al.dosis_promedio }} {{ al.unidad_medida }} (Límite NRD:
                    {{ al.limite_nrd }} {{ al.unidad_medida }})
                </span>
            </div>
        </div>

        <!-- 3.2 Alertas Predictivas de Deriva Técnica de Tubos (Machine Drift) -->
        <div v-if="alertasDeriva && alertasDeriva.length > 0" class="alert alert-warning m-b-20">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="card-title text-warning m-b-5">
                <i class="fa fa-wrench m-r-10"></i>Alerta Predictiva de Deriva Técnica (Machine
                Drift Detection)
            </h4>
            <p class="font-14 text-muted m-b-10">
                Se detectaron equipos emisores con incremento atípico (+20% sobre la línea base
                histórica del periodo), sugiriendo posible descalibración física o fatiga del tubo:
            </p>
            <div class="d-flex flex-wrap">
                <span
                    v-for="(ad, idx) in alertasDeriva"
                    :key="'drift-' + idx"
                    class="label label-warning m-r-5 m-b-5"
                >
                    <strong>{{ ad.equipo_nombre }}</strong> ({{ ad.nom_sede }}) |
                    {{ ad.tipo_metrica }}: +{{ ad.cambio_porcentaje }}% ({{
                        ad.media_inicial
                    }}
                    &rarr; {{ ad.media_reciente }} {{ ad.unidad_medida }})
                </span>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- 4. Filtros Interactivos (Formularios Nativos AdminWrap) -->
        <!-- ============================================================== -->
        <div class="card m-b-20">
            <div class="card-body p-20">
                <div class="row align-items-end">
                    <div class="col-md-3 col-lg-2 m-b-10">
                        <div class="form-group m-b-0">
                            <label class="control-label font-12 m-b-5 text-muted"
                                >Sede Hospitalaria</label
                            >
                            <select
                                v-model="filtros.sede_id"
                                class="form-control form-control-sm custom-select"
                                @change="cargarDatos"
                            >
                                <option value="">Todas las Sedes</option>
                                <option v-for="s in sedes" :key="s.id" :value="s.id">
                                    {{ s.nom_sede }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3 col-lg-2 m-b-10">
                        <div class="form-group m-b-0">
                            <label class="control-label font-12 m-b-5 text-muted">Modalidad</label>
                            <select
                                v-model="filtros.modalidad"
                                class="form-control form-control-sm custom-select"
                                @change="cargarDatos"
                            >
                                <option value="TODAS">Todas (CT, MG, DX)</option>
                                <option value="CT">Tomografía (CT)</option>
                                <option value="MG">Mamografía (MG)</option>
                                <option value="DX">Rayos X Digital (DX)</option>
                                <option value="CR">Rayos X Computarizado (CR)</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3 col-lg-2 m-b-10">
                        <div class="form-group m-b-0">
                            <label class="control-label font-12 m-b-5 text-muted"
                                >Región Anatómica</label
                            >
                            <select
                                v-model="filtros.region_anatomica"
                                class="form-control form-control-sm custom-select"
                                @change="cargarDatos"
                            >
                                <option value="TODAS">Todas las Regiones</option>
                                <option value="CRANEO">Cráneo / Encéfalo</option>
                                <option value="TORAX">Tórax / Cardio</option>
                                <option value="ABDOMEN">Abdomen / Hepático</option>
                                <option value="PELVIS">Pelvis / Urología</option>
                                <option value="COLUMNA">Columna Vertebral</option>
                                <option value="EXTREMIDADES">Extremidades</option>
                                <option value="MAMA">Mama / Senos</option>
                                <option value="GENERAL">General / Otras</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3 col-lg-2 m-b-10">
                        <div class="form-group m-b-0">
                            <label class="control-label font-12 m-b-5 text-muted"
                                >Métrica Dosis</label
                            >
                            <select
                                v-model="filtros.tipo_metrica"
                                class="form-control form-control-sm custom-select"
                                @change="cargarDatos"
                            >
                                <option value="TODAS">Todas las Métricas</option>
                                <option value="CTDIvol">CTDIvol (mGy)</option>
                                <option value="DLP">DLP (mGy·cm)</option>
                                <option value="DGM">DGM (mGy)</option>
                                <option value="DAP">DAP (Gy·cm²)</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3 col-lg-1 m-b-10">
                        <div class="form-group m-b-0">
                            <label class="control-label font-12 m-b-5 text-muted">Desde</label>
                            <input
                                v-model="filtros.fecha_desde"
                                type="date"
                                class="form-control form-control-sm"
                                @change="cargarDatos"
                            />
                        </div>
                    </div>

                    <div class="col-md-3 col-lg-2 m-b-10">
                        <div class="form-group m-b-0">
                            <label class="control-label font-12 m-b-5 text-muted">Hasta</label>
                            <input
                                v-model="filtros.fecha_hasta"
                                type="date"
                                class="form-control form-control-sm"
                                @change="cargarDatos"
                            />
                        </div>
                    </div>

                    <div class="col-md-1 m-b-10 text-right">
                        <button
                            type="button"
                            class="btn btn-sm btn-secondary waves-effect btn-block"
                            title="Limpiar filtros de búsqueda"
                            @click="limpiarFiltros"
                        >
                            <i class="fa fa-eraser"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- 5. Fila 1: Dosis vs Límite NRD (8 cols) & Cumplimiento (4 cols) (AdminWrap index.html & index3.html) -->
        <!-- ============================================================== -->
        <div class="row">
            <!-- 5.1 Gráfica Barras Comparativas por Sede -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex no-block">
                            <div>
                                <h4 class="card-title">
                                    <span class="lstick"></span>Dosis Promedio por Sede vs Límite
                                    NRD
                                </h4>
                                <h6 class="card-subtitle">
                                    Contraste institucional frente a los Niveles de Referencia para
                                    Diagnóstico
                                </h6>
                            </div>
                        </div>
                        <div v-if="loading" class="text-center p-30">
                            <i class="fa fa-spinner fa-spin fa-2x text-info"></i>
                            <p class="text-muted small m-t-10">Cargando telemetría analítica...</p>
                        </div>
                        <div
                            v-else-if="chartDataBarras.labels && chartDataBarras.labels.length > 0"
                            style="height: 320px; position: relative"
                        >
                            <Bar :data="chartDataBarras" :options="chartOptionsBarras" />
                        </div>
                        <div v-else class="text-center p-30 text-muted">
                            <i class="fa fa-info-circle fa-2x m-b-10 text-muted"></i>
                            <p class="m-b-0">
                                No se encontraron registros de dosis para los filtros seleccionados.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5.2 Cumplimiento Normativo NRD (Torta / Donut) -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex no-block">
                            <div>
                                <h4 class="card-title">
                                    <span class="lstick"></span>Cumplimiento NRD
                                </h4>
                                <h6 class="card-subtitle">Resolución 482 de 2018</h6>
                            </div>
                        </div>
                        <div v-if="loading" class="text-center p-30">
                            <i class="fa fa-spinner fa-spin fa-2x text-warning"></i>
                        </div>
                        <div
                            v-else-if="
                                chartDataCumplimiento.labels &&
                                chartDataCumplimiento.labels.length > 0
                            "
                            style="height: 250px; position: relative"
                        >
                            <Doughnut
                                :data="chartDataCumplimiento"
                                :options="chartOptionsCumplimiento"
                            />
                        </div>
                        <div v-else class="text-center p-30 text-muted">
                            <p class="m-b-0">Sin evaluaciones NRD en el periodo.</p>
                        </div>
                        <ul class="list-inline m-t-20 text-center font-12">
                            <li><i class="fa fa-circle text-success"></i> Seguro (&lt;80%)</li>
                            <li><i class="fa fa-circle text-warning"></i> Alerta (&gt;80%)</li>
                            <li><i class="fa fa-circle text-danger"></i> Crítico (&gt;NRD)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- 6. Fila 2: Equipos Emisores (8 cols) & Timeline Auditoría (4 cols) (AdminWrap index.html & index3.html) -->
        <!-- ============================================================== -->
        <div class="row">
            <!-- 6.1 Tabla Equipos Emisores Estilo "Projects of the Month" -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex no-block">
                            <div>
                                <h4 class="card-title">
                                    <span class="lstick"></span>Equipos Emisores y Consolas
                                </h4>
                                <h6 class="card-subtitle">
                                    Carga de trabajo dosimétrica y calibración de tubos en el
                                    periodo
                                </h6>
                            </div>
                        </div>
                        <div class="table-responsive m-t-20 no-wrap">
                            <table class="table vm no-th-brd pro-of-month">
                                <thead>
                                    <tr>
                                        <th colspan="2">Equipo / Consola</th>
                                        <th>Sede</th>
                                        <th>Disparos</th>
                                        <th>Dosis Media</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="eq in equiposRanking" :key="'eq-' + eq.id">
                                        <td style="width: 50px">
                                            <span
                                                class="round"
                                                :class="getRoundClass(eq.modalidad)"
                                                >{{ eq.modalidad }}</span
                                            >
                                        </td>
                                        <td>
                                            <h6>{{ eq.nombre }}</h6>
                                            <small class="text-muted"
                                                >{{ eq.aet_title || "AET Default" }} ·
                                                {{ eq.marca || "Emisor" }}</small
                                            >
                                        </td>
                                        <td>{{ eq.nom_sede }}</td>
                                        <td>
                                            <strong>{{
                                                eq.total_estudios?.toLocaleString() || 0
                                            }}</strong>
                                        </td>
                                        <td>
                                            {{ eq.dosis_promedio }}
                                            <small class="text-muted">{{ eq.unidad_medida }}</small>
                                        </td>
                                        <td>
                                            <span
                                                class="label label-rounded"
                                                :class="eq.badge_class || 'label-success'"
                                                >{{ eq.estado_evaluacion }}</span
                                            >
                                        </td>
                                    </tr>
                                    <tr v-if="equiposRanking.length === 0">
                                        <td colspan="6" class="text-center text-muted p-20">
                                            No hay equipos registrados para los filtros
                                            seleccionados.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 6.2 Historial de Auditoría y Eventos Estilo "Today's Schedule" -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            <span class="lstick"></span>Auditoría y Eventos Clínicos
                        </h4>
                        <h6 class="card-subtitle">
                            Trazabilidad en tiempo real de radioprotección
                        </h6>
                        <div class="steamline m-t-20">
                            <div
                                v-for="(ev, idx) in eventosTimeline"
                                :key="'ev-' + idx"
                                class="sl-item"
                            >
                                <div class="sl-left" :class="ev.clase_bg">
                                    <i :class="ev.icono"></i>
                                </div>
                                <div class="sl-right">
                                    <div class="font-medium">
                                        {{ ev.titulo }} <span class="sl-date">{{ ev.tiempo }}</span>
                                    </div>
                                    <div class="desc small text-muted">{{ ev.descripcion }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- 7. Fila 3: Tendencia Mensual (7 cols) & Distribución Modalidad (5 cols) -->
        <!-- ============================================================== -->
        <div class="row">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex no-block">
                            <div>
                                <h4 class="card-title">
                                    <span class="lstick"></span>Tendencia Mensual de Dosis
                                </h4>
                                <h6 class="card-subtitle">Evolución cronológica institucional</h6>
                            </div>
                        </div>
                        <div v-if="loading" class="text-center p-30">
                            <i class="fa fa-spinner fa-spin fa-2x text-success"></i>
                            <p class="text-muted small m-t-10">Calculando tendencias...</p>
                        </div>
                        <div
                            v-else-if="chartDataLineas.labels && chartDataLineas.labels.length > 0"
                            style="height: 280px; position: relative"
                        >
                            <Line :data="chartDataLineas" :options="chartOptionsLineas" />
                        </div>
                        <div v-else class="text-center p-30 text-muted">
                            <i class="fa fa-info-circle fa-2x m-b-10 text-muted"></i>
                            <p class="m-b-0">Sin datos de tendencia para el rango actual.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex no-block">
                            <div>
                                <h4 class="card-title">
                                    <span class="lstick"></span>Distribución por Modalidad
                                </h4>
                                <h6 class="card-subtitle">Estudios registrados en el periodo</h6>
                            </div>
                        </div>
                        <div v-if="loading" class="text-center p-30">
                            <i class="fa fa-spinner fa-spin fa-2x text-info"></i>
                        </div>
                        <div
                            v-else-if="
                                chartDataModalidades.labels &&
                                chartDataModalidades.labels.length > 0
                            "
                            style="height: 280px; position: relative"
                        >
                            <Doughnut
                                :data="chartDataModalidades"
                                :options="chartOptionsModalidades"
                            />
                        </div>
                        <div v-else class="text-center p-30 text-muted">
                            <i class="fa fa-info-circle fa-2x m-b-10 text-muted"></i>
                            <p class="m-b-0">Sin datos de modalidades para el rango actual.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- 8. Fila 4: Tabla de Últimas Mediciones Dosimétricas Recibidas (Edge) -->
        <!-- ============================================================== -->
        <div class="card m-b-20">
            <div class="card-body p-20">
                <div class="d-flex no-block">
                    <div>
                        <h4 class="card-title">
                            <span class="lstick"></span>Últimas Mediciones de Dosis Recibidas (Edge)
                        </h4>
                        <h6 class="card-subtitle">
                            Muestreo reciente de telemetría anonimizada (HMAC-SHA256)
                        </h6>
                    </div>
                    <div class="ml-auto">
                        <span class="label label-rounded label-info">Últimos 50 estudios</span>
                    </div>
                </div>

                <!-- Skeleton Loader durante la carga -->
                <table-skeleton-loader :visible="loading" :rows="6" :columns="9" />

                <!-- Tabla de datos -->
                <div v-if="!loading && ultimosRegistros.length > 0" class="table-responsive m-t-20">
                    <table class="table table-hover vm">
                        <thead>
                            <tr>
                                <th>Fecha Estudio</th>
                                <th>Sede</th>
                                <th>Equipo / AET</th>
                                <th>Modalidad</th>
                                <th>Región</th>
                                <th>Métrica</th>
                                <th>Valor Dosis</th>
                                <th>Patient ID (Hash)</th>
                                <th class="text-center">Estado NRD</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="reg in ultimosRegistros" :key="reg.id">
                                <td class="text-nowrap font-12">
                                    {{ formatearFecha(reg.fecha_estudio) }}
                                </td>
                                <td>
                                    <strong>{{ reg.nom_sede }}</strong>
                                </td>
                                <td>
                                    <span class="label label-light-inverse">{{
                                        reg.equipo_nombre
                                    }}</span>
                                </td>
                                <td>
                                    <span :class="getModalidadBadgeClass(reg.modalidad)">{{
                                        reg.modalidad
                                    }}</span>
                                </td>
                                <td>
                                    <span :class="getRegionBadgeClass(reg.region_anatomica)">
                                        {{ reg.region_anatomica || "GENERAL" }}
                                    </span>
                                    <span
                                        v-if="reg.es_rdsr"
                                        class="label label-info m-l-5"
                                        title="Objeto DICOM Radiation Dose Structured Report (RDSR PS3.16)"
                                    >
                                        <i class="fa fa-certificate m-r-5"></i>RDSR
                                    </span>
                                </td>
                                <td>
                                    <span class="font-medium">{{ reg.tipo_metrica }}</span>
                                </td>
                                <td>
                                    <strong class="font-14">{{ reg.valor_dosis }}</strong>
                                    <small class="text-muted m-l-5">{{ reg.unidad_medida }}</small>
                                </td>
                                <td>
                                    <code class="text-muted small" :title="reg.patient_id_hashed">
                                        {{ reg.patient_id_hashed.substring(0, 10) }}...
                                    </code>
                                </td>
                                <td class="text-center">
                                    <span :class="getEstadoNrdClass(reg)">
                                        {{ getEstadoNrdLabel(reg) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State si no hay registros -->
                <div v-if="!loading && ultimosRegistros.length === 0" class="p-30 text-center">
                    <empty-state-component
                        title="Sin registros de dosimetría"
                        message="No se han recibido telemetrías de radiación para los filtros seleccionados."
                    />
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- 9. Modal Catálogo de Umbrales NRD Regulatorios (Res. 482 de 2018) -->
        <!-- ============================================================== -->
        <div
            v-if="mostrarModalUmbrales"
            class="modal fade show"
            tabindex="-1"
            role="dialog"
            style="display: block; background: rgba(0, 0, 0, 0.5)"
        >
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">
                            <i class="fa fa-sliders text-warning m-r-10"></i>Niveles de Referencia
                            para Diagnóstico (Resolución 482 de 2018)
                        </h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            @click="mostrarModalUmbrales = false"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted">
                            Los NRD constituyen una herramienta de optimización de la protección
                            radiológica médica (IAEA/ICRP). Cuando los valores promedio
                            institucionales superan de forma reiterada estos límites, se requiere
                            auditoría médica.
                        </p>
                        <div class="table-responsive m-t-20">
                            <table class="table table-bordered table-striped vm">
                                <thead>
                                    <tr>
                                        <th>Modalidad</th>
                                        <th>Métrica</th>
                                        <th>Nivel Alerta (Preventivo)</th>
                                        <th>Límite NRD Regulatorio</th>
                                        <th>Unidad</th>
                                        <th>Descripción / Procedimiento</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="u in umbralesNrd" :key="u.id">
                                        <td>
                                            <span class="label label-inverse">{{
                                                u.modalidad
                                            }}</span>
                                        </td>
                                        <td>
                                            <strong>{{ u.tipo_metrica }}</strong>
                                        </td>
                                        <td class="text-warning font-bold">{{ u.valor_alerta }}</td>
                                        <td class="text-danger font-bold font-14">
                                            {{ u.valor_limite_nrd }}
                                        </td>
                                        <td>{{ u.unidad_medida }}</td>
                                        <td class="small">{{ u.descripcion }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-secondary waves-effect"
                            @click="mostrarModalUmbrales = false"
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
/**
 * Componente Analítico de Dosimetría y Niveles de Referencia para Diagnóstico (NRD).
 * Cumple con la Resolución 482 de 2018 (Colombia), ICRP 103 e ISO/IEC 27001.
 * Maquetado con los estilos nativos de AdminWrap (index.html, index2.html, index3.html).
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-03
 */

import { ref, reactive, computed, onMounted, onBeforeUnmount, getCurrentInstance } from "vue";
import axios from "axios";
import { Bar, Line, Doughnut } from "vue-chartjs";
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    ArcElement,
} from "chart.js";

// Registrar componentes de Chart.js
ChartJS.register(
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    ArcElement
);

const { appContext } = getCurrentInstance() || {};
const emitter = appContext?.config?.globalProperties?.emitter;

// Estado reactivo
const loading = ref(false);
const exportando = ref(false);
const mostrarModalUmbrales = ref(false);

const filtros = reactive({
    sede_id: "",
    modalidad: "TODAS",
    tipo_metrica: "TODAS",
    region_anatomica: "TODAS",
    fecha_desde: "",
    fecha_hasta: "",
});

const kpis = ref({
    total_estudios: 0,
    total_equipos: 0,
    total_alertas: 0,
    total_derivas: 0,
    total_pacientes: 0,
    dosis_efectiva_promedio: "0.00",
    tasa_conformidad: 100,
    sedes_online: 0,
    sedes_total: 0,
});

const consolidado = ref([]);
const alertas = ref([]);
const alertasDeriva = ref([]);
const equiposRanking = ref([]);
const eventosTimeline = ref([]);
const ultimosRegistros = ref([]);
const umbralesNrd = ref([]);
const sedes = ref([]);

// Mapa reactivo de umbrales para lookup rápido
const mapaUmbrales = computed(() => {
    const map = {};
    umbralesNrd.value.forEach((u) => {
        map[`${u.modalidad}_${u.tipo_metrica}`] = u;
    });
    return map;
});

// Helper de clases nativas de AdminWrap para equipos ("Projects of the Month")
function getRoundClass(mod) {
    switch (mod) {
        case "CT":
            return "round-primary";
        case "MG":
            return "round-danger";
        case "DX":
        case "CR":
            return "round-info";
        default:
            return "round-success";
    }
}

// Helpers de conexión de sedes (Estilo nativo AdminWrap)
function getSedeConexionLabelClass(estado) {
    switch (estado) {
        case "ONLINE":
            return "label-success";
        case "DELAYED":
            return "label-warning";
        default:
            return "label-danger";
    }
}

function getSedeConexionDotColor(estado) {
    switch (estado) {
        case "ONLINE":
            return "text-success";
        case "DELAYED":
            return "text-warning";
        default:
            return "text-danger";
    }
}

function getSedeConexionTooltip(sede) {
    if (!sede) return "";
    const estado = sede.estado_conexion || "OFFLINE";
    const mins =
        sede.minutos_sin_contacto !== null && sede.minutos_sin_contacto !== undefined
            ? `${sede.minutos_sin_contacto} min`
            : "Sin contacto";
    const ult = sede.ultimo_contacto || "Nunca";
    const version = sede.version_agente ? ` | v${sede.version_agente}` : "";
    const pend = sede.pendientes !== undefined ? ` | ${sede.pendientes} pendientes` : "";
    return `Estado: ${estado} (${mins}) | Último latido: ${ult}${version}${pend}`;
}

// Helper de clases de modalidad (Nativo AdminWrap: label)
function getModalidadBadgeClass(mod) {
    switch (mod) {
        case "CT":
            return "label label-primary";
        case "MG":
            return "label label-danger";
        case "DX":
        case "CR":
            return "label label-info";
        default:
            return "label label-inverse";
    }
}

// Helper de clases de región anatómica (Nativo AdminWrap: label-light-*)
function getRegionBadgeClass(region) {
    switch (region) {
        case "CRANEO":
            return "label label-light-primary";
        case "TORAX":
            return "label label-light-info";
        case "ABDOMEN":
            return "label label-light-success";
        case "PELVIS":
            return "label label-light-warning";
        case "COLUMNA":
            return "label label-light-inverse";
        case "EXTREMIDADES":
            return "label label-inverse";
        case "MAMA":
            return "label label-light-danger";
        default:
            return "label label-light-inverse";
    }
}

// Helper de estado NRD para filas individuales
function getEstadoNrdClass(reg) {
    const umbral = mapaUmbrales.value[`${reg.modalidad}_${reg.tipo_metrica}`];
    if (!umbral) return "label label-rounded label-success";

    const valor = parseFloat(reg.valor_dosis);
    if (valor >= parseFloat(umbral.valor_limite_nrd)) {
        return "label label-rounded label-danger";
    }
    if (valor >= parseFloat(umbral.valor_alerta)) {
        return "label label-rounded label-warning";
    }
    return "label label-rounded label-success";
}

function getEstadoNrdLabel(reg) {
    const umbral = mapaUmbrales.value[`${reg.modalidad}_${reg.tipo_metrica}`];
    if (!umbral) return "NORMAL";

    const valor = parseFloat(reg.valor_dosis);
    if (valor >= parseFloat(umbral.valor_limite_nrd)) {
        return "SUPERÓ NRD";
    }
    if (valor >= parseFloat(umbral.valor_alerta)) {
        return "ADVERTENCIA";
    }
    return "SEGURO";
}

function formatearFecha(fechaStr) {
    if (!fechaStr) return "-";
    const d = new Date(fechaStr);
    return isNaN(d.getTime())
        ? fechaStr
        : d.toLocaleDateString("es-CO", {
              year: "numeric",
              month: "short",
              day: "2-digit",
              hour: "2-digit",
              minute: "2-digit",
          });
}

// Configuración de Chart.js para Barras Comparativas por Sede
const chartDataBarras = computed(() => {
    const sedesMap = {};
    consolidado.value.forEach((c) => {
        if (!sedesMap[c.nom_sede]) {
            sedesMap[c.nom_sede] = { total: 0, count: 0, maxLimite: 0 };
        }
        sedesMap[c.nom_sede].total += parseFloat(c.dosis_promedio || 0);
        sedesMap[c.nom_sede].count += 1;
        if (c.limite_nrd) {
            sedesMap[c.nom_sede].maxLimite = Math.max(
                sedesMap[c.nom_sede].maxLimite,
                parseFloat(c.limite_nrd)
            );
        }
    });

    const labels = Object.keys(sedesMap);
    const dataPromedios = labels.map((l) =>
        sedesMap[l].count > 0 ? (sedesMap[l].total / sedesMap[l].count).toFixed(2) : 0
    );
    const dataLimites = labels.map((l) => sedesMap[l].maxLimite || null);

    return {
        labels,
        datasets: [
            {
                label: "Dosis Promedio Registrada",
                backgroundColor: "#0284c7",
                borderColor: "#0369a1",
                borderWidth: 1,
                data: dataPromedios,
            },
            {
                label: "Nivel de Referencia (NRD Máximo)",
                backgroundColor: "rgba(239, 68, 68, 0.35)",
                borderColor: "#dc2626",
                borderWidth: 2,
                type: "line",
                data: dataLimites,
            },
        ],
    };
});

const chartOptionsBarras = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: "top",
        },
        tooltip: {
            callbacks: {
                label: function (context) {
                    return ` ${context.dataset.label}: ${context.parsed.y}`;
                },
            },
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            title: {
                display: true,
                text: "Magnitud Dosimétrica (mGy / mGy·cm / Gy·cm²)",
            },
        },
    },
};

// Configuración de Chart.js para Tendencia Mensual de Dosis (Líneas)
const chartDataLineas = computed(() => {
    const meses = [...new Set(consolidado.value.map((c) => c.mes_periodo))].sort();
    if (meses.length === 0) {
        return { labels: [], datasets: [] };
    }

    const modalidades = ["CT", "DX", "MG", "CR"];
    const colors = {
        CT: "#0284c7",
        DX: "#06b6d4",
        MG: "#ec4899",
        CR: "#3b82f6",
    };

    const datasets = modalidades
        .map((mod) => {
            const data = meses.map((mes) => {
                const registrosMes = consolidado.value.filter(
                    (c) => c.mes_periodo === mes && c.modalidad === mod
                );
                if (registrosMes.length === 0) return null;
                const suma = registrosMes.reduce(
                    (acc, curr) => acc + parseFloat(curr.dosis_promedio || 0),
                    0
                );
                return (suma / registrosMes.length).toFixed(2);
            });

            if (data.every((v) => v === null)) return null;

            return {
                label: `Modalidad ${mod}`,
                borderColor: colors[mod] || "#64748b",
                backgroundColor: colors[mod] || "#64748b",
                data,
                tension: 0.3,
                fill: false,
                spanGaps: true,
            };
        })
        .filter(Boolean);

    return {
        labels: meses,
        datasets,
    };
});

const chartOptionsLineas = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: "top",
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            title: {
                display: true,
                text: "Dosis Promedio Mensual",
            },
        },
    },
};

// Configuración de Chart.js para Distribución por Modalidad (Doughnut)
const chartDataModalidades = computed(() => {
    const modMap = {};
    consolidado.value.forEach((c) => {
        const mod = c.modalidad || "OTRA";
        modMap[mod] = (modMap[mod] || 0) + parseInt(c.total_estudios || 0, 10);
    });

    const keys = Object.keys(modMap);
    if (keys.length === 0) {
        return { labels: [], datasets: [] };
    }

    const labelMap = {
        CT: "Tomografía (CT)",
        DX: "Rayos X Digital (DX)",
        CR: "Rayos X Comp. (CR)",
        MG: "Mamografía (MG)",
    };

    const colorMap = {
        CT: "#0284c7",
        DX: "#06b6d4",
        CR: "#3b82f6",
        MG: "#ec4899",
    };

    const labels = keys.map((k) => labelMap[k] || k);
    const data = keys.map((k) => modMap[k]);
    const backgroundColor = keys.map((k) => colorMap[k] || "#8b5cf6");

    return {
        labels,
        datasets: [
            {
                data,
                backgroundColor,
                borderWidth: 2,
                borderColor: "#ffffff",
                hoverOffset: 6,
            },
        ],
    };
});

const chartOptionsModalidades = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: "bottom",
            labels: {
                boxWidth: 12,
                padding: 12,
                font: { size: 11 },
            },
        },
        tooltip: {
            callbacks: {
                label: function (context) {
                    const label = context.label || "";
                    const value = context.parsed || 0;
                    const dataset = context.dataset;
                    const total = dataset.data.reduce((acc, curr) => acc + curr, 0);
                    const pct = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                    return ` ${label}: ${value.toLocaleString()} estudios (${pct}%)`;
                },
            },
        },
    },
    cutout: "60%",
};

// Configuración de Chart.js para Cumplimiento Normativo NRD (Doughnut)
const chartDataCumplimiento = computed(() => {
    let conformes = 0;
    let alertasPreventivas = 0;
    let superaciones = 0;

    consolidado.value.forEach((c) => {
        const total = parseInt(c.total_estudios || 0, 10);
        if (c.supera_nrd) {
            superaciones += total;
        } else if (c.supera_alerta) {
            alertasPreventivas += total;
        } else {
            conformes += total;
        }
    });

    const totalGeneral = conformes + alertasPreventivas + superaciones;
    if (totalGeneral === 0) {
        return { labels: [], datasets: [] };
    }

    return {
        labels: [
            "Conforme (Rango Seguro)",
            "Alerta Preventiva (>80% NRD)",
            "Superación NRD Crítica",
        ],
        datasets: [
            {
                data: [conformes, alertasPreventivas, superaciones],
                backgroundColor: ["#10b981", "#f59e0b", "#ef4444"],
                borderWidth: 2,
                borderColor: "#ffffff",
                hoverOffset: 6,
            },
        ],
    };
});

const chartOptionsCumplimiento = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            callbacks: {
                label: function (context) {
                    const label = context.label || "";
                    const value = context.parsed || 0;
                    const dataset = context.dataset;
                    const total = dataset.data.reduce((acc, curr) => acc + curr, 0);
                    const pct = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                    return ` ${label}: ${value.toLocaleString()} estudios (${pct}%)`;
                },
            },
        },
    },
    cutout: "65%",
};

// Carga de datos desde API
async function cargarDatos() {
    loading.value = true;
    try {
        const params = {};
        if (filtros.sede_id) params.sede_id = filtros.sede_id;
        if (filtros.modalidad && filtros.modalidad !== "TODAS")
            params.modalidad = filtros.modalidad;
        if (filtros.tipo_metrica && filtros.tipo_metrica !== "TODAS")
            params.tipo_metrica = filtros.tipo_metrica;
        if (filtros.region_anatomica && filtros.region_anatomica !== "TODAS")
            params.region_anatomica = filtros.region_anatomica;
        if (filtros.fecha_desde) params.fecha_desde = filtros.fecha_desde;
        if (filtros.fecha_hasta) params.fecha_hasta = filtros.fecha_hasta;

        const res = await axios.get("/api/dosimetria/dashboard", { params });
        const resData = res.data?.data || res.data || {};

        consolidado.value = resData.consolidado || [];
        alertas.value = resData.alertas || [];
        alertasDeriva.value = resData.alertas_deriva || [];
        equiposRanking.value = resData.equipos_ranking || [];
        eventosTimeline.value = resData.eventos_timeline || [];
        ultimosRegistros.value = resData.ultimos_registros || [];
        umbralesNrd.value = resData.umbrales_nrd || [];
        sedes.value = resData.sedes || [];
        kpis.value = resData.kpis || {
            total_estudios: 0,
            total_equipos: 0,
            total_alertas: 0,
            total_derivas: 0,
            total_pacientes: 0,
            dosis_efectiva_promedio: "0.00",
            tasa_conformidad: 100,
            sedes_online: 0,
            sedes_total: 0,
        };
    } catch (error) {
        console.error("Error al cargar telemetría de dosimetría:", error);
    } finally {
        loading.value = false;
    }
}

function limpiarFiltros() {
    filtros.sede_id = "";
    filtros.modalidad = "TODAS";
    filtros.tipo_metrica = "TODAS";
    filtros.region_anatomica = "TODAS";
    filtros.fecha_desde = "";
    filtros.fecha_hasta = "";
    cargarDatos();
}

/**
 * Exporta el informe analítico de dosimetría y registros individuales a Excel (.xlsx).
 *
 * Flujo del proceso:
 * 1. Empaqueta los filtros activos (sede, modalidad, métrica, rango de fechas).
 * 2. Abre en pestaña nueva la ruta /api/dosis/exportar para streaming de descarga.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-26
 */
function exportarExcel() {
    const params = new URLSearchParams();
    if (filtros.sede_id) params.append("sede_id", filtros.sede_id);
    if (filtros.modalidad && filtros.modalidad !== "TODAS")
        params.append("modalidad", filtros.modalidad);
    if (filtros.tipo_metrica && filtros.tipo_metrica !== "TODAS")
        params.append("tipo_metrica", filtros.tipo_metrica);
    if (filtros.region_anatomica && filtros.region_anatomica !== "TODAS")
        params.append("region_anatomica", filtros.region_anatomica);
    if (filtros.fecha_desde) params.append("fecha_desde", filtros.fecha_desde);
    if (filtros.fecha_hasta) params.append("fecha_hasta", filtros.fecha_hasta);

    window.open(`/api/dosis/exportar?${params.toString()}`, "_blank");
}

// Sincronización reactiva con EventBus global
function onSedeSeleccionada(datosSede) {
    if (!datosSede) return;
    const id = typeof datosSede === "object" ? datosSede.idSede || datosSede.id : datosSede;
    if (id && filtros.sede_id !== id) {
        filtros.sede_id = id;
        cargarDatos();
    }
}

onMounted(() => {
    // Sincronizar sede guardada en sesión si existe
    const sedeGuardada = sessionStorage.getItem("ST-sede");
    if (sedeGuardada) {
        filtros.sede_id = sedeGuardada;
    }

    if (emitter) {
        emitter.on("sedeSeleccionada", onSedeSeleccionada);
    }

    cargarDatos();
});

onBeforeUnmount(() => {
    if (emitter) {
        emitter.off("sedeSeleccionada", onSedeSeleccionada);
    }
});
</script>
