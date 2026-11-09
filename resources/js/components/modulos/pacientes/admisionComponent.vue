<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Admisión de Pacientes</h3>
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
                            Pacientes
                        </button>
                    </li>
                    <li class="breadcrumb-item active">Admisión</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-outline-info m-r-5"
                    :disabled="isReconciliandoPacs || loadingBuscar"
                    @click="reconciliarConPacs()"
                    title="Verificar y reconciliar órdenes con el PACS Dcm4chee"
                >
                    <i
                        :class="isReconciliandoPacs ? 'fa fa-spinner fa-spin' : 'fa fa-refresh'"
                        class="m-r-5"
                    ></i>
                    {{ isReconciliandoPacs ? "Verificando PACS..." : "Reconciliar PACS" }}
                </button>
                <button type="button" class="btn btn-info" @click="nuevoRegistro()">
                    <i class="fa fa-plus-circle m-r-5"></i> Nuevo registro
                </button>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- Start Page Content -->
        <!-- ============================================================== -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Filtros de búsqueda</h4>
                        <h6 class="card-subtitle text-info">
                            Filtre los pacientes para su atención o envío a la Worklist de Rayos X.
                        </h6>
                        <form action="#">
                            <div class="form-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label" for="bus_documento">
                                                # Documento
                                            </label>
                                            <input
                                                type="text"
                                                id="bus_documento"
                                                name="bus_documento"
                                                v-model="busqueda.bus_documento"
                                                class="form-control"
                                                @keyup.enter="buscar()"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label" for="bus_nombre">
                                                Nombre / Apellido
                                            </label>
                                            <input
                                                type="text"
                                                id="bus_nombre"
                                                name="bus_nombre"
                                                v-model="busqueda.bus_nombre"
                                                class="form-control"
                                                @keyup.enter="buscar()"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label" for="bus_fech_ini">
                                                Fecha Inicio
                                            </label>
                                            <input
                                                type="date"
                                                id="bus_fech_ini"
                                                name="bus_fech_ini"
                                                v-model="busqueda.bus_fech_ini"
                                                class="form-control"
                                                @keyup.enter="buscar()"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label" for="bus_fecha_fin">
                                                Fecha Fin
                                            </label>
                                            <input
                                                type="date"
                                                id="bus_fecha_fin"
                                                name="bus_fecha_fin"
                                                v-model="busqueda.bus_fecha_fin"
                                                class="form-control"
                                                @keyup.enter="buscar()"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div class="form-actions mt-3">
                                    <button
                                        type="button"
                                        class="btn btn-success m-r-5"
                                        @click="buscar()"
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
                                <h4 class="card-title">Listado de pacientes</h4>
                                <h6 class="card-subtitle text-info">
                                    Envíe los pacientes al equipo de Rayos X o gestione sus datos.
                                </h6>
                            </div>
                            <div class="text-right d-flex align-items-center">
                                <span class="badge badge-primary" v-if="contadorRegistros > 0">
                                    {{ contadorRegistros }} registro(s)
                                </span>
                                <span class="badge badge-secondary" v-else>Sin resultados</span>
                            </div>
                        </div>

                        <table-skeleton-loader :visible="loadingBuscar" :rows="6" :columns="7" />

                        <empty-state-component
                            v-if="!loadingBuscar && registros.length === 0"
                            icon="fa fa-folder-open-o"
                            title="No hay pacientes para mostrar"
                            description="No se encontraron pacientes con los filtros seleccionados o use los filtros para buscar."
                            action-text="Limpiar filtros"
                            action-icon="fa fa-refresh"
                            @action="btnLimpiar()"
                        />

                        <div
                            class="table-responsive"
                            v-show="!loadingBuscar && registros.length > 0"
                        >
                            <table
                                id="tablaAdmisionPacientes"
                                ref="dt"
                                class="table table-bordered table-striped"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th style="width: 5%">Tipo</th>
                                        <th style="width: 12%"># Documento</th>
                                        <th style="width: 25%">Paciente</th>
                                        <th style="width: 10%">Sexo / Edad</th>
                                        <th style="width: 15%">Contacto</th>
                                        <th style="width: 20%">Estado Worklist</th>
                                        <th style="width: 13%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td class="text-center">
                                            <span class="badge badge-secondary">
                                                {{ item.tipo_documento?.codigo || "DOC" }}
                                            </span>
                                        </td>
                                        <td class="text-nowrap align-middle">
                                            <div
                                                class="text-primary font-weight-bold"
                                                style="font-size: 15px"
                                            >
                                                <i class="fa fa-id-card-o mr-1"></i>
                                                {{ item.num_docu }}
                                            </div>
                                        </td>
                                        <td>{{ item.nombre_completo || item.nombres }}</td>
                                        <td>
                                            {{ item.sexo }} ({{
                                                calcularEdadPaciente(item.fec_naci)
                                            }})
                                        </td>
                                        <td>{{ item.cel || item.tel || "—" }}</td>
                                        <td>
                                            <div v-if="item.orden_worklist_activa">
                                                <span
                                                    v-if="
                                                        item.orden_worklist_activa.estado ===
                                                        'ADQUIRIDO'
                                                    "
                                                    class="badge badge-info m-r-5"
                                                    title="Imágenes recibidas en el PACS - Listo para Asignar"
                                                >
                                                    <i class="fa fa-check-circle"></i> Imágenes en
                                                    PACS
                                                </span>
                                                <span
                                                    v-else-if="
                                                        item.orden_worklist_activa.estado ===
                                                        'COMPLETADO'
                                                    "
                                                    class="badge badge-success m-r-5"
                                                    title="Estudio asignado formalmente en el sistema"
                                                >
                                                    <i class="fa fa-user-md"></i> Asignado a Lectura
                                                </span>
                                                <span
                                                    v-else
                                                    class="badge badge-warning text-dark m-r-5"
                                                    title="En espera de adquisición en la sala de Rayos X"
                                                >
                                                    <i class="fa fa-clock-o"></i> En espera de Rayos
                                                    X
                                                </span>
                                                <small class="text-muted d-block">
                                                    Acc:
                                                    {{ item.orden_worklist_activa.accession_no }}
                                                    <span
                                                        v-if="
                                                            item.orden_worklist_activa.adquirido_at
                                                        "
                                                    >
                                                        | Toma:
                                                        {{
                                                            item.orden_worklist_activa.adquirido_at
                                                        }}
                                                    </span>
                                                    <span v-else>
                                                        ({{
                                                            item.orden_worklist_activa.created_at
                                                        }})
                                                    </span>
                                                </small>
                                            </div>
                                            <span v-else class="badge badge-light text-muted"
                                                >Sin orden activa</span
                                            >
                                        </td>
                                        <td class="text-nowrap">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-success btn-sm m-r-5"
                                                @click="abrirModalWorklist(item)"
                                                title="enviar para procedimiento"
                                                aria-label="enviar para procedimiento"
                                            >
                                                <i class="fa fa-list-alt"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-sm m-r-5"
                                                :class="
                                                    sedeInterface
                                                        ? 'btn-outline-info'
                                                        : 'btn-outline-secondary'
                                                "
                                                :disabled="
                                                    !sedeInterface ||
                                                    loadingInterfacePacienteId === item.id
                                                "
                                                :title="
                                                    sedeInterface
                                                        ? 'Consultar órdenes y facturas en interface ' +
                                                          (
                                                              sedeInterfaceSoftware || 'SAHI'
                                                          ).toUpperCase()
                                                        : 'La sede actual no cuenta con interface configurada'
                                                "
                                                :aria-label="
                                                    sedeInterface
                                                        ? 'Consultar órdenes y facturas en interface'
                                                        : 'Interface no configurada en esta sede'
                                                "
                                                @click="abrirModalInterface(item)"
                                            >
                                                <i
                                                    v-if="loadingInterfacePacienteId === item.id"
                                                    class="fa fa-spinner fa-spin"
                                                ></i>
                                                <i v-else class="fa fa-database"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm m-r-5"
                                                @click="mostrarRegistro(item)"
                                                title="Editar datos del paciente"
                                                aria-label="Editar datos del paciente"
                                            >
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <a
                                                v-if="
                                                    item.orden_worklist_activa &&
                                                    item.orden_worklist_activa.estado ===
                                                        'ADQUIRIDO'
                                                "
                                                href="/estudios.asignar.listar"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm m-r-5"
                                                title="Ir a Asignar Estudio (Imágenes listas en PACS)"
                                                aria-label="Ir a Asignar Estudio"
                                            >
                                                <i class="fa fa-arrow-circle-right"></i>
                                            </a>
                                            <button
                                                v-if="
                                                    item.orden_worklist_activa &&
                                                    item.orden_worklist_activa.estado ===
                                                        'PENDIENTE'
                                                "
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                @click="cancelarOrdenWorklist(item)"
                                                title="Retirar de Worklist"
                                                aria-label="Retirar de Worklist"
                                            >
                                                <i class="fa fa-times"></i>
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
                class="modal-backdrop-custom"
                @click="cerrarModal()"
                aria-hidden="true"
            ></div>
        </transition>

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
                    <form action="#">
                        <div class="form-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label" for="tipo_documento_id">
                                            Tipo de Documento <span class="text-danger">*</span>
                                        </label>
                                        <select
                                            id="tipo_documento_id"
                                            name="tipo_documento_id"
                                            v-model="registro.tipo_documento_id"
                                            class="form-control"
                                        >
                                            <option :value="null" disabled>
                                                -- Seleccione Tipo --
                                            </option>
                                            <option
                                                v-for="tipo in tiposDocumento"
                                                :key="tipo.id"
                                                :value="tipo.id"
                                            >
                                                {{ tipo.codigo }} - {{ tipo.tipo_documento }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <form-paciente
                                :registro="registro"
                                :errores="errores"
                                :mostrar-errores="true"
                            />

                            <div class="form-actions mt-3">
                                <button
                                    type="button"
                                    class="btn btn-success m-r-5"
                                    @click="guardarPaciente()"
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
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Right sidebar -->
        <!-- ============================================================== -->

        <!-- Modal Worklist -->
        <div
            class="modal fade"
            :class="{ show: modalWorklistVisible, 'd-block': modalWorklistVisible }"
            tabindex="-1"
            role="dialog"
            v-if="modalWorklistVisible"
        >
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title font-weight-bold">
                            <i class="fa fa-list-alt text-info mr-2"></i> Enviar a Worklist de Rayos
                            X
                        </h4>
                        <button
                            type="button"
                            class="close"
                            @click="cerrarModalWorklist"
                            aria-label="Cerrar"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="card card-outline-info p-3 mb-3">
                            <h5 class="card-title text-info mb-1">
                                <i class="fa fa-user"></i>
                                {{
                                    pacienteSeleccionado?.nombre_completo ||
                                    pacienteSeleccionado?.nombres
                                }}
                            </h5>
                            <div class="text-muted small">
                                <strong># Documento:</strong> {{ pacienteSeleccionado?.num_docu }} |
                                <strong>Sexo:</strong>
                                {{ pacienteSeleccionado?.sexo === "F" ? "F" : "M" }} |
                                <strong>Sede:</strong> {{ sedeNombre }}
                            </div>
                        </div>

                        <form action="#">
                            <div class="form-group">
                                <label class="control-label" for="wl_producto">
                                    Producto / Examen de Rayos X <span class="text-danger">*</span>
                                </label>
                                <select
                                    id="wl_producto"
                                    v-model="worklistForm.producto_id"
                                    class="form-control"
                                    required
                                >
                                    <option :value="null" disabled>-- Seleccione Examen --</option>
                                    <option
                                        v-for="prod in productosRayosX"
                                        :key="prod.id"
                                        :value="prod.id"
                                    >
                                        {{ prod.nom_produc }} (CUPS: {{ prod.cod_cups }})
                                    </option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="control-label" for="wl_acc">
                                    Accession Number <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input
                                        type="text"
                                        id="wl_acc"
                                        v-model="worklistForm.accession_no"
                                        class="form-control font-weight-bold"
                                        required
                                    />
                                    <div class="input-group-append">
                                        <button
                                            type="button"
                                            class="btn btn-secondary"
                                            @click="generarAccessionSugerido"
                                            title="Regenerar sugerido"
                                        >
                                            <i class="fa fa-refresh"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">
                                    Generado automáticamente. Puede editarlo si cuenta con un número
                                    de orden específico.
                                </small>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label" for="wl_aet"
                                            >Equipo / Sala de Rayos X (Station AE Title)
                                            <span class="text-danger">*</span></label
                                        >
                                        <div v-if="loadingEstaciones" class="py-2 text-muted">
                                            <i class="fa fa-spinner fa-spin"></i> Consultando
                                            equipos en el PACS de la sede...
                                        </div>
                                        <select
                                            v-else-if="estacionesWorklist.length > 0"
                                            id="wl_aet"
                                            v-model="worklistForm.station_aet"
                                            class="form-control"
                                            required
                                        >
                                            <option value="" disabled>
                                                -- Seleccione equipo o sala --
                                            </option>
                                            <option
                                                v-for="est in estacionesWorklist"
                                                :key="est.aet"
                                                :value="est.aet"
                                            >
                                                {{ est.nombre }}
                                            </option>
                                        </select>
                                        <div v-else>
                                            <input
                                                type="text"
                                                id="wl_aet"
                                                v-model="worklistForm.station_aet"
                                                class="form-control"
                                                placeholder="Ingrese AE Title del equipo"
                                                required
                                            />
                                            <small class="text-muted">
                                                <i class="fa fa-info-circle"></i> No se encontraron
                                                equipos en el PACS de esta sede. Ingrese el AE Title
                                                manualmente.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-inverse"
                            @click="cerrarModalWorklist"
                            :disabled="loadingWorklist"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            class="btn btn-success"
                            @click="confirmarEnvioWorklist"
                            :disabled="
                                loadingWorklist ||
                                !worklistForm.producto_id ||
                                !worklistForm.accession_no
                            "
                        >
                            <i v-if="loadingWorklist" class="fa fa-spinner fa-spin m-r-5"></i>
                            <i v-else class="fa fa-check m-r-5"></i>
                            Confirmar Envío a Worklist
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="modalWorklistVisible" class="modal-backdrop fade show"></div>

        <!-- ============================================================== -->
        <!-- Modal: Consulta de Órdenes y Facturas en Interface (SAHI)      -->
        <!-- ============================================================== -->
        <div
            v-if="modalInterfaceVisible"
            class="modal fade show"
            id="modalInterface"
            tabindex="-1"
            role="dialog"
            aria-labelledby="modalInterfaceLabel"
            style="display: block; background: rgba(0, 0, 0, 0.55)"
        >
            <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                <div class="modal-content shadow-lg">
                    <div class="modal-header bg-info text-white">
                        <h4 class="modal-title text-white" id="modalInterfaceLabel">
                            <i class="fa fa-database m-r-5"></i>
                            Órdenes y Facturas en Interface
                            {{ (sedeInterfaceSoftware || "SAHI").toUpperCase() }}
                        </h4>
                        <button
                            type="button"
                            class="close text-white"
                            @click="cerrarModalInterface"
                            aria-label="Cerrar"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body p-4">
                        <!-- Cabecera contextual del Paciente -->
                        <div class="alert alert-light border mb-3 p-3">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="mb-1 text-dark">
                                        <i class="fa fa-user-circle text-info m-r-5"></i>
                                        <strong>{{
                                            pacienteInterface?.nombre_completo ||
                                            pacienteInterface?.nombres
                                        }}</strong>
                                    </h5>
                                    <div class="text-muted small">
                                        <span
                                            ><strong>Documento:</strong>
                                            {{ pacienteInterface?.tipo_documento?.codigo || "DOC" }}
                                            {{ pacienteInterface?.num_docu }}</span
                                        >
                                        <span class="mx-2">•</span>
                                        <span
                                            ><strong>Sexo / Edad:</strong>
                                            {{ pacienteInterface?.sexo }} ({{
                                                calcularEdadPaciente(pacienteInterface?.fec_naci)
                                            }})</span
                                        >
                                        <span class="mx-2">•</span>
                                        <span><strong>Sede Activa:</strong> {{ sedeNombre }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4 text-md-right mt-2 mt-md-0">
                                    <span class="badge badge-success px-3 py-2">
                                        <i class="fa fa-plug m-r-5"></i> Interface
                                        {{ (sedeInterfaceSoftware || "SAHI").toUpperCase() }}
                                        Conectada
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Selector de Atenciones -->
                        <div class="card mb-3 border">
                            <div class="card-body bg-light py-3">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <label
                                            class="form-label font-weight-bold mb-1"
                                            for="selectInterfaceAtencion"
                                        >
                                            <i class="fa fa-calendar-check-o text-info m-r-5"></i>
                                            Seleccione una Atención del Paciente:
                                        </label>
                                        <select
                                            id="selectInterfaceAtencion"
                                            class="form-control custom-select"
                                            v-model="interfaceAtencionSeleccionada"
                                            @change="onCambioAtencion"
                                            :disabled="
                                                loadingInterfaceAtenciones ||
                                                loadingInterfaceProductos
                                            "
                                        >
                                            <option
                                                v-if="loadingInterfaceAtenciones"
                                                value=""
                                                disabled
                                            >
                                                Cargando atenciones desde el HIS...
                                            </option>
                                            <option
                                                v-else-if="interfaceAtenciones.length === 0"
                                                value=""
                                                disabled
                                            >
                                                No se encontraron atenciones registradas en el HIS
                                                para este paciente
                                            </option>
                                            <option
                                                v-for="atn in interfaceAtenciones"
                                                :key="atn.IdAtencion"
                                                :value="atn.IdAtencion"
                                            >
                                                Atención #{{ atn.IdAtencion }} — Ingreso:
                                                {{
                                                    atn.FecIngreso
                                                        ? atn.FecIngreso.substring(0, 10)
                                                        : "Sin fecha"
                                                }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 text-md-right mt-2 mt-md-0">
                                        <button
                                            type="button"
                                            class="btn btn-outline-info waves-effect"
                                            @click="recargarInterface"
                                            :disabled="
                                                loadingInterfaceAtenciones ||
                                                loadingInterfaceProductos
                                            "
                                            title="Actualizar datos desde la interface"
                                        >
                                            <i
                                                class="fa fa-refresh m-r-5"
                                                :class="{
                                                    'fa-spin':
                                                        loadingInterfaceAtenciones ||
                                                        loadingInterfaceProductos,
                                                }"
                                            ></i>
                                            Recargar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Estado de carga de productos -->
                        <div v-if="loadingInterfaceProductos" class="text-center py-5">
                            <i class="fa fa-spinner fa-spin fa-2x text-info"></i>
                            <h5 class="mt-3 text-muted">
                                Consultando órdenes y facturas en la atención #{{
                                    interfaceAtencionSeleccionada
                                }}...
                            </h5>
                        </div>

                        <!-- Sin atenciones -->
                        <div
                            v-else-if="
                                !loadingInterfaceAtenciones && interfaceAtenciones.length === 0
                            "
                            class="text-center py-5"
                        >
                            <i class="fa fa-folder-open-o fa-3x text-muted mb-3"></i>
                            <h5>No se encontraron atenciones</h5>
                            <p class="text-muted">
                                El paciente con documento {{ pacienteInterface?.num_docu }} no posee
                                atenciones registradas en la interface
                                {{ (sedeInterfaceSoftware || "SAHI").toUpperCase() }}.
                            </p>
                        </div>

                        <!-- Atención seleccionada sin productos -->
                        <div
                            v-else-if="
                                !loadingInterfaceAtenciones &&
                                interfaceAtenciones.length > 0 &&
                                interfaceProductos.length === 0
                            "
                            class="alert alert-warning text-center py-4"
                        >
                            <i class="fa fa-exclamation-triangle fa-2x text-warning mb-2"></i>
                            <h5 class="mb-1 text-warning">Sin procedimientos diagnósticos</h5>
                            <p class="mb-0 text-dark">
                                No se encontraron productos facturados ni órdenes de procedimiento
                                registradas para la Atención #{{ interfaceAtencionSeleccionada }}.
                            </p>
                        </div>

                        <!-- Tabla de Órdenes y Facturas encontradas -->
                        <div v-else-if="interfaceProductos.length > 0" class="table-responsive">
                            <table
                                class="table table-hover table-striped table-bordered align-middle mb-0"
                            >
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 12%" class="text-center">Tipo / #</th>
                                        <th style="width: 10%" class="text-center">CUPS</th>
                                        <th style="width: 32%">Estudio / Procedimiento</th>
                                        <th style="width: 18%">Médico Ordenante</th>
                                        <th style="width: 16%">EPS / Responsable</th>
                                        <th style="width: 12%" class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(prod, idx) in interfaceProductos" :key="idx">
                                        <td class="text-center">
                                            <span
                                                v-if="prod.TipoRegistro === 'FACTURA'"
                                                class="badge badge-success text-uppercase"
                                            >
                                                <i class="fa fa-file-text-o m-r-5"></i> Factura
                                                <div v-if="prod.NumFactura" class="small mt-1">
                                                    {{ prod.NumFactura }}
                                                </div>
                                            </span>
                                            <span v-else class="badge badge-primary text-uppercase">
                                                <i class="fa fa-clipboard m-r-5"></i> Orden
                                                <div v-if="prod.NumFactura" class="small mt-1">
                                                    #{{ prod.NumFactura }}
                                                </div>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-info">{{
                                                prod.CodProducto || prod.cod_cups || "N/A"
                                            }}</span>
                                        </td>
                                        <td>
                                            <strong class="text-dark d-block">{{
                                                prod.NomProducto ||
                                                prod.nom_produc ||
                                                "Sin descripción"
                                            }}</strong>
                                            <small class="text-muted" v-if="prod.ValProduc">
                                                Valor: ${{
                                                    Number(prod.ValProduc).toLocaleString("es-CO")
                                                }}
                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-dark">{{
                                                prod.quien_ordeno || "No registrado"
                                            }}</small>
                                        </td>
                                        <td>
                                            <small class="d-block font-weight-bold text-dark">{{
                                                prod.NomTercero || "Particular"
                                            }}</small>
                                            <small
                                                class="text-muted"
                                                v-if="prod.DesContrato || prod.DesPlan"
                                            >
                                                {{ prod.DesContrato || prod.DesPlan }}
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <button
                                                type="button"
                                                class="btn btn-success btn-sm waves-effect waves-light"
                                                @click="seleccionarParaWorklist(prod)"
                                                title="Cargar y enviar este estudio a Worklist"
                                            >
                                                <i class="fa fa-paper-plane m-r-5"></i> Worklist
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-secondary waves-effect"
                            @click="cerrarModalInterface"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="modalInterfaceVisible" class="modal-backdrop fade show"></div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, getCurrentInstance, nextTick } from "vue";
