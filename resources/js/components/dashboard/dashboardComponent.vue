<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Dashboard</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->

        <div v-if="alerts.length" class="row">
            <div class="col-12">
                <div
                    v-for="(a, idx) in alerts"
                    :key="idx"
                    class="alert"
                    :class="alertClass(a.level)"
                >
                    <strong>{{ a.title }}:</strong> {{ a.message }}
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body p-b-0">
                        <div class="d-flex no-block">
                            <div>
                                <h4 class="card-title m-b-0">Filtros</h4>
                                <small class="text-muted"
                                    >Afectan todas las métricas del dashboard</small
                                >
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-3 col-md-6 m-b-10">
                                <label class="text-muted m-b-5">Sede</label>
                                <select
                                    v-model.number="filters.selected.sede_id"
                                    class="custom-select"
                                    @change="fetchDashboard"
                                >
                                    <option :value="null">Todas</option>
                                    <option
                                        v-for="s in filters.options.sedes"
                                        :key="s.id"
                                        :value="s.id"
                                    >
                                        {{ s.nom_sede }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6 m-b-10">
                                <label class="text-muted m-b-5">Modalidad</label>
                                <select
                                    v-model="filters.selected.modalidad"
                                    class="custom-select"
                                    @change="fetchDashboard"
                                >
                                    <option :value="null">Todas</option>
                                    <option
                                        v-for="m in filters.options.modalidades"
                                        :key="m"
                                        :value="m"
                                    >
                                        {{ m }}
                                    </option>
                                </select>
                            </div>
                            <div v-if="isAdmin" class="col-lg-4 col-md-6 m-b-10">
                                <label class="text-muted m-b-5">Médico</label>
                                <select
                                    v-model.number="filters.selected.medico_id"
                                    class="custom-select"
                                    @change="fetchDashboard"
                                >
                                    <option :value="null">Todos</option>
                                    <option
                                        v-for="u in filters.options.medicos"
                                        :key="u.id"
                                        :value="u.id"
                                    >
                                        {{ u.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6 m-b-10">
                                <label class="text-muted m-b-5">Rango</label>
                                <select
                                    v-model.number="days"
                                    class="custom-select"
                                    @change="fetchDashboard"
                                >
                                    <option :value="7">7 días</option>
                                    <option :value="30">30 días</option>
                                    <option :value="90">90 días</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- Operación (hoy) -->
        <!-- ============================================================== -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body p-b-0">
                        <div class="d-flex no-block">
                            <div>
                                <h4 class="card-title m-b-0">Operación (hoy)</h4>
                                <small class="text-muted">Resumen en tiempo real</small>
                            </div>
                            <div class="ml-auto">
                                <small class="text-muted">Rol: {{ rolesusuario }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-2 col-md-4 col-6 m-b-15">
                                <div class="d-flex">
                                    <div class="m-r-10">
                                        <i class="text-info icon-Target-Market"></i>
                                    </div>
                                    <div>
                                        <h4 class="m-b-0">
                                            <span v-if="loading">...</span>
                                            <span v-else>{{ operacion.asignadosHoy }}</span>
                                        </h4>
                                        <small class="text-muted">Asignados hoy</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4 col-6 m-b-15">
                                <div class="d-flex">
                                    <div class="m-r-10">
                                        <i class="text-warning icon-Inbox-Forward"></i>
                                    </div>
                                    <div>
                                        <h4 class="m-b-0">
                                            <span v-if="loading">...</span>
                                            <span v-else>{{ operacion.pendientesLectura }}</span>
                                        </h4>
                                        <small class="text-muted">Pend. lectura</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4 col-6 m-b-15">
                                <div class="d-flex">
                                    <div class="m-r-10"><i class="text-success icon-Yes"></i></div>
                                    <div>
                                        <h4 class="m-b-0">
                                            <span v-if="loading">...</span>
                                            <span v-else>{{ operacion.leidosHoy }}</span>
                                        </h4>
                                        <small class="text-muted">Leídos hoy</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4 col-6 m-b-15">
                                <div class="d-flex">
                                    <div class="m-r-10">
                                        <i class="text-danger icon-File-HorizontalText"></i>
                                    </div>
                                    <div>
                                        <h4 class="m-b-0">
                                            <span v-if="loading">...</span>
                                            <span v-else>{{
                                                operacion.pendientesTranscripcion
                                            }}</span>
                                        </h4>
                                        <small class="text-muted">Pend. transcripción</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4 col-6 m-b-15">
                                <div class="d-flex">
                                    <div class="m-r-10">
                                        <i class="text-primary icon-Receipt-4"></i>
                                    </div>
                                    <div>
                                        <h4 class="m-b-0">
                                            <span v-if="loading">...</span>
                                            <span v-else>{{ operacion.transcritosHoy }}</span>
                                        </h4>
                                        <small class="text-muted">Transcritos hoy</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4 col-6 m-b-15">
                                <div class="d-flex">
                                    <div class="m-r-10">
                                        <i class="text-muted icon-Time-Clock"></i>
                                    </div>
                                    <div>
                                        <h4 class="m-b-0">
                                            <span v-if="loading">...</span>
                                            <span v-else>{{ operacion.pendientesLecturaHoy }}</span>
                                        </h4>
                                        <small class="text-muted">Pend. lectura (hoy)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="error" class="alert alert-danger m-b-0">
                            {{ error }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- Info box (totales) -->
        <!-- ============================================================== -->
        <div class="row">
            <!-- Column -->
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
                                    <span v-else>{{ indicadores.estudiosAsignados }}</span>
                                    <small>
                                        <i class="ti-angle-down text-danger"></i>
                                    </small>
                                </h2>
                                <h6 class="text-muted m-b-0">Total estudios asignados</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Column -->
            <!-- Column -->
            <div class="col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex p-10 no-block">
                            <div class="align-self-center display-6 m-r-20">
                                <i class="text-primary icon-Inbox-Forward"></i>
                            </div>
                            <div class="align-slef-center">
                                <h2 class="m-b-0">
                                    <span v-if="loading">...</span>
                                    <span v-else>{{ indicadores.estudiosLeidos }}</span>
                                    <small>
                                        <i class="ti-angle-up text-success"></i>
                                    </small>
                                </h2>
                                <h6 class="text-muted m-b-0">Total de estudios leidos</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Column -->
            <!-- Column -->
            <template v-if="isAdmin">
                <div class="col-lg-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex p-10 no-block">
                                <div class="align-self-center display-6 m-r-20">
                                    <i class="text-info icon-Dollar-Sign"></i>
                                </div>
                                <div class="align-slef-center">
                                    <h2 class="m-b-0">
                                        <span v-if="loading">...</span>
                                        <span v-else>{{ indicadores.estudiosPorTranscribir }}</span>
                                        <small>
                                            <i class="ti-angle-up text-success"></i>
                                        </small>
                                    </h2>
                                    <h6 class="text-muted m-b-0">Total estudios por transcribir</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Column -->

                <!-- Column -->
                <div class="col-lg-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex p-10 no-block">
                                <div class="align-self-center display-6 m-r-20">
                                    <i class="text-danger icon-Contrast"></i>
                                </div>
                                <div class="align-slef-center">
                                    <h2 class="m-b-0">
                                        <span v-if="loading">...</span>
                                        <span v-else>{{ indicadores.estudiosDescargados }}</span>
                                        <small><i class="ti-angle-down text-danger"></i> </small>
                                    </h2>
                                    <h6 class="text-muted m-b-0">Total de estudios descargados</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <!-- Column -->
            <!-- Column -->
        </div>
        <!-- ============================================================== -->
        <!-- End Info box -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- A) Operación + C) Calidad -->
        <!-- ============================================================== -->
        <div class="row">
            <!-- Column -->
            <div class="col-lg-4 col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex m-b-30 no-block">
                            <h5 class="card-title m-b-0 align-self-center">Modalidades (Top)</h5>
                            <div class="ml-auto">
                                <small class="text-muted">Actualizado</small>
                            </div>
                        </div>
                        <div id="visitor" style="height: 260px; width: 100%"></div>
                        <div v-if="loading" class="text-center text-muted m-t-10">Cargando...</div>
                    </div>
                </div>
            </div>
            <!-- Column -->
            <div class="col-lg-4 col-md-12">
                <div class="card o-income">
                    <div class="card-body">
                        <div class="d-flex m-b-30 no-block">
                            <h5 class="card-title m-b-0 align-self-center">Resumen</h5>
                            <div class="ml-auto">
                                <small class="text-muted">Transcritos vs Pendientes</small>
                            </div>
                        </div>
                        <div id="income" style="height: 260px; width: 100%"></div>
                        <div v-if="loading" class="text-center text-muted m-t-10">Cargando...</div>
                    </div>
                </div>
            </div>
            <!-- Column -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Estudios más solicitados</h5>
                        <table class="table browser m-t-30 no-border">
                            <tbody>
                                <tr
                                    v-for="item in estudiosMasSolicitados"
                                    :key="item.mods_in_study"
                                >
                                    <td>{{ item.mods_in_study }}</td>
                                    <td class="text-right">{{ item.total }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <div
                            v-if="!loading && estudiosMasSolicitados.length === 0"
                            class="text-muted"
                        >
                            Sin datos para mostrar.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4 col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex m-b-10 no-block">
                            <h5 class="card-title m-b-0 align-self-center">Aging pend. lectura</h5>
                            <div class="ml-auto">
                                <small class="text-muted">0-2h / 2-6h / 6-24h / 24h+</small>
                            </div>
                        </div>
                        <div id="aging-lectura" style="height: 220px; width: 100%"></div>
                        <div v-if="loading" class="text-center text-muted m-t-10">Cargando...</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex m-b-10 no-block">
                            <h5 class="card-title m-b-0 align-self-center">
                                Aging pend. transcripción
                            </h5>
                            <div class="ml-auto">
                                <small class="text-muted">Desde lectura</small>
                            </div>
                        </div>
                        <div id="aging-transcripcion" style="height: 220px; width: 100%"></div>
                        <div v-if="loading" class="text-center text-muted m-t-10">Cargando...</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex m-b-10 no-block">
                            <h5 class="card-title m-b-0 align-self-center">Tiempos promedio</h5>
                            <div class="ml-auto">
                                <small class="text-muted">Últimos {{ days }} días</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <h6 class="text-muted m-b-0">Asignado → Lectura</h6>
                                <h3 class="m-b-0">
                                    <span v-if="loading">...</span>
                                    <span v-else>{{
                                        formatMin(calidad.tiempos.asignadoALectura?.avgMin)
                                    }}</span>
                                </h3>
                                <small class="text-muted"
                                    >muestras: {{ calidad.tiempos.asignadoALectura?.n || 0 }}</small
                                >
                            </div>
                            <div class="col-6">
                                <h6 class="text-muted m-b-0">Lectura → Transcrito</h6>
                                <h3 class="m-b-0">
                                    <span v-if="loading">...</span>
                                    <span v-else>{{
                                        formatMin(calidad.tiempos.lecturaATranscrito?.avgMin)
                                    }}</span>
                                </h3>
                                <small class="text-muted"
                                    >muestras:
                                    {{ calidad.tiempos.lecturaATranscrito?.n || 0 }}</small
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex no-block">
                            <div>
                                <h5 class="card-title m-b-0">Cumplimiento de tiempos (Lectura)</h5>
                                <small class="text-muted"
                                    >Por prioridad (últimos {{ days }} días)</small
                                >
                            </div>
                        </div>
                        <div id="sla-lectura" style="height: 260px; width: 100%"></div>
                        <div v-if="loading" class="text-center text-muted m-t-10">Cargando...</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex no-block">
                            <div>
                                <h5 class="card-title m-b-0">
                                    Cumplimiento de tiempos (Transcripción)
                                </h5>
                                <small class="text-muted"
                                    >Por prioridad (últimos {{ days }} días)</small
                                >
                            </div>
                        </div>
                        <div id="sla-transcripcion" style="height: 260px; width: 100%"></div>
                        <div v-if="loading" class="text-center text-muted m-t-10">Cargando...</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex no-block">
                            <div>
                                <h5 class="card-title m-b-0">Heatmap (últimos 7 días)</h5>
                                <small class="text-muted">Hora × Día</small>
                            </div>
                            <div class="ml-auto d-flex align-items-center" style="gap: 8px">
                                <small class="text-muted">Métrica</small>
                                <select
                                    v-model="heatmapMetric"
                                    class="custom-select custom-select-sm"
                                    style="width: 160px"
                                >
                                    <option value="transcritos">Transcritos</option>
                                    <option value="leidos">Leídos</option>
                                    <option value="asignados">Asignados</option>
                                </select>
                            </div>
                        </div>

                        <div class="table-responsive m-t-10">
                            <table class="table table-sm table-bordered m-b-0">
                                <thead>
                                    <tr>
                                        <th style="min-width: 60px">Hora</th>
                                        <th
                                            v-for="d in heatmapDays"
                                            :key="d"
                                            class="text-center"
                                            style="min-width: 90px"
                                        >
                                            {{ d }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="h in 24" :key="h">
                                        <td class="text-center font-500">{{ h - 1 }}</td>
                                        <td
                                            v-for="d in heatmapDays"
                                            :key="`${d}-${h - 1}`"
                                            class="text-center"
                                            :title="`${d} ${h - 1}:00 → ${heatmapValue(d, h - 1)}`"
                                            :style="{
                                                backgroundColor: heatmapColor(
                                                    heatmapValue(d, h - 1),
                                                    heatmapMax
                                                ),
                                            }"
                                        >
                                            {{ heatmapValue(d, h - 1) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-if="loading" class="text-center text-muted m-t-10">Cargando...</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Pendientes de lectura (Top 10)</h5>
                        <div class="table-responsive">
                            <table class="table table-sm m-b-0">
                                <thead>
                                    <tr>
                                        <th>Paciente</th>
                                        <th>Sede</th>
                                        <th>Accession</th>
                                        <th>Mod</th>
                                        <th>Acción</th>
                                        <th class="text-right">Edad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in tablas.pendientesLecturaTop" :key="row.id">
                                        <td>
                                            <div class="font-500">
                                                {{ formatPacienteNombre(row) }}
                                            </div>
                                            <small class="text-muted">{{
                                                row.paciente_num_docu || ""
                                            }}</small>
                                        </td>
                                        <td>{{ row.sede || "" }}</td>
                                        <td>{{ row.accession_no || row.study_id || row.id }}</td>
                                        <td>{{ row.mods_in_study }}</td>
                                        <td>
                                            <button
                                                class="btn btn-sm btn-info"
                                                type="button"
                                                @click="goToLectura(row)"
                                            >
                                                Ir
                                            </button>
                                        </td>
                                        <td class="text-right">
                                            {{ formatEdadMin(row.edad_min) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div
                            v-if="!loading && tablas.pendientesLecturaTop.length === 0"
                            class="text-muted"
                        >
                            Sin pendientes de lectura.
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Pendientes de transcripción (Top 10)</h5>
                        <div class="table-responsive">
                            <table class="table table-sm m-b-0">
                                <thead>
                                    <tr>
                                        <th>Paciente</th>
                                        <th>Sede</th>
                                        <th>Accession</th>
                                        <th>Mod</th>
                                        <th>Acción</th>
                                        <th class="text-right">Edad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="row in tablas.pendientesTranscripcionTop"
                                        :key="row.id"
                                    >
                                        <td>
                                            <div class="font-500">
                                                {{ formatPacienteNombre(row) }}
                                            </div>
                                            <small class="text-muted">{{
                                                row.paciente_num_docu || ""
                                            }}</small>
                                        </td>
                                        <td>{{ row.sede || "" }}</td>
                                        <td>{{ row.accession_no || row.study_id || row.id }}</td>
                                        <td>{{ row.mods_in_study }}</td>
                                        <td>
                                            <button
                                                class="btn btn-sm btn-primary"
                                                type="button"
                                                @click="goToTranscripcion(row)"
                                            >
                                                Ir
                                            </button>
                                        </td>
                                        <td class="text-right">
                                            {{ formatEdadMin(row.edad_min) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div
                            v-if="!loading && tablas.pendientesTranscripcionTop.length === 0"
                            class="text-muted"
                        >
                            Sin pendientes de transcripción.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex m-b-10 no-block">
                            <h5 class="card-title m-b-0 align-self-center">
                                Ranking médicos (transcritos)
                            </h5>
                            <div class="ml-auto">
                                <small class="text-muted">Últimos {{ days }} días</small>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Médico</th>
                                        <th class="text-right">Total</th>
                                        <th class="text-right">Δ vs prev</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="row in productividad.rankingMedicosTranscritos"
                                        :key="row.medico_id"
                                    >
                                        <td>{{ row.medico }}</td>
                                        <td class="text-right">{{ row.total }}</td>
                                        <td class="text-right">{{ formatDelta(row) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div
                            v-if="!loading && productividad.rankingMedicosTranscritos.length === 0"
                            class="text-muted"
                        >
                            Sin datos para mostrar.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- Sales Chart and browser state-->
        <!-- ============================================================== -->
        <div class="row">
            <!-- Column -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex no-block">
                            <div>
                                <h5 class="card-title m-b-0">Flujo diario</h5>
                            </div>
                            <div class="ml-auto">
                                <small class="text-muted"
                                    >Asignados / Leídos / Transcritos ({{ days }} días)</small
                                >
                            </div>
                        </div>
                        <div class="" id="sales-chart" style="height: 355px"></div>
                        <div v-if="loading" class="text-center text-muted m-t-10">Cargando...</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex no-block">
                            <div>
                                <h5 class="card-title m-b-0">Distribución por hora (hoy)</h5>
                            </div>
                            <div class="ml-auto">
                                <small class="text-muted">0–23h</small>
                            </div>
                        </div>
                        <div id="por-hora-hoy" style="height: 355px; width: 100%"></div>
                        <div v-if="loading" class="text-center text-muted m-t-10">Cargando...</div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="isAdmin" class="row">
            <div class="col-lg-6 col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex no-block">
                            <div>
                                <h5 class="card-title m-b-0">Transcritos por sede</h5>
                            </div>
                            <div class="ml-auto">
                                <small class="text-muted">{{ days }} días</small>
                            </div>
                        </div>
                        <div id="por-sede" style="height: 260px; width: 100%"></div>
                        <div v-if="loading" class="text-center text-muted m-t-10">Cargando...</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Transcritos por sede (Top)</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Sede</th>
                                        <th class="text-right">Total</th>
                                        <th class="text-right">Δ vs prev</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="row in productividad.transcritosPorSede"
                                        :key="row.sede_id"
                                    >
                                        <td>{{ row.sede }}</td>
                                        <td class="text-right">{{ row.total }}</td>
                                        <td class="text-right">{{ formatDelta(row) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div
                            v-if="!loading && productividad.transcritosPorSede.length === 0"
                            class="text-muted"
                        >
                            Sin datos para mostrar.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Sales Chart -->
        <!-- ============================================================== -->
    </div>
</template>

<script>
export default {
    props: ["usuarioactual", "rolesusuario"],
    computed: {
        isAdmin() {
            return this.rolesusuario === "Administrador";
        },
        heatmapDays() {
            const arr = this.series?.heatmap7d?.[this.heatmapMetric] || [];
            const uniq = [...new Set(arr.map((x) => String(x.fecha)))].filter(Boolean).sort();
            if (uniq.length) return uniq;

            // fallback: últimos 7 días
            const d = window.dayjs ? window.dayjs() : null;
            if (!d) return [];
            return Array.from({ length: 7 }, (_, i) =>
                d.subtract(6 - i, "day").format("YYYY-MM-DD")
            );
        },
        heatmapMax() {
            const arr = this.series?.heatmap7d?.[this.heatmapMetric] || [];
            let max = 0;
            for (const x of arr) {
                const v = Number(x.total || 0);
                if (v > max) max = v;
            }
            return max;
        },
    },
    watch: {
        heatmapMetric() {
            this.reindexHeatmap();
        },
    },
    async mounted() {
        this.initCharts();
        await this.fetchDashboard();
    },
    data() {
        return {
            loading: false,
            error: null,
            days: 30,
            alerts: [],
            filters: {
                selected: { sede_id: null, modalidad: null, medico_id: null },
                options: { sedes: [], modalidades: [], medicos: [] },
            },
            indicadores: {
                estudiosAsignados: 0,
                estudiosLeidos: 0,
                estudiosPorTranscribir: 0,
                estudiosDescargados: 0,
            },
            operacion: {
                asignadosHoy: 0,
                pendientesLectura: 0,
                pendientesLecturaHoy: 0,
                leidosHoy: 0,
                pendientesTranscripcion: 0,
                transcritosHoy: 0,
            },
            estudiosMasSolicitados: [],
            series: {
                transcritosPorDia: [],
                asignadosPorDia: [],
                leidosPorDia: [],
                porHoraHoy: { asignados: [], leidos: [], transcritos: [] },
                heatmap7d: { days: 7, asignados: [], leidos: [], transcritos: [] },
            },
            heatmapMetric: "transcritos",
            heatmapIndex: {},
            calidad: {
                tiempos: {
                    asignadoALectura: { avgMin: 0, n: 0 },
                    lecturaATranscrito: { avgMin: 0, n: 0 },
                    dias: 30,
                },
                agingLectura: [],
                agingTranscripcion: [],
            },
            sla: { days: 30, lectura: [], transcripcion: [] },
            tablas: {
                pendientesLecturaTop: [],
                pendientesTranscripcionTop: [],
            },
            productividad: {
                rankingMedicosTranscritos: [],
                transcritosPorSede: [],
            },
            charts: {
                modalidades: null,
                resumen: null,
                transcritos: null,
                agingLectura: null,
                agingTranscripcion: null,
                porHoraHoy: null,
                porSede: null,
                slaLectura: null,
                slaTranscripcion: null,
            },
        };
    },
    methods: {
        safeC3Generate(selector, config) {
            const el = document.querySelector(selector);
            if (!el) return null;
            return c3.generate(config);
        },
        initCharts() {
            // Donut: modalidades
            this.charts.modalidades = c3.generate({
                bindto: "#visitor",
                data: {
                    columns: [],
                    type: "donut",
                },
                donut: {
                    label: { show: false },
                    title: "Modalidades",
                    width: 20,
                },
                legend: { hide: true },
                color: {
                    pattern: [
                        "#24d2b5",
                        "#20aee3",
                        "#6772e5",
                        "#ff9041",
                        "#7460ee",
                        "#f62d51",
                        "#90a4ae",
                    ],
                },
            });

            // Bar: resumen transcritos vs pendientes
            this.charts.resumen = c3.generate({
                bindto: "#income",
                data: {
                    columns: [
                        ["Transcritos", 0],
                        ["Pendientes", 0],
                    ],
                    type: "bar",
                },
                bar: {
                    width: 20,
                },
                axis: {
                    x: { show: false },
                    y: { tick: { count: 4, outer: false } },
                },
                legend: { show: true },
                grid: { y: { show: true } },
                size: { height: 290 },
                color: { pattern: ["#24d2b5", "#f62d51"] },
            });

            // Área: flujo diario (asignados / leídos / transcritos)
            // Morris puede fallar en `resizeHandler` si se inicializa con `data: []`
            // y ocurre un resize antes de que llegue `setData(...)`. Usamos un punto dummy.
            const morrisAreaInitialData = [{ period: "", asignados: 0, leidos: 0, transcritos: 0 }];
            this.charts.transcritos = Morris.Area({
                element: "sales-chart",
                data: morrisAreaInitialData,
                xkey: "period",
                ykeys: ["asignados", "leidos", "transcritos"],
                labels: ["Asignados", "Leídos", "Transcritos"],
                pointSize: 2,
                fillOpacity: 0.2,
                pointStrokeColors: ["#26c6da", "#ffb22b", "#24d2b5"],
                behaveLikeLine: true,
                gridLineColor: "#e0e0e0",
                lineWidth: 2,
                hideHover: "auto",
                lineColors: ["#26c6da", "#ffb22b", "#24d2b5"],
                resize: true,
            });

            // Donut: aging lectura
            this.charts.agingLectura = c3.generate({
                bindto: "#aging-lectura",
                data: { columns: [], type: "donut" },
                donut: { label: { show: false }, title: "Lectura", width: 18 },
                legend: { hide: true },
                color: { pattern: ["#26c6da", "#ffb22b", "#7460ee", "#f62d51"] },
            });

            // Donut: aging transcripción
            this.charts.agingTranscripcion = c3.generate({
                bindto: "#aging-transcripcion",
                data: { columns: [], type: "donut" },
                donut: { label: { show: false }, title: "Transcripción", width: 18 },
                legend: { hide: true },
                color: { pattern: ["#26c6da", "#ffb22b", "#7460ee", "#f62d51"] },
            });

            // Bar: distribución por hora (hoy)
            this.charts.porHoraHoy = this.safeC3Generate("#por-hora-hoy", {
                bindto: "#por-hora-hoy",
                data: {
                    x: "x",
                    columns: [["x"], ["Asignados"], ["Leídos"], ["Transcritos"]],
                    type: "bar",
                    groups: [["Asignados", "Leídos", "Transcritos"]],
                },
                axis: {
                    x: {
                        type: "category",
                        tick: {
                            rotate: 75,
                            multiline: false,
                        },
                        height: 70,
                    },
                    y: {
                        tick: { count: 4, outer: false },
                    },
                },
                bar: { width: { ratio: 0.6 } },
                legend: { show: true },
                grid: { y: { show: true } },
                color: { pattern: ["#26c6da", "#ffb22b", "#24d2b5"] },
            });

            // Donut: transcritos por sede (solo si existe el contenedor)
            this.charts.porSede = this.safeC3Generate("#por-sede", {
                bindto: "#por-sede",
                data: { columns: [], type: "donut" },
                donut: { label: { show: false }, title: "Sedes", width: 18 },
                legend: { hide: true },
                color: {
                    pattern: ["#745af2", "#26c6da", "#24d2b5", "#ffb22b", "#f62d51", "#90a4ae"],
                },
            });

            // Cumplimiento de tiempos (charts)
            this.charts.slaLectura = this.safeC3Generate("#sla-lectura", {
                bindto: "#sla-lectura",
                data: { x: "x", columns: [["x"], ["Incumplimiento %"]], type: "bar" },
                axis: {
                    x: { type: "category" },
                    y: { tick: { count: 4, outer: false }, max: 100, min: 0 },
                },
                bar: { width: { ratio: 0.7 } },
                legend: { hide: true },
                grid: { y: { show: true } },
                color: { pattern: ["#f62d51"] },
            });
            this.charts.slaTranscripcion = this.safeC3Generate("#sla-transcripcion", {
                bindto: "#sla-transcripcion",
                data: { x: "x", columns: [["x"], ["Incumplimiento %"]], type: "bar" },
                axis: {
                    x: { type: "category" },
                    y: { tick: { count: 4, outer: false }, max: 100, min: 0 },
                },
                bar: { width: { ratio: 0.7 } },
                legend: { hide: true },
                grid: { y: { show: true } },
                color: { pattern: ["#ffb22b"] },
            });
        },
        async fetchDashboard() {
            this.loading = true;
            this.error = null;

            try {
                const params = {
                    days: this.days,
                    sede_id: this.filters?.selected?.sede_id || undefined,
                    modalidad: this.filters?.selected?.modalidad || undefined,
                    medico_id: this.isAdmin
                        ? this.filters?.selected?.medico_id || undefined
                        : undefined,
                };
                const res = await axios.get("/api/reportes.dashboard", {
                    params,
                });

                const data = res?.data || {};

                if (data.filters) {
                    this.filters = data.filters;
                }
                this.alerts = Array.isArray(data.alerts) ? data.alerts : [];
                this.sla = data.sla || this.sla;

                this.indicadores.estudiosAsignados = Number(data.estudiosAsignados || 0);
                this.indicadores.estudiosLeidos = Number(data.estudiosLeidos || 0);
                this.indicadores.estudiosPorTranscribir = Number(data.estudiosPorTranscribir || 0);
                this.indicadores.estudiosDescargados = Number(data.estudiosDescargados || 0);

                this.estudiosMasSolicitados = Array.isArray(data.estudiosMasSolicitados)
                    ? data.estudiosMasSolicitados
                    : [];
                this.series.transcritosPorDia = Array.isArray(data?.series?.transcritosPorDia)
                    ? data.series.transcritosPorDia
                    : [];
                this.series.asignadosPorDia = Array.isArray(data?.series?.asignadosPorDia)
                    ? data.series.asignadosPorDia
                    : [];
                this.series.leidosPorDia = Array.isArray(data?.series?.leidosPorDia)
                    ? data.series.leidosPorDia
                    : [];
                this.series.porHoraHoy = data?.series?.porHoraHoy || this.series.porHoraHoy;
                this.series.heatmap7d = data?.series?.heatmap7d || this.series.heatmap7d;

                this.operacion = data.operacion || this.operacion;
                this.calidad = data.calidad || this.calidad;
                this.tablas = data.tablas || this.tablas;
                this.productividad = data.productividad || this.productividad;

                this.reindexHeatmap();
                this.renderCharts();
            } catch (e) {
                this.error = "No fue posible cargar las estadísticas del dashboard.";
                // opcional: log mínimo para depurar en consola sin ruido
                // console.error(e);
            } finally {
                this.loading = false;
            }
        },

        renderCharts() {
            // Donut (modalidades): usa topModalidades del backend
            const donutColumns = (this.estudiosMasSolicitados || [])
                .filter((x) => x && x.mods_in_study)
                .map((x) => [String(x.mods_in_study), Number(x.total || 0)]);

            if (this.charts.modalidades && donutColumns.length > 0) {
                this.charts.modalidades.load({ columns: donutColumns });
            } else if (this.charts.modalidades) {
                this.charts.modalidades.load({ columns: [["Sin datos", 1]] });
            }

            // Bar (resumen)
            if (this.charts.resumen) {
                this.charts.resumen.load({
                    columns: [
                        ["Transcritos", this.indicadores.estudiosLeidos],
                        ["Pendientes", this.indicadores.estudiosPorTranscribir],
                    ],
                });
            }

            // Área (flujo por día): unir asignados/leídos/transcritos por fecha
            const byDate = {};
            const upsert = (arr, key) => {
                (arr || []).forEach((x) => {
                    if (!x || !x.fecha) return;
                    const f = String(x.fecha);
                    if (!byDate[f])
                        byDate[f] = { period: f, asignados: 0, leidos: 0, transcritos: 0 };
                    byDate[f][key] = Number(x.total || 0);
                });
            };
            upsert(this.series.asignadosPorDia, "asignados");
            upsert(this.series.leidosPorDia, "leidos");
            upsert(this.series.transcritosPorDia, "transcritos");

            const areaData = Object.keys(byDate)
                .sort()
                .map((k) => byDate[k]);

            if (this.charts.transcritos && typeof this.charts.transcritos.setData === "function") {
                this.charts.transcritos.setData(areaData);
            }

            // Donut aging lectura
            const agingLecturaCols = (this.calidad?.agingLectura || []).map((x) => [
                String(x.bucket),
                Number(x.total || 0),
            ]);
            if (this.charts.agingLectura) {
                this.charts.agingLectura.load({
                    columns: agingLecturaCols.length ? agingLecturaCols : [["Sin datos", 1]],
                });
            }

            // Donut aging transcripción
            const agingTransCols = (this.calidad?.agingTranscripcion || []).map((x) => [
                String(x.bucket),
                Number(x.total || 0),
            ]);
            if (this.charts.agingTranscripcion) {
                this.charts.agingTranscripcion.load({
                    columns: agingTransCols.length ? agingTransCols : [["Sin datos", 1]],
                });
            }

            // Bar por hora (hoy)
            if (this.charts.porHoraHoy) {
                const hours = Array.from({ length: 24 }, (_, i) => String(i));
                const mk24 = () => Array.from({ length: 24 }, () => 0);

                const asignados = mk24();
                const leidos = mk24();
                const transcritos = mk24();

                (this.series?.porHoraHoy?.asignados || []).forEach((x) => {
                    const h = Number(x.hora);
                    if (h >= 0 && h <= 23) asignados[h] = Number(x.total || 0);
                });
                (this.series?.porHoraHoy?.leidos || []).forEach((x) => {
                    const h = Number(x.hora);
                    if (h >= 0 && h <= 23) leidos[h] = Number(x.total || 0);
                });
                (this.series?.porHoraHoy?.transcritos || []).forEach((x) => {
                    const h = Number(x.hora);
                    if (h >= 0 && h <= 23) transcritos[h] = Number(x.total || 0);
                });

                this.charts.porHoraHoy.load({
                    columns: [
                        ["x", ...hours],
                        ["Asignados", ...asignados],
                        ["Leídos", ...leidos],
                        ["Transcritos", ...transcritos],
                    ],
                });
            }

            // Donut transcritos por sede (admin)
            if (this.charts.porSede) {
                const cols = (this.productividad?.transcritosPorSede || []).map((x) => [
                    String(x.sede),
                    Number(x.total || 0),
                ]);
                this.charts.porSede.load({ columns: cols.length ? cols : [["Sin datos", 1]] });
            }

            // Cumplimiento de tiempos (incumplimiento %)
            if (this.charts.slaLectura) {
                const x = ["x", ...(this.sla?.lectura || []).map((r) => String(r.prioridad))];
                const y = [
                    "Incumplimiento %",
                    ...(this.sla?.lectura || []).map((r) =>
                        Math.round(Number(r.breachRate || 0) * 100)
                    ),
                ];
                this.charts.slaLectura.load({ columns: [x, y] });
            }
            if (this.charts.slaTranscripcion) {
                const x = ["x", ...(this.sla?.transcripcion || []).map((r) => String(r.prioridad))];
                const y = [
                    "Incumplimiento %",
                    ...(this.sla?.transcripcion || []).map((r) =>
                        Math.round(Number(r.breachRate || 0) * 100)
                    ),
                ];
                this.charts.slaTranscripcion.load({ columns: [x, y] });
            }
        },

        reindexHeatmap() {
            const arr = this.series?.heatmap7d?.[this.heatmapMetric] || [];
            const idx = {};
            for (const x of arr) {
                const d = String(x.fecha);
                const h = Number(x.hora);
                if (!idx[d]) idx[d] = {};
                idx[d][h] = Number(x.total || 0);
            }
            this.heatmapIndex = idx;
        },

        heatmapValue(date, hour) {
            return this.heatmapIndex?.[date] && this.heatmapIndex[date][hour]
                ? this.heatmapIndex[date][hour]
                : 0;
        },

        heatmapColor(value, max) {
            const v = Number(value || 0);
            const m = Number(max || 0);
            if (m <= 0) return "transparent";
            const t = Math.max(0, Math.min(1, v / m));
            // base: #26c6da
            return `rgba(38, 198, 218, ${0.15 + t * 0.75})`;
        },

        alertClass(level) {
            switch (String(level || "").toLowerCase()) {
                case "danger":
                    return "alert-danger";
                case "warning":
                    return "alert-warning";
                case "info":
                    return "alert-info";
                case "success":
                    return "alert-success";
                default:
                    return "alert-secondary";
            }
        },

        formatEdadMin(min) {
            const m = Number(min || 0);
            if (!m || m < 0) return "0m";
            const days = Math.floor(m / (60 * 24));
            const hours = Math.floor((m % (60 * 24)) / 60);
            const minutes = Math.floor(m % 60);
            if (days > 0) return `${days}d ${hours}h`;
            if (hours > 0) return `${hours}h ${minutes}m`;
            return `${minutes}m`;
        },

        formatMin(min) {
            const m = Number(min || 0);
            if (!m || m < 0) return "0m";
            const hours = Math.floor(m / 60);
            const minutes = Math.floor(m % 60);
            if (hours > 0) return `${hours}h ${minutes}m`;
            return `${minutes}m`;
        },

        formatDelta(row) {
            const total = Number(row?.total || 0);
            const prev = Number(row?.prev_total || 0);
            const delta =
                row && typeof row.delta !== "undefined" ? Number(row.delta) : total - prev;
            const pct =
                row && row.deltaPct !== null && typeof row.deltaPct !== "undefined"
                    ? Number(row.deltaPct)
                    : null;

            const sign = delta > 0 ? "+" : "";
            if (prev <= 0) {
                return `${sign}${delta}`;
            }
            const pctStr = pct === null ? "" : `${Math.round(pct * 100)}%`;
            return `${sign}${delta} (${pctStr})`;
        },

        formatPacienteNombre(row) {
            if (!row) return "";
            const parts = [
                row.paciente_nombres,
                row.paciente_seg_nom,
                row.paciente_pri_ape,
                row.paciente_seg_ape,
            ]
                .filter(Boolean)
                .map((x) => String(x).trim())
                .filter(Boolean);

            return parts.join(" ") || "Paciente";
        },

        goToLectura(row) {
            try {
                if (row && row.sede_id) sessionStorage.setItem("ST-sede", String(row.sede_id));
                if (row && row.accession_no)
                    sessionStorage.setItem("ST-accession", String(row.accession_no));
            } catch (e) {
                /* noop */
            }
            window.location.href = "/estudios.leer.estudio.listar";
        },

        goToTranscripcion(row) {
            try {
                if (row && row.sede_id) sessionStorage.setItem("ST-sede", String(row.sede_id));
                if (row && row.accession_no)
                    sessionStorage.setItem("ST-accession", String(row.accession_no));
            } catch (e) {
                /* noop */
            }
            window.location.href = "/estudios.transcribir.listar";
        },
    },
};
</script>

<style></style>
