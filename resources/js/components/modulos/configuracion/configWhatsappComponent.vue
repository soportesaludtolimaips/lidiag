<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Configuración</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Configuración</a>
                    </li>
                    <li class="breadcrumb-item active">WhatsApp Bot</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-info btnDesplegarRigthSidebar"
                    v-if="tabActivo !== 'productos'"
                    @click="
                        actualizar = false;
                        mostrarRegistro();
                    "
                >
                    <i class="fa fa-plus-circle m-r-5"></i> Nuevo registro
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-3" role="tablist">
                            <li class="nav-item">
                                <a
                                    class="nav-link"
                                    :class="{ active: tabActivo === 'numeros' }"
                                    href="#"
                                    @click.prevent="cambiarTab('numeros')"
                                >
                                    <i class="fa fa-phone"></i> Números WhatsApp
                                </a>
                            </li>
                            <li class="nav-item">
                                <a
                                    class="nav-link"
                                    :class="{ active: tabActivo === 'saludos' }"
                                    href="#"
                                    @click.prevent="cambiarTab('saludos')"
                                >
                                    <i class="fa fa-comment"></i> Saludos del Bot
                                </a>
                            </li>
                            <li class="nav-item">
                                <a
                                    class="nav-link"
                                    :class="{ active: tabActivo === 'productos' }"
                                    href="#"
                                    @click.prevent="cambiarTab('productos')"
                                >
                                    <i class="fa fa-cubes"></i> Productos por Servicio
                                </a>
                            </li>
                        </ul>

                        <div v-if="tabActivo === 'numeros'">
                            <h4 class="card-title">Números WhatsApp por sede</h4>
                            <h6 class="card-subtitle">
                                Administración de los números de teléfono asociados a cada sede para
                                el envío de mensajes.
                            </h6>

                            <div v-if="loadingNumeros" class="text-center m-t-40">
                                <i class="fa fa-spinner fa-spin fa-2x"></i>
                                <p class="text-muted m-t-10">Cargando números...</p>
                            </div>

                            <div v-else-if="errorNumeros" class="alert alert-danger m-t-20">
                                <i class="fa fa-exclamation-triangle"></i>
                                {{ errorNumeros }}
                            </div>

                            <div
                                v-else-if="registrosNumeros.length === 0"
                                class="text-center m-t-40"
                            >
                                <p class="text-muted">
                                    No hay números WhatsApp configurados. Cree un nuevo registro
                                    para comenzar.
                                </p>
                            </div>

                            <div class="table-responsive m-t-40" v-else>
                                <table
                                    id="tblWhatsappNumbers"
                                    ref="dtNumeros"
                                    class="display nowrap table table-hover table-striped table-bordered"
                                    cellspacing="0"
                                    width="100%"
                                >
                                    <thead>
                                        <tr>
                                            <th>Sede</th>
                                            <th>Phone Number ID</th>
                                            <th>Número</th>
                                            <th>Descripción</th>
                                            <th>Activo</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in registrosNumeros" :key="item.id">
                                            <td>{{ item.sede ? item.sede.nom_sede : "—" }}</td>
                                            <td>{{ item.phone_number_id }}</td>
                                            <td>{{ item.numero }}</td>
                                            <td>{{ item.descripcion || "—" }}</td>
                                            <td>
                                                <span
                                                    class="label"
                                                    :class="
                                                        item.activo
                                                            ? 'label-success'
                                                            : 'label-danger'
                                                    "
                                                >
                                                    {{ item.activo ? "Activo" : "Inactivo" }}
                                                </span>
                                            </td>
                                            <td class="text-nowrap">
                                                <button
                                                    type="button"
                                                    class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                    @click="
                                                        actualizar = true;
                                                        mostrarRegistro(item);
                                                    "
                                                >
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                    @click="eliminarRegistro(item.id)"
                                                    :disabled="loadingEliminarId === item.id"
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

                        <div v-if="tabActivo === 'saludos'">
                            <h4 class="card-title">Saludos del Bot</h4>
                            <h6 class="card-subtitle">
                                Mensajes de bienvenida personalizados por sede y servicio para el
                                bot de WhatsApp.
                            </h6>

                            <div v-if="loadingSaludos" class="text-center m-t-40">
                                <i class="fa fa-spinner fa-spin fa-2x"></i>
                                <p class="text-muted m-t-10">Cargando saludos...</p>
                            </div>

                            <div v-else-if="errorSaludos" class="alert alert-danger m-t-20">
                                <i class="fa fa-exclamation-triangle"></i>
                                {{ errorSaludos }}
                            </div>

                            <div
                                v-else-if="registrosSaludos.length === 0"
                                class="text-center m-t-40"
                            >
                                <p class="text-muted">
                                    No hay saludos configurados. Cree un nuevo registro para
                                    comenzar.
                                </p>
                            </div>

                            <div class="table-responsive m-t-40" v-else>
                                <table
                                    id="tblWhatsappSaludos"
                                    ref="dtSaludos"
                                    class="display nowrap table table-hover table-striped table-bordered"
                                    cellspacing="0"
                                    width="100%"
                                >
                                    <thead>
                                        <tr>
                                            <th>Sede</th>
                                            <th>Servicio</th>
                                            <th>Mensaje de bienvenida</th>
                                            <th>Activo</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in registrosSaludos" :key="item.id">
                                            <td>
                                                {{
                                                    item.sede
                                                        ? item.sede.nom_sede
                                                        : "Todas las sedes"
                                                }}
                                            </td>
                                            <td>
                                                {{
                                                    item.servicio
                                                        ? item.servicio.nombre
                                                        : "Todos los servicios"
                                                }}
                                            </td>
                                            <td class="text-wrap" style="max-width: 400px">
                                                {{ item.mensaje_bienvenida.substring(0, 50)
                                                }}{{
                                                    item.mensaje_bienvenida.length > 50 ? "..." : ""
                                                }}
                                            </td>
                                            <td>
                                                <span
                                                    class="label"
                                                    :class="
                                                        item.activo
                                                            ? 'label-success'
                                                            : 'label-danger'
                                                    "
                                                >
                                                    {{ item.activo ? "Activo" : "Inactivo" }}
                                                </span>
                                            </td>
                                            <td class="text-nowrap">
                                                <button
                                                    type="button"
                                                    class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                    @click="
                                                        actualizar = true;
                                                        mostrarRegistro(item);
                                                    "
                                                >
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm m-r-5"
                                                    @click="abrirProbarEnvio()"
                                                >
                                                    <i class="fa fa-send"></i>
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                    @click="eliminarRegistro(item.id)"
                                                    :disabled="loadingEliminarId === item.id"
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

                        <div v-if="tabActivo === 'productos'" class="m-t-20">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h4 class="card-title">Servicios</h4>
                                            <h6 class="card-subtitle text-info">
                                                Seleccione un servicio para administrar sus
                                                productos.
                                            </h6>

                                            <div
                                                v-if="loadingServiciosProductos"
                                                class="text-center m-t-20"
                                            >
                                                <i class="fa fa-spinner fa-spin fa-2x"></i>
                                                <p class="text-muted m-t-10">
                                                    Cargando servicios...
                                                </p>
                                            </div>

                                            <div
                                                v-else-if="errorServiciosProductos"
                                                class="alert alert-danger m-t-20"
                                            >
                                                <i class="fa fa-exclamation-triangle"></i>
                                                {{ errorServiciosProductos }}
                                            </div>

                                            <div
                                                v-else-if="serviciosProductos.length === 0"
                                                class="text-center m-t-20"
                                            >
                                                <p class="text-muted">
                                                    No hay servicios disponibles. Contacte al
                                                    administrador para asignarle servicios.
                                                </p>
                                            </div>

                                            <div class="list-group m-t-10" v-else>
                                                <button
                                                    v-for="servicio in serviciosProductos"
                                                    :key="servicio.id"
                                                    type="button"
                                                    class="list-group-item list-group-item-action"
                                                    :class="
                                                        servicioSeleccionado === servicio.id
                                                            ? 'active'
                                                            : ''
                                                    "
                                                    @click="seleccionarServicio(servicio)"
                                                >
                                                    <div
                                                        class="d-flex justify-content-between align-items-center w-100"
                                                    >
                                                        <div>
                                                            <div>{{ servicio.nombre }}</div>
                                                            <small
                                                                class="d-block"
                                                                style="
                                                                    font-size: 11px;
                                                                    opacity: 0.75;
                                                                "
                                                            >
                                                                <i class="fa fa-map-marker"></i>
                                                                {{
                                                                    servicio.sede?.nom_sede ||
                                                                    "Sin sede"
                                                                }}
                                                            </small>
                                                        </div>
                                                        <span
                                                            class="badge badge-danger badge-pill"
                                                            v-if="servicio.productos_count"
                                                        >
                                                            {{ servicio.productos_count }}
                                                        </span>
                                                    </div>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-8">
                                    <div class="card" v-if="servicioSeleccionado">
                                        <div class="card-body">
                                            <div
                                                class="d-flex justify-content-between align-items-center"
                                            >
                                                <div>
                                                    <h4 class="card-title">
                                                        {{ servicioActual?.nombre }}
                                                    </h4>
                                                    <h6 class="card-subtitle text-info">
                                                        Productos vinculados a este servicio
                                                    </h6>
                                                </div>
                                                <button
                                                    type="button"
                                                    class="btn btn-info"
                                                    @click="mostrarModalAgregar()"
                                                >
                                                    <i class="fa fa-plus-circle m-r-5"></i> Agregar
                                                    Producto
                                                </button>
                                            </div>
                                            <div class="table-responsive m-t-20">
                                                <table class="table table-bordered table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Código</th>
                                                            <th>Producto</th>
                                                            <th>Estado</th>
                                                            <th>Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr
                                                            v-for="item in productosVinculados"
                                                            :key="item.id"
                                                        >
                                                            <td>{{ item.producto?.cod_cups }}</td>
                                                            <td>{{ item.producto?.nom_produc }}</td>
                                                            <td>
                                                                <span
                                                                    class="label label-success"
                                                                    v-if="item.estado"
                                                                    >Activo</span
                                                                >
                                                                <span
                                                                    class="label label-danger"
                                                                    v-else
                                                                    >Inactivo</span
                                                                >
                                                            </td>
                                                            <td class="text-nowrap">
                                                                <button
                                                                    type="button"
                                                                    class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                                    @click="
                                                                        eliminarVinculo(item.id)
                                                                    "
                                                                >
                                                                    <i class="fa fa-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <tr v-if="productosVinculados.length === 0">
                                                            <td
                                                                colspan="4"
                                                                class="text-center text-muted"
                                                            >
                                                                No hay productos vinculados a este
                                                                servicio.
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card" v-else>
                                        <div class="card-body text-center">
                                            <p class="text-muted">
                                                Seleccione un servicio de la lista para ver sus
                                                productos.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="right-sidebar" style="overflow-x: hidden; overflow-y: scroll; height: 100%">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <span>
                        <i class="ti-close right-side-toggle" id="btnCerrarModalForm"></i>
                    </span>
                </div>
                <div class="r-panel-body">
                    <form action="#">
                        <div class="form-body">
                            <template v-if="tabActivo === 'numeros'">
                                <div class="row p-t-20">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label"
                                                >Sede <span class="text-danger">*</span></label
                                            >
                                            <select
                                                v-model="form.sede_id"
                                                class="form-control"
                                                :class="{ 'is-invalid': errores.sede_id }"
                                            >
                                                <option value="">Seleccione una sede</option>
                                                <option
                                                    v-for="s in sedes"
                                                    :key="s.id"
                                                    :value="s.id"
                                                >
                                                    {{ s.nom_sede }}
                                                </option>
                                            </select>
                                            <span class="text-danger" v-if="errores.sede_id">{{
                                                errores.sede_id[0]
                                            }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label"
                                                >Phone Number ID
                                                <span class="text-danger">*</span></label
                                            >
                                            <input
                                                type="text"
                                                v-model="form.phone_number_id"
                                                class="form-control"
                                                :class="{ 'is-invalid': errores.phone_number_id }"
                                                placeholder="ID de Meta"
                                            />
                                            <span
                                                class="text-danger"
                                                v-if="errores.phone_number_id"
                                                >{{ errores.phone_number_id[0] }}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label"
                                                >Número <span class="text-danger">*</span></label
                                            >
                                            <input
                                                type="text"
                                                v-model="form.numero"
                                                class="form-control"
                                                :class="{ 'is-invalid': errores.numero }"
                                                placeholder="573001234567"
                                            />
                                            <span class="text-danger" v-if="errores.numero">{{
                                                errores.numero[0]
                                            }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label class="control-label">Descripción</label>
                                            <input
                                                type="text"
                                                v-model="form.descripcion"
                                                class="form-control"
                                                placeholder="Ej: Ecografías"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <div class="checkbox checkbox-success m-t-25">
                                                <input
                                                    type="checkbox"
                                                    id="form_activo_numero"
                                                    v-model="form.activo"
                                                    :true-value="1"
                                                    :false-value="0"
                                                />
                                                <label for="form_activo_numero"> Activo </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template v-if="tabActivo === 'saludos'">
                                <div class="row p-t-20">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label"
                                                >Sede
                                                <small class="text-muted">(opcional)</small></label
                                            >
                                            <select
                                                v-model="form.sede_id"
                                                class="form-control"
                                                :class="{ 'is-invalid': errores.sede_id }"
                                            >
                                                <option value="">Todas las sedes</option>
                                                <option
                                                    v-for="s in sedes"
                                                    :key="s.id"
                                                    :value="s.id"
                                                >
                                                    {{ s.nom_sede }}
                                                </option>
                                            </select>
                                            <span class="text-danger" v-if="errores.sede_id">{{
                                                errores.sede_id[0]
                                            }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label"
                                                >Servicio
                                                <small class="text-muted">(opcional)</small></label
                                            >
                                            <select
                                                v-model="form.servicio_cita_id"
                                                class="form-control"
                                                :class="{ 'is-invalid': errores.servicio_cita_id }"
                                            >
                                                <option value="">Todos los servicios</option>
                                                <option
                                                    v-for="s in servicios"
                                                    :key="s.id"
                                                    :value="s.id"
                                                >
                                                    {{
                                                        s.nombre +
                                                        (s.sede ? " — " + s.sede.nom_sede : "")
                                                    }}
                                                </option>
                                            </select>
                                            <span
                                                class="text-danger"
                                                v-if="errores.servicio_cita_id"
                                                >{{ errores.servicio_cita_id[0] }}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">
                                                Mensaje de bienvenida
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-info m-l-10"
                                                    @click="mostrarPreviewSaludo"
                                                    :disabled="!form.mensaje_bienvenida"
                                                >
                                                    <i class="fa fa-eye"></i> Vista previa
                                                </button>
                                            </label>
                                            <div class="whatsapp-editor">
                                                <div class="whatsapp-toolbar">
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-light"
                                                        title="Negrita"
                                                        @click="insertFormat('*', '*')"
                                                    >
                                                        <b>B</b>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-light"
                                                        title="Cursiva"
                                                        @click="insertFormat('_', '_')"
                                                    >
                                                        <i>I</i>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-light"
                                                        title="Tachado"
                                                        @click="insertFormat('~', '~')"
                                                    >
                                                        <s>S</s>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-light"
                                                        title="Código"
                                                        @click="insertFormat('```', '```')"
                                                    >
                                                        <code>{"</code>
                                                    </button>
                                                    <span class="whatsapp-separator">|</span>
                                                    <EmojiPicker @select="insertEmoji" />
                                                </div>
                                                <textarea
                                                    ref="mensajeTextarea"
                                                    v-model="form.mensaje_bienvenida"
                                                    class="form-control"
                                                    rows="18"
                                                    :class="{
                                                        'is-invalid': errores.mensaje_bienvenida,
                                                    }"
                                                    placeholder="Escriba el mensaje de bienvenida que verá el paciente al iniciar el chat..."
                                                ></textarea>
                                            </div>
                                            <span
                                                class="text-danger"
                                                v-if="errores.mensaje_bienvenida"
                                                >{{ errores.mensaje_bienvenida[0] }}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div class="checkbox checkbox-success">
                                                <input
                                                    type="checkbox"
                                                    id="form_activo_saludo"
                                                    v-model="form.activo"
                                                    :true-value="1"
                                                    :false-value="0"
                                                />
                                                <label for="form_activo_saludo"> Activo </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template v-if="tabActivo === 'productos'">
                                <div class="row p-t-20">
                                    <div class="col-md-12">
                                        <div class="form-group" id="producto-parent">
                                            <label class="control-label"
                                                >Producto <span class="text-danger">*</span></label
                                            >
                                            <Select2
                                                :value="productoSeleccionado"
                                                :options="productosDisponiblesOptions"
                                                width="100%"
                                                :allow-clear="false"
                                                @input="onProductoSelect"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Recomendación</label>
                                            <FormattedTextEditor
                                                v-model="recomendacion"
                                                rows="18"
                                                placeholder="Texto de recomendación para el paciente. Use saltos de línea si es necesario."
                                            />
                                            <small class="text-muted">
                                                Variables disponibles:
                                                <code>${num_docu_paciente}</code>
                                                <code>${nom_paciente}</code>
                                                <code>${cod_cups}</code>
                                                <code>${nom_produc}</code>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div class="form-actions mt-3" v-if="tabActivo !== 'productos'">
                                <button
                                    type="button"
                                    class="btn btn-success m-r-5"
                                    @click="guardarRegistro()"
                                    v-if="!actualizar"
                                    :disabled="loadingGuardar"
                                >
                                    <i
                                        class="fa"
                                        :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-check'"
                                    ></i>
                                    Guardar
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-warning m-r-5"
                                    v-if="actualizar"
                                    @click="guardarRegistro()"
                                    :disabled="loadingGuardar"
                                >
                                    <i
                                        class="fa"
                                        :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-pencil'"
                                    ></i>
                                    Actualizar
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-inverse"
                                    @click="cerrarFormSidebar()"
                                >
                                    Cancel
                                </button>
                            </div>

                            <div class="form-actions mt-3" v-if="tabActivo === 'productos'">
                                <button
                                    type="button"
                                    class="btn btn-inverse"
                                    @click="cerrarModal()"
                                >
                                    Cerrar
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-success m-l-5"
                                    @click="agregarProducto()"
                                    :disabled="loadingGuardar || !productoSeleccionado"
                                >
                                    <i
                                        class="fa"
                                        :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-save'"
                                    ></i>
                                    {{ loadingGuardar ? "Guardando..." : "Guardar" }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Vista previa del saludo -->
        <div
            class="modal fade"
            id="modalPreviewSaludo"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Vista previa del mensaje</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                            &times;
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="card card-body bg-light">
                            <p style="white-space: pre-wrap">{{ previewMensaje }}</p>
                        </div>
                        <small class="text-muted">
                            Variables:
                            <code>${num_docu_paciente}</code> → <strong>1234567890</strong>,
                            <code>${nom_paciente}</code> → <strong>Paciente</strong>,
                            <code>${cod_cups}</code> → <strong>CUPS001</strong>,
                            <code>${nom_produc}</code> → <strong>Producto</strong>
                        </small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-inverse" data-dismiss="modal">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal probar envío WhatsApp -->
        <div
            class="modal fade"
            id="modalProbarEnvio"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Probar envío de WhatsApp</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                            &times;
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="control-label"
                                >Número de teléfono <span class="text-danger">*</span></label
                            >
                            <input
                                type="text"
                                v-model="testPhoneNumber"
                                class="form-control"
                                placeholder="573001234567"
                            />
                            <small class="text-muted"
                                >Ingrese el número con código de país (Ej: 573001234567)</small
                            >
                        </div>
                        <div v-if="testResultado" class="m-t-10">
                            <div
                                class="alert"
                                :class="testResultado.ok ? 'alert-success' : 'alert-danger'"
                            >
                                <i
                                    class="fa"
                                    :class="
                                        testResultado.ok
                                            ? 'fa-check-circle'
                                            : 'fa-exclamation-triangle'
                                    "
                                ></i>
                                {{ testResultado.mensaje }}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-inverse"
                            data-dismiss="modal"
                            @click="testResultado = null"
                        >
                            Cerrar
                        </button>
                        <button
                            type="button"
                            class="btn btn-info"
                            @click="probarEnvio()"
                            :disabled="!testPhoneNumber || loadingTestEnvio"
                        >
                            <i
                                class="fa"
                                :class="loadingTestEnvio ? 'fa-spinner fa-spin' : 'fa-send'"
                            ></i>
                            {{ loadingTestEnvio ? "Enviando..." : "Enviar" }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";

import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { alertSuccess, alertError, confirmAction } from "@/helpers/alerts.js";
import EmojiPicker from "@/components/shared/EmojiPicker.vue";
import FormattedTextEditor from "@/components/shared/FormattedTextEditor.vue";
import axios from "axios";

const INIT_FORM_NUMEROS = {
    sede_id: "",
    phone_number_id: "",
    numero: "",
    descripcion: "",
    activo: 1,
};
const INIT_FORM_SALUDOS = { sede_id: "", servicio_cita_id: "", mensaje_bienvenida: "", activo: 1 };

const tabActivo = ref("numeros");
const id = ref(0);
const registrosNumeros = ref([]);
const registrosSaludos = ref([]);
const sedes = ref([]);
const servicios = ref([]);
const form = ref({ ...INIT_FORM_NUMEROS });
const tituloModal = ref("Nuevo registro");
const actualizar = ref(false);
const errores = ref({});
const loadingNumeros = ref(false);
const loadingSaludos = ref(false);
const errorNumeros = ref("");
const errorSaludos = ref("");
const loadingGuardar = ref(false);
const loadingEliminarId = ref(null);
const dtNumeros = ref(null);
const dtSaludos = ref(null);
const serviciosProductos = ref([]);
const loadingServiciosProductos = ref(false);
const errorServiciosProductos = ref("");
const servicioSeleccionado = ref(null);
const servicioActual = ref(null);
const productosVinculados = ref([]);
const productosDisponibles = ref([]);
const productoSeleccionado = ref("");
const recomendacion = ref("");
const mensajeTextarea = ref(null);
const loadingAgregarProducto = ref(false);
const previewMensaje = ref("");
const testPhoneNumber = ref("");
const testResultado = ref(null);
const loadingTestEnvio = ref(false);

const productosDisponiblesOptions = computed(() => {
    return (productosDisponibles.value || []).map(function (p) {
        return {
            id: String(p.id),
            cod_cups: p.cod_cups,
            nom_produc: p.nom_produc,
            recomendacion_producto: p.recomendacion_producto,
        };
    });
});

function cambiarTab(tab) {
    tabActivo.value = tab;
    errores.value = {};
    if (tab === "productos") {
        cargarServiciosProductos();
    }
    nextTick(() => {
        if (tab === "numeros") {
            safeInitDataTable(dtNumeros.value || "#tblWhatsappNumbers", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
            });
        } else if (tab === "saludos") {
            safeInitDataTable(dtSaludos.value || "#tblWhatsappSaludos", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
            });
        }
    });
}
async function listarNumeros() {
    if (loadingNumeros.value) return;
    loadingNumeros.value = true;
    errorNumeros.value = "";
    try {
        const res = await axios.get("/api/config-sede-whatsapp-numbers");
        safeDestroyDataTable(dtNumeros.value || "#tblWhatsappNumbers");
        registrosNumeros.value = res.data.data;
        nextTick(() => {
            if (tabActivo.value === "numeros") {
                safeInitDataTable(dtNumeros.value || "#tblWhatsappNumbers", {
                    dom: "Bfrtip",
                    buttons: ["copy", "csv", "excel", "pdf", "print"],
                });
            }
        });
    } catch (error) {
        errorNumeros.value =
            error?.response?.data?.message || "No fue posible cargar los números WhatsApp.";
        registrosNumeros.value = [];
    } finally {
        loadingNumeros.value = false;
    }
}
async function listarSaludos() {
    if (loadingSaludos.value) return;
    loadingSaludos.value = true;
    errorSaludos.value = "";
    try {
        const res = await axios.get("/api/config-whatsapp");
        safeDestroyDataTable(dtSaludos.value || "#tblWhatsappSaludos");
        registrosSaludos.value = res.data.data;
        nextTick(() => {
            if (tabActivo.value === "saludos") {
                safeInitDataTable(dtSaludos.value || "#tblWhatsappSaludos", {
                    dom: "Bfrtip",
                    buttons: ["copy", "csv", "excel", "pdf", "print"],
                });
            }
        });
    } catch (error) {
        errorSaludos.value = error?.response?.data?.message || "No fue posible cargar los saludos.";
        registrosSaludos.value = [];
    } finally {
        loadingSaludos.value = false;
    }
}
async function listarSedes() {
    try {
        const res = await axios.get("/api/config-sedes/listar/sedes/activas");
        sedes.value = res.data.data;
    } catch (error) {
        console.error(error);
    }
}
async function listarServicios() {
    try {
        const res = await axios.get("/api/config-servicio-citas");
        servicios.value = res.data.data;
    } catch (error) {
        console.error(error);
    }
}
async function guardarRegistro() {
    if (loadingGuardar.value) return;
    errores.value = {};

    if (tabActivo.value === "numeros") {
        const errs = {};
        if (!form.value.sede_id) errs.sede_id = ["La sede es obligatoria."];
        if (!form.value.phone_number_id)
            errs.phone_number_id = ["El Phone Number ID es obligatorio."];
        if (!form.value.numero) errs.numero = ["El número es obligatorio."];
        if (Object.keys(errs).length > 0) {
            errores.value = errs;
            return;
        }
    } else if (tabActivo.value === "saludos") {
        const errs = {};
        if (!form.value.mensaje_bienvenida || !form.value.mensaje_bienvenida.trim()) {
            errs.mensaje_bienvenida = ["El mensaje de bienvenida es obligatorio."];
        }
        if (Object.keys(errs).length > 0) {
            errores.value = errs;
            return;
        }
    }

    loadingGuardar.value = true;
    try {
        const payload = {
            ...form.value,
            activo: form.value.activo === 1 || form.value.activo === true,
        };

        const esNumero = tabActivo.value === "numeros";
        const baseUrl = esNumero ? "/api/config-sede-whatsapp-numbers" : "/api/config-whatsapp";

        if (actualizar.value === false) {
            const res = await axios.post(baseUrl, payload);
            if (res.status == 201 || res.status == 200) {
                recargarTabActivo();
                $.toast({
                    heading: "Ok!!!",
                    text: "Registro guardado correctamente.",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "success",
                    hideAfter: 3500,
                    stack: 6,
                });
                cerrarFormSidebar();
                limpiar();
            }
        } else {
            const res = await axios.put(`${baseUrl}/${id.value}`, payload);
            if (res.status == 200) {
                recargarTabActivo();
                $.toast({
                    heading: "Ok!!!",
                    text: "Registro actualizado correctamente.",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "success",
                    hideAfter: 3500,
                    stack: 6,
                });
                cerrarFormSidebar();
                limpiar();
            }
        }
    } catch (error) {
        errores.value = error?.response?.data?.errors || {};
    } finally {
        loadingGuardar.value = false;
    }
}
async function eliminarRegistro(id) {
    const ok = await confirmAction({
        title: "¿Estas seguro?",
        text: "No podrás recuperar este registro!",
        icon: "warning",
    });
    if (!ok) return;

    try {
        if (loadingEliminarId.value) return;
        loadingEliminarId.value = id;
        const baseUrl =
            tabActivo.value === "numeros"
                ? "/api/config-sede-whatsapp-numbers"
                : "/api/config-whatsapp";
        const response = await axios.delete(`${baseUrl}/${id}`);
        alertSuccess("Ok!", response?.data?.message || "Registro eliminado.", "success");
        recargarTabActivo();
    } catch (error) {
        alertError("Error", "No fue posible eliminar el registro.", "error");
    } finally {
        loadingEliminarId.value = null;
    }
}
function mostrarRegistro(data = {}) {
    if (tabActivo.value === "productos") return;
    if (tabActivo.value === "numeros") {
        if (actualizar.value) {
            tituloModal.value = "Actualizar número WhatsApp";
            id.value = data.id;
            form.value = {
                sede_id: data.sede_id || "",
                phone_number_id: data.phone_number_id || "",
                numero: data.numero || "",
                descripcion: data.descripcion || "",
                activo: data.activo ? 1 : 0,
            };
        } else {
            tituloModal.value = "Nuevo número WhatsApp";
            id.value = 0;
            form.value = { ...INIT_FORM_NUMEROS };
        }
    } else {
        if (actualizar.value) {
            tituloModal.value = "Actualizar saludo: " + (data.sede?.nom_sede || "Todas las sedes");
            id.value = data.id;
            form.value = {
                sede_id: data.sede_id || "",
                servicio_cita_id: data.servicio_cita_id || "",
                mensaje_bienvenida: data.mensaje_bienvenida || "",
                activo: data.activo ? 1 : 0,
            };
        } else {
            tituloModal.value = "Nuevo saludo";
            id.value = 0;
            form.value = { ...INIT_FORM_SALUDOS };
        }
    }
    btnCerrarModalForm();
}
function recargarTabActivo() {
    if (tabActivo.value === "numeros") {
        listarNumeros();
    } else {
        listarSaludos();
    }
}
function limpiar() {
    actualizar.value = false;
    tituloModal.value = "Nuevo registro";
    id.value = 0;
    if (tabActivo.value === "numeros") {
        form.value = { ...INIT_FORM_NUMEROS };
    } else {
        form.value = { ...INIT_FORM_SALUDOS };
    }
}
function btnCerrarModalForm() {
    $(".right-sidebar").slideDown(50);
    $(".right-sidebar").addClass("shw-rside");
}
function cerrarFormSidebar() {
    $(".right-sidebar").slideUp(50);
    $(".right-sidebar").removeClass("shw-rside");
}
async function cargarServiciosProductos() {
    loadingServiciosProductos.value = true;
    errorServiciosProductos.value = "";
    try {
        const res = await axios.get("/api/config-servicio-citas");
        serviciosProductos.value = res.data.data;
        if (serviciosProductos.value.length > 0) {
            seleccionarServicio(serviciosProductos.value[0]);
        }
    } catch (error) {
        const msg = error?.response?.data?.message || error.message || "Error al cargar servicios";
        errorServiciosProductos.value = msg;
        serviciosProductos.value = [];
    } finally {
        loadingServiciosProductos.value = false;
    }
}
function seleccionarServicio(servicio) {
    servicioSeleccionado.value = servicio.id;
    servicioActual.value = servicio;
    cargarProductosVinculados();
}
async function cargarProductosVinculados() {
    if (!servicioSeleccionado.value) return;
    try {
        const res = await axios.get("/api/servicio-producto", {
            params: { servicio_cita_id: servicioSeleccionado.value },
        });
        productosVinculados.value = res.data.data;
    } catch (error) {
        console.error("Error al cargar productos vinculados:", error);
    }
}
async function mostrarModalAgregar() {
    tituloModal.value = "Agregar producto a: " + (servicioActual.value?.nombre || "");
    productoSeleccionado.value = "";
    recomendacion.value = "";
    loadingAgregarProducto.value = false;
    try {
        const res = await axios.get("/api/servicio-producto/productos-no-vinculados", {
            params: { servicio_cita_id: servicioSeleccionado.value },
        });
        productosDisponibles.value = res.data.data;
        abrirModal();
    } catch (error) {
        console.error("Error al cargar productos disponibles:", error);
    }
}
function onProductoSelect(value) {
    productoSeleccionado.value = value || "";
    const encontrado = productosDisponibles.value.find(function (p) {
        return String(p.id) === String(value);
    });
    recomendacion.value =
        (encontrado &&
            encontrado.recomendacion_producto &&
            encontrado.recomendacion_producto.recomendacion) ||
        "";
}
function abrirModal() {
    $(".right-sidebar").slideDown(50);
    $(".right-sidebar").addClass("shw-rside");
}
function cerrarModal() {
    $(".right-sidebar").removeClass("shw-rside");
}
async function agregarProducto() {
    if (!productoSeleccionado.value) return;
    loadingGuardar.value = true;
    try {
        await axios.post("/api/servicio-producto", {
            servicio_cita_id: servicioSeleccionado.value,
            producto_id: productoSeleccionado.value,
        });
        if (recomendacion.value && recomendacion.value.trim()) {
            await axios.post("/api/config-recomendacion-productos", {
                producto_id: productoSeleccionado.value,
                recomendacion: recomendacion.value,
            });
        }
        alertSuccess("Éxito", "Producto agregado correctamente");
        cerrarModal();
        cargarProductosVinculados();
        cargarServiciosProductos();
    } catch (error) {
    } finally {
        loadingGuardar.value = false;
    }
}
async function eliminarVinculo(id) {
    const ok = await confirmAction({
        title: "¿Está seguro?",
        text: "El producto se desvinculará del servicio.",
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    });
    if (!ok) return;
    try {
        await axios.delete("/api/servicio-producto/" + id);
        alertSuccess("Éxito", "Producto desvinculado");
        cargarProductosVinculados();
        cargarServiciosProductos();
    } catch (error) {}
}
function abrirProbarEnvio() {
    testPhoneNumber.value = "";
    testResultado.value = null;
    $("#modalProbarEnvio").modal("show");
}
function mostrarPreviewSaludo() {
    if (!form.value.mensaje_bienvenida) return;
    previewMensaje.value = form.value.mensaje_bienvenida
        .replace(/\$\{num_docu_paciente\}/g, "1234567890")
        .replace(/\$\{nom_paciente\}/g, "Paciente")
        .replace(/\$\{cod_cups\}/g, "CUPS001")
        .replace(/\$\{nom_produc\}/g, "Nombre del producto");
    $("#modalPreviewSaludo").modal("show");
}
function insertFormat(before, after) {
    const ta = mensajeTextarea.value;
    if (!ta) return;
    const start = ta.selectionStart;
    const end = ta.selectionEnd;
    const text = form.value.mensaje_bienvenida || "";
    const selected = text.substring(start, end);
    const replacement = before + selected + after;
    form.value.mensaje_bienvenida = text.substring(0, start) + replacement + text.substring(end);
    nextTick(() => {
        ta.focus();
        ta.selectionStart = start + before.length;
        ta.selectionEnd = start + before.length + selected.length;
    });
}
function insertEmoji(emoji) {
    const ta = mensajeTextarea.value;
    if (!ta) return;
    const start = ta.selectionStart;
    const end = ta.selectionEnd;
    const text = form.value.mensaje_bienvenida || "";
    form.value.mensaje_bienvenida = text.substring(0, start) + emoji + text.substring(end);
    nextTick(() => {
        ta.focus();
        const pos = start + emoji.length;
        ta.selectionStart = pos;
        ta.selectionEnd = pos;
    });
}
async function probarEnvio() {
    if (!testPhoneNumber.value || loadingTestEnvio.value) return;
    loadingTestEnvio.value = true;
    testResultado.value = null;
    try {
        const res = await axios.post("/api/citas/test/enviar", {
            phone: testPhoneNumber.value,
        });
        testResultado.value = {
            ok: true,
            mensaje: res.data?.message || "Mensaje enviado correctamente.",
        };
    } catch (error) {
        const msg =
            error?.response?.data?.message || error.message || "Error al enviar el mensaje.";
        testResultado.value = {
            ok: false,
            mensaje: msg,
        };
    } finally {
        loadingTestEnvio.value = false;
    }
}

onMounted(() => {
    listarNumeros();
    listarSaludos();
    listarSedes();
    listarServicios();
    cargarServiciosProductos();
});

onBeforeUnmount(() => {
    safeDestroyDataTable(dtNumeros.value || "#tblWhatsappNumbers");
    safeDestroyDataTable(dtSaludos.value || "#tblWhatsappSaludos");
});
</script>
<style scoped>
.right-sidebar {
    position: fixed;
    right: -70%;
    width: 70%;
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

.shw-rside {
    right: 0px;
    width: 70%;
    overflow-y: auto;
}

.whatsapp-editor {
    position: relative;
}

.whatsapp-toolbar {
    display: flex;
    align-items: center;
    gap: 2px;
    padding: 6px 8px;
    border: 1px solid #ced4da;
    border-bottom: none;
    border-radius: 4px 4px 0 0;
    background: #f8f9fa;
    flex-wrap: wrap;
    position: relative;
}

.whatsapp-toolbar .btn-light {
    border: 1px solid transparent;
    padding: 2px 10px;
    font-size: 14px;
    line-height: 1.5;
    min-width: 32px;
}

.whatsapp-toolbar .btn-light:hover {
    border-color: #ced4da;
    background: #e9ecef;
}

.whatsapp-toolbar .btn-light b {
    font-weight: 700;
}

.whatsapp-separator {
    color: #ced4da;
    padding: 0 4px;
    user-select: none;
}

.whatsapp-editor textarea {
    border-top-left-radius: 0;
    border-top-right-radius: 0;
}
</style>