import axios from "axios";
import FormPaciente from "@/components/comunes/formPacienteComponent.vue";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import {
    toastMessage,
    confirmAction,
    alertError,
    alertSuccess,
    getUserSafeError,
} from "@/helpers/alerts";
import { calcularEdad } from "@/helpers/dateUtils";
import { useAuth } from "@/composables/useAuth";

const props = defineProps({
    usuarioactual: { type: Object, default: () => ({}) },
    permisos: { type: Array, default: () => [] },
});

const instance = getCurrentInstance();
const emitter = instance?.appContext?.config?.globalProperties?.emitter;

/**
 * Parsea y normaliza un ID de sede tolerando números, strings u objetos { idSede, id, sede_id }.
 */
function parsearSedeId(val) {
    if (val === null || val === undefined || val === "") return null;
    if (typeof val === "object") {
        val = val.idSede ?? val.id ?? val.sede_id;
    }
    if (val === "null" || val === "undefined") return null;
    const num = parseInt(val, 10);
    return isNaN(num) || num <= 0 ? null : num;
}

/**
 * Obtiene el ID de sede activa desde sessionStorage o el usuario autenticado.
 */
function obtenerSedeActualId() {
    const parsed = parsearSedeId(sessionStorage.getItem("ST-sede"));
    if (parsed) return parsed;
    if (props.usuarioactual?.sedes && props.usuarioactual.sedes.length > 0) {
        return props.usuarioactual.sedes[0].id;
    }
    const { user } = useAuth();
    if (user.value?.sedes && user.value.sedes.length > 0) {
        return user.value.sedes[0].id;
    }
    return 1;
}

