<template>
    <div>
        <!-- BREADCRUMB -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Contratación</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <router-link to="/">Inicio</router-link>
                    </li>
                    <li class="breadcrumb-item active">Contratos</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-info btnDesplegarRigthSidebar"
                    @click="
                        actualizar = false;
                        mostrarRegistro();
                    "
                >
                    <i class="fa fa-plus-circle m-r-5"></i> Nuevo contrato
                </button>
            </div>
        </div>

        <!-- LISTADO -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Listado de contratos</h4>
                        <h6 class="card-subtitle">Gestión de contratación por sedes.</h6>
                        <table-skeleton-loader :visible="loadingListar" :rows="5" :columns="6" />
                        <empty-state-component
                            v-if="!loadingListar && registros.length === 0"
                            title="No hay contratos registrados"
                            description="No se encontraron contratos para la sede seleccionada."
                            icon="fa-file-text-o"
                        />
                        <div
                            v-show="!loadingListar && registros.length > 0"
                            class="table-responsive m-t-40"
                        >
                            <table
                                id="tablaContratos"
                                ref="dt"
                                class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th>Sede</th>
                                        <th>Fecha Inicio</th>
                                        <th>Fecha Fin</th>
                                        <th>Total M&eacute;dicos</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.sede?.nom_sede }}</td>
                                        <td>{{ formatDate(item.fecha_inicio) }}</td>
                                        <td>{{ formatDate(item.fecha_fin) }}</td>
                                        <td>
                                            <span class="label label-info">
                                                {{ contarMedicos(item) }} m&eacute;dicos
                                            </span>
                                        </td>
                                        <td>
                                            <span class="label label-success" v-if="item.estado"
                                                >Activo</span
                                            >
                                            <span class="label label-danger" v-else>Inactivo</span>
                                        </td>
                                        <td class="text-nowrap">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                @click="
                                                    actualizar = true;
                                                    mostrarRegistro(item);
                                                "
                                                title="Editar contrato"
                                            >
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm m-r-5"
                                                @click="abrirModalTarifasEntidad(item)"
                                                title="Plantilla 1: Tarifas Entidad (Hospital/Aseguradora) - Descarga e Importación"
                                            >
                                                <i class="fa fa-hospital-o"></i> Tarifas Entidad
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-success btn-sm m-r-5"
                                                @click="abrirModalTarifasMedico(item)"
                                                title="Plantilla 2: Tarifas Médico Lector - Descarga e Importación con Validación Estricta"
                                            >
                                                <i class="fa fa-user-md"></i> Tarifas Médico
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm m-r-5"
                                                @click="abrirDetalle(item)"
                                                title="Configurar detalle médicos-producto"
                                            >
                                                <i class="fa fa-cogs"></i> Detalle
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                @click="desactivarRegistro(item)"
                                                :disabled="loadingEliminarId === item.id"
                                                title="Eliminar contrato"
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

        <!-- SIDEBAR: CREAR/EDITAR CONTRATO -->
        <div class="right-sidebar" :class="{ 'shw-rside': sidebarVisible }">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <button
                        type="button"
                        class="right-side-toggle"
                        @click="btnCerrarModalForm()"
                        aria-label="Cerrar panel"
                    >
                        <i class="ti-close"></i>
                    </button>
                </div>
                <div class="r-panel-body">
                    <form @submit.prevent="guardarRegistro()">
                        <div class="form-body">
                            <div class="row p-t-20">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label" for="contrato-sede"
                                            >Sede</label
                                        >
                                        <input type="hidden" v-model="registro.sede_id" />
                                        <input
                                            id="contrato-sede"
                                            type="text"
                                            class="form-control"
                                            :value="sedeNombreActual"
                                            readonly
                                            disabled
                                        />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label" for="contrato-fecha-inicio"
                                            >Fecha Inicio <span class="text-danger">*</span></label
                                        >
                                        <input
                                            id="contrato-fecha-inicio"
                                            type="date"
                                            v-model="registro.fecha_inicio"
                                            class="form-control"
                                            @keyup.enter="guardarRegistro()"
                                        />
                                        <span class="text-danger" v-if="errores.fecha_inicio">
                                            {{ errores.fecha_inicio[0] }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label" for="contrato-fecha-fin"
                                            >Fecha Fin <span class="text-danger">*</span></label
                                        >
                                        <input
                                            id="contrato-fecha-fin"
                                            type="date"
                                            v-model="registro.fecha_fin"
                                            class="form-control"
                                            @keyup.enter="guardarRegistro()"
                                        />
                                        <span class="text-danger" v-if="errores.fecha_fin">
                                            {{ errores.fecha_fin[0] }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label" for="contrato-observaciones"
                                            >Observaciones</label
                                        >
                                        <textarea
                                            id="contrato-observaciones"
                                            v-model="registro.observaciones"
                                            class="form-control"
                                            rows="3"
                                            maxlength="500"
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="checkbox checkbox-success">
                                            <input
                                                type="checkbox"
                                                id="estado"
                                                v-model="registro.estado"
                                                :true-value="1"
                                                :false-value="0"
                                            />
                                            <label for="estado"> Activo </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions mt-3">
                                <button
                                    type="button"
                                    class="btn btn-success m-r-5"
                                    @click="guardarRegistro()"
                                    :disabled="loadingGuardar"
                                >
                                    <i v-if="loadingGuardar" class="fa fa-spinner fa-spin"></i>
                                    <i v-else class="fa fa-check"></i>
                                    {{ actualizar ? "Actualizar" : "Guardar" }}
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-inverse"
                                    @click="btnCerrarModalForm()"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- SIDEBAR: DETALLE (MÉDICOS + PRODUCTOS) -->
        <div class="right-sidebar sidebar-scroll" :class="{ 'shw-rside': detalleVisible }">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    Detalle: {{ contratoSeleccionado?.sede?.nom_sede }}
                    <button
                        type="button"
                        class="right-side-toggle"
                        @click="cerrarDetalle()"
                        aria-label="Cerrar panel"
                    >
                        <i class="ti-close"></i>
                    </button>
                </div>
                <div class="r-panel-body">
                    <!-- Acción: importar médicos-producto -->
                    <h5 class="card-title text-info">
                        <i class="fa fa-users"></i> M&Eacute;DICOS-PRODUCTO DEL CONTRATO
                    </h5>
                    <hr />
                    <div class="row">
                        <div class="col-md-12">
                            <button
                                type="button"
                                class="btn btn-outline-success btn-sm"
                                @click="abrirModalTarifasMedico(contratoSeleccionado)"
                            >
                                <i class="fa fa-user-md m-r-5"></i>
                                Importar tarifas médico (Plantilla 2)
                            </button>
                        </div>
                    </div>

                    <!-- Panel de cobertura de productos -->
                    <div class="card mb-3" v-if="cobertura">
                        <div class="card-body py-2">
                            <h6 class="card-subtitle mb-2">
                                Cobertura de productos
                                <span
                                    class="badge"
                                    :class="
                                        cobertura.faltantes.length
                                            ? 'badge-warning'
                                            : 'badge-success'
                                    "
                                >
                                    {{ cobertura.tarifados }} / {{ cobertura.total }}
                                </span>
                            </h6>
                            <p v-if="!cobertura.faltantes.length" class="text-success mb-0">
                                Todos los productos que requieren lectura tienen tarifa.
                            </p>
                            <div v-else style="max-height: 160px; overflow-y: auto">
                                <p class="text-warning mb-1">
                                    Estos productos se pueden leer sin generar pago:
                                </p>
                                <div
                                    v-for="p in cobertura.faltantes"
                                    :key="p.id"
                                    class="small text-muted"
                                >
                                    <strong>{{ p.cod_cups }}</strong> - {{ p.nom_produc }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de médicos-producto -->
                    <div class="col-md-12 table-responsive table-scroll mt-3">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>M&eacute;dico</th>
                                    <th>C&oacute;digo</th>
                                    <th>Producto</th>
                                    <th>Val Consulta</th>
                                    <th>Val Urgencias</th>
                                    <th>Fecha Ingreso</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in detallesLocales" :key="item.id">
                                    <td>{{ item.medico_name }}</td>
                                    <td>{{ item.cod_cups }}</td>
                                    <td>{{ item.nom_produc }}</td>
                                    <td>
                                        <input
                                            type="number"
                                            v-model.number="item.valor_consulta"
                                            class="form-control"
                                            step="0.01"
                                            min="0"
                                        />
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            v-model.number="item.valor_urgencias"
                                            class="form-control"
                                            step="0.01"
                                            min="0"
                                        />
                                    </td>
                                    <td>
                                        <input
                                            type="date"
                                            v-model="item.fecha_ingreso"
                                            class="form-control"
                                        />
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                            @click="quitarProductoDetalle(index)"
                                            title="Quitar producto"
                                        >
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!detallesLocales.length">
                                    <td colspan="7" class="text-center text-muted">
                                        No hay m&eacute;dicos-producto agregados. Use el
                                        bot&oacute;n "Importar tarifas m&eacute;dico" o los botones
                                        de tarifas del listado.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Botones -->
                    <div class="form-actions mt-3">
                        <button
                            type="button"
                            class="btn btn-success m-r-5"
                            @click="guardarDetalles()"
                            :disabled="loadingGuardarDetalle"
                        >
                            <i v-if="loadingGuardarDetalle" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-check"></i>
                            Guardar
                        </button>
                        <button type="button" class="btn btn-inverse" @click="cerrarDetalle()">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: TARIFAS DE ENTIDAD / HOSPITAL (PLANTILLA 1) -->
        <div
            class="modal fade"
            id="modalTarifasEntidad"
            tabindex="-1"
            role="dialog"
            aria-labelledby="modalTarifasEntidadLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="modalTarifasEntidadLabel">
                            <i class="fa fa-hospital-o m-r-5 text-primary"></i> Tarifas Entidad /
                            Hospital (Plantilla 1)
                        </h4>
                        <button
                            type="button"
                            class="close"
                            @click="cerrarModalTarifasEntidad()"
                            aria-label="Cerrar"
                            aria-hidden="true"
                        >
                            &times;
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2">
                            <strong>Contrato #{{ modalEntidadForm.contratoId }}</strong> — Sede:
                            <strong>{{ modalEntidadForm.sedeNombre }}</strong>
                            <br />
                            <small
                                >Vigencia: {{ formatDate(modalEntidadForm.fechaInicio) }} al
                                {{ formatDate(modalEntidadForm.fechaFin) }}</small
                            >
                        </div>

                        <div class="card border mb-3">
                            <div class="card-body p-3">
                                <h5 class="card-title font-weight-bold">
                                    <i class="fa fa-download text-primary m-r-5"></i> 1. Descargar
                                    Plantilla de Tarifas de la Entidad
                                </h5>
                                <p class="text-muted small mb-2">
                                    Descargue el archivo Excel con metadatos oficiales (ID Contrato
                                    en A2, Sede en A3 y Vigencia en A4). Incluye el catálogo de
                                    productos exigibles.
                                </p>
                                <button
                                    type="button"
                                    class="btn btn-outline-success btn-sm"
                                    @click="descargarPlantillaEntidad(modalEntidadForm.contratoId)"
                                >
                                    <i class="fa fa-file-excel-o m-r-5"></i> Descargar Plantilla de
                                    Entidad (Excel)
                                </button>
                            </div>
                        </div>

                        <div class="card border mb-0">
                            <div class="card-body p-3">
                                <h5 class="card-title font-weight-bold">
                                    <i class="fa fa-upload text-info m-r-5"></i> 2. Importar y
                                    Analizar Tarifas con Vista Previa
                                </h5>
                                <p class="text-muted small mb-2">
                                    Seleccione el archivo diligenciado. El backend validará la celda
                                    A2 y generará una
                                    <strong>vista previa no destructiva</strong> con el desglose de
                                    altas, tarifas actualizadas, bajas lógicas y cobertura.
                                </p>
                                <div class="form-group mb-2">
                                    <label
                                        class="control-label font-weight-bold"
                                        for="archivo-entidad-input"
                                    >
                                        Archivo Excel de Entidad (.xlsx, .xls)
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        id="archivo-entidad-input"
                                        type="file"
                                        ref="archivoEntidadRef"
                                        accept=".xlsx,.xls"
                                        class="form-control"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-default waves-effect"
                            @click="cerrarModalTarifasEntidad()"
                        >
                            Cerrar
                        </button>
                        <button
                            type="button"
                            class="btn btn-primary waves-effect waves-light"
                            @click="procesarPreviewEntidad()"
                            :disabled="loadingPreviewEntidad"
                        >
                            <i v-if="loadingPreviewEntidad" class="fa fa-spinner fa-spin m-r-5"></i>
                            <i v-else class="fa fa-search m-r-5"></i>
                            Analizar y Ver Vista Previa
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: TARIFAS DE MÉDICO LECTOR (PLANTILLA 2) -->
        <div
            class="modal fade"
            id="modalTarifasMedico"
            tabindex="-1"
            role="dialog"
            aria-labelledby="modalTarifasMedicoLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="modalTarifasMedicoLabel">
                            <i class="fa fa-user-md m-r-5 text-success"></i> Tarifas de Médicos
                            Lectores (Plantilla 2)
                        </h4>
                        <button
                            type="button"
                            class="close"
                            @click="cerrarModalTarifasMedico()"
                            aria-label="Cerrar"
                            aria-hidden="true"
                        >
                            &times;
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2">
                            <strong>Contrato #{{ modalMedicoForm.contratoId }}</strong> — Sede:
                            <strong>{{ modalMedicoForm.sedeNombre }}</strong>
                            <br />
                            <small
                                >Vigencia: {{ formatDate(modalMedicoForm.fechaInicio) }} al
                                {{ formatDate(modalMedicoForm.fechaFin) }}</small
                            >
                        </div>

                        <div class="form-group">
                            <label class="control-label font-weight-bold" for="sel-medico-tarifas">
                                Médico Lector <span class="text-danger">*</span>
                            </label>
                            <Select2
                                id="sel-medico-tarifas"
                                :value="modalMedicoForm.medico_id"
                                :options="medicoOptions"
                                :label-fields="['name']"
                                width="100%"
                                :allow-clear="true"
                                placeholder="Seleccione el médico lector..."
                                dropdown-parent-selector="#modalTarifasMedico .modal-body"
                                @input="(val) => (modalMedicoForm.medico_id = val)"
                            />
                            <small class="text-muted">
                                Seleccione el médico para descargar su plantilla personalizada o
                                para procesar su importación.
                            </small>
                        </div>

                        <div class="card border mb-3">
                            <div class="card-body p-3">
                                <h5 class="card-title font-weight-bold">
                                    <i class="fa fa-download text-success m-r-5"></i> 1. Descargar
                                    Plantilla del Médico Seleccionado
                                </h5>
                                <p class="text-muted small mb-2">
                                    Genera el archivo con los metadatos inyectados: Contrato en A2,
                                    Sede en A3, ID Médico en A4 y Nombre en B4, con columnas de
                                    Consulta Externa y Urgencias/Hosp.
                                </p>
                                <button
                                    type="button"
                                    class="btn btn-outline-success btn-sm"
                                    :disabled="!modalMedicoForm.medico_id"
                                    @click="
                                        descargarPlantillaMedico(
                                            modalMedicoForm.contratoId,
                                            modalMedicoForm.medico_id
                                        )
                                    "
                                >
                                    <i class="fa fa-file-excel-o m-r-5"></i> Descargar Plantilla de
                                    Médico (Excel)
                                </button>
                            </div>
                        </div>

                        <div class="card border mb-0">
                            <div class="card-body p-3">
                                <h5 class="card-title font-weight-bold">
                                    <i class="fa fa-upload text-primary m-r-5"></i> 2. Importar
                                    Plantilla con Validación Estricta
                                </h5>
                                <p class="text-muted small mb-2">
                                    El backend verificará obligatoriamente que la celda A4 del
                                    archivo coincida exactamente con el médico seleccionado. Se
                                    generará la vista previa con el diff y la cobertura antes de
                                    persistir.
                                </p>
                                <div class="form-group mb-2">
                                    <label
                                        class="control-label font-weight-bold"
                                        for="archivo-medico-input"
                                    >
                                        Archivo Excel de Tarifas del Médico (.xlsx, .xls)
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        id="archivo-medico-input"
                                        type="file"
                                        ref="archivoMedicoRef"
                                        accept=".xlsx,.xls"
                                        class="form-control"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-default waves-effect"
                            @click="cerrarModalTarifasMedico()"
                        >
                            Cerrar
                        </button>
                        <button
                            type="button"
                            class="btn btn-success waves-effect waves-light"
                            @click="procesarPreviewMedico()"
                            :disabled="loadingPreviewMedico || !modalMedicoForm.medico_id"
                        >
                            <i v-if="loadingPreviewMedico" class="fa fa-spinner fa-spin m-r-5"></i>
                            <i v-else class="fa fa-search m-r-5"></i>
                            Analizar y Ver Vista Previa
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: VISTA PREVIA (DIFF NO DESTRUCTIVO Y COBERTURA) -->
        <div
            class="modal fade"
            id="modalPreviewDiff"
            tabindex="-1"
            role="dialog"
            aria-labelledby="modalPreviewDiffLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="modalPreviewDiffLabel">
                            <i class="fa fa-balance-scale m-r-5 text-warning"></i>
                            Vista Previa:
                            {{
                                previewData.tipo === "medico"
                                    ? "Tarifas Médico - " + (previewData.medico?.name || "")
                                    : "Tarifas Entidad / Hospital"
                            }}
                        </h4>
                        <button
                            type="button"
                            class="close"
                            @click="cerrarModalPreview()"
                            aria-label="Cerrar"
                            aria-hidden="true"
                        >
                            &times;
                        </button>
                    </div>
                    <div class="modal-body p-3">
                        <!-- KPI Badges -->
                        <div class="row m-b-15">
                            <div class="col-md-12 d-flex flex-wrap align-items-center">
                                <span class="badge badge-success badge-pill p-2 m-r-10 font-14">
                                    <i class="fa fa-plus-circle m-r-5"></i> Altas:
                                    {{ previewData.resumen?.altas || 0 }}
                                </span>
                                <span
                                    class="badge badge-warning badge-pill p-2 m-r-10 font-14 text-dark"
                                >
                                    <i class="fa fa-refresh m-r-5"></i> Actualizados:
                                    {{ previewData.resumen?.cambios || 0 }}
                                </span>
                                <span class="badge badge-danger badge-pill p-2 m-r-10 font-14">
                                    <i class="fa fa-minus-circle m-r-5"></i> Bajas Lógicas
                                    (Retiros): {{ previewData.resumen?.retiros || 0 }}
                                </span>
                                <span class="badge badge-secondary badge-pill p-2 m-r-10 font-14">
                                    <i class="fa fa-check m-r-5"></i> Sin Cambios:
                                    {{ previewData.resumen?.sin_cambios || 0 }}
                                </span>
                                <span class="badge badge-info badge-pill p-2 font-14 ml-auto">
                                    <i class="fa fa-shield m-r-5"></i> Cobertura:
                                    {{ previewData.cobertura?.tarifados || 0 }} /
                                    {{ previewData.cobertura?.total_catalogo || 0 }}
                                </span>
                            </div>
                        </div>

                        <!-- Tabs de Navegación -->
                        <ul class="nav nav-tabs customtab" role="tablist">
                            <li class="nav-item">
                                <a
                                    class="nav-link"
                                    :class="{ active: previewTabActiva === 'altas' }"
                                    href="javascript:void(0)"
                                    @click="previewTabActiva = 'altas'"
                                >
                                    <i class="fa fa-plus-circle text-success m-r-5"></i> Altas
                                    <span class="badge badge-success badge-pill m-l-5">{{
                                        previewData.altas?.length || 0
                                    }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a
                                    class="nav-link"
                                    :class="{ active: previewTabActiva === 'cambios' }"
                                    href="javascript:void(0)"
                                    @click="previewTabActiva = 'cambios'"
                                >
                                    <i class="fa fa-refresh text-warning m-r-5"></i> Tarifas
                                    Actualizadas
                                    <span class="badge badge-warning badge-pill m-l-5 text-dark">{{
                                        previewData.cambios?.length || 0
                                    }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a
                                    class="nav-link"
                                    :class="{ active: previewTabActiva === 'retiros' }"
                                    href="javascript:void(0)"
                                    @click="previewTabActiva = 'retiros'"
                                >
                                    <i class="fa fa-minus-circle text-danger m-r-5"></i> Bajas
                                    Lógicas (Retiros)
                                    <span class="badge badge-danger badge-pill m-l-5">{{
                                        previewData.retiros?.length || 0
                                    }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a
                                    class="nav-link"
                                    :class="{ active: previewTabActiva === 'cobertura' }"
                                    href="javascript:void(0)"
                                    @click="previewTabActiva = 'cobertura'"
                                >
                                    <i class="fa fa-exclamation-triangle text-info m-r-5"></i>
                                    Cobertura Faltante
                                    <span
                                        class="badge badge-pill m-l-5"
                                        :class="
                                            previewData.cobertura?.faltantes?.length
                                                ? 'badge-warning text-dark'
                                                : 'badge-success'
                                        "
                                    >
                                        {{ previewData.cobertura?.faltantes?.length || 0 }}
                                    </span>
                                </a>
                            </li>
                        </ul>

                        <!-- Contenido de las pestañas -->
                        <div class="tab-content m-t-15" style="max-height: 420px; overflow-y: auto">
                            <!-- TAB 1: ALTAS -->
                            <div v-show="previewTabActiva === 'altas'">
                                <div
                                    v-if="!previewData.altas?.length"
                                    class="text-center text-muted py-4"
                                >
                                    <i class="fa fa-info-circle fa-2x m-b-10 text-muted"></i>
                                    <p>No se encontraron productos nuevos para agregar.</p>
                                </div>
                                <div v-else class="table-responsive">
                                    <table class="table table-sm table-bordered table-striped">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>CUPS</th>
                                                <th>Producto</th>
                                                <th v-if="previewData.tipo === 'entidad'">
                                                    Valor Tarifa
                                                </th>
                                                <th v-if="previewData.tipo === 'medico'">
                                                    Consulta Externa
                                                </th>
                                                <th v-if="previewData.tipo === 'medico'">
                                                    Urgencias / Hosp
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="(item, idx) in previewData.altas"
                                                :key="'alta_' + idx"
                                            >
                                                <td class="font-weight-bold">
                                                    {{ item.cod_cups }}
                                                </td>
                                                <td>{{ item.nom_produc }}</td>
                                                <td
                                                    v-if="previewData.tipo === 'entidad'"
                                                    class="text-success font-weight-bold"
                                                >
                                                    {{ formatMoney(item.valor_contrato) }}
                                                </td>
                                                <td
                                                    v-if="previewData.tipo === 'medico'"
                                                    class="text-success font-weight-bold"
                                                >
                                                    {{ formatMoney(item.valor_consulta) }}
                                                </td>
                                                <td
                                                    v-if="previewData.tipo === 'medico'"
                                                    class="text-success font-weight-bold"
                                                >
                                                    {{ formatMoney(item.valor_urgencias) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- TAB 2: CAMBIOS -->
                            <div v-show="previewTabActiva === 'cambios'">
                                <div
                                    v-if="!previewData.cambios?.length"
                                    class="text-center text-muted py-4"
                                >
                                    <i class="fa fa-info-circle fa-2x m-b-10 text-muted"></i>
                                    <p>No hay tarifas existentes modificadas en este archivo.</p>
                                </div>
                                <div v-else class="table-responsive">
                                    <table class="table table-sm table-bordered table-striped">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>CUPS</th>
                                                <th>Producto</th>
                                                <th v-if="previewData.tipo === 'entidad'">
                                                    Tarifa Anterior
                                                </th>
                                                <th v-if="previewData.tipo === 'entidad'">
                                                    Nueva Tarifa
                                                </th>
                                                <th v-if="previewData.tipo === 'medico'">
                                                    Consulta (Ant -> Nueva)
                                                </th>
                                                <th v-if="previewData.tipo === 'medico'">
                                                    Urgencias (Ant -> Nueva)
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="(item, idx) in previewData.cambios"
                                                :key="'cambio_' + idx"
                                            >
                                                <td class="font-weight-bold">
                                                    {{ item.cod_cups }}
                                                </td>
                                                <td>{{ item.nom_produc }}</td>
                                                <td
                                                    v-if="previewData.tipo === 'entidad'"
                                                    class="text-muted"
                                                >
                                                    {{ formatMoney(item.valor_anterior) }}
                                                </td>
                                                <td
                                                    v-if="previewData.tipo === 'entidad'"
                                                    class="text-primary font-weight-bold"
                                                >
                                                    {{ formatMoney(item.valor_contrato) }}
                                                    <span
                                                        v-if="item.reactiva"
                                                        class="badge badge-info m-l-5"
                                                        >Reactivado</span
                                                    >
                                                </td>
                                                <td v-if="previewData.tipo === 'medico'">
                                                    <span class="text-muted">{{
                                                        formatMoney(item.valor_consulta_anterior)
                                                    }}</span>
                                                    &rarr;
                                                    <span class="text-primary font-weight-bold">{{
                                                        formatMoney(item.valor_consulta)
                                                    }}</span>
                                                </td>
                                                <td v-if="previewData.tipo === 'medico'">
                                                    <span class="text-muted">{{
                                                        formatMoney(item.valor_urgencias_anterior)
                                                    }}</span>
                                                    &rarr;
                                                    <span class="text-primary font-weight-bold">{{
                                                        formatMoney(item.valor_urgencias)
                                                    }}</span>
                                                    <span
                                                        v-if="item.reactiva"
                                                        class="badge badge-info m-l-5"
                                                        >Reactivado</span
                                                    >
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- TAB 3: RETIROS -->
                            <div v-show="previewTabActiva === 'retiros'">
                                <div class="alert alert-warning py-2 mb-2">
                                    <i class="fa fa-info-circle m-r-5"></i>
                                    <strong>Baja lógica garantizada:</strong> Los siguientes
                                    registros pasarán a estado inactivo (<code>estado = 0</code
                                    >{{
                                        previewData.tipo === "medico"
                                            ? " y fecha_retiro = hoy"
                                            : ""
                                    }}).
                                    <strong>Ningún registro se eliminará físicamente</strong> de la
                                    base de datos para preservar la trazabilidad asistencial y
                                    contable.
                                </div>
                                <div
                                    v-if="!previewData.retiros?.length"
                                    class="text-center text-muted py-4"
                                >
                                    <i class="fa fa-check-circle fa-2x m-b-10 text-success"></i>
                                    <p>
                                        No hay productos para retirar; todas las tarifas existentes
                                        se mantienen.
                                    </p>
                                </div>
                                <div v-else class="table-responsive">
                                    <table class="table table-sm table-bordered table-striped">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>CUPS</th>
                                                <th>Producto</th>
                                                <th>Tarifa Anterior</th>
                                                <th>Motivo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="(item, idx) in previewData.retiros"
                                                :key="'retiro_' + idx"
                                            >
                                                <td class="font-weight-bold text-danger">
                                                    {{ item.cod_cups }}
                                                </td>
                                                <td>{{ item.nom_produc }}</td>
                                                <td>
                                                    {{
                                                        formatMoney(
                                                            item.valor_contrato ||
                                                                item.valor_consulta
                                                        )
                                                    }}
                                                </td>
                                                <td class="text-muted small">
                                                    <span class="badge badge-danger m-r-5"
                                                        >Baja Lógica</span
                                                    >
                                                    {{ item.motivo }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- TAB 4: COBERTURA -->
                            <div v-show="previewTabActiva === 'cobertura'">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <p class="text-muted mb-0 small">
                                        Productos exigibles del catálogo institucional (requieren
                                        lectura) que
                                        <strong>NO tendrán tarifa configurada</strong> tras aplicar
                                        este documento.
                                    </p>
                                    <input
                                        type="text"
                                        v-model="busquedaCobertura"
                                        placeholder="Filtrar por CUPS o nombre..."
                                        class="form-control form-control-sm w-auto"
                                    />
                                </div>

                                <div
                                    v-if="!coberturaFiltrada.length"
                                    class="alert alert-success py-3 text-center mb-0"
                                >
                                    <i class="fa fa-check-circle fa-2x m-b-5"></i>
                                    <h5>¡Cobertura Completa!</h5>
                                    <p class="mb-0">
                                        Todos los productos exigibles del catálogo cuentan con
                                        tarifa asignada.
                                    </p>
                                </div>
                                <div v-else class="table-responsive">
                                    <table class="table table-sm table-bordered table-striped">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>CUPS</th>
                                                <th>Producto Exigible Sin Tarifa</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="(prod, idx) in coberturaFiltrada"
                                                :key="'falt_' + idx"
                                            >
                                                <td class="font-weight-bold text-warning">
                                                    {{ prod.cod_cups }}
                                                </td>
                                                <td>{{ prod.nom_produc }}</td>
                                                <td>
                                                    <span class="badge badge-warning text-dark">
                                                        <i class="fa fa-clock-o m-r-5"></i> Sin
                                                        Tarifa
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-default waves-effect"
                            @click="cerrarModalPreview()"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            class="btn btn-success waves-effect waves-light"
                            @click="ejecutarConfirmacionImportacion()"
                            :disabled="loadingConfirmarImportacion"
                        >
                            <i
                                v-if="loadingConfirmarImportacion"
                                class="fa fa-spinner fa-spin m-r-5"
                            ></i>
                            <i v-else class="fa fa-check m-r-5"></i>
                            Confirmar e Importar Tarifas
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, computed, getCurrentInstance } from "vue";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { toastMessage, confirmAction } from "@/helpers/alerts.js";
import axios from "axios";
import Select2 from "@/components/shared/Select2.vue";

const { proxy } = getCurrentInstance();

// ─── REFERENCIAS ──────────────────────────────────────────────────────────────

/**
 * Referencia al elemento DOM de la tabla DataTable
 * @type {import('vue').Ref<HTMLElement|null>}
 */
const dt = ref(null);

/**
 * Listado de contratos cargados desde la API
 * @type {import('vue').Ref<Array>}
 */
const registros = ref([]);

/**
 * Formulario del contrato (crear/editar)
 * @type {import('vue').Ref<Object>}
 */
const registro = ref({
    id: 0,
    sede_id: "",
    fecha_inicio: "",
    fecha_fin: "",
    observaciones: "",
    estado: 1,
});

/**
 * Título dinámico del sidebar de crear/editar contrato
 * @type {import('vue').Ref<string>}
 */
const tituloModal = ref("Nuevo contrato");

/**
 * Flag: true = modo actualizar, false = modo crear
 * @type {import('vue').Ref<boolean>}
 */
const actualizar = ref(false);

/**
 * Errores de validación del formulario principal
 * @type {import('vue').Ref<Object>}
 */
const errores = ref({});

// ─── FLAGS DE CARGA ───────────────────────────────────────────────────────────

/**
 * Flag de carga al listar contratos
 * @type {import('vue').Ref<boolean>}
 */
const loadingListar = ref(false);

/**
 * Flag de carga al guardar contrato
 * @type {import('vue').Ref<boolean>}
 */
const loadingGuardar = ref(false);

/**
 * ID del contrato que se está eliminando (para deshabilitar botón)
 * @type {import('vue').Ref<number|null>}
 */
const loadingEliminarId = ref(null);

// ─── VISIBILIDAD DE SIDEBARS ──────────────────────────────────────────────────

/**
 * Visibilidad del sidebar de crear/editar contrato
 * @type {import('vue').Ref<boolean>}
 */
const sidebarVisible = ref(false);

/**
 * Visibilidad del sidebar de detalle (médicos-producto)
 * @type {import('vue').Ref<boolean>}
 */
const detalleVisible = ref(false);

// ─── SEDE ACTUAL DESDE SESSION / LOCAL STORAGE ────────────────────────────────

/**
 * Obtiene la sede actual desde sessionStorage o localStorage (key: ST-sede).
 * Soporta formato numérico directo o JSON almacenado.
 *
 * @returns {{ id: number, nom_sede: string } | null}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
function getSedeActual() {
    try {
        const raw =
            window.sessionStorage.getItem("ST-sede") || window.localStorage.getItem("ST-sede");
        if (!raw) return null;
        const nomSede =
            window.sessionStorage.getItem("ST-nomsede") ||
            window.localStorage.getItem("ST-nomsede") ||
            "Sede";

        if (!Number.isNaN(Number(raw))) {
            return {
                id: Number(raw),
                nom_sede: nomSede,
            };
        }

        const parsed = JSON.parse(raw);
        return {
            id: parsed.id ?? parsed.sede_id ?? null,
            nom_sede: parsed.nom_sede ?? parsed.nombre ?? nomSede,
        };
    } catch {
        return null;
    }
}

/**
 * Sede actual del usuario logueado, obtenida de sessionStorage o localStorage
 * @type {import('vue').Ref<{ id: number, nom_sede: string }|null>}
 */
const sedeActual = ref(getSedeActual());

/**
 * Listener reactivo ante cambio de sede en el navbar.
 *
 * @param {Object} event
 * @returns {void}
 */
function onSedeSeleccionada(event) {
    const rawSede =
        event?.sede ?? sessionStorage.getItem("ST-sede") ?? localStorage.getItem("ST-sede");
    const nuevaSedeId = rawSede ? Number(rawSede) : null;
    if (!nuevaSedeId || Number(sedeActual.value?.id) === nuevaSedeId) return;

    sedeActual.value = getSedeActual();
    listar();
}

/**
 * Nombre legible de la sede actual para mostrar en el formulario (readonly)
 * @type {import('vue').ComputedRef<string>}
 */
const sedeNombreActual = computed(() => sedeActual.value?.nom_sede ?? "No disponible");

// ─── DETALLE ──────────────────────────────────────────────────────────────────

/**
 * Contrato seleccionado para ver/editar su detalle de médicos/productos
 * @type {import('vue').Ref<Object|null>}
 */
const contratoSeleccionado = ref(null);

/**
 * Cobertura de productos del contrato abierto en el detalle.
 * Estructura: { total, tarifados, faltantes: [{ id, cod_cups, nom_produc }] } o null si no se ha cargado.
 * @type {import('vue').Ref<Object|null>}
 */
const cobertura = ref(null);

/**
 * Flag de carga al consultar la cobertura de productos del contrato
 * @type {import('vue').Ref<boolean>}
 */
const loadingCobertura = ref(false);

/**
 * Lista local de médicos-producto del contrato en edición.
 * Cada elemento: { id, medico_id, medico_name, producto_id, cod_cups, nom_produc, valor_consulta, valor_urgencias }
 * @type {import('vue').Ref<Array>}
 */
const detallesLocales = ref([]);

/**
 * Catálogo de usuarios médicos (con reg_med no nulo)
 * @type {import('vue').Ref<Array>}
 */
const medicos = ref([]);

/**
 * Flag de carga al guardar detalles
 * @type {import('vue').Ref<boolean>}
 */
const loadingGuardarDetalle = ref(false);

// ─── COMPUTED: OPTIONS PARA SELECT2 ──────────────────────────────────────────

/**
 * Opciones formateadas para Select2 de médicos.
 * Convierte IDs a String para compatibilidad con Select2.
 *
 * @returns {Array<{id: string, name: string}>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
const medicoOptions = computed(() =>
    medicos.value.map((m) => ({
        id: String(m.id),
        name: m.reg_med ? `${m.reg_med} - ${m.name}` : m.name,
    }))
);

// ─── UTILIDADES ───────────────────────────────────────────────────────────────

/**
 * Formatea una fecha ISO a formato local colombiano (dd/mm/yyyy)
 *
 * @param {string|null} dateStr - Fecha en formato ISO o null
 * @returns {string} Fecha formateada o cadena vacía
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
function formatDate(dateStr) {
    if (!dateStr) return "";
    const cleanDate = String(dateStr).split(/[ T]/)[0];
    const parts = cleanDate.split("-");
    if (parts.length === 3) {
        const [year, month, day] = parts;
        return `${day.padStart(2, "0")}/${month.padStart(2, "0")}/${year}`;
    }
    const d = new Date(dateStr);
    return isNaN(d.getTime()) ? "" : d.toLocaleDateString("es-CO");
}

/**
 * Cuenta los médicos únicos vinculados a un contrato.
 * Como cada registro de contratos_medicos es un par médico+producto,
 * se descuentan duplicados por medico_id para obtener el total real.
 *
 * @param {Object} item - Contrato con su colección medicos cargada
 * @returns {number} Cantidad de médicos distintos del contrato
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-24
 */
function contarMedicos(item) {
    const unicos = new Set((item.medicos || []).map((m) => m.medico_id));
    return unicos.size;
}

// ─── LISTADO PRINCIPAL ────────────────────────────────────────────────────────

/**
 * Carga el listado de contratos desde la API y renderiza la DataTable.
 * Destruye la instancia previa antes de inicializar una nueva.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
async function listar() {
    if (loadingListar.value) return;
    loadingListar.value = true;
    try {
        safeDestroyDataTable(dt.value || "#tablaContratos");
        const res = await axios.get("/api/contratos");
        let data = res.data?.data?.data ?? res.data?.data;
        if (typeof data === "string") {
            try {
                data = JSON.parse(data);
            } catch (_e) {
                data = [];
            }
        }
        registros.value = Array.isArray(data) ? data : [];
    } catch (error) {
        console.error(error);
        toastMessage({ heading: "Error", text: "Error al cargar los contratos", icon: "error" });
        registros.value = [];
    } finally {
        loadingListar.value = false;
        await nextTick();
        if (registros.value.length > 0) {
            safeDestroyDataTable(dt.value || "#tablaContratos");
            safeInitDataTable(dt.value || "#tablaContratos", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
                autoWidth: false,
                columnDefs: [{ targets: [5], orderable: false }],
            });
        }
    }
}

// ─── CRUD PRINCIPAL ───────────────────────────────────────────────────────────

/**
 * Abre el sidebar para crear o actualizar un contrato.
 * Si se pasa un item, carga el formulario en modo edición.
 *
 * @param {Object|null} item - Contrato existente para edición, o null para crear nuevo
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
function mostrarRegistro(item = null) {
    if (detalleVisible.value) {
        detalleVisible.value = false;
    }
    errores.value = {};
    const sedeId = sedeActual.value?.id ?? "";
    if (item) {
        tituloModal.value = "Actualizar contrato";
        registro.value = {
            id: item.id,
            sede_id: item.sede_id,
            fecha_inicio: item.fecha_inicio?.split("T")[0] || "",
            fecha_fin: item.fecha_fin?.split("T")[0] || "",
            observaciones: item.observaciones || "",
            estado: item.estado ? 1 : 0,
        };
    } else {
        tituloModal.value = "Nuevo contrato";
        registro.value = {
            id: 0,
            sede_id: sedeId,
            fecha_inicio: "",
            fecha_fin: "",
            observaciones: "",
            estado: 1,
        };
    }
    sidebarVisible.value = true;
    nextTick(() => {
        document.getElementById("sede_id")?.focus();
    });
}

/**
 * Cierra el sidebar de crear/editar contrato eliminando la clase CSS.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
function btnCerrarModalForm() {
    sidebarVisible.value = false;
}

/**
 * Guarda o actualiza el contrato actual según el modo (crear/actualizar).
 * Envía los datos como JSON string en el campo 'registro'.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
async function guardarRegistro() {
    if (loadingGuardar.value) return;
    loadingGuardar.value = true;
    errores.value = {};
    try {
        const payload = {
            registro: JSON.stringify({
                sede_id: registro.value.sede_id,
                fecha_inicio: registro.value.fecha_inicio,
                fecha_fin: registro.value.fecha_fin,
                observaciones: registro.value.observaciones,
                estado: registro.value.estado,
            }),
        };
        if (actualizar.value) {
            await axios.put(`/api/contratos/${registro.value.id}`, payload);
            toastMessage({
                heading: "Ok",
                text: "Contrato actualizado correctamente",
                icon: "success",
            });
        } else {
            await axios.post("/api/contratos", payload);
            toastMessage({ heading: "Ok", text: "Contrato creado correctamente", icon: "success" });
        }
        btnCerrarModalForm();
        await listar();
    } catch (error) {
        if (error.response?.status === 422) {
            errores.value = error.response.data.errors || {};
        } else {
            const msg = error.response?.data?.message || "Error al guardar el contrato";
            toastMessage({ heading: "Error", text: msg, icon: "error" });
        }
    } finally {
        loadingGuardar.value = false;
    }
}

/**
 * Elimina un contrato tras confirmación del usuario.
 * Muestra diálogo de confirmación antes de proceder.
 *
 * @param {Object} item - Contrato a eliminar (debe contener id)
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
async function desactivarRegistro(item) {
    const confirmed = await confirmAction({
        title: "Eliminar contrato",
        text: "¿Está seguro de eliminar este contrato? Se eliminarán también sus productos y médicos vinculados.",
        icon: "warning",
    });
    if (!confirmed) return;
    loadingEliminarId.value = item.id;
    try {
        await axios.delete(`/api/contratos/${item.id}`);
        toastMessage({ heading: "Ok", text: "Contrato eliminado correctamente", icon: "success" });
        await listar();
    } catch (error) {
        console.error(error);
        const msg = error.response?.data?.message || "Error al eliminar el contrato";
        toastMessage({ heading: "Error", text: msg, icon: "error" });
    } finally {
        loadingEliminarId.value = null;
    }
}

// ─── CATÁLOGOS ────────────────────────────────────────────────────────────────

/**
 * Carga el catálogo de médicos para los Select2 del módulo.
 * Se ejecuta una vez en onMounted (patrón AsignarEstudio).
 * Médicos: usuarios con reg_med no nulo del módulo actual.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
async function cargarCatalogos() {
    try {
        const moduloId =
            sessionStorage.getItem("ST-modulo") || localStorage.getItem("ST-modulo") || "1";
        const medRes = await axios.get(`/api/user.listarUsuarios/Medico/${moduloId}`);
        medicos.value = medRes.data.data || [];
    } catch (error) {
        console.error("Error cargando catálogos:", error);
        const msg = error.response?.data?.message || error.message || "Error desconocido";
        toastMessage({ heading: "Error", text: "Error cargando médicos: " + msg, icon: "error" });
    }
}

// ─── DETALLE: SIDEBAR ─────────────────────────────────────────────────────────

/**
 * Abre el sidebar de detalle de un contrato.
 * Puebla detallesLocales desde los médicos vinculados del contrato y muestra el sidebar.
 * Los catálogos ya se cargaron en onMounted.
 *
 * @param {Object} contrato - Contrato con su sede y médicos vinculados
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
async function abrirDetalle(contrato) {
    if (sidebarVisible.value) {
        sidebarVisible.value = false;
    }
    contratoSeleccionado.value = contrato;
    cargarCobertura(contrato.id);
    detallesLocales.value = mapearMedicosDetalle(contrato.medicos || []);
    await nextTick();
    detalleVisible.value = true;
}

/**
 * Mapea la colección de contratos_medicos (con relaciones cargadas) a la
 * estructura plana que usa la tabla local del sidebar de detalle.
 *
 * @param {Array} medicosContrato - Registros con medico y contratoProducto.producto incluidos
 * @returns {Array<{id: number, medico_id: number, medico_name: string, producto_id: number|null, cod_cups: string, nom_produc: string, valor_consulta: number, valor_urgencias: number, fecha_ingreso: string}>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
function mapearMedicosDetalle(medicosContrato) {
    return (medicosContrato || []).map((m) => ({
        id: m.id,
        medico_id: m.medico_id,
        medico_name: m.medico?.name || "",
        producto_id: m.contratoProducto?.producto?.id ?? null,
        cod_cups: m.contratoProducto?.producto?.cod_cups || "",
        nom_produc: m.contratoProducto?.producto?.nom_produc || "",
        valor_consulta: m.valor_consulta ?? 0,
        valor_urgencias: m.valor_urgencias ?? 0,
        fecha_ingreso: m.fecha_ingreso || "",
    }));
}

/**
 * Consulta qué productos del catálogo exigibles no tienen tarifa en el contrato.
 *
 * @param {number} contratoId - Identificador del contrato abierto en el detalle.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-30
 */
async function cargarCobertura(contratoId) {
    loadingCobertura.value = true;
    try {
        const res = await axios.get(`/api/contratos/${contratoId}/cobertura`);
        cobertura.value = res.data.data;
    } catch (_error) {
        cobertura.value = null;
    } finally {
        loadingCobertura.value = false;
    }
}

/**
 * Cierra el sidebar de detalle y limpia todo el estado relacionado.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
function cerrarDetalle() {
    contratoSeleccionado.value = null;
    cobertura.value = null;
    detallesLocales.value = [];
    detalleVisible.value = false;
}

/**
 * Elimina un médico-producto de la tabla local de detalles por índice con confirmación preventiva.
 *
 * @param {number} index - Índice del elemento a eliminar en detallesLocales
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
async function quitarProductoDetalle(index) {
    const item = detallesLocales.value[index];
    const descripcion = item?.nom_produc ? `el producto "${item.nom_produc}"` : "este producto";
    const confirmed = await confirmAction({
        title: "Quitar producto",
        text: `¿Está seguro de retirar ${descripcion} del detalle del contrato?`,
        icon: "warning",
    });
    if (!confirmed) return;
    detallesLocales.value.splice(index, 1);
}

/**
 * Previsualiza y guarda el detalle de médicos-producto del contrato.
 *
 * Antes de escribir pide al backend el diff y muestra al usuario cuántos
 * registros se agregan, cambian o quedan sin aplicar. Las filas que no vienen
 * en la lista solo se retiran si el usuario lo confirma de forma explícita.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-30
 */
async function guardarDetalles() {
    if (loadingGuardarDetalle.value) return;
    if (!detallesLocales.value.length) {
        toastMessage({
            heading: "Atención",
            text: "Agregue al menos un médico-producto",
            icon: "warning",
        });
        return;
    }

    loadingGuardarDetalle.value = true;
    try {
        const payload = {
            medicos: detallesLocales.value.map((d) => ({
                medico_id: d.medico_id,
                producto_id: d.producto_id,
                valor_consulta: d.valor_consulta ?? 0,
                valor_urgencias: d.valor_urgencias ?? 0,
                fecha_ingreso: d.fecha_ingreso || null,
            })),
        };

        const url = `/api/contratos/${contratoSeleccionado.value.id}/medicos`;
        const prev = await axios.post(`${url}/preview`, payload);
        const diff = prev.data.data;

        if (diff.errores?.length) {
            toastMessage({
                heading: "Error",
                text: diff.errores.join(" "),
                icon: "error",
            });
            return;
        }

        const resumen = `Se agregarán ${diff.altas?.length ?? 0} y se actualizarán ${diff.cambios?.length ?? 0}.`;

        if (diff.ausentes?.length) {
            const texto =
                `Hay ${diff.ausentes?.length ?? 0} registro(s) guardados que no están en la lista. ` +
                `Si los retira, se marcarán como inactivos; ` +
                `si cancela, quedarán activos con su tarifa anterior, ` +
                `pero las altas y cambios sí se guardarán. ` +
                `¿Desea retirarlos? (Sí, continuar = retirarlos; Cancelar = guardar sin retirar)`;

            const retirar = await confirmAction({
                title: "Registros que no están en la lista",
                text: resumen + " " + texto,
                icon: "warning",
            });

            if (!retirar) {
                const soloAltas = await confirmAction({
                    title: "Confirmar cambios parciales",
                    text:
                        resumen +
                        " Los registros ausentes se conservarán. ¿Desea aplicar solo las altas y cambios?",
                    icon: "question",
                });
                if (!soloAltas) return;
            }
            payload.retirar_faltantes = retirar;
        } else {
            const ok = await confirmAction({
                title: "Confirmar cambios",
                text: resumen + " ¿Desea aplicarlos?",
                icon: "question",
            });
            if (!ok) return;
        }

        await axios.put(url, payload);

        toastMessage({
            heading: "Ok",
            text: "Médicos-producto guardados correctamente",
            icon: "success",
        });

        const res = await axios.get(`/api/contratos/${contratoSeleccionado.value.id}`);
        contratoSeleccionado.value = res.data.data;
        detallesLocales.value = mapearMedicosDetalle(res.data.data.medicos || []);
        await cargarCobertura(contratoSeleccionado.value.id);
        await listar();
    } catch (_error) {
        const errorData = _error.response?.data;
        let msg = typeof errorData === "object" ? errorData?.message : null;
        if (!msg && errorData?.errors && typeof errorData.errors === "object") {
            msg = Object.values(errorData.errors).flat().join(", ");
        }
        toastMessage({
            heading: "Error",
            text: msg || "Error al guardar los detalles del contrato",
            icon: "error",
        });
    } finally {
        loadingGuardarDetalle.value = false;
    }
}

// ─── TARIFAS ENTIDAD Y MÉDICOS (PLANTILLAS Y PREVIEW DIFF) ─────────────────

/**
 * Formulario del modal de tarifas de entidad (Plantilla 1)
 * @type {import('vue').Ref<{ contratoId: number|null, sedeNombre: string, fechaInicio: string, fechaFin: string }>}
 */
const modalEntidadForm = ref({
    contratoId: null,
    sedeNombre: "",
    fechaInicio: "",
    fechaFin: "",
});

/**
 * Formulario del modal de tarifas de médicos lectores (Plantilla 2)
 * @type {import('vue').Ref<{ contratoId: number|null, sedeNombre: string, fechaInicio: string, fechaFin: string, medico_id: string|null }>}
 */
const modalMedicoForm = ref({
    contratoId: null,
    sedeNombre: "",
    fechaInicio: "",
    fechaFin: "",
    medico_id: null,
});

/**
 * Referencia al input de archivo para tarifas de entidad
 * @type {import('vue').Ref<HTMLInputElement|null>}
 */
const archivoEntidadRef = ref(null);

/**
 * Referencia al input de archivo para tarifas de médico
 * @type {import('vue').Ref<HTMLInputElement|null>}
 */
const archivoMedicoRef = ref(null);

/**
 * Flag de carga para vista previa de entidad
 * @type {import('vue').Ref<boolean>}
 */
const loadingPreviewEntidad = ref(false);

/**
 * Flag de carga para vista previa de médico
 * @type {import('vue').Ref<boolean>}
 */
const loadingPreviewMedico = ref(false);

/**
 * Flag de carga al confirmar e importar tarifas
 * @type {import('vue').Ref<boolean>}
 */
const loadingConfirmarImportacion = ref(false);

/**
 * Datos del preview (diff y cobertura) devueltos por el backend
 * @type {import('vue').Ref<Object>}
 */
const previewData = ref({});

/**
 * Pestaña activa en el modal de vista previa: 'altas' | 'cambios' | 'retiros' | 'cobertura'
 * @type {import('vue').Ref<string>}
 */
const previewTabActiva = ref("altas");

/**
 * Búsqueda reactiva para filtrar productos faltantes en la pestaña de cobertura
 * @type {import('vue').Ref<string>}
 */
const busquedaCobertura = ref("");

/**
 * Lista filtrada de productos exigibles que carecen de tarifa
 * @type {import('vue').ComputedRef<Array>}
 */
const coberturaFiltrada = computed(() => {
    const list = previewData.value?.cobertura?.faltantes || [];
    const q = busquedaCobertura.value.trim().toLowerCase();
    if (!q) return list;
    return list.filter(
        (p) =>
            String(p.cod_cups || "")
                .toLowerCase()
                .includes(q) ||
            String(p.nom_produc || "")
                .toLowerCase()
                .includes(q)
    );
});

/**
 * Formatea un número como valor monetario en pesos colombianos ($COP).
 *
 * @param {number|string|null} valor
 * @returns {string}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-10
 */
function formatMoney(valor) {
    const num = Number(valor);
    if (isNaN(num)) return "$ 0";
    return new Intl.NumberFormat("es-CO", {
        style: "currency",
        currency: "COP",
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(num);
}

/**
 * Abre el modal para descargar o importar la plantilla de tarifas de entidad (Plantilla 1).
 *
 * @param {Object} item
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-10
 */
function abrirModalTarifasEntidad(item) {
    modalEntidadForm.value = {
        contratoId: item.id,
        sedeNombre: item.sede?.nom_sede || sedeNombreActual.value,
        fechaInicio: item.fecha_inicio || "",
        fechaFin: item.fecha_fin || "",
    };
    if (archivoEntidadRef.value) {
        archivoEntidadRef.value.value = "";
    }
    $("#modalTarifasEntidad").modal("show");
}

/**
 * Cierra el modal de tarifas de entidad.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-10
 */
function cerrarModalTarifasEntidad() {
    $("#modalTarifasEntidad").modal("hide");
}

/**
 * Descarga el archivo Excel con las tarifas de entidad (Plantilla 1).
 *
 * @param {number} contratoId
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-10
 */
function descargarPlantillaEntidad(contratoId) {
    if (!contratoId) return;
    window.open(`/api/contratos/${contratoId}/plantilla-entidad`, "_blank");
}

/**
 * Envía el archivo Excel de la entidad para generar la vista previa (diff no destructivo y cobertura).
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-10
 */
async function procesarPreviewEntidad() {
    if (loadingPreviewEntidad.value) return;
    const file = archivoEntidadRef.value?.files?.[0];
    if (!file) {
        toastMessage({
            heading: "Atención",
            text: "Seleccione un archivo Excel de tarifas de la entidad",
            icon: "warning",
        });
        return;
    }

    loadingPreviewEntidad.value = true;
    try {
        const formData = new FormData();
        formData.append("archivo", file);

        const res = await axios.post(
            `/api/contratos/${modalEntidadForm.value.contratoId}/preview-importar-entidad`,
            formData,
            { headers: { "Content-Type": "multipart/form-data" } }
        );

        previewData.value = res.data.data || {};
        previewTabActiva.value = "altas";
        busquedaCobertura.value = "";
        cerrarModalTarifasEntidad();
        await nextTick();
        $("#modalPreviewDiff").modal("show");
    } catch (error) {
        const msg = error.response?.data?.message || "Error al analizar el archivo de entidad";
        toastMessage({ heading: "Error", text: msg, icon: "error" });
    } finally {
        loadingPreviewEntidad.value = false;
    }
}

/**
 * Abre el modal interactivo para descargar o importar la plantilla de tarifas de médico (Plantilla 2).
 *
 * @param {Object} item
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-10
 */
function abrirModalTarifasMedico(item) {
    modalMedicoForm.value = {
        contratoId: item.id,
        sedeNombre: item.sede?.nom_sede || sedeNombreActual.value,
        fechaInicio: item.fecha_inicio || "",
        fechaFin: item.fecha_fin || "",
        medico_id: null,
    };
    if (archivoMedicoRef.value) {
        archivoMedicoRef.value.value = "";
    }
    $("#modalTarifasMedico").modal("show");
}

/**
 * Cierra el modal de tarifas de médico lector.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-10
 */
function cerrarModalTarifasMedico() {
    $("#modalTarifasMedico").modal("hide");
}

/**
 * Descarga el archivo Excel con las tarifas del médico seleccionado (Plantilla 2).
 *
 * @param {number} contratoId
 * @param {number|string|null} medicoId
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-10
 */
function descargarPlantillaMedico(contratoId, medicoId) {
    if (!contratoId) return;
    if (!medicoId) {
        toastMessage({
            heading: "Atención",
            text: "Seleccione un médico lector para descargar su plantilla",
            icon: "warning",
        });
        return;
    }
    window.open(`/api/contratos/${contratoId}/medicos/${medicoId}/plantilla`, "_blank");
}

/**
 * Envía el archivo Excel del médico con validación estricta de celda A4 para generar vista previa.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-10
 */
async function procesarPreviewMedico() {
    if (loadingPreviewMedico.value) return;
    if (!modalMedicoForm.value.medico_id) {
        toastMessage({
            heading: "Atención",
            text: "Seleccione un médico lector",
            icon: "warning",
        });
        return;
    }

    const file = archivoMedicoRef.value?.files?.[0];
    if (!file) {
        toastMessage({
            heading: "Atención",
            text: "Seleccione un archivo Excel de tarifas de médico",
            icon: "warning",
        });
        return;
    }

    loadingPreviewMedico.value = true;
    try {
        const formData = new FormData();
        formData.append("archivo", file);
        formData.append("medico_id", modalMedicoForm.value.medico_id);

        const res = await axios.post(
            `/api/contratos/${modalMedicoForm.value.contratoId}/preview-importar-medicos`,
            formData,
            { headers: { "Content-Type": "multipart/form-data" } }
        );

        previewData.value = res.data.data || {};
        previewTabActiva.value = "altas";
        busquedaCobertura.value = "";
        cerrarModalTarifasMedico();
        await nextTick();
        $("#modalPreviewDiff").modal("show");
    } catch (error) {
        const msg =
            error.response?.data?.message || "Error al analizar el archivo de tarifas de médico";
        toastMessage({ heading: "Error", text: msg, icon: "error" });
    } finally {
        loadingPreviewMedico.value = false;
    }
}

/**
 * Cierra el modal de vista previa (diff no destructivo y cobertura).
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-10
 */
function cerrarModalPreview() {
    $("#modalPreviewDiff").modal("hide");
}

/**
 * Confirma y persiste las tarifas analizadas en la base de datos de forma no destructiva.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-10
 */
async function ejecutarConfirmacionImportacion() {
    if (loadingConfirmarImportacion.value) return;
    const tipo = previewData.value?.tipo;
    const contratoId = previewData.value?.contrato_id;
    if (!tipo || !contratoId) return;

    const confirmed = await confirmAction({
        title: "¿Confirmar importación?",
        text: `Se aplicarán ${previewData.value.resumen?.altas || 0} altas, ${previewData.value.resumen?.cambios || 0} cambios y ${previewData.value.resumen?.retiros || 0} bajas lógicas.`,
        icon: "question",
    });
    if (!confirmed) return;

    loadingConfirmarImportacion.value = true;
    try {
        if (tipo === "entidad") {
            const payload = {
                altas: previewData.value.altas || [],
                cambios: previewData.value.cambios || [],
                retiros: previewData.value.retiros || [],
            };
            await axios.post(`/api/contratos/${contratoId}/confirmar-importar-entidad`, payload);
            toastMessage({
                heading: "Ok",
                text: "Tarifas de la entidad importadas exitosamente",
                icon: "success",
            });
        } else if (tipo === "medico") {
            const payload = {
                medico_id: previewData.value.medico_id,
                altas: previewData.value.altas || [],
                cambios: previewData.value.cambios || [],
                retiros: previewData.value.retiros || [],
            };
            await axios.post(`/api/contratos/${contratoId}/confirmar-importar-medicos`, payload);
            toastMessage({
                heading: "Ok",
                text: "Tarifas del médico lector importadas exitosamente",
                icon: "success",
            });
        }
        cerrarModalPreview();

        // Refrescar sidebar de detalle si está abierto para este contrato
        if (contratoSeleccionado.value?.id === contratoId) {
            const detalle = await axios.get(`/api/contratos/${contratoId}`);
            contratoSeleccionado.value = detalle.data.data;
            detallesLocales.value = mapearMedicosDetalle(detalle.data.data.medicos || []);
            await cargarCobertura(contratoId);
        }
        await listar();
    } catch (error) {
        const msg = error.response?.data?.message || "Error al confirmar e importar tarifas";
        toastMessage({ heading: "Error", text: msg, icon: "error" });
    } finally {
        loadingConfirmarImportacion.value = false;
    }
}

// ─── LIFECYCLE ────────────────────────────────────────────────────────────────

function handleKeydown(e) {
    if (e.key === "Escape") {
        if ($("#modalPreviewDiff").hasClass("show")) {
            cerrarModalPreview();
        } else if ($("#modalTarifasMedico").hasClass("show")) {
            cerrarModalTarifasMedico();
        } else if ($("#modalTarifasEntidad").hasClass("show")) {
            cerrarModalTarifasEntidad();
        } else if (detalleVisible.value) {
            cerrarDetalle();
        } else if (sidebarVisible.value) {
            btnCerrarModalForm();
        }
    }
}

/**
 * Lifecycle onMounted: inicializa la sede, carga catálogos y listado.
 * Los catálogos se cargan UNA VEZ aquí (patrón AsignarEstudio),
 * no al abrir el drawer, para que Select2 ya tenga datos disponibles.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
onMounted(() => {
    window.addEventListener("keydown", handleKeydown);
    proxy?.emitter?.on("sedeSeleccionada", onSedeSeleccionada);
    sedeActual.value = getSedeActual();
    if (!sedeActual.value?.id) {
        proxy?.emitter?.emit("solicitarSedeActiva");
    }
    cargarCatalogos();
    listar();
});

/**
 * Lifecycle onBeforeUnmount: destruye la DataTable para evitar memory leaks.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-20
 */
onBeforeUnmount(() => {
    window.removeEventListener("keydown", handleKeydown);
    proxy?.emitter?.off("sedeSeleccionada", onSedeSeleccionada);
    safeDestroyDataTable(dt.value || "#tablaContratos");
});
</script>
<style scoped>
.right-sidebar {
    position: fixed;
    right: -80%;
    width: 80%;
    display: none;
    z-index: 1100;
    top: 0px;
    padding-bottom: 20px;
    height: 100%;
    -webkit-box-shadow: 5px 1px 40px rgba(0, 0, 0, 0.1);
    box-shadow: 5px 1px 40px rgba(0, 0, 0, 0.1);
    -webkit-transition: all 0.3s ease;
    -o-transition: all 0.3s ease;
    transition: all 0.3s ease;
}

.shw-rside {
    right: 0px;
    width: 80%;
    display: block;
}

.sidebar-scroll {
    overflow-x: hidden;
    overflow-y: scroll;
    height: 100%;
}

@media (max-width: 767px) {
    .right-sidebar {
        width: 100% !important;
        right: -100%;
    }
    .shw-rside {
        right: 0px !important;
        width: 100% !important;
    }
}
</style>
