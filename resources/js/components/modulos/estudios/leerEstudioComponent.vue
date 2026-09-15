<template>
    <!-- ============================================================== -->
    <!-- Bread crumb and right sidebar toggle -->
    <!-- ============================================================== -->
    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <h3 class="text-themecolor">Estudios</h3>
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
                        Estudios
                    </button>
                </li>
                <li class="breadcrumb-item active">Leer Estudios</li>
            </ol>
        </div>
        <div class="col-md-7 align-self-center text-right d-none d-md-block">
            <i
                class="fa fa-life-ring model_img img-responsive"
                @click="abrirModalAyuda()"
                role="button"
                aria-label="Ayuda: comandos de voz"
                tabindex="0"
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
                        <button
                            type="button"
                            class="close"
                            @click="cerrarModalAyuda()"
                            aria-hidden="true"
                        >
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
                            @click="cerrarModalAyuda()"
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
                                            aria-label="Abrir estudio"
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
                                            :disabled="
                                                loadingVerImagen === item.estudio_producto_id
                                            "
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
                                            aria-label="Ver imágenes"
                                        >
                                            <i
                                                v-if="loadingVerImagen === item.estudio_producto_id"
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
                                            aria-label="Marcar como visto"
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
    <div class="right-sidebar sidebar-scroll">
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
                                                    :disabled="loadingVerImagen !== null"
                                                    @click="
                                                        verImagen(
                                                            registro.urlOviyam,
                                                            registro.patientId,
                                                            registro.studyUID,
                                                            registro.serverName,
                                                            registro.estudio_producto_id
                                                        )
                                                    "
                                                >
                                                    <i
                                                        v-if="loadingVerImagen"
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
                                            <WysiwygEditor
                                                v-model="registro.lectura"
                                                :rows="15"
                                                placeholder="Ingrese aquí la lectura del estudio ..."
                                            />
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
                                                    @click="
                                                        listarAudios(registro.estudio_producto_id)
                                                    "
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
                                                                loadingDescarga ===
                                                                itemArchivo.archivo_encrip
                                                                    ? 'none'
                                                                    : 'auto',
                                                        }"
                                                        @click="
                                                            descargaSoporte(
                                                                itemArchivo.archivo_original,
                                                                itemArchivo.archivo_encrip
                                                            )
                                                        "
                                                    >
                                                        <i
                                                            v-if="
                                                                loadingDescarga ===
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
                                                                            aria-label="Eliminar audio"
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
                                                                                    aria-label="Seleccionar"
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
                                                                                aria-label="Eliminar audio"
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
                                                                    aria-label="Imprimir reporte"
                                                                    :disabled="
                                                                        loadingImprimir ===
                                                                        item.estudio_producto_id
                                                                    "
                                                                >
                                                                    <i
                                                                        v-if="
                                                                            loadingImprimir ===
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
<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, getCurrentInstance } from "vue";
const { proxy } = getCurrentInstance();
const emitter = proxy.emitter;

import LoadingSpinner from "@/components/comunes/LoadingSpinner.vue";
import DatosPacienteDisplay from "@/components/comunes/datosPacienteDisplayComponent.vue";
import { normalizarFecha } from "@/helpers/dateUtils.js";
import {
    safeDestroyDataTable,
    safeInitDataTable,
    safeRemoveDataTableRow,
} from "@/helpers/datatables.js";
import { toastMessage, getUserSafeError } from "@/helpers/alerts";
import { useMedicalDictionary } from "@/composables/useMedicalDictionary";
import { useDictationFlow } from "@/composables/useDictationFlow";
import { useAudioUploadFlow } from "@/composables/useAudioUploadFlow";
import { useEstudioHelpers } from "@/composables/useEstudioHelpers";
import WysiwygEditor from "@/components/shared/WysiwygEditor.vue";
import axios from "axios";

const props = defineProps({ usuarioactual: Object, permisos: { type: Array, default: () => [] } });