// Referencia de DataTable
const dt = ref(null);

// Estado de la Sede
const sedeId = ref(obtenerSedeActualId());
const sedeNombre = ref(sessionStorage.getItem("ST-nomsede") || "Principal");

// Estado de carga y datos
const loadingBuscar = ref(false);
const isReconciliandoPacs = ref(false);
const registros = ref([]);
const tiposDocumento = ref([]);
const productosRayosX = ref([]);

const contadorRegistros = computed(() => {
    return registros.value.length;
});

// Filtros de búsqueda
const busqueda = ref({
    bus_documento: "",
    bus_nombre: "",
    bus_fech_ini: "",
    bus_fecha_fin: "",
});

// Control de Sidebar (Crear / Editar Paciente)
const sidebarVisible = ref(false);
const tituloModal = ref("Registrar Paciente");
const isEditing = ref(false);
const pacienteEditId = ref(null);
const loadingGuardar = ref(false);
const errores = ref({});

const registro = ref({
    tipo_documento_id: null,
    num_docu: "",
    nombres: "",
    sexo: "M",
    fec_naci: "",
    tel: "",
    cel: "",
    email: "",
    direccion: "",
});

// Control de Modal (Worklist)
const modalWorklistVisible = ref(false);
const pacienteSeleccionado = ref(null);
const loadingWorklist = ref(false);
const loadingEstaciones = ref(false);
const estacionesWorklist = ref([]);

