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
                        <form action="#">
                            <div class="form-body">
                                <div class="row p-t-2">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">
                                                # de atención, # de documento ó Nombres de paciente
                                            </label>
                                            <input
                                                type="text"
                                                id="bus_nom_num_docu"
                                                name="bus_nom_num_docu"
                                                v-model="busqueda.bus_nom_num_docu"
                                                class="form-control"
                                                placeholder=" Atención, Nombres de paciente o # de documento"
                                                @keyup.enter="buscarStudy()"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Inicio</label>
                                            <input
                                                type="date"
                                                id="bus_fech_ini"
                                                name="bus_fech_ini"
                                                v-model="busqueda.bus_fech_ini"
                                                class="form-control"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Fin</label>
                                            <input
                                                type="date"
                                                id="bus_fecha_fin"
                                                name="bus_fecha_fin"
                                                v-model="busqueda.bus_fecha_fin"
                                                class="form-control"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions mt-3">
                                    <button
                                        type="button"
                                        class="btn btn-success m-r-5"
                                        @click="listarAtenciones()"
                                        :disabled="isBuscandoAtenciones"
                                    >
                                        <i
                                            class="fa"
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
                                        class="btn btn-inverse"
                                        @click="btnLimpiar()"
                                        :disabled="isBuscandoAtenciones || isGuardandoEcografia"
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
                        <h4 class="card-title">Listado de atenciones disponibles.</h4>
                        <h6 class="card-subtitle text-info">
                            Asigne los médicos para su respectiva lectura.
                        </h6>
                        <div class="table-responsive">
                            <table
                                id="example23"
                                ref="dt"
                                class="table table-bordered table-striped"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th with="5%">Fecha</th>
                                        <th with="5%"># Documento</th>
                                        <th with="30%">Paciente</th>
                                        <th>Examen</th>
                                        <th>Cantidad</th>
                                        <th>Val. Examen</th>
                                        <th>Administradora</th>
                                        <th>Contrato</th>
                                        <th>Plan</th>
                                        <th>Quien Ordeno</th>
                                        <th>Estado</th>
                                        <th>SAHI</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in registros" :key="index">
                                        <td>{{ item.FecIngreso }}</td>
                                        <td>{{ item.NumDocumento }}</td>
                                        <td>
                                            {{ item.PrimerNombre }} {{ item.SegundoNombre }}
                                            {{ item.PrimerApellido }} {{ item.SegundoApellido }}
                                        </td>
                                        <td>
                                            <div>{{ item.NomProducto }}</div>
                                            <small
                                                v-if="
                                                    item.tiene_plantilla_consentimiento &&
                                                    item.consentimiento_estado ===
                                                        'PENDIENTE_PACIENTE'
                                                "
                                                class="text-danger"
                                            >
                                                Pendiente firma paciente/acompañante
                                            </small>
                                            <small
                                                v-else-if="
                                                    item.tiene_plantilla_consentimiento &&
                                                    item.consentimiento_estado ===
                                                        'PENDIENTE_MEDICO'
                                                "
                                                class="text-warning"
                                            >
                                                Pendiente firma médico
                                            </small>
                                            <small
                                                v-else-if="
                                                    item.tiene_plantilla_consentimiento &&
                                                    item.consentimiento_estado === 'COMPLETO'
                                                "
                                                class="text-success"
                                            >
                                                Consentimiento completo
                                            </small>
                                        </td>
                                        <td align="right">{{ item.Cantidad }}</td>
                                        <td align="right">{{ formatoMoneda(item.ValProduc) }}</td>
                                        <td>{{ item.NomTercero }}</td>
                                        <td>{{ item.DesContrato }}</td>
                                        <td>{{ item.DesPlan }}</td>
                                        <td>{{ item.quien_ordeno }}</td>
                                        <td>
                                            <span
                                                class="label label-info"
                                                v-if="item.estado_ecografia === 'EN PROCESO'"
                                                >{{ item.estado_ecografia }}</span
                                            >
                                            <span
                                                class="label label-success"
                                                v-if="item.estado_ecografia === 'LEIDO'"
                                                >{{ item.estado_ecografia }}</span
                                            >
                                            <span
                                                class="label label-danger"
                                                v-if="item.estado_ecografia === 'ANULADO'"
                                                >{{ item.estado_ecografia }}</span
                                            >
                                        </td>
                                        <td>
                                            <template
                                                v-if="sedeInterface && item.ecografia_producto_id"
                                            >
                                                <span
                                                    v-if="item.estado_envio_sahi"
                                                    class="label"
                                                    :class="{
                                                        'label-success':
                                                            item.estado_envio_sahi === 'ENVIADO',
                                                        'label-warning':
                                                            item.estado_envio_sahi === 'ENVIANDO' ||
                                                            item.estado_envio_sahi === 'PENDIENTE',
                                                        'label-danger':
                                                            item.estado_envio_sahi === 'ERROR',
                                                        'label-default': !item.estado_envio_sahi,
                                                    }"
                                                >
                                                    {{ item.estado_envio_sahi }}
                                                </span>
                                                <span v-else class="text-muted">—</span>

                                                <div class="m-t-5">
                                                    <button
                                                        type="button"
                                                        class="btn btn-info btn-xs m-r-5"
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
                                                    class="text-danger d-block"
                                                >
                                                    {{ item.error_envio_sahi }}
                                                </small>
                                            </template>
                                            <template v-else>
                                                <span class="text-muted">—</span>
                                            </template>
                                        </td>
                                        <td class="text-nowrap">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm m-r-5"
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
                                                class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm m-r-5"
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
        <!-- .right-sidebar -->
        <div class="right-sidebar sidebar-scroll">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <span @click="btnCerralModalForm()">
                        <i class="ti-close right-side-toggle" id="btnCerralModalForm"></i>
                    </span>
                </div>
                <div class="r-panel-body">
                    <!-- ============================================================== -->
                    <!-- Aqui va el contenido de los formularios -->
                    <!-- ============================================================== -->
                    <form action="#">
                        <div class="form-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> Datos del Paciente
                                    </h5>
                                    <hr />
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Tipo de documento</label>
                                                <Select2
                                                    :value="registro.tipo_documento_id"
                                                    :options="tipoDocumentoOptions"
                                                    width="100%"
                                                    :allow-clear="true"
                                                    :label-fields="['codigo', 'tipo_documento']"
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
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label" for="nombres"
                                                    >Primer Nombre</label
                                                >
                                                <input
                                                    type="text"
                                                    id="nombres"
                                                    name="nombres"
                                                    v-model="registro.nombres"
                                                    class="form-control"
                                                    placeholder="Nombres"
                                                />
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label" for="seg_nom"
                                                    >Segundo Nombre</label
                                                >
                                                <input
                                                    type="text"
                                                    id="seg_nom"
                                                    name="seg_nom"
                                                    v-model="registro.seg_nom"
                                                    class="form-control"
                                                    placeholder="seg_nom"
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
                                                    placeholder="pri_ape"
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
                                                    placeholder="seg_ape"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label" for="sexo">Sexo</label>
                                                <input
                                                    type="text"
                                                    id="sexo"
                                                    name="sexo"
                                                    v-model="registro.sexo"
                                                    class="form-control"
                                                />
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
                                        <i class="fa fa-user"></i> Datos de la Asignacion
                                    </h5>
                                    <hr />
                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label>Médico</label>
                                                <Select2
                                                    :value="registro.medico_id"
                                                    :options="medicoOptions"
                                                    width="100%"
                                                    :allow-clear="true"
                                                    :label-fields="['name']"
                                                    @input="onMedicoSelect"
                                                />

                                                <span class="text-danger" v-if="errores.medico_id">
                                                    {{ errores.medico_id[0] }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-7">
                                            <div class="form-group">
                                                <label>Producto</label>
                                                <Select2
                                                    :value="productoSelecciondo"
                                                    :options="productoOptions"
                                                    width="100%"
                                                    :allow-clear="true"
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
                                                        <th>Cantidad</th>
                                                        <th>Valor</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr
                                                        v-for="item in registro.productosAgenda"
                                                        :key="item.id"
                                                    >
                                                        <td>{{ item.CodLegal }}</td>
                                                        <td>{{ item.NomProducto }}</td>
                                                        <td align="right">{{ item.Cantidad }}</td>
                                                        <td align="right">
                                                            {{ formatoMoneda(item.ValProduc) }}
                                                        </td>
                                                        <td class="text-nowrap">
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
                                    class="btn btn-success m-r-5"
                                    @click="guardarRegistro()"
                                    :disabled="isGuardandoEcografia"
                                >
                                    <i
                                        class="fa"
                                        :class="
                                            isGuardandoEcografia ? 'fa-spinner fa-spin' : 'fa-check'
                                        "
                                    ></i>
                                    {{ isGuardandoEcografia ? "Guardando..." : "Guardar" }}
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-inverse"
                                    @click="btnCerralModalForm()"
                                    :disabled="isGuardandoEcografia"
                                >
                                    Cancel
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
                                    :class="isGenerandoLinkFirma ? 'fa-spinner fa-spin' : 'fa-link'"
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
                    <button type="button" class="btn btn-default waves-effect" data-dismiss="modal">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
const { proxy } = getCurrentInstance();
const emitter = proxy.emitter;
import { computed, nextTick, onMounted, ref, getCurrentInstance } from "vue";

import usePermissions from "@/helpers/permissions.js";
import { formatoMoneda } from "@/helpers/funciones.js";
import { alertError, alertSuccess, confirmAction, toastMessage } from "@/helpers/alerts.js";
import { setMapValue } from "@/helpers/sahi";
import { useSede } from "@/composables/useSede.js";
import { useSahiInterface } from "@/composables/useSahiInterface.js";
import axios from "axios";

const props = defineProps({ usuarioactual: Object, permisos: Array });
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
    productosAgenda: [],
    contrato: null,
    plan: null,
    admin_salud: null,
    quien_ordeno: null,
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

const medicoOptions = computed(() => {
    // Opciones para el Select2 local de medicos.
    return (medicos.value || []).map((p) => ({ id: String(p.id), name: p.name }));
});
const tipoDocumentoOptions = computed(() => {
    // Opciones para el Select2 local de medicos.
    return (tipoDocumentos.value || []).map((p) => ({
        id: String(p.id),
        codigo: p.codigo,
        tipo_documento: p.tipo_documento,
    }));
});
const productoOptions = computed(() => {
    // Opciones para el Select2 local de productos.
    return (productos.value || []).map((p) => ({
        id: String(p.id),
        cod_cups: p.cod_cups,
        nom_produc: p.nom_produc,
    }));
});

function buscarStudy() {
    // Soporte para Enter en el input de búsqueda
    return listarAtenciones();
}
function btnLimpiar() {
    // Limpia criterios de búsqueda (mantiene sede/medico del contexto)
    busqueda.value.bus_nom_num_docu = "";
    busqueda.value.bus_fech_ini = "";
    busqueda.value.bus_fecha_fin = "";
    erroresBusqueda.value = {};

    safeDestroyDataTable(dt.value || "#example23");
    registros.value = [];
    limpiarFormulario();
}
function safeDestroyDataTable(selector = "#example23") {
    if (typeof window === "undefined" || !window.$ || !window.$.fn || !window.$.fn.DataTable)
        return;
    const $t = window.$(selector);
    if (!$t.length) return;
    if (window.$.fn.DataTable.isDataTable($t)) {
        $t.DataTable().clear().destroy();
    }
}
function safeInitDataTable(selector = "#example23") {
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
async function listarAtenciones() {
    try {
        isBuscandoAtenciones.value = true;

        const res = await axios.post(
            "/api/interface-sahi-ecografias/listar/productos/atencion",
            busqueda.value
        );

        if (res.status == 200) {
            safeDestroyDataTable(dt.value || "#example23");

            registros.value = res.data.data;

            safeInitDataTable(dt.value || "#example23");
        }
    } catch (error) {
        erroresBusqueda.value = error?.response?.data?.errors || {};
    } finally {
        isBuscandoAtenciones.value = false;
    }
}
function abrirModalConsentimiento(item) {
    consentimientoItem.value = item;
    consentimientoFirmanteTipo.value = "PACIENTE";
    acompanante.value = { cedula: "", nombres: "", email: "", direccion: "", telefono: "" };
    consentimientoUrl.value = "";
}
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
    } finally {
        isGenerandoLinkFirma.value = false;
    }
}
function abrirLinkFirma() {
    if (!consentimientoUrl.value) return;
    window.open(consentimientoUrl.value, "_blank", "noopener,noreferrer");
}
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
async function guardarRegistro() {
    try {
        isGuardandoEcografia.value = true;

        const ecografiaRes = await axios.post("/api/ecografias", registro.value);

        if (ecografiaRes.status === 201) {
            // Mostrar notificación de éxito
            toastMessage({
                heading: "¡Éxito!",
                text: "Agenda guardada correctamente.",
                icon: "success",
            });

            // Actualizar la lista de datos y limpiar el formulario
            listarAtenciones();
            limpiarFormulario();
            btnCerralModalForm();
        } else {
            toastMessage({
                heading: "Upss!!!",
                text: ecografiaRes.data.message,
                icon: "error",
            });
        }
    } catch (error) {
        // Mostrar errores específicos en el formulario
        if (error.response?.data?.errors) {
            errores.value = error.response.data.errors;
        }
    } finally {
        isGuardandoEcografia.value = false;
    }
}
function registroParaAnular(data) {
    registroAnular.value.ecografia_id = data.id;
    registroAnular.value.atencion_id = data?.atencion_id ?? data?.IdAtencion ?? "";
}
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
        } catch (error) {
            alertError("Error", "No fue posible anular la ecografía.", "error");
        } finally {
            isAnulandoEcografia.value = false;
        }
    });
}
async function initSede() {
    registro.value.sede_id = sessionStorage.getItem("ST-sede");
    busqueda.value.sede_id = sessionStorage.getItem("ST-sede");
    await establecerSedeComposable();
    registros.value = [];
}
async function establecerMedico() {
    registro.value.sede_id = sessionStorage.getItem("ST-sede");
    busqueda.value.sede_id = sessionStorage.getItem("ST-sede");
    medicoActual.value =
        localStorage.getItem("ST-ecografia-medico") || sessionStorage.getItem("ST-medico");

    await establecerSedeComposable();
    registros.value = [];
}
async function consultarEstadoSahi(item) {
    if (!sedeInterface.value) return;
    if (!item?.ecografia_producto_id) return;

    const id = item.ecografia_producto_id;
    try {
        setMapValue(proxy, "isConsultandoSahi", id, true);
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
        setMapValue(proxy, "isConsultandoSahi", id, false);
    }
}
async function reenviarASahi(item) {
    if (!sedeInterface.value) return;
    if (!item?.ecografia_producto_id) return;

    const id = item.ecografia_producto_id;
    try {
        setMapValue(proxy, "isEnviandoSahi", id, true);
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
        setMapValue(proxy, "isEnviandoSahi", id, false);
    }
}
async function listarMedicos() {
    try {
        const pathname = String(window?.location?.pathname || "").toLowerCase();
        const moduloId =
            sessionStorage.getItem("ST-modulo") || (pathname.includes("ecografia") ? "2" : "1");
        const res = await axios.get(`/api/user.listarUsuarios/Medico/${moduloId}`);
        medicos.value = res.data.data;
    } catch (error) {
        medicos.value = [];
    }
}
async function listarTipoDocumentos() {
    try {
        const res = await axios.get("/api/config-tipos-documentos/listar/activos");
        tipoDocumentos.value = res.data.data;
    } catch (error) {
        tipoDocumentos.value = [];
    }
}
async function listarProductos() {
    try {
        const res = await axios.get("/api/config-productos");
        productos.value = res.data.data;
    } catch (error) {
        productos.value = [];
    }
}
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
function onProductoSelect(value) {
    let selectedId = value || null;
    if (selectedId != null) {
        var prod = productos.value.find(function (p) {
            return String(p.id) === String(selectedId);
        });
        if (prod) {
            const yaExiste = registro.value.productosAgenda.some((p) => p.id == prod.id);
            if (!yaExiste) {
                registro.value.productosAgenda.push({
                    IdProducto: prod.id,
                    CodLegal: prod.cod_cups,
                    NomProducto: prod.nom_produc,
                    Cantidad: 1,
                    ValProduc: prod.val_produc,
                });
            }

            // Limpiar selección
            productoSelecciondo.value = "";
        }
    }
}
function btnCerralModalForm() {
    $(".right-sidebar").slideDown(50);
    $(".right-sidebar").toggleClass("shw-rside");
}
function mostrarRegistro(data = {}) {
    tituloModal.value = `Admisionar al paciente: ${data.PrimerNombre} ${data.SegundoNombre}`;

    registro.value.paciente_id = data.paciente_id;
    registro.value.num_docu = data.NumDocumento;
    registro.value.nombres = data.PrimerNombre;
    registro.value.seg_nom = data.SegundoNombre;
    registro.value.pri_ape = data.PrimerApellido;
    registro.value.seg_ape = data.SegundoApellido;
    registro.value.sexo = data.IdSexo;
    registro.value.fec_naci = data.FecNacimiento;
    registro.value.direccion = data.DirCasa;
    registro.value.tel = data.TelCasa;
    registro.value.cel = data.TelCelular || data.TelCasa;
    registro.value.atencion_id = data?.IdAtencion ?? data?.atencion_id ?? null;
    registro.value.contrato = data.DesContrato;
    registro.value.plan = data.DesPlan;
    registro.value.admin_salud = data.NomTercero;
    registro.value.quien_ordeno = data.quien_ordeno;

    registro.value.productosAgenda = [data];
    // Homologar tipo de documento
    const codigo = data.CodTipoDoc;
    const tipoHomologado = tipoDocumentoOptions.value.find((t) => t.codigo === codigo);

    if (tipoHomologado) {
        registro.value.tipo_documento_id = tipoHomologado.id;
    }

    btnCerralModalForm();
}
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

    registro.value.productosAgenda = [];
}
function quitarProductoAgenda(codProduc) {
    registro.value.productosAgenda = registro.value.productosAgenda.filter(
        (x) => x.CodLegal !== codProduc
    );
}

onMounted(() => {
    if (sessionStorage.getItem("ST-sede") == null) {
        $.toast({
            heading: "Upsss!!!",
            text: "Debes seleccionar una sede para realizar la busqueda.",
            position: "top-right",
            loaderBg: "#ff6849",
            icon: "warning",
            hideAfter: 3500,
            stack: 6,
        });
        return;
    }

    listarMedicos();
    initSede();
    listarTipoDocumentos();
    listarProductos();

    emitter.on("sedeSeleccionada", (data) => {
        registro.value.sede_id = data.idSede;
        initSede();
        establecerMedico();
    });
});
</script>
<style scoped>
.right-sidebar {
    position: fixed;
    right: -80%;
    width: 80%;
    display: none;
    z-index: 180;
    background: #ffffff;
    top: 0px;
    padding-bottom: 20px;
    height: 80%;
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
</style>