const medicalDict = useMedicalDictionary();
const dictationFlow = useDictationFlow();
const {
    recording,
    recognizing,
    uppercase,
    microfono,
    toggleSpeechRecognition,
    stopAll,
    stopRecording,
    sendToBackend,
    clearAudioBuffer,
    setCallbacks,
    setupKeyboardShortcut,
    audioBlob,
} = dictationFlow;
const audioUploadFlow = useAudioUploadFlow({ showToast: (cfg) => toastMessage(cfg) });
const {
    audiosCargadosPendientes,
    audioSeleccionados,
    modoTranscripcion,
    transcribiendo,
    insertandoAudios,
    progresoTranscripcion,
    eliminandoAudioId,
    audiosSubiendo,
    limpiarAudiosCargados,
    obtenerAudioUrl,
    previewTranscripcion,
    badgeClaseEstado,
    formatDuracion,
    formatSize,
} = audioUploadFlow;
const {
    loadingDescarga,
    loadingAudios,
    loadingImprimir,
    loadingVerImagen,
    audiosDeEstudio,
    formatDate,
    descargaSoporte,
    listarAudios,
    imprimirReporte,
    verImagen,
} = useEstudioHelpers();
const dt = ref(null);
const loadingBuscar = ref(false);

const id = ref(0);
const registros = ref([]);
const productoEstudio = ref({ cod_produc: "", nom_produc: "" });
const diagnosticosEstudio = ref([]);
const historial_estudios = ref([]);
const soportesHC = ref([]);
const tituloModal = ref("Nuevo registro");
const registro = ref({
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
});
const errores = ref({});
const idRow = ref(null);
const sede_id = ref(null);
const loadingListar = ref(false);
const tablaVisible = ref(true);
const autoRefreshEnabled = ref(false);
const autoRefreshInterval = ref(30000);
const autoRefreshTimer = ref(null);
const loadingGuardar = ref(false);
const loadingAbrirId = ref(null);
const loadingVistoId = ref(null);

const contadorPendientes = computed(() => {
    return registros.value ? registros.value.length : 0;
});

/**
 * Aplica un prellenado del dashboard si existe un accession almacenado en sessionStorage. Si se encuentra un accession válido, se busca el estudio correspondiente y se muestra en la interfaz. Si no hay un accession válido, simplemente se listan los estudios pendientes.
 * @returns {void}
 * @author: Jhon Javer Lozano Arce
 */
function applyDashboardPrefill() {
    try {
        const accession = sessionStorage.getItem("ST-accession");
        if (accession && String(accession).trim()) {
            const term = String(accession).trim();
            sessionStorage.removeItem("ST-accession");
            listarMisPendientes().then(() => {
                const found = (registros.value || []).find(
                    (x) => String(x.accession_no || "") === term
                );
                if (found) {
                    mostrarRegistro(found);
                }
            });
            return;
        }
    } catch (e) {
        console.error(e);
    }
    listarMisPendientes();
}
function normalizeWord(text) {
    return String(text || "");
}

/**
 * Inicializa la tabla de datos con las configuraciones necesarias, incluyendo botones de exportación y opciones de idioma.
 * @returns {void}
 * @author: Jhon Javer Lozano Arce
 */