const worklistForm = ref({
    paciente_id: null,
    sede_id: null,
    producto_id: null,
    accession_no: "",
    modality: "DX",
    station_aet: "",
});

// Control de Modal de Consulta de Interface (SAHI / HIS)
const sedeInterface = ref(false);
const sedeInterfaceSoftware = ref(null);
const modalInterfaceVisible = ref(false);
const loadingInterfacePacienteId = ref(null);
const loadingInterfaceAtenciones = ref(false);
const loadingInterfaceProductos = ref(false);
const pacienteInterface = ref(null);
const interfaceAtenciones = ref([]);
const interfaceAtencionSeleccionada = ref(null);
const interfaceProductos = ref([]);

function destroyDataTable() {
    safeDestroyDataTable(dt.value || "#tablaAdmisionPacientes");
}

function resolverNombreSede() {
    try {
        const nom = sessionStorage.getItem("ST-nomsede");
        if (nom) {
            sedeNombre.value = nom;
            return;
        }
        const sedesGuardadas = sessionStorage.getItem("ST-sedes");
        if (sedesGuardadas) {
            const lista = JSON.parse(sedesGuardadas);
            const encontrada = lista.find((s) => Number(s.id) === Number(sedeId.value));
            if (encontrada) {
                sedeNombre.value = encontrada.nom_sede;
                return;
            }
        }
    } catch {
        // Fallback
    }
}

