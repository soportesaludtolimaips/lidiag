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
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Ecografias</a>
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
                            <i class="fa fa-filter"></i> Filtros para busqueda de atención
                        </h4>
                        <form action="#">
                            <div class="form-body">
                                <div class="row p-t-2">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">
                                                # de atención, # de documento ó Nombres de paciente
                                            </label>
                                            <input type="text" id="bus_nom_num_docu" name="bus_nom_num_docu"
                                                v-model="busqueda.bus_nom_num_docu" class="form-control"
                                                placeholder=" Atención, Nombres de paciente o # de documento"
                                                @keyup.enter="buscarStudy()" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Inicio</label>
                                            <input type="date" id="bus_fehc_ini" name="bus_fehc_ini"
                                                v-model="busqueda.bus_fehc_ini" class="form-control" />
                                            <!-- <span class="text-danger" v-if="errores.bus_fehc_ini">
                                                {{ erroresBusqueda.bus_fehc_ini[0] }}
                                            </span> -->
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Fin</label>
                                            <input type="date" id="bus_fecha_fin" name="bus_fecha_fin"
                                                v-model="busqueda.bus_fecha_fin" class="form-control" />
                                            <!-- <span class="text-danger" v-if="errores.bus_fecha_fin">
                                                {{ erroresBusqueda.bus_fecha_fin[0] }}
                                            </span> -->
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions mt-3">
                                    <button type="button" class="btn btn-success m-r-5" @click="listarAtenciones()">
                                        <i class="fa fa-search"></i> Buscar
                                    </button>

                                    <button type="button" class="btn btn-inverse" @click="btnLimpiar()">
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
                        <h6 class="card-subtitle text-info">Asigne los médicos para su respectiva lectura.
                        </h6>
                        <div class="table-responsive">
                            <table id="example23" class="table table-bordered table-striped" cellspacing="0"
                                width="100%">
                                <thead>
                                    <tr>
                                        <th with='5%'>Fecha</th>
                                        <th with='5%'># Documento</th>
                                        <th with='30%'>Paciente</th>
                                        <th>Examen</th>
                                        <th>Val. Examen</th>
                                        <th>Administradora</th>
                                        <th>Contrato</th>
                                        <th>Plan</th>
                                        <th>Quien Ordeno</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in registros" :key="index">
                                        <td>{{ item.FecIngreso }}</td>
                                        <td>{{ item.NumDocumento }}</td>
                                        <td>{{ item.PrimerNombre }} {{ item.SegundoNombre }} {{ item.PrimerApellido }}
                                            {{ item.SegundoApellido }}</td>
                                        <td>{{ item.NomProducto }}</td>
                                        <td>{{ item.ValProduc }}</td>
                                        <td>{{ item.NomTercero }}</td>
                                        <td>{{ item.DesContrato }}</td>
                                        <td>{{ item.DesPlan }}</td>
                                        <td>{{ item.quien_ordeno }}</td>
                                        <td>
                                            <span class="label label-info"
                                                v-if="item.estado_ecografia === 'EN PROCESO'">{{ item.estado_ecografia
                                                }}</span>
                                            <span class="label label-success"
                                                v-if="item.estado_ecografia === 'LEIDO'">{{ item.estado_ecografia
                                                }}</span>
                                            <span class="label label-danger"
                                                v-if="item.estado_ecografia === 'ANULADO'">{{ item.estado_ecografia
                                                }}</span>
                                        </td>
                                        <td class="text-nowrap">
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm m-r-5"
                                                @click="actualizar = false; registroParaAnular(item);"
                                                title="Anular agenda" alt="default" data-toggle="modal"
                                                data-target="#responsive-modal"
                                                v-if="item.estado_ecografia === 'EN PROCESO'">
                                                <i class=" fa fa-trash"></i>
                                            </button>

                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm m-r-5"
                                                @click="actualizar = true;
                                                mostrarRegistro(item);" title="Agendar ecografia">
                                                <i class=" fa fa-check"></i>
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
        <div class="right-sidebar" style="overflow-x: hidden; overflow-y: scroll; overflow-x: hidden; height: 100%;">
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
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Tipo de documento</label>
                                                <Select2 :value="registro.tipo_documento_id"
                                                    :options="tipoDocumentoOptions" width="100%" :allow-clear="true"
                                                    :label-fields="['codigo', 'tipo_documento']"
                                                    :dropdown-parent-selector="'#tipoDocumentos-parent'"
                                                    @input="onTipoDocumentoSelect" />

                                                <span class="text-danger" v-if="errores.tipo_documento_id">
                                                    {{ errores.tipo_documento_id[0] }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label"># Documento</label>
                                                <input type="text" id="num_docu" name="num_docu"
                                                    v-model="registro.num_docu" class="form-control"
                                                    placeholder="# de Documento" />
                                                <!-- <span class="text-danger" v-if="errores.num_docu">
                                                    {{ errores.num_docu[0] }}
                                                </span> -->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label">Primer Nombre</label>
                                                <input type="text" id="nombres" name="nombres"
                                                    v-model="registro.nombres" class="form-control"
                                                    placeholder="Nombres" />
                                                <!-- <span class="text-danger" v-if="errores.nombres">
                                                    {{ errores.nombres[0] }}
                                                </span> -->
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label">Segundo Nombre</label>
                                                <input type="text" id="seg_nom" name="seg_nom"
                                                    v-model="registro.seg_nom" class="form-control"
                                                    placeholder="seg_nom" />
                                                <!-- <span class="text-danger" v-if="errores.seg_nom">
                                                    {{ errores.seg_nom[0] }}
                                                </span> -->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label">Primer Apellido</label>
                                                <input type="text" id="pri_ape" name="pri_ape"
                                                    v-model="registro.pri_ape" class="form-control"
                                                    placeholder="pri_ape" />
                                                <!-- <span class="text-danger" v-if="errores.pri_ape">
                                                    {{ errores.pri_ape[0] }}
                                                </span> -->
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label">Segundo Apellido</label>
                                                <input type="text" id="seg_ape" name="seg_ape"
                                                    v-model="registro.seg_ape" class="form-control"
                                                    placeholder="seg_ape" />
                                                <!-- <span class="text-danger" v-if="errores.seg_ape">
                                                    {{ errores.seg_ape[0] }}
                                                </span> -->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label">Sexo</label>
                                                <input type="text" id="sexo" name="sexo" v-model="registro.sexo"
                                                    class="form-control" />
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label">Fec. Nacimi</label>
                                                <input type="date" id="fec_naci" name="fec_naci"
                                                    v-model="registro.fec_naci" class="form-control" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-7">
                                            <div class="form-group">
                                                <label class="control-label">Dirección</label>
                                                <input type="text" id="direccion" name="direccion"
                                                    v-model="registro.direccion" class="form-control" />
                                                <!-- <span class="text-danger" v-if="errores.direccion">
                                                    {{ errores.direccion[0] }}
                                                </span> -->
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">Télefono</label>
                                                <input type="number" id="telefono" name="telefono"
                                                    v-model="registro.telefono" class="form-control" />
                                                <!-- <span class="text-danger" v-if="errores.telefono">
                                                    {{ errores.telefono[0] }}
                                                </span> -->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-7">
                                            <div class="form-group">
                                                <label class="control-label">E - Mail</label>
                                                <input type="email" id="email" name="email" v-model="registro.email"
                                                    class="form-control" />
                                                <!-- <span class="text-danger" v-if="errores.email">
                                                    {{ errores.email[0] }}</span> -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> Datos de la Asignacion
                                    </h5>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="form-group">
                                                <label>Médico</label>
                                                <Select2 :value="registro.medico_id" :options="medicoOptions"
                                                    width="100%" :allow-clear="true" :label-fields="['name']"
                                                    :dropdown-parent-selector="'#medicos-parent'"
                                                    @input="onMedicoSelect" />

                                                <span class="text-danger" v-if="errores.medico_id">
                                                    {{ errores.medico_id[0] }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-product-hunt"></i> Productos
                                    </h5>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-12 table-responsive">
                                            <table id="myTable" class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Código</th>
                                                        <th>Producto</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="item in registro.productosAgenda" :key="item.id">
                                                        <td>{{ item.CodLegal }}</td>
                                                        <td>{{ item.NomProducto }}</td>
                                                        <td class="text-nowrap">
                                                            <button type="button"
                                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                                @click="quitarProductoAgenda(item.CodLegal)">
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
                                <button type="button" class="btn btn-success m-r-5" @click="guardarRegistro()">
                                    <i class="fa fa-check"></i> Guardar
                                </button>

                                <button type="button" class="btn btn-inverse"
                                    @click="btnCerralModalForm()">Cancel</button>
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

    <div id="responsive-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
        aria-hidden="true" style="display: none;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Anular Agenda</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="form-group">
                            <label for="message-text" class="control-label">Motivo de anulación:</label>
                            <textarea class="form-control" id="message-text"
                                v-model="registroAnular.motivo_anula"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default waves-effect" data-dismiss="modal"
                        id="btnCerrarModalAunular">Close</button>
                    <button type="button" class="btn btn-danger waves-effect waves-light"
                        @click="anularEcografia()">Anular</button>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    props: ['usuarioactual'],
    mounted() {
        this.listarMedicos();
        this.establecerSede();
        this.listarTipoDocumentos();

        this.emitter.on("sedeSeleccionada", (data) => {
            this.registro.sede_id = data.idSede;
            this.establecerSede();
            this.establecerMedico();
        });
    },
    data() {
        return {
            tituloModal: 'Nueva adminsión',
            busqueda: { sede_id: this.sedeActual, medico_id: this.medicoActual, bus_nom_num_docu: "6341296", bus_fehc_ini: "", bus_fecha_fin: "" },
            registros: [],
            registro: {
                paciente_id: "", tipo_documento_id: "", num_docu: "", nombres: "", seg_nom: "", pri_ape: "", seg_ape: "", sexo: "", fec_naci: "", direccion: "", tel: "",
                sede_id: this.sedeActual, medico_id: this.medicoActual, quien_registro_id: this.usuarioactual.id, cel: "", atencion_id: null, productosAgenda: [], contrato: null, plan: null,
                admin_salud: null, quien_ordeno: null
            },
            registroAnular: { ecografia_id: "", atencion_id: "", quien_anulo_id: this.usuarioactual.id, motivo_anula: '' },
            errores: {},
            erroresBusqueda: {},
            medicos: [],
            tipoDocumentos: [],
            sedeActual: sessionStorage.getItem('ST-sede'),
            medicoActual: sessionStorage.getItem('ST-medico'),
            productosAgenda: [],
        }
    },
    computed: {
        medicoOptions() {
            // Opciones para el Select2 local de medicos.
            return (this.medicos || []).map(p => ({ id: String(p.id), name: p.name }));
        },
        tipoDocumentoOptions() {
            // Opciones para el Select2 local de medicos.
            return (this.tipoDocumentos || []).map(p => ({ id: String(p.id), codigo: p.codigo, tipo_documento: p.tipo_documento }));
        },
    },
    methods: {
        async listarAtenciones() {

            const res = await axios.post('/interface-sahi-ecografias/listar/productos/atencion', this.busqueda);
            console.log(res.data);
            if (res.status == 200) {
                $("#example23").DataTable().destroy();

                this.registros = res.data;

                this.$nextTick(() => {
                    $("#example23").DataTable({
                        dom: "Bfrtip",
                        buttons: ["copy", "csv", "excel", "pdf", "print"],
                    });
                });
            }
        },
        async guardarRegistro() {
            try {

                const ecografiaRes = await axios.post('/ecografias', this.registro);

                if (ecografiaRes.status === 201) {

                    // Mostrar notificación de éxito
                    $.toast({
                        heading: '¡Éxito!',
                        text: 'Agenda guardada correctamente.',
                        position: 'top-right',
                        loaderBg: '#ff6849',
                        icon: 'success',
                        hideAfter: 3500,
                        stack: 6,
                    });

                    // Actualizar la lista de datos y limpiar el formulario
                    this.listarAtenciones();
                    this.limpiarFormulario();
                    this.btnCerralModalForm();

                } else {
                    $.toast({
                        heading: "Upss!!!",
                        text: res.data.message,
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "danger",
                        hideAfter: 3500,
                        stack: 6,
                    });
                }
            } catch (error) {
                // Manejar errores y mostrar notificación
                console.error('Error al guardar la agenda:', error);

                $.toast({
                    heading: 'Error',
                    text: error.response?.data?.error || 'Ocurrió un error al guardar la agenda.',
                    position: 'top-right',
                    loaderBg: '#ff6849',
                    icon: 'error',
                    hideAfter: 3500,
                    stack: 6,
                });

                // Mostrar errores específicos en el formulario
                if (error.response?.data?.errors) {
                    this.errores = error.response.data.errors;
                }
            }
        },
        registroParaAnular(data) {
            this.registroAnular.ecografia_id = data.id;
            this.registroAnular.atencion_id = data.IdAtencion;
        },
        anularEcografia() {
            try {
                if (this.registroAnular.motivo_anula === "") {
                    $.toast({
                        heading: '¡Atención!',
                        text: 'Debe ingresar el motivo de anulación.',
                        position: 'top-right',
                        loaderBg: '#ff6849',
                        icon: 'warning',
                        hideAfter: 3500,
                        stack: 6,
                    });
                    return;
                }

                var $this = this;
                swal({
                    title: "Estas seguro de anular la ecografia?",
                    text: "No podrás recuperar este registro!",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "si, eliminarlo!",
                    closeOnConfirm: false
                }, function (isConfirm) {
                    if (isConfirm) {

                        axios.post(`/ecografia-anular/${$this.registroAnular.ecografia_id}`, $this.registroAnular).then(response => {

                            swal("Ok!!!!", response.data.message, "success");
                            const cerrarModal = document.getElementById('btnCerrarModalAunular');
                            cerrarModal.click();
                            this.listarAtenciones();

                        }).catch(error => {
                            console.log(error)
                        })
                    }
                });
            } catch (error) {
                console.log(error);
            }
        },
        establecerSede() {
            this.registro.sede_id = sessionStorage.getItem('ST-sede');
            this.busqueda.sede_id = sessionStorage.getItem('ST-sede');
            this.sedeActual = sessionStorage.getItem('ST-sede');

            if (this.registro.sede_id == 1) {
                this.sedeInterface = true;
            } else {
                this.sedeInterface = false;
            }
            this.registros = [];
        },
        establecerMedico() {
            this.registro.sede_id = sessionStorage.getItem('ST-sede');
            this.busqueda.sede_id = sessionStorage.getItem('ST-sede');
            this.medicoActual = sessionStorage.getItem('ST-medico');

            if (this.registro.sede_id == 1) {
                this.sedeInterface = true;
            } else {
                this.sedeInterface = false;
            }
            this.registros = [];
        },
        async listarMedicos() {
            try {
                const res = await axios.get("/user.listarUsuarios/Medico/2");
                this.medicos = res.data;
            } catch (error) {
                console.log(error);
            }
        },
        async listarTipoDocumentos() {
            try {
                const res = await axios.get("/config-tipos-documentos/listar/activos");
                this.tipoDocumentos = res.data;
            } catch (error) {
                console.log(error);
            }
        },
        onMedicoSelect(value) {
            let selectedId = null;
            if (Array.isArray(value) && value.length > 0) {
                const v = value[0];
                selectedId = String(typeof v === 'object' && v && v.id != null ? v.id : v);
            } else if (value && typeof value === 'object' && value.id != null) {
                selectedId = String(value.id);
            } else if (value != null) {
                selectedId = String(value);
            }
            this.registro.medico_id = selectedId || "";
        },
        onTipoDocumentoSelect(value) {
            let selectedId = null;
            if (Array.isArray(value) && value.length > 0) {
                const v = value[0];
                selectedId = String(typeof v === 'object' && v && v.id != null ? v.id : v);
            } else if (value && typeof value === 'object' && value.id != null) {
                selectedId = String(value.id);
            } else if (value != null) {
                selectedId = String(value);
            }
            this.registro.tipo_documento_id = selectedId || "";
        },
        btnCerralModalForm() {
            $(".right-sidebar").slideDown(50);
            $(".right-sidebar").toggleClass("shw-rside");
        },
        mostrarRegistro(data = {}) {
            this.tituloModal = `Admisionar al paciente: ${data.PrimerNombre} ${data.SegundoNombre}`

            this.registro.paciente_id = data.paciente_id;
            this.registro.num_docu = data.NumDocumento;
            this.registro.nombres = data.PrimerNombre;
            this.registro.seg_nom = data.SegundoNombre;
            this.registro.pri_ape = data.PrimerApellido;
            this.registro.seg_ape = data.SegundoApellido;
            this.registro.sexo = data.IdSexo;
            this.registro.fec_naci = data.FecNacimiento;
            this.registro.direccion = data.DirCasa;
            this.registro.tel = data.TelCasa;
            this.registro.cel = data.TelCasa;
            this.registro.atencion_id = data.IdAtencion;
            this.registro.contrato = data.DesContrato
            this.registro.plan = data.DesPlan
            this.registro.admin_salud = data.NomTercero
            this.registro.quien_ordeno = data.quien_ordeno

            this.registro.productosAgenda = this.registros
            console.log(data);
            // Homologar tipo de documento
            const codigo = data.CodTipoDoc;
            const tipoHomologado = this.tipoDocumentoOptions.find(
                t => t.codigo === codigo
            );

            if (tipoHomologado) {
                this.registro.tipo_documento_id = tipoHomologado.id;
            }

            this.btnCerralModalForm();
        },
        limpiarFormulario() {
            this.tituloModal = '';

            this.registro.paciente_id = '';
            this.registro.num_docu = '';
            this.registro.nombres = '';
            this.registro.seg_nom = '';
            this.registro.pri_ape = '';
            this.registro.seg_ape = '';
            this.registro.sexo = '';
            this.registro.fec_naci = '';
            this.registro.direccion = '';
            this.registro.tel = '';
            this.registro.cel = '';
            this.registro.atencion_id = '';
            this.registro.contrato = '';
            this.registro.plan = '';
            this.registro.admin_salud = '';
            this.registro.quien_ordeno = '';

            this.registro.productosAgenda = []
        },
        quitarProductoAgenda(codProduc) {
            this.registro.productosAgenda = this.registro.productosAgenda.filter(x => x.CodLegal !== codProduc);
        },
        btnCerralModalForm() {
            $(".right-sidebar").slideDown(50);
            $(".right-sidebar").toggleClass("shw-rside");
        },
    }
}
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
</style>