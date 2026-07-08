<template>
    <!-- ============================================================== -->
    <!-- Bread crumb and right sidebar toggle -->
    <!-- ============================================================== -->
    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <h3 class="text-themecolor">Estudios</h3>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="javascript:void(0)">Inicio</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="javascript:void(0)">Estudios</a>
                </li>
                <li class="breadcrumb-item active">Leer Estudios</li>
            </ol>
        </div>
        <div class="col-md-7 align-self-center text-right d-none d-md-block">
            <i
                class="fa fa-life-ring model_img img-responsive"
                data-toggle="modal"
                data-target=".bs-example-modal-lg"
            ></i>
        </div>

        <!-- sample modal content -->
        <div
            class="modal fade bs-example-modal-lg"
            tabindex="-1"
            role="dialog"
            aria-labelledby="myLargeModalLabel"
            aria-hidden="true"
            style="display: none"
        >
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="myLargeModalLabel">Comando de voz</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-2">Mayúsculas</h5>
                                <ul>
                                    <li>iniciar mayúscula sostenida</li>
                                    <li>iniciar mayúsculas sostenida</li>
                                    <li>iniciar mayúsculas / mayúscula</li>
                                    <li>activar mayúsculas / mayúscula</li>
                                    <li>habilitar mayúsculas / mayúscula</li>
                                    <li>desactivar mayúsculas / mayúscula</li>
                                    <li>deshabilitar mayúsculas / mayúscula</li>
                                    <li>terminar mayúsculas / mayúscula</li>
                                    <li>fin mayúsculas / mayúscula</li>
                                    <li>finalizar mayúsculas / mayúscula</li>
                                </ul>
                                <h5 class="mb-2 mt-3">Saltos de línea</h5>
                                <ul>
                                    <li>punto aparte</li>
                                    <li>nuevo párrafo</li>
                                    <li>nueva sección</li>
                                    <li>separador</li>
                                    <li>salto de línea</li>
                                    <li>doble salto de línea</li>
                                    <li>enter / doble enter</li>
                                    <li>línea en blanco / doble línea en blanco</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5 class="mb-2">Puntuación</h5>
                                <ul>
                                    <li>punto / punto y seguido / punto seguido</li>
                                    <li>coma</li>
                                    <li>dos puntos</li>
                                    <li>punto y coma</li>
                                    <li>nueva oración</li>
                                    <li>abrir/cerrar paréntesis</li>
                                    <li>abrir/cerrar corchetes</li>
                                    <li>abrir/cerrar llaves</li>
                                    <li>abrir/cerrar comillas</li>
                                    <li>abrir/cerrar comillas simples</li>
                                </ul>
                                <h5 class="mb-2 mt-3">Símbolos</h5>
                                <ul>
                                    <li>guion</li>
                                    <li>guion bajo</li>
                                    <li>barra diagonal</li>
                                    <li>barra invertida</li>
                                    <li>dar espacio / un espacio / dos espacio / doble espacio</li>
                                    <li>por ciento (%)</li>
                                    <li>menos / más / igual</li>
                                    <li>multiplicar (×) / dividir (÷)</li>
                                    <li>asterisco / numeral / ampersand / arroba</li>
                                    <li>dólar / euro</li>
                                </ul>
                                <h5 class="mb-2 mt-3">Atajos</h5>
                                <ul>
                                    <li><strong>F2</strong> - Iniciar/detener grabación</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-danger waves-effect text-left"
                            data-dismiss="modal"
                        >
                            Close
                        </button>
                    </div>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
        <!-- /.modal -->
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
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title">Listado de mis pendientes por leer</h4>
                        <div class="text-right">
                            <span class="badge badge-danger" v-if="contadorPendientes > 0"
                                >{{ contadorPendientes }} pendiente(s)</span
                            >
                            <span class="badge badge-success" v-else>Sin pendientes</span>
                            <button
                                v-if="autoRefreshEnabled"
                                class="btn btn-sm btn-outline-secondary ml-2"
                                @click="toggleAutoRefresh"
                                title="Auto-refresh activo"
                            >
                                <i class="fa fa-refresh fa-spin"></i>
                                {{ autoRefreshInterval / 1000 }}s
                            </button>
                            <button
                                v-else
                                class="btn btn-sm btn-outline-secondary ml-2"
                                @click="toggleAutoRefresh"
                                title="Activar auto-refresh"
                            >
                                <i class="fa fa-refresh"></i> {{ autoRefreshInterval / 1000 }}s
                            </button>
                        </div>
                    </div>
                    <loading-spinner :visible="!tablaVisible" />
                    <div class="table-responsive" v-show="tablaVisible">
                        <table
                            id="example23"
                            ref="dt"
                            class="table table-bordered table-striped"
                            cellspacing="0"
                            width="100%"
                        >
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Sede</th>
                                    <th>Fecha del Toma</th>
                                    <th>Fecha del Asignación</th>
                                    <th>Quien Asigno</th>
                                    <th>Paciente</th>
                                    <th>Detalle</th>
                                    <th>Producto</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="item in registros"
                                    :key="item.estudio_producto_id"
                                    :class="{ 'text-info': item.visto === 1 }"
                                    :id="'row-' + item.estudio_producto_id"
                                >
                                    <td width="2%">
                                        <button
                                            type="button"
                                            v-if="item.priori_id == 1"
                                            class="btn waves-effect waves-light btn-rounded btn-sm btn-danger"
                                        >
                                            A
                                        </button>
                                        <button
                                            type="button"
                                            v-else-if="item.priori_id == 2"
                                            class="btn waves-effect waves-light btn-rounded btn-sm btn-warning"
                                        >
                                            M
                                        </button>
                                        <button
                                            type="button"
                                            v-else-if="item.priori_id == 3"
                                            class="btn waves-effect waves-light btn-rounded btn-sm btn-info"
                                        >
                                            B
                                        </button>
                                        <button
                                            type="button"
                                            v-else-if="item.priori_id == 4"
                                            class="btn waves-effect waves-light btn-rounded btn-sm"
                                        >
                                            S
                                        </button>
                                    </td>
                                    <td>{{ item.nom_sede }}</td>
                                    <td>{{ item.fec_estudio }}</td>
                                    <td>{{ formatDate(item.created_at) }}</td>
                                    <td>{{ item.quien_registro }}</td>
                                    <td>
                                        {{ item.num_docu }} <br />
                                        {{ item.nom_pacien }}
                                    </td>
                                    <td>
                                        {{ item.study_desc }} <br />
                                        {{ item.accession_no }}
                                    </td>
                                    <td>
                                        {{ item.nom_produc }}
                                    </td>
                                    <td class="text-nowrap">
                                        <button
                                            type="button"
                                            class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                            :disabled="loadingAbrirId === item.estudio_producto_id"
                                            @click="
                                                actualizar = true;
                                                mostrarRegistro(item);
                                            "
                                            title="Abrir estudio"
                                        >
                                            <i
                                                v-if="loadingAbrirId === item.estudio_producto_id"
                                                class="fa fa-spinner fa-spin"
                                            ></i>
                                            <i v-else class="fa fa-pencil"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm"
                                            :disabled="loadingImagenId === item.estudio_producto_id"
                                            @click="
                                                verImagen(
                                                    item.url_oviyam,
                                                    item.num_docu,
                                                    item.study_iuid,
                                                    item.tap_oviyam,
                                                    item.estudio_producto_id
                                                )
                                            "
                                            title="Ver imágenes"
                                        >
                                            <i
                                                v-if="loadingImagenId === item.estudio_producto_id"
                                                class="fa fa-spinner fa-spin"
                                            ></i>
                                            <i v-else class="fa fa-file-photo-o"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm m-r-5"
                                            :disabled="loadingVistoId === item.estudio_producto_id"
                                            @click="
                                                actualizar = true;
                                                visto(item.estudio_producto_id);
                                            "
                                            title="Marcar como visto"
                                        >
                                            <i
                                                v-if="loadingVistoId === item.estudio_producto_id"
                                                class="fa fa-spinner fa-spin"
                                            ></i>
                                            <i v-else class="fa fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-if="registros.length === 0" class="text-center py-4 text-muted">
                            <i class="fa fa-inbox fa-3x mb-3"></i>
                            <p>No hay estudios pendientes</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ============================================================== -->
    <!-- End PAge Content -->
    <!-- ============================================================== -->

    <!-- ============================================================== -->
    <!-- Right sidebar -->
    <!-- ============================================================== -->
    <!-- .right-sidebar -->
    <div class="right-sidebar" style="overflow-x: hidden; overflow-y: scroll; height: 100%">
        <div class="slimscrollright" style="overflow-x: hidden">
            <div class="rpanel-title">
                {{ tituloModal }}
                <button
                    type="button"
                    class="right-side-toggle"
                    @click="cerrarSidebar()"
                    aria-label="Cerrar panel"
                >
                    <i class="ti-close"></i>
                </button>
            </div>
            <div class="r-panel-body">
                <!-- ============================================================== -->
                <!-- Aqui va el contenido de los formularios -->
                <!-- ============================================================== -->
                <form action="#">
                    <div class="form-body">
                        <div class="row">
                            <div class="col-md-6">
                                <datos-paciente-display :registro="registro" />

                                <h5 class="card-title text-info">
                                    <i class="fa fa-user"></i> Datos del Estudio
                                </h5>
                                <hr />

                                <input
                                    type="hidden"
                                    id="estudio_producto_id"
                                    name="estudio_producto_id"
                                    v-model="registro.estudio_producto_id"
                                />
                                <input
                                    type="hidden"
                                    id="email"
                                    name="email"
                                    v-model="registro.email"
                                />

                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <dl>
                                                <dt>Descripción:</dt>
                                                <dd>{{ registro.study_desc }}</dd>
                                            </dl>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <dl>
                                                <dt>Detalle</dt>
                                                <dd>{{ registro.accession_no }}</dd>
                                            </dl>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <dl>
                                                <dt>Fecha del estudio</dt>
                                                <dd>{{ registro.fec_estudio }}</dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <dl>
                                                <dt>Observaciones</dt>
                                                <dd>{{ registro.observaciones }}</dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>

                                <h5 class="card-title text-info">
                                    <i class="fa fa-user"></i> Producto
                                </h5>
                                <hr />

                                <div class="row table-responsive">
                                    <div class="col-md-12">
                                        <dl>
                                            <dd>{{ productoEstudio.nom_produc }}</dd>
                                        </dl>
                                    </div>
                                </div>
                                <br />
                                <h5 class="card-title text-info">
                                    <i class="fa fa-user"></i> Diagnosticos
                                </h5>
                                <hr />

                                <div
                                    class="row table-responsive"
                                    style="height: 150px; overflow-y: scroll"
                                >
                                    <div class="col-md-12">
                                        <ul
                                            v-for="(item, index) in diagnosticosEstudio"
                                            :key="index"
                                        >
                                            <li>{{ item.nom_diagnos }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-9">
                                        <h5 class="card-title text-info">
                                            <i class="fa fa-edit"></i> Lectura
                                        </h5>
                                        <hr />
                                    </div>

                                    <div class="ml-auto">
                                        <ul class="list-inline text-center">
                                            <li>
                                                <button
                                                    type="button"
                                                    class="btn btn-secondary btn-xs"
                                                    :disabled="loadingImagenSidebar"
                                                    @click="
                                                        verImagen(
                                                            registro.urlOviyam,
                                                            registro.patientId,
                                                            registro.studyUID,
                                                            registro.serverName
                                                        )
                                                    "
                                                >
                                                    <i
                                                        v-if="loadingImagenSidebar"
                                                        class="fa fa-spinner fa-spin"
                                                    ></i>
                                                    <i v-else class="fa fa-file-image-o"></i>
                                                </button>
                                            </li>
                                            <li>
                                                <button
                                                    type="button"
                                                    @click="toggleSpeechRecognition"
                                                    :aria-label="
                                                        recognizing
                                                            ? 'Detener grabación'
                                                            : 'Iniciar grabación'
                                                    "
                                                    :aria-pressed="String(recognizing)"
                                                    style="
                                                        width: 44px;
                                                        height: 44px;
                                                        padding: 0;
                                                        border: none;
                                                        background: none;
                                                        cursor: pointer;
                                                    "
                                                >
                                                    <img
                                                        title="Grabadora"
                                                        :src="microfono"
                                                        :class="{
                                                            'pulse-animation':
                                                                recording || recognizing,
                                                        }"
                                                    />
                                                </button>
                                                <span
                                                    v-if="uppercase"
                                                    class="badge badge-success ml-2"
                                                    title="Mayúsculas activada"
                                                    >MAYÚS</span
                                                >
                                                <span
                                                    v-if="recording"
                                                    class="badge badge-danger ml-2"
                                                    >Grabando</span
                                                >
                                                <span
                                                    v-if="recognizing && !recording"
                                                    class="badge badge-warning ml-2"
                                                    >Escuchando</span
                                                >
                                                <span
                                                    class="text-muted ml-2"
                                                    style="font-size: 11px"
                                                    >F2</span
                                                >
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <span class="text-danger" v-if="errores.lectura">{{
                                    errores.lectura[0]
                                }}</span>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <textarea
                                                v-model="registro.lectura"
                                                class="form-control"
                                                rows="15"
                                                id="lectura"
                                                style="height: 100%"
                                                placeholder="Ingrese aquí la lectura del estudio ..."
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <!-- Nav tabs -->
                                        <ul class="nav nav-tabs" role="tablist">
                                            <li class="nav-item">
                                                <a
                                                    class="nav-link active"
                                                    data-toggle="tab"
                                                    href="#adjunto_hc"
                                                    role="tab"
                                                >
                                                    <h5 class="card-title text-info">
                                                        <i class="fa fa-paperclip"></i> Adjuntos
                                                    </h5>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a
                                                    class="nav-link"
                                                    data-toggle="tab"
                                                    href="#audios_hc"
                                                    role="tab"
                                                    @click="listarAudios()"
                                                >
                                                    <span>
                                                        <h5 class="card-title text-info">
                                                            <i class="fa fa-file-audio-o"></i>
                                                            Audios
                                                        </h5>
                                                    </span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a
                                                    class="nav-link"
                                                    data-toggle="tab"
                                                    href="#motivo_examen_hc"
                                                    role="tab"
                                                >
                                                    <span>
                                                        <h5 class="card-title text-info">
                                                            <i class="fa fa-history"></i> HC
                                                        </h5>
                                                    </span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a
                                                    class="nav-link"
                                                    data-toggle="tab"
                                                    href="#history_estudios"
                                                    role="tab"
                                                >
                                                    <span>
                                                        <h5 class="card-title text-info">
                                                            <i class="fa fa-history"></i> Historia
                                                            del estudios
                                                        </h5>
                                                    </span>
                                                </a>
                                            </li>
                                        </ul>
                                        <!-- Tab panes -->
                                        <div class="tab-content tabcontent-border">
                                            <div
                                                class="tab-pane active p-20"
                                                id="adjunto_hc"
                                                role="tabpanel"
                                            >
                                                <div
                                                    class="col-md-8 col-lg-9"
                                                    v-for="(itemArchivo, index) in soportesHC"
                                                    :key="index"
                                                >
                                                    <a
                                                        href="javascript:void(0)"
                                                        :style="{
                                                            pointerEvents:
                                                                loadingDescargaSoporte ===
                                                                itemArchivo.archivo_encrip
                                                                    ? 'none'
                                                                    : 'auto',
                                                        }"
                                                        @click="
                                                            descargaSoportte(
                                                                itemArchivo.archivo_original,
                                                                itemArchivo.archivo_encrip
                                                            )
                                                        "
                                                    >
                                                        <i
                                                            v-if="
                                                                loadingDescargaSoporte ===
                                                                itemArchivo.archivo_encrip
                                                            "
                                                            class="fa fa-spinner fa-spin"
                                                        ></i>
                                                        {{ itemArchivo.archivo_original }}
                                                    </a>
                                                </div>
                                            </div>
                                            <div
                                                class="tab-pane p-20"
                                                id="audios_hc"
                                                role="tabpanel"
                                            >
                                                <div v-if="loadingAudios" class="text-muted">
                                                    <i class="fa fa-spinner fa-spin"></i> Cargando
                                                    audios...
                                                </div>

                                                <template v-if="!loadingAudios">
                                                    <!-- Audios de la Lectura (grabados y cargados) -->
                                                    <div class="row mb-3">
                                                        <div class="col-12">
                                                            <h5 class="text-primary">
                                                                <i class="fa fa-headphones"></i>
                                                                Audios de la Lectura
                                                            </h5>
                                                            <hr />
                                                            <div
                                                                v-if="
                                                                    audiosDeEstudio &&
                                                                    audiosDeEstudio.length > 0
                                                                "
                                                            >
                                                                <div
                                                                    v-for="(
                                                                        item, index
                                                                    ) in audiosDeEstudio"
                                                                    :key="item.id || index"
                                                                    class="audio-item mb-2 p-2 border rounded"
                                                                >
                                                                    <div
                                                                        class="d-flex align-items-center"
                                                                    >
                                                                        <span
                                                                            class="badge mr-2"
                                                                            :class="
                                                                                item.tipo ===
                                                                                'cargado'
                                                                                    ? 'badge-info'
                                                                                    : 'badge-success'
                                                                            "
                                                                            style="min-width: 70px"
                                                                        >
                                                                            <i
                                                                                :class="
                                                                                    item.tipo ===
                                                                                    'cargado'
                                                                                        ? 'fa fa-upload'
                                                                                        : 'fa fa-microphone'
                                                                                "
                                                                            ></i>
                                                                            {{
                                                                                item.tipo ===
                                                                                "cargado"
                                                                                    ? "Cargado"
                                                                                    : "Grabado"
                                                                            }}
                                                                        </span>
                                                                        <audio
                                                                            :src="
                                                                                '/audios/' +
                                                                                item.audio
                                                                            "
                                                                            controls
                                                                            style="
                                                                                height: 30px;
                                                                                flex: 1;
                                                                            "
                                                                        ></audio>
                                                                        <button
                                                                            v-if="
                                                                                item.tipo ===
                                                                                'cargado'
                                                                            "
                                                                            type="button"
                                                                            class="btn btn-danger btn-sm ml-2"
                                                                            @click="
                                                                                eliminarAudioCargado(
                                                                                    item.id
                                                                                )
                                                                            "
                                                                            :disabled="
                                                                                eliminandoAudioId ===
                                                                                item.id
                                                                            "
                                                                            title="Eliminar audio"
                                                                        >
                                                                            <i
                                                                                v-if="
                                                                                    eliminandoAudioId ===
                                                                                    item.id
                                                                                "
                                                                                class="fa fa-spinner fa-spin"
                                                                            ></i>
                                                                            <i
                                                                                v-else
                                                                                class="fa fa-trash"
                                                                            ></i>
                                                                        </button>
                                                                    </div>
                                                                    <small
                                                                        class="text-muted"
                                                                        v-if="item.nombre_original"
                                                                    >
                                                                        {{ item.nombre_original }}
                                                                        <span v-if="item.duracion"
                                                                            >({{
                                                                                formatDuracion(
                                                                                    item.duracion
                                                                                )
                                                                            }})</span
                                                                        >
                                                                    </small>
                                                                </div>
                                                            </div>
                                                            <div v-else class="text-muted">
                                                                No hay audios para este estudio.
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Cargar Audios para Transcribir -->
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <h5 class="text-primary">
                                                                <i class="fa fa-file-audio-o"></i>
                                                                Cargar Audios para Transcribir
                                                            </h5>
                                                            <hr />

                                                            <!-- Zona de carga -->
                                                            <div class="form-group">
                                                                <label
                                                                    for="audioFileInput"
                                                                    class="btn btn-outline-primary btn-block"
                                                                >
                                                                    <i class="fa fa-upload"></i>
                                                                    Seleccionar Archivos de Audio
                                                                    <span
                                                                        v-if="audiosSubiendo > 0"
                                                                        class="ml-2"
                                                                    >
                                                                        <i
                                                                            class="fa fa-spinner fa-spin"
                                                                        ></i>
                                                                        Subiendo ({{
                                                                            audiosSubiendo
                                                                        }})
                                                                    </span>
                                                                </label>
                                                                <input
                                                                    type="file"
                                                                    id="audioFileInput"
                                                                    ref="audioFileInput"
                                                                    multiple
                                                                    accept=".mp3,.wav,.m4a,.webm,.ogg,.flac"
                                                                    @change="handleAudioFileSelect"
                                                                    style="display: none"
                                                                />
                                                                <small class="text-muted">
                                                                    Formatos: mp3, wav, m4a, webm,
                                                                    ogg | Maximo: 100MB
                                                                </small>
                                                            </div>

                                                            <!-- Lista de audios cargados pendientes -->
                                                            <div
                                                                v-if="
                                                                    audiosCargadosPendientes.length >
                                                                    0
                                                                "
                                                                class="mt-3"
                                                            >
                                                                <!-- Selector de modo de transcripcion -->
                                                                <div class="form-group">
                                                                    <label class="font-weight-bold"
                                                                        >Modo de
                                                                        transcripcion:</label
                                                                    >
                                                                    <div
                                                                        class="custom-control custom-radio custom-control-inline"
                                                                    >
                                                                        <input
                                                                            type="radio"
                                                                            id="modoReemplazar"
                                                                            name="modoTranscripcion"
                                                                            value="reemplazar"
                                                                            v-model="
                                                                                modoTranscripcion
                                                                            "
                                                                            class="custom-control-input"
                                                                        />
                                                                        <label
                                                                            class="custom-control-label"
                                                                            for="modoReemplazar"
                                                                        >
                                                                            Reemplazar texto actual
                                                                        </label>
                                                                    </div>
                                                                    <div
                                                                        class="custom-control custom-radio custom-control-inline"
                                                                    >
                                                                        <input
                                                                            type="radio"
                                                                            id="modoAgregar"
                                                                            name="modoTranscripcion"
                                                                            value="agregar"
                                                                            v-model="
                                                                                modoTranscripcion
                                                                            "
                                                                            class="custom-control-input"
                                                                        />
                                                                        <label
                                                                            class="custom-control-label"
                                                                            for="modoAgregar"
                                                                        >
                                                                            Agregar al texto actual
                                                                        </label>
                                                                    </div>
                                                                </div>

                                                                <!-- Tarjetas de audio -->
                                                                <div
                                                                    v-for="(
                                                                        audio, index
                                                                    ) in audiosCargadosPendientes"
                                                                    :key="audio.id"
                                                                    class="card mb-2 shadow-sm"
                                                                >
                                                                    <div class="card-body p-2">
                                                                        <div
                                                                            class="d-flex align-items-center mb-1"
                                                                        >
                                                                            <div
                                                                                class="custom-control custom-checkbox mr-2"
                                                                            >
                                                                                <input
                                                                                    type="checkbox"
                                                                                    :id="
                                                                                        'check-audio-' +
                                                                                        audio.id
                                                                                    "
                                                                                    :value="
                                                                                        audio.id
                                                                                    "
                                                                                    v-model="
                                                                                        audioSeleccionados
                                                                                    "
                                                                                    class="custom-control-input"
                                                                                />
                                                                                <label
                                                                                    :for="
                                                                                        'check-audio-' +
                                                                                        audio.id
                                                                                    "
                                                                                    class="custom-control-label"
                                                                                ></label>
                                                                            </div>
                                                                            <i
                                                                                class="fa fa-file-audio-o text-muted mr-1"
                                                                            ></i>
                                                                            <strong
                                                                                class="mr-2 text-truncate"
                                                                                style="
                                                                                    max-width: 180px;
                                                                                "
                                                                                :title="
                                                                                    audio.nombre_original
                                                                                "
                                                                            >
                                                                                {{
                                                                                    audio.nombre_original ||
                                                                                    "Audio " +
                                                                                        (index + 1)
                                                                                }}
                                                                            </strong>
                                                                            <span
                                                                                class="badge mr-1"
                                                                                :class="
                                                                                    badgeClaseEstado(
                                                                                        audio.transcripcion_estado
                                                                                    )
                                                                                "
                                                                            >
                                                                                {{
                                                                                    audio.transcripcion_estado ||
                                                                                    "Pendiente"
                                                                                }}
                                                                            </span>
                                                                            <small
                                                                                class="text-muted mr-1"
                                                                                v-if="
                                                                                    audio.duracion
                                                                                "
                                                                            >
                                                                                <i
                                                                                    class="fa fa-clock-o"
                                                                                ></i>
                                                                                {{
                                                                                    formatDuracion(
                                                                                        audio.duracion
                                                                                    )
                                                                                }}
                                                                            </small>
                                                                            <small
                                                                                class="text-muted"
                                                                                v-if="audio.tamano"
                                                                            >
                                                                                {{
                                                                                    formatSize(
                                                                                        audio.tamano
                                                                                    )
                                                                                }}
                                                                            </small>
                                                                        </div>

                                                                        <div
                                                                            class="d-flex align-items-center"
                                                                            style="min-width: 0"
                                                                        >
                                                                            <audio
                                                                                :src="
                                                                                    obtenerAudioUrl(
                                                                                        audio
                                                                                    )
                                                                                "
                                                                                controls
                                                                                style="
                                                                                    height: 30px;
                                                                                    flex: 1;
                                                                                    min-width: 0;
                                                                                    max-width: 100%;
                                                                                "
                                                                            ></audio>
                                                                            <button
                                                                                type="button"
                                                                                class="btn btn-sm btn-outline-primary ml-1"
                                                                                style="
                                                                                    white-space: nowrap;
                                                                                "
                                                                                @click="
                                                                                    insertarAudioIndividual(
                                                                                        audio
                                                                                    )
                                                                                "
                                                                                :disabled="
                                                                                    transcribiendo
                                                                                "
                                                                                title="Transcribir este audio a la lectura"
                                                                            >
                                                                                <i
                                                                                    class="fa fa-file-text-o"
                                                                                ></i>
                                                                                Transcribir
                                                                            </button>
                                                                            <button
                                                                                type="button"
                                                                                class="btn btn-sm btn-outline-danger ml-1"
                                                                                @click="
                                                                                    eliminarAudioCargado(
                                                                                        audio.id
                                                                                    )
                                                                                "
                                                                                :disabled="
                                                                                    eliminandoAudioId ===
                                                                                    audio.id
                                                                                "
                                                                                title="Eliminar audio"
                                                                            >
                                                                                <i
                                                                                    v-if="
                                                                                        eliminandoAudioId ===
                                                                                        audio.id
                                                                                    "
                                                                                    class="fa fa-spinner fa-spin"
                                                                                ></i>
                                                                                <i
                                                                                    v-else
                                                                                    class="fa fa-trash"
                                                                                ></i>
                                                                            </button>
                                                                        </div>

                                                                        <div
                                                                            v-if="
                                                                                audio.transcripcion
                                                                            "
                                                                            class="bg-light rounded p-1 mt-1"
                                                                            style="
                                                                                font-size: 0.8rem;
                                                                                max-height: 40px;
                                                                                overflow: hidden;
                                                                            "
                                                                        >
                                                                            {{
                                                                                previewTranscripcion(
                                                                                    audio.transcripcion,
                                                                                    120
                                                                                )
                                                                            }}
                                                                            <span
                                                                                v-if="
                                                                                    audio
                                                                                        .transcripcion
                                                                                        .length >
                                                                                    120
                                                                                "
                                                                                class="text-primary small"
                                                                                >...</span
                                                                            >
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- Boton de insertar batch -->
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-primary btn-block mt-3"
                                                                    @click="
                                                                        transcribirAudiosSeleccionados
                                                                    "
                                                                    :disabled="
                                                                        insertandoAudios ||
                                                                        audioSeleccionados.length ===
                                                                            0
                                                                    "
                                                                >
                                                                    <i
                                                                        v-if="insertandoAudios"
                                                                        class="fa fa-spinner fa-spin"
                                                                    ></i>
                                                                    <i
                                                                        v-else
                                                                        class="fa fa-file-text-o"
                                                                    ></i>
                                                                    {{
                                                                        insertandoAudios
                                                                            ? "Insertando..."
                                                                            : "Insertar Seleccionados en Lectura"
                                                                    }}
                                                                </button>
                                                            </div>

                                                            <div v-else class="text-muted mt-3">
                                                                No hay audios cargados pendientes de
                                                                transcripcion.
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                            <div
                                                class="tab-pane p-20"
                                                id="motivo_examen_hc"
                                                role="tabpanel"
                                            >
                                                <div class="form-group">
                                                    <textarea
                                                        id="motivo_examen"
                                                        name="motivo_examen"
                                                        v-model="registro.motivo_examen"
                                                        class="form-control"
                                                        placeholder="Ingrese aquí las observaciones del estudio"
                                                        rows="11"
                                                    ></textarea>
                                                </div>
                                            </div>
                                            <div
                                                class="tab-pane p-20"
                                                id="history_estudios"
                                                role="tabpanel"
                                            >
                                                <table
                                                    id="example23-1"
                                                    class="table table-bordered table-striped"
                                                    cellspacing="0"
                                                    width="100%"
                                                >
                                                    <thead>
                                                        <tr>
                                                            <th>Fecha del Estudio</th>
                                                            <th>Fecha de lectura</th>
                                                            <th>Producto</th>
                                                            <th></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr
                                                            v-for="item in historial_estudios"
                                                            :key="item.estudio_producto_id"
                                                        >
                                                            <td>{{ item.fec_estudio }}</td>
                                                            <td>{{ item.fechor_lectura }}</td>
                                                            <td>{{ item.nom_produc }}</td>
                                                            <td class="text-nowrap">
                                                                <button
                                                                    type="button"
                                                                    class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm"
                                                                    @click="imprimirReporte(item)"
                                                                    title="Imprimir reporte"
                                                                    :disabled="
                                                                        loadingImprimirId ===
                                                                        item.estudio_producto_id
                                                                    "
                                                                >
                                                                    <i
                                                                        v-if="
                                                                            loadingImprimirId ===
                                                                            item.estudio_producto_id
                                                                        "
                                                                        class="fa fa-spinner fa-spin"
                                                                    ></i>
                                                                    <i
                                                                        v-else
                                                                        class="fa fa-file-pdf-o"
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
                                Guardar
                            </button>

                            <button type="button" class="btn btn-inverse" @click="cerrarSidebar()">
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
</template>