async function consultarConfigSede() {
    if (!sedeId.value) {
        sedeInterface.value = false;
        sedeInterfaceSoftware.value = null;
        return;
    }
    try {
        const res = await axios.get(`/api/config-sedes/${sedeId.value}/buscarPorId`);
        const data = res?.data?.data || {};
        sedeInterfaceSoftware.value = data.interface_software || null;
        sedeInterface.value = Boolean(
            data.interface_software && data.interface_software.toLowerCase() === "sahi"
        );
        if (data.nom_sede) {
            sedeNombre.value = data.nom_sede;
        }
    } catch {
        sedeInterface.value = false;
        sedeInterfaceSoftware.value = null;
    }
}

async function onSedeSeleccionada(data) {
    const nuevoId = parsearSedeId(data) ?? parsearSedeId(sessionStorage.getItem("ST-sede"));
    if (nuevoId) {
        sedeId.value = nuevoId;
        sessionStorage.setItem("ST-sede", String(nuevoId));
        if (data && typeof data === "object" && data.nomSede) {
            sedeNombre.value = data.nomSede;
            sessionStorage.setItem("ST-nomsede", data.nomSede);
        } else {
            resolverNombreSede();
        }
        await consultarConfigSede();
        buscar();
        cargarEstacionesWorklist();
    }
}