function initDataTable() {
    safeInitDataTable(dt.value || "#example23", {
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
}
function destroyDataTable() {
    safeDestroyDataTable("#example23");
}
function destroyDataTableHistorial() {
    safeDestroyDataTable("#example23-1");
}

function handleAudioFileSelect(event) {
    audioUploadFlow.handleAudioFileSelect(
        event,
        id.value,
        registro.value.estudio_producto_id,
        (texto) => {
            registro.value.lectura = audioUploadFlow.insertarTranscripcion(
                texto,
                registro.value.lectura
            );
        }
    );
}

async function transcribirAudiosSeleccionados() {
    const texto = await audioUploadFlow.transcribirAudiosSeleccionados();
    if (texto) {
        registro.value.lectura = audioUploadFlow.insertarTranscripcion(
            texto,
            registro.value.lectura
        );
    }
}

function insertarAudioIndividual(audio) {
    const texto = audioUploadFlow.insertarAudioIndividual(audio);
    if (texto) {
        registro.value.lectura = audioUploadFlow.insertarTranscripcion(
            texto,
            registro.value.lectura
        );
    }
}

function eliminarAudioCargado(audioId) {
    audioUploadFlow.eliminarAudioCargado(audioId, () => {
        listarAudios(registro.value.estudio_producto_id);
    });
}

/**
 * Lista los estudios pendientes del médico actual según la sede seleccionada y actualiza la tabla de datos con los resultados obtenidos.
 * @returns {Promise<void>}
 * @author: Jhon Javer Lozano Arce
 */
async function listarMisPendientes() {
    if (sessionStorage.getItem("ST-sede") == null) {
        toastMessage({
            heading: "Upsss!!!",
            text: "Debes establece la sede en la cual vas a trabajar.",
            icon: "warning",
        });
    } else {
        if (loadingListar.value) return;

        tablaVisible.value = false;
        loadingListar.value = true;

        try {
            const res = await axios.post("/api/estudio-listarPendientesMedico", {
                id: props.usuarioactual?.id,
                sede_id: sede_id.value,
            });
            destroyDataTable();

            registros.value = res.data.data;

            nextTick(() => {
                initDataTable();
                tablaVisible.value = true;
            });
        } catch (error) {
            // Restaurar visibilidad de la tabla para no dejar el spinner eterno
            tablaVisible.value = true;
            toastMessage({
                heading: "Error",
                text: getUserSafeError(error, "No fue posible listar tus estudios pendientes."),
                icon: "error",
            });
        } finally {
            loadingListar.value = false;
        }
    }
}

/**
 * Activa o desactiva la función de auto-refresh para actualizar automáticamente la lista de estudios pendientes cada cierto intervalo de tiempo.
 * Si el auto-refresh está activado, se detiene; si está desactivado, se inicia un temporizador que llama a
 * la función `listarMisPendientes` cada `autoRefreshInterval` milisegundos.
 * @returns {void}
 * @author: Jhon Javer Lozano Arce
 */
function toggleAutoRefresh() {
    if (autoRefreshEnabled.value) {
        if (autoRefreshTimer.value) {
            clearInterval(autoRefreshTimer.value);
            autoRefreshTimer.value = null;
        }
        autoRefreshEnabled.value = false;
        toastMessage({
            heading: "Auto-refresh",
            text: "Auto-refresh desactivado",
            icon: "info",
            hideAfter: 2000,
        });
    } else {
        autoRefreshTimer.value = setInterval(() => {
            listarMisPendientes();
        }, autoRefreshInterval.value);
        autoRefreshEnabled.value = true;
        toastMessage({
            heading: "Auto-refresh",
            text: `Actualizando cada ${autoRefreshInterval.value / 1000} segundos`,
            icon: "success",
        });
    }
}

/**
 * Guarda el registro de lectura del estudio actual. Antes de guardar, detiene cualquier dictado en curso y verifica que haya un estudio seleccionado.
 * @returns {Promise<void>}
 * @author: Jhon Javer Lozano Arce
 */
async function guardarRegistro() {
    if (loadingGuardar.value) return;
    loadingGuardar.value = true;
    try {
        if (!registro.value || !registro.value.estudio_producto_id) {
            toastMessage({
                heading: "Upsss!!!",
                text: "No hay un estudio seleccionado para guardar.",
                icon: "warning",
            });
            return;
        }

        if (recording.value) {
            await stopRecording();
        }

        const res = await axios.post("/api/estudio-leerEstudio", registro.value);

        if (res.status >= 200 && res.status < 300) {
            const blobToSend = audioBlob.value;
            const estudioId = id.value;
            const productoId = registro.value.estudio_producto_id;

            toastMessage({
                heading: "Completado",
                text: res.data.message,
                icon: "success",
            });

            const estudioEliminar = registros.value.findIndex(
                (x) => x.estudio_producto_id == idRow.value
            );
            if (estudioEliminar >= 0) {
                try {
                    safeRemoveDataTableRow(dt.value || "#example23", "#row-" + idRow.value);
                } catch (e) {
                    console.warn("DataTable row removal failed:", e.message);
                }
                registros.value.splice(estudioEliminar, 1);
            }

            cerrarSidebar();
            limpiar();

            if (blobToSend && blobToSend instanceof Blob) {
                const formData = new FormData();
                formData.append("audio", blobToSend, "audio.webm");
                formData.append("estudio_id", estudioId);
                formData.append("estudio_producto_id", productoId);
                axios.post("/api/upload-audio", formData).catch((e) => {
                    console.error("Audio upload failed:", e);
                });
            }
        }
    } catch (error) {
        errores.value =
            error?.response?.data?.errors && typeof error.response.data.errors === "object"
                ? error.response.data.errors
                : {};
    } finally {
        await stopAll();
        loadingGuardar.value = false;
    }
}

/**
 * Marca un estudio como "visto" en la interfaz y realiza una llamada al backend para actualizar el estado en la base de datos.
 * @param {number} id - El ID del estudio a marcar como visto.
 * @returns {Promise<void>}
 * @author: Jhon Javer Lozano Arce
 */
async function visto(id) {
    if (loadingVistoId.value) return;

    const row = document.getElementById("row-" + id);

    // Evitar doble click verificando de forma inmediata si ya tiene la clase
    if (row && row.classList.contains("text-info")) return;

    loadingVistoId.value = id;

    // 1. Modificación directa del DOM (Instantáneo y compatible con DataTables)
    if (row) {
        row.classList.add("text-info");
    }

    // 2. Modificación del estado sin disparar la reactividad pesada de $set
    const idx = registros.value.findIndex((x) => x.estudio_producto_id === id);
    if (idx !== -1) {
        registros.value[idx].visto = 1;
    }

    try {
        // 3. Llamada en segundo plano; si falla, revertir DOM y estado reactivo
        await axios.post("/api/marcar-visto/" + id);
    } catch (error) {
        if (row) row.classList.remove("text-info");
        if (idx !== -1) {
            registros.value[idx].visto = 0;
        }
        toastMessage({
            heading: "Error",
            text: getUserSafeError(error, "No fue posible marcar el estudio como visto."),
            icon: "error",
        });
    } finally {
        loadingVistoId.value = null;
    }
}

/**
 * Lista el historial de estudios de un paciente según su número de documento y actualiza la tabla correspondiente en la interfaz.
 * @param {string} num_docu - El número de documento del paciente.
 * @returns {Promise<void>}
 * @author: Jhon Javer Lozano Arce
 */
async function listar_historial_estudios(num_docu) {
    try {
        const res = await axios.post("/api/listar.estudios.notificaciones", {
            bus_num_docu: num_docu,
        });

        if (res.status == 200) {
            destroyDataTableHistorial();

            historial_estudios.value = res.data.data;

            nextTick(() => {
                safeInitDataTable("#example23-1", {
                    dom: "Bfrtip",
                    buttons: ["copy", "csv", "excel", "pdf", "print"],
                });
            });
        }
    } catch (error) {
        console.error(error);
    } finally {
        loadingBuscar.value = false;
    }
}

/**
 * Muestra el registro del estudio seleccionado en el panel lateral, deteniendo cualquier dictado en curso y cargando los datos relevantes del estudio.
 * @param {Object} data - Los datos del estudio a mostrar.
 * @returns {Promise<void>}
 * @author: Jhon Javer Lozano Arce
 */
async function mostrarRegistro(data = {}) {
    if (loadingAbrirId.value !== null) return;
    loadingAbrirId.value = data.estudio_producto_id;

    await stopAll();
    audiosDeEstudio.value = [];
    audioUploadFlow.limpiarAudiosCargados();

    tituloModal.value =
        "Lectura de paciente: " + data.nom_pacien + " — Producto: " + data.nom_produc;
    id.value = data.id;
    idRow.value = data.estudio_producto_id;
    registro.value.estudio_producto_id = data.estudio_producto_id;
    registro.value.fec_estudio = data.fec_estudio;
    registro.value.accession_no = data.accession_no;
    registro.value.study_desc = data.study_desc;
    registro.value.observaciones = data.observaciones;
    registro.value.motivo_examen = data.motivo_examen;
    registro.value.num_docu = data.num_docu;
    registro.value.nom_pacien = data.nom_pacien;
    registro.value.sexo = data.sexo || data.pat_sex || "";
    registro.value.fec_naci = normalizarFecha(data.fec_naci || data.pat_birthdate) || "";
    registro.value.tel = data.tel || "";
    registro.value.direccion = data.direccion || "";

    registro.value.urlOviyam = data.url_oviyam;
    registro.value.patientId = data.num_docu;
    registro.value.studyUID = data.study_iuid;
    registro.value.serverName = data.tap_oviyam;

    productoEstudio.value.cod_produc = data.cod_cups;
    productoEstudio.value.nom_produc = data.nom_produc;

    listar_historial_estudios(data.num_docu);

    // El panel debe abrirse aunque fallen las cargas complementarias;
    // el médico necesita ver los datos base del estudio.
    try {
        const res = await axios.get("/api/study-listar-diagnostico-por-estudio?id=" + id.value);
        diagnosticosEstudio.value = res.data.data;

        const resSoportesHC = await axios.get("/api/listar-soportes-hc-estudio?id=" + id.value);
        soportesHC.value = resSoportesHC.data.data;

        await listarAudios(registro.value.estudio_producto_id);

        errores.value = {};
    } catch (error) {
        toastMessage({
            heading: "Atención",
            text: getUserSafeError(
                error,
                "No fue posible cargar diagnósticos/soportes del estudio."
            ),
            icon: "warning",
        });
    } finally {
        abrirSidebar();
        loadingAbrirId.value = null;
    }
}

function establecerSede() {
    sede_id.value = sessionStorage.getItem("ST-sede");
    registros.value = [];
}
function limpiar() {
    tituloModal.value = "";
    id.value = 0;
    registro.value.estudio_producto_id = "";
    registro.value.fec_estudio = "";
    registro.value.accession_no = "";
    registro.value.study_desc = "";

    registro.value.num_docu = "";
    registro.value.nom_pacien = "";
    registro.value.sexo = "";
    registro.value.fec_naci = "";
    registro.value.tel = "";
    registro.value.direccion = "";
    registro.value.lectura = "";

    clearAudioBuffer();
    audioUploadFlow.limpiarAudiosCargados();
    audiosDeEstudio.value = [];
}
async function abrirSidebar() {
    await stopAll();
    $(".right-sidebar").slideDown(50);
    $(".right-sidebar").addClass("shw-rside");
}
async function cerrarSidebar() {
    await stopAll();
    $(".right-sidebar").slideUp(50);
    $(".right-sidebar").removeClass("shw-rside");
}

/**
 * Abre el modal de ayuda con los comandos de voz del dictado.
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2024-04-04
 */
function abrirModalAyuda() {
    $(".bs-example-modal-lg").modal("show");
}

/**
 * Cierra el modal de ayuda de comandos de voz.
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2024-04-04
 */
function cerrarModalAyuda() {
    $(".bs-example-modal-lg").modal("hide");
}

function onSedeSeleccionada(data) {
    sede_id.value = data.idSede;
    destroyDataTable();
    listarMisPendientes();
}

onMounted(() => {
    establecerSede();
    sede_id.value = sessionStorage.getItem("ST-sede");
    applyDashboardPrefill();

    setCallbacks({
        onText: (text) => {
            if (registro.value) registro.value.lectura += text;
        },
        showToast: (cfg) => toastMessage(cfg),
        onState: (state) => {},
        onAudioSaved: () => {
            listarAudios(registro.value.estudio_producto_id);
        },
    });

    emitter.on("sedeSeleccionada", onSedeSeleccionada);

    const cleanupKeyboardShortcut = setupKeyboardShortcut();
});

onBeforeUnmount(() => {
    stopAll();
    cleanupKeyboardShortcut?.();
    emitter.off("sedeSeleccionada", onSedeSeleccionada);
    if (autoRefreshTimer.value) {
        clearInterval(autoRefreshTimer.value);
        autoRefreshTimer.value = null;
    }
    destroyDataTable();
    registros.value = [];
    historial_estudios.value = [];
    soportesHC.value = [];
    audiosDeEstudio.value = [];
});
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

.sidebar-scroll {
    overflow-x: hidden;
    overflow-y: scroll;
    height: 100%;
}
</style>