<script>
import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import timezone from "dayjs/plugin/timezone";
import LoadingSpinner from "@/components/comunes/LoadingSpinner.vue";
import DatosPacienteDisplay from "@/components/comunes/datosPacienteDisplayComponent.vue";
import { normalizarFecha } from "@/helpers/dateUtils.js";
import {
    safeDestroyDataTable,
    safeInitDataTable,
    safeRemoveDataTableRow,
} from "@/helpers/datatables.js";
import { handleAxiosErrorToast } from "@/helpers/alerts";
import { useMedicalDictionary } from "@/composables/useMedicalDictionary";
import { useDictationFlow } from "@/composables/useDictationFlow";
import { useAudioUploadFlow } from "@/composables/useAudioUploadFlow";

dayjs.extend(utc);
dayjs.extend(timezone);

export default {
    props: ["usuarioactual", "permisos"],
    components: {
        LoadingSpinner,
        DatosPacienteDisplay,
    },
    computed: {
        contadorPendientes() {
            return this.registros ? this.registros.length : 0;
        },
    },
    setup() {
        const medicalDict = useMedicalDictionary();
        const dictationFlow = useDictationFlow();
        const audioUploadFlow = useAudioUploadFlow({
            showToast: (cfg) => {
                $.toast(cfg);
            },
        });
        return {
            medicalDict,
            dictationFlow,
            audioUploadFlow,
            recognizing: dictationFlow.recognizing,
            recording: dictationFlow.recording,
            microfono: dictationFlow.microfono,
            uppercase: dictationFlow.uppercase,
            audiosCargadosPendientes: audioUploadFlow.audiosCargadosPendientes,
            audioSeleccionados: audioUploadFlow.audioSeleccionados,
            modoTranscripcion: audioUploadFlow.modoTranscripcion,
            transcribiendo: audioUploadFlow.transcribiendo,
            insertandoAudios: audioUploadFlow.insertandoAudios,
            eliminandoAudioId: audioUploadFlow.eliminandoAudioId,
            audiosSubiendo: audioUploadFlow.audiosSubiendo,
            obtenerAudioUrl: audioUploadFlow.obtenerAudioUrl,
            badgeClaseEstado: audioUploadFlow.badgeClaseEstado,
            formatDuracion: audioUploadFlow.formatDuracion,
            formatSize: audioUploadFlow.formatSize,
            previewTranscripcion: audioUploadFlow.previewTranscripcion,
            toggleSpeechRecognition: dictationFlow.toggleSpeechRecognition,
        };
    },
    mounted() {
        this.establecerSede();
        this.sede_id = sessionStorage.getItem("ST-sede");
        this.applyDashboardPrefill();

        this.dictationFlow.setCallbacks({
            onText: (text) => {
                if (this.registro) this.registro.lectura += text;
            },
            showToast: (cfg) => {
                $.toast(cfg);
            },
            onState: (state) => {},
            onAudioSaved: () => {
                this.listarAudios();
            },
        });

        this.emitter.on("sedeSeleccionada", (data) => {
            this.sede_id = data.idSede;
            this.destroyDataTable();
            this.listarMisPendientes();
        });

        this.dictationFlow.setupKeyboardShortcut();
    },
    beforeUnmount() {
        if (this.autoRefreshTimer) {
            clearInterval(this.autoRefreshTimer);
            this.autoRefreshTimer = null;
        }
        this.destroyDataTable();
        this.registros = [];
        this.historial_estudios = [];
        this.soportesHC = [];
        this.audiosDeEstudio = [];
    },
    data() {
        return {
            id: 0,
            registros: [],
            productoEstudio: { cod_produc: "", nom_produc: "" },
            diagnosticosEstudio: [],
            historial_estudios: [],
            loadingImprimirId: null,
            soportesHC: [],
            tituloModal: "Nuevo registro",
            registro: {
                estudio_producto_id: 0,
                lectura: "",
                fec_estudio: "",
                accession_no: "",
                study_desc: "",
                observaciones: "",
                motivo_examen: "",
                num_docu: "",
                nom_pacien: "",
                sexo: "",
                fec_naci: "",
                email: "",
                tel: "",
                direccion: "",
                diagnosticosEstudio: [],
                urlOviyam: "",
                patientId: "",
                studyUID: "",
                serverName: "",
            },
            errores: {},
            tipoPrioridad: 0,
            idRow: null,
            sede_id: null,
            loadingListar: false,
            tablaVisible: true,
            autoRefreshEnabled: false,
            autoRefreshInterval: 30000,
            autoRefreshTimer: null,
            searchQuery: "",
            loadingGuardar: false,
            loadingAbrirId: null,
            loadingVistoId: null,
            loadingDescargaSoporte: null,
            loadingImagenId: null,
            loadingImagenSidebar: false,
            loadingAudios: false,
            audiosDeEstudio: [],
        };
    },
    methods: {
        applyDashboardPrefill() {
            try {
                const accession = sessionStorage.getItem("ST-accession");
                if (accession && String(accession).trim()) {
                    const term = String(accession).trim();
                    sessionStorage.removeItem("ST-accession");
                    this.listarMisPendientes().then(() => {
                        const found = (this.registros || []).find(
                            (x) => String(x.accession_no || "") === term
                        );
                        if (found) {
                            this.mostrarRegistro(found);
                        }
                    });
                    return;
                }
            } catch (e) {}
            this.listarMisPendientes();
        },

        normalizeWord(text) {
            return this.medicalDict.normalizeWord(text);
        },
        initDataTable() {
            safeInitDataTable(this.$refs.dt || "#example23", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
                retrieve: true,
                order: [[0, "asc"]], // Ordenar por primera columna (prioridad)
                language: {
                    search: "Buscar:",
                    lengthMenu: "Mostrar _MENU_ registros",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    paginate: {
                        first: "Primero",
                        last: "Último",
                        next: "Siguiente",
                        previous: "Anterior",
                    },
                },
            });
        },
        destroyDataTable() {
            safeDestroyDataTable("#example23");
        },
        destroyDataTableHistorial() {
            safeDestroyDataTable("#example23-1");
        },

        async listarAudios() {
            if (this.loadingAudios) return;
            this.loadingAudios = true;
            try {
                if (!this.registro.estudio_producto_id) {
                    this.audiosDeEstudio = [];
                    return;
                }
                const res = await axios.get(
                    "/api/listar-audios-estudio/" + this.registro.estudio_producto_id
                );
                this.audiosDeEstudio = res.data;
            } catch (e) {
                handleAxiosErrorToast(e, "No fue posible listar audios.");
                this.audiosDeEstudio = [];
            } finally {
                this.loadingAudios = false;
            }
        },

        handleAudioFileSelect(event) {
            this.audioUploadFlow.handleAudioFileSelect(
                event,
                this.id,
                this.registro.estudio_producto_id
            );
        },

        async transcribirAudiosSeleccionados() {
            const texto = await this.audioUploadFlow.transcribirAudiosSeleccionados();
            if (texto) {
                this.registro.lectura = this.audioUploadFlow.insertarTranscripcion(
                    texto,
                    this.registro.lectura
                );
            }
        },

        insertarAudioIndividual(audio) {
            const texto = this.audioUploadFlow.insertarAudioIndividual(audio);
            if (texto) {
                this.registro.lectura = this.audioUploadFlow.insertarTranscripcion(
                    texto,
                    this.registro.lectura
                );
            }
        },

        eliminarAudioCargado(audioId) {
            this.audioUploadFlow.eliminarAudioCargado(audioId, () => {
                this.listarAudios();
            });
        },

        async listarMisPendientes() {
            if (sessionStorage.getItem("ST-sede") == null) {
                $.toast({
                    heading: "Upsss!!!",
                    text: "Debes establece la sede en la cual vas a trabajar.",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "warning",
                    hideAfter: 3500,
                    stack: 6,
                });
            } else {
                if (this.loadingListar) return;

                this.tablaVisible = false;
                this.loadingListar = true;

                try {
                    const res = await axios.post("/api/estudio-listarPendientesMedico", {
                        id: this.usuarioactual.id,
                        sede_id: this.sede_id,
                    });
                    this.destroyDataTable();

                    this.registros = res.data;

                    this.$nextTick(() => {
                        this.initDataTable();
                        this.tablaVisible = true;
                    });
                } catch (error) {
                    handleAxiosErrorToast(error, "No fue posible listar los estudios pendientes.");
                } finally {
                    this.loadingListar = false;
                }
            }
        },
        toggleAutoRefresh() {
            if (this.autoRefreshEnabled) {
                if (this.autoRefreshTimer) {
                    clearInterval(this.autoRefreshTimer);
                    this.autoRefreshTimer = null;
                }
                this.autoRefreshEnabled = false;
                $.toast({
                    heading: "Auto-refresh",
                    text: "Auto-refresh desactivado",
                    position: "top-right",
                    icon: "info",
                    hideAfter: 2000,
                });
            } else {
                this.autoRefreshTimer = setInterval(() => {
                    this.listarMisPendientes();
                }, this.autoRefreshInterval);
                this.autoRefreshEnabled = true;
                $.toast({
                    heading: "Auto-refresh",
                    text: `Actualizando cada ${this.autoRefreshInterval / 1000} segundos`,
                    position: "top-right",
                    icon: "success",
                    hideAfter: 2000,
                });
            }
        },
        async guardarRegistro() {
            if (this.loadingGuardar) return;
            this.loadingGuardar = true;
            try {
                await this.dictationFlow.stopAll();

                if (!this.registro || !this.registro.estudio_producto_id) {
                    $.toast({
                        heading: "Upsss!!!",
                        text: "No hay un estudio seleccionado para guardar.",
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "warning",
                        hideAfter: 3500,
                        stack: 6,
                    });
                    return;
                }

                const res = await axios.post("/api/estudio-leerEstudio", this.registro);

                if (res.status == 200) {
                    await this.dictationFlow.sendToBackend(
                        this.id,
                        this.registro.estudio_producto_id
                    );

                    $.toast({
                        heading: "Completado",
                        text: res.data.message,
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "success",
                        hideAfter: 3500,
                        stack: 6,
                    });

                    const estudioEliminar = this.registros.findIndex(
                        (x) => x.estudio_producto_id == this.idRow
                    );
                    if (estudioEliminar >= 0) {
                        // En guardado NO destruimos/reinicializamos la tabla:
                        // solo removemos la fila correspondiente y mantenemos el resto de pendientes visible.
                        try {
                            safeRemoveDataTableRow(
                                this.$refs.dt || "#example23",
                                "#row-" + this.idRow
                            );
                        } catch (e) {
                            // best-effort: si DataTables no está listo, seguimos con el splice (Vue actualizará el DOM)
                        }
                        this.registros.splice(estudioEliminar, 1);
                    }

                    this.cerrarSidebar();
                    this.limpiar();
                }
            } catch (error) {
                this.errores = (error?.response?.data?.errors && typeof error.response.data.errors === "object") ? error.response.data.errors : {};
                handleAxiosErrorToast(error, "No fue posible guardar la lectura.");
            } finally {
                this.loadingGuardar = false;
            }
        },
        async visto(id) {
            if (this.loadingVistoId) return;
            this.loadingVistoId = id;

            const row = document.getElementById("row-" + id);

            // Evitar doble click verificando de forma inmediata si ya tiene la clase
            if (row && row.classList.contains("text-info")) return;

            // 1. Modificación directa del DOM (Instantáneo y compatible con DataTables)
            if (row) {
                row.classList.add("text-info");
            }

            // 2. Modificación del estado sin disparar la reactividad pesada de $set
            const idx = this.registros.findIndex((x) => x.estudio_producto_id === id);
            if (idx !== -1) {
                this.registros[idx].visto = 1;
            }

            try {
                // 3. Verdadera llamada "fire-and-forget" en segundo plano
                const marcarVisto = await axios.post("/api/marcar-visto/" + id).catch(() => {
                    // Opcional: Si falla, podríamos revertir la clase
                    if (row) row.classList.remove("text-info");
                });
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible marcar como visto.");
            } finally {
                this.loadingVistoId = null;
            }
        },
        async listar_historial_estudios(num_docu) {
            try {
                const res = await axios.post("/api/listar.estudios.notificaciones", {
                    bus_num_docu: num_docu,
                });

                if (res.status == 200) {
                    this.destroyDataTableHistorial();

                    this.historial_estudios = res.data;

                    this.$nextTick(() => {
                        safeInitDataTable("#example23-1", {
                            dom: "Bfrtip",
                            buttons: ["copy", "csv", "excel", "pdf", "print"],
                        });
                    });
                }
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar el historial de estudios.");
            } finally {
                this.loadingBuscar = false;
            }
        },
        async imprimirReporte(estudio) {
            if (this.loadingImprimirId) return;
            this.loadingImprimirId = estudio.estudio_producto_id;
            try {
                $.toast({
                    heading: "Procesando...",
                    text: "Generando reportes, por favor espere",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "info",
                    hideAfter: 3500,
                    stack: 6,
                });

                const res = await axios.post("/api/edit-word", estudio);

                window.open(
                    `/descargar.pdf.lectura/${res.data.plantilla}`,
                    "_blank",
                    "noopener,noreferrer"
                );
            } catch (error) {
                this.errores = (error?.response?.data?.errors && typeof error.response.data.errors === "object") ? error.response.data.errors : {};
            } finally {
                this.loadingImprimirId = null;
            }
        },
        async mostrarRegistro(data = {}) {
            if (this.loadingAbrirId !== null) return;
            this.loadingAbrirId = data.estudio_producto_id;

            await this.dictationFlow.stopAll();
            this.audiosDeEstudio = [];
            this.audioUploadFlow.limpiarAudiosCargados();

            this.tituloModal =
                "Lectura de paciente: " + data.nom_pacien + " — Producto: " + data.nom_produc;
            this.id = data.id;
            this.idRow = data.estudio_producto_id;
            this.registro.estudio_producto_id = data.estudio_producto_id;
            this.registro.fec_estudio = data.fec_estudio;
            this.registro.accession_no = data.accession_no;
            this.registro.study_desc = data.study_desc;
            this.registro.observaciones = data.observaciones;
            this.registro.motivo_examen = data.motivo_examen;
            this.registro.num_docu = data.num_docu;
            this.registro.nom_pacien = data.nom_pacien;
            this.registro.sexo = data.sexo || data.pat_sex || "";
            this.registro.fec_naci = normalizarFecha(data.fec_naci || data.pat_birthdate) || "";
            this.registro.tel = data.tel || "";
            this.registro.direccion = data.direccion || "";

            this.registro.urlOviyam = data.url_oviyam;
            this.registro.patientId = data.num_docu;
            this.registro.studyUID = data.study_iuid;
            this.registro.serverName = data.tap_oviyam;

            this.productoEstudio.cod_produc = data.cod_cups;
            this.productoEstudio.nom_produc = data.nom_produc;

            this.listar_historial_estudios(data.num_docu);

            try {
                const res = await axios.get(
                    "/api/study-listar-diagnostico-por-estudio?id=" + this.id
                );
                this.diagnosticosEstudio = res.data;

                const resSoportesHC = await axios.get(
                    "/api/listar-soportes-hc-estudio?id=" + this.id
                );
                this.soportesHC = resSoportesHC.data;

                await this.listarAudios();

                this.abrirSidebar();
                this.errores = {};
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible cargar el estudio seleccionado.");
            } finally {
                this.loadingAbrirId = null;
            }
        },
        descargaSoportte(nomArchivoOriginal, nomArchivoEncriptado) {
            if (this.loadingDescargaSoporte) return;
            this.loadingDescargaSoporte = nomArchivoEncriptado;
            const url = `/api/descarga-soportes-hc/${nomArchivoEncriptado}`;

            axios
                .get(url, { responseType: "blob" })
                .then((response) => {
                    const blobUrl = window.URL.createObjectURL(new Blob([response.data]));
                    const link = document.createElement("a");
                    link.href = blobUrl;
                    link.setAttribute("download", nomArchivoOriginal);
                    document.body.appendChild(link);
                    link.click();
                    setTimeout(() => window.URL.revokeObjectURL(blobUrl), 2000);
                })
                .catch((error) => {
                    handleAxiosErrorToast(error, "No fue posible descargar el soporte.");
                })
                .finally(() => {
                    this.loadingDescargaSoporte = null;
                });
        },
        verImagen(url, pat_id, study_iuid, servername, estudio_producto_id = null) {
            // No hace axios, pero evitamos doble click y mostramos spinner breve
            if (estudio_producto_id !== null) {
                if (this.loadingImagenId) return;
                this.loadingImagenId = estudio_producto_id;
            } else {
                if (this.loadingImagenSidebar) return;
                this.loadingImagenSidebar = true;
            }

            try {
                const Url = `${url}?patientID=${encodeURIComponent(pat_id)}&studyUID=${encodeURIComponent(study_iuid)}&serverName=${encodeURIComponent(servername)}`;
                window.open(Url, "_blank", "noopener,noreferrer");
            } finally {
                setTimeout(() => {
                    this.loadingImagenId = null;
                    this.loadingImagenSidebar = false;
                }, 500);
            }
        },
        establecerSede() {
            this.sede_id = sessionStorage.getItem("ST-sede");
            this.registros = [];
        },
        formatDate(timestamp) {
            // Convertir el timestamp UTC a la zona horaria local y formatear
            return dayjs.utc(timestamp).local().format("YYYY-MM-DD HH:mm:ss");
        },
        limpiar() {
            this.tituloModal = "";
            this.id = 0;
            this.registro.estudio_producto_id = "";
            this.registro.fec_estudio = "";
            this.registro.accession_no = "";
            this.registro.study_desc = "";

            this.registro.num_docu = "";
            this.registro.nom_pacien = "";
            this.registro.sexo = "";
            this.registro.fec_naci = "";
            this.registro.tel = "";
            this.registro.direccion = "";
            this.registro.lectura = "";

            this.dictationFlow.clearAudioBuffer();
            this.audioUploadFlow.limpiarAudiosCargados();
            this.audiosDeEstudio = [];
        },
        async abrirSidebar() {
            await this.dictationFlow.stopAll();
            $(".right-sidebar").slideDown(50);
            $(".right-sidebar").addClass("shw-rside");
        },
        async cerrarSidebar() {
            await this.dictationFlow.stopAll();
            $(".right-sidebar").slideUp(50);
            $(".right-sidebar").removeClass("shw-rside");
        },
    },
};
</script>

<style scoped>
.pulse-animation {
    animation: pulse 1s infinite;
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

.right-sidebar {
    position: fixed;
    right: -80%;
    width: 80%;
    display: none;
    z-index: 1100;
    background: #ffffff;
    top: 0px;
    padding-bottom: 20px;
    height: 100%;
    -webkit-box-shadow: 5px 1px 40px rgba(0, 0, 0, 0.1);
    box-shadow: 5px 1px 40px rgba(0, 0, 0, 0.1);
    -webkit-transition: all 0.3s ease;
    -o-transition: all 0.3s ease;
    transition: all 0.3s ease;
}

@media (max-width: 767px) {
    .right-sidebar {
        right: -100%;
        width: 100%;
    }
}

.shw-rside {
    right: 0px;
    width: 80%;
    display: block;
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
</style>