async function buscar() {
    destroyDataTable();

    if (loadingBuscar.value) return;
    loadingBuscar.value = true;

    try {
        if (!sedeId.value) {
            sedeId.value = obtenerSedeActualId();
        }

        const payload = {
            sede_id: sedeId.value,
            bus_documento: busqueda.value.bus_documento,
            bus_nombre: busqueda.value.bus_nombre,
            bus_fech_ini: busqueda.value.bus_fech_ini,
            bus_fecha_fin: busqueda.value.bus_fecha_fin,
        };

        const res = await axios.post("/api/pacientes/listar", payload);
        let data = res.data?.data;
        if (typeof data === "string") {
            try {
                data = JSON.parse(data);
            } catch (_error) {
                data = [];
            }
        }

        destroyDataTable();
        registros.value = Array.isArray(data) ? data : [];
        loadingBuscar.value = false;

        nextTick(() => {
            destroyDataTable();
            safeInitDataTable(dt.value || "#tablaAdmisionPacientes", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
                pageLength: 10,
                language: {
                    paginate: {
                        first: "Primero",
                        last: "Último",
                        next: "Siguiente",
                        previous: "Anterior",
                    },
                    info: "Mostrando _START_ a _END_ de _TOTAL_ pacientes",
                    search: "Buscar:",
                    emptyTable: "No hay pacientes para mostrar",
                },
            });
        });
    } catch (err) {
        const msg = err.response?.data?.message || "Error consultando la lista de pacientes.";
        toastMessage({ heading: "Error", text: msg, icon: "error" });
    } finally {
        loadingBuscar.value = false;
    }
}

function btnLimpiar() {
    busqueda.value = {
        bus_documento: "",
        bus_nombre: "",
        bus_fech_ini: "",
        bus_fecha_fin: "",
    };
    registros.value = [];
    destroyDataTable();
    cerrarModal();
}

async function cargarCatalogos() {
    try {
        const res = await axios.get("/api/pacientes/catalogos");
        const data = res.data?.data || {};
        tiposDocumento.value = data.tipos_documento || [];
        productosRayosX.value = data.productos_rayos_x || [];
    } catch {
        // Fallback
    }
}

function calcularEdadPaciente(fecha) {
    if (!fecha) return "Sin fecha";
    const edad = calcularEdad(fecha);
    return edad !== null ? `${edad} años` : "Sin fecha";
}

function nuevoRegistro() {
    isEditing.value = false;
    pacienteEditId.value = null;
    tituloModal.value = "Registrar Paciente";
    errores.value = {};

    registro.value = {
        tipo_documento_id: tiposDocumento.value[0]?.id || null,
        num_docu: busqueda.value.bus_documento || "",
        nombres: "",
        sexo: "M",
        fec_naci: "",
        tel: "",
        cel: "",
        email: "",
        direccion: "",
    };

    sidebarVisible.value = true;
}

function mostrarRegistro(item) {
    isEditing.value = true;
    pacienteEditId.value = item.id;
    tituloModal.value = "Editar Paciente";
    errores.value = {};

    registro.value = {
        tipo_documento_id: item.tipo_documento_id,
        num_docu: item.num_docu,
        nombres: item.nombres,
        sexo: item.sexo || "M",
        fec_naci: item.fec_naci ? item.fec_naci.slice(0, 10) : "",
        tel: item.tel || "",
        cel: item.cel || "",
        email: item.email || "",
        direccion: item.direccion || "",
    };

    sidebarVisible.value = true;
}

function cerrarModal() {
    sidebarVisible.value = false;
    errores.value = {};
}

async function guardarPaciente() {
    if (loadingGuardar.value) return;
    loadingGuardar.value = true;
    errores.value = {};

    try {
        let res;
        if (isEditing.value) {
            res = await axios.put(`/api/pacientes/${pacienteEditId.value}`, registro.value);
        } else {
            res = await axios.post("/api/pacientes", registro.value);
        }

        toastMessage({
            heading: "Operación Exitosa",
            text: res.data?.message || "Paciente guardado correctamente.",
            icon: "success",
        });

        cerrarModal();
        buscar();
    } catch (err) {
        if (err.response?.status === 422) {
            errores.value = err.response.data?.errors || {};
            toastMessage({
                heading: "Validación",
                text: "Por favor complete los campos obligatorios.",
                icon: "warning",
            });
        } else {
            const msg = err.response?.data?.message || "Error al guardar los datos del paciente.";
            alertError("Error", msg);
        }
    } finally {
        loadingGuardar.value = false;
    }
}

async function abrirModalWorklist(item) {
    if (!sedeId.value) {
        sedeId.value = obtenerSedeActualId();
    }
    pacienteSeleccionado.value = item;

    worklistForm.value = {
        paciente_id: item.id,
        sede_id: sedeId.value,
        producto_id: productosRayosX.value[0]?.id || null,
        accession_no: "",
        modality: "DX",
        station_aet: "",
    };

    modalWorklistVisible.value = true;
    await Promise.all([generarAccessionSugerido(), cargarEstacionesWorklist()]);
}

async function cargarEstacionesWorklist() {
    loadingEstaciones.value = true;
    estacionesWorklist.value = [];
    try {
        if (!sedeId.value) {
            sedeId.value = obtenerSedeActualId();
        }
        if (!sedeId.value) {
            return;
        }
        const res = await axios.get("/api/pacientes/worklist-estaciones", {
            params: { sede_id: sedeId.value },
        });
        if (res.data?.status === 200 && Array.isArray(res.data?.data)) {
            estacionesWorklist.value = res.data.data;
            if (estacionesWorklist.value.length > 0) {
                const existe = estacionesWorklist.value.some(
                    (e) => e.aet === worklistForm.value.station_aet
                );
                if (!existe || !worklistForm.value.station_aet) {
                    worklistForm.value.station_aet = estacionesWorklist.value[0].aet;
                }
            } else {
                worklistForm.value.station_aet = "";
            }
        } else {
            estacionesWorklist.value = [];
            worklistForm.value.station_aet = "";
        }
    } catch {
        estacionesWorklist.value = [];
        worklistForm.value.station_aet = "";
    } finally {
        loadingEstaciones.value = false;
    }
}

function cerrarModalWorklist() {
    modalWorklistVisible.value = false;
    pacienteSeleccionado.value = null;
}

async function generarAccessionSugerido() {
    try {
        if (!sedeId.value) {
            sedeId.value = obtenerSedeActualId();
        }
        const res = await axios.get("/api/pacientes/accession-sugerido", {
            params: { sede_id: sedeId.value },
        });
        if (res.data?.data?.accession_no) {
            worklistForm.value.accession_no = res.data.data.accession_no;
        }
    } catch {
        const hoy = new Date().toISOString().slice(0, 10).replace(/-/g, "");
        worklistForm.value.accession_no = `RX-${hoy}-0001`;
    }
}

async function confirmarEnvioWorklist() {
    if (loadingWorklist.value) return;
    loadingWorklist.value = true;

    try {
        const payload = {
            paciente_id: worklistForm.value.paciente_id,
            sede_id: sedeId.value,
            producto_id: worklistForm.value.producto_id,
            accession_no: worklistForm.value.accession_no,
            modality: worklistForm.value.modality,
            station_aet: worklistForm.value.station_aet,
        };

        const res = await axios.post("/api/pacientes/enviar-worklist", payload);

        toastMessage({
            heading: "¡Enviado a Worklist!",
            text:
                res.data?.message ||
                "Paciente cargado en la consola del equipo de Rayos X exitosamente.",
            icon: "success",
        });

        cerrarModalWorklist();
        buscar();
    } catch (err) {
        if (err.response?.status === 422) {
            toastMessage({
                heading: "Atención",
                text: err.response.data?.message || "Verifique los campos.",
                icon: "warning",
            });
        } else {
            const msg =
                err.response?.data?.message ||
                "No fue posible registrar la orden en el PACS Dcm4chee.";
            alertError("Error de Conexión DICOM", msg);
        }
    } finally {
        loadingWorklist.value = false;
    }
}

async function cancelarOrdenWorklist(item) {
    const orden = item.orden_worklist_activa;
    if (!orden) return;

    const confirmado = await confirmAction(
        "¿Retirar de Worklist?",
        `¿Desea retirar la orden ${orden.accession_no} de la lista de trabajo del equipo de Rayos X? El tecnólogo ya no verá al paciente en su consola.`
    );

    if (!confirmado) return;

    try {
        const res = await axios.post("/api/pacientes/cancelar-worklist", {
            orden_id: orden.id,
        });

        toastMessage({
            heading: "Orden Retirada",
            text: res.data?.message || "La orden fue retirada de la Worklist exitosamente.",
            icon: "info",
        });

        buscar();
    } catch (err) {
        const msg = err.response?.data?.message || "Error retirando la orden de la Worklist.";
        alertError("Error", msg);
    }
}

async function reconciliarConPacs() {
    if (isReconciliandoPacs.value) return;
    isReconciliandoPacs.value = true;
    try {
        const res = await axios.post("/api/pacientes/worklist-reconciliar", {
            sede_id: sedeId.value,
        });

        if (res.data?.status === 200) {
            const data = res.data.data;
            if (data?.adquiridas > 0 || data?.completadas > 0) {
                alertSuccess(
                    "Reconciliación PACS exitosa",
                    `Se detectaron ${data.adquiridas} estudio(s) con imágenes en PACS y ${data.completadas} completado(s).`
                );
            } else {
                toastMessage({
                    heading: "PACS al día",
                    text: "No se detectaron nuevas órdenes adquiridas en este momento.",
                    icon: "info",
                });
            }
            buscar();
        } else {
            toastMessage({
                heading: "Atención",
                text: res.data?.message || "No se pudo reconciliar con el PACS.",
                icon: "warning",
            });
        }
    } catch (err) {
        const msg = getUserSafeError(err, "Error al reconciliar órdenes con el PACS.");
        alertError("Error de Reconciliación", msg);
    } finally {
        isReconciliandoPacs.value = false;
    }
}

async function abrirModalInterface(item) {
    if (!sedeInterface.value) {
        toastMessage({
            heading: "Interface no disponible",
            text: "La sede actual no cuenta con una interfaz HIS/SAHI configurada.",
            icon: "warning",
        });
        return;
    }

    pacienteInterface.value = item;
    interfaceAtenciones.value = [];
    interfaceAtencionSeleccionada.value = null;
    interfaceProductos.value = [];
    modalInterfaceVisible.value = true;
    loadingInterfacePacienteId.value = item.id;
    loadingInterfaceAtenciones.value = true;

    try {
        const res = await axios.get(
            `/api/interface-sahi-listar-atenciones/${encodeURIComponent(item.num_docu)}`
        );
        const atenciones = res.data?.data || [];
        interfaceAtenciones.value = atenciones;

        if (atenciones.length > 0) {
            interfaceAtencionSeleccionada.value = atenciones[0].IdAtencion;
            await cargarProductosAtencion(atenciones[0].IdAtencion);
        }
    } catch (err) {
        const msg =
            err.response?.data?.message ||
            "Error al consultar las atenciones del paciente en la interface.";
        toastMessage({ heading: "Error Interface", text: msg, icon: "error" });
    } finally {
        loadingInterfacePacienteId.value = null;
        loadingInterfaceAtenciones.value = false;
    }
}

async function onCambioAtencion() {
    if (!interfaceAtencionSeleccionada.value) return;
    await cargarProductosAtencion(interfaceAtencionSeleccionada.value);
}

async function recargarInterface() {
    if (!pacienteInterface.value) return;
    await abrirModalInterface(pacienteInterface.value);
}

async function cargarProductosAtencion(idAtencion) {
    if (!idAtencion) return;
    loadingInterfaceProductos.value = true;
    interfaceProductos.value = [];

    try {
        const res = await axios.post("/api/interface-sahi/listar/productos/atencion", {
            atencion_id: idAtencion,
            bus_nom_num_docu: idAtencion,
        });
        interfaceProductos.value = res.data?.data || [];
    } catch (err) {
        const msg =
            err.response?.data?.message || "Error consultando órdenes y facturas de la atención.";
        toastMessage({ heading: "Error", text: msg, icon: "error" });
    } finally {
        loadingInterfaceProductos.value = false;
    }
}

function cerrarModalInterface() {
    modalInterfaceVisible.value = false;
    pacienteInterface.value = null;
    interfaceAtenciones.value = [];
    interfaceAtencionSeleccionada.value = null;
    interfaceProductos.value = [];
}

async function seleccionarParaWorklist(prod) {
    const paciente = pacienteInterface.value;
    cerrarModalInterface();

    await abrirModalWorklist(paciente);

    const cupCode = String(prod.CodProducto || prod.cod_cups || "").trim();
    if (cupCode) {
        const encontrado = productosRayosX.value.find(
            (p) => String(p.cod_cups || "").trim() === cupCode
        );

        if (encontrado) {
            worklistForm.value.producto_id = encontrado.id;
            toastMessage({
                heading: "Producto Asociado",
                text: `Se asoció automáticamente "${encontrado.nom_produc}" (CUPS: ${cupCode}).`,
                icon: "success",
            });
        } else {
            toastMessage({
                heading: "Selección Manual",
                text: `El CUPS "${cupCode}" (${prod.NomProducto || "Estudio"}) no coincide con un producto de Rayos X en LIDiag. Por favor selecciónelo manualmente.`,
                icon: "warning",
            });
        }
    }
}

onMounted(async () => {
    resolverNombreSede();
    await consultarConfigSede();
    cargarCatalogos();
    buscar();

    if (emitter) {
        emitter.on("sedeSeleccionada", onSedeSeleccionada);
    }
});

onBeforeUnmount(() => {
    destroyDataTable();
    if (emitter) {
        emitter.off("sedeSeleccionada", onSedeSeleccionada);
    }
});
</script>

<style scoped>
.modal-backdrop-custom {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0.45);
    z-index: 1090;
    cursor: pointer;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
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
</style>
