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
                        <h4 class="card-title">Listado de estudios disponibles</h4>
                        <h6 class="card-subtitle text-info">Asigne los estudios a los médicos para su respectiva
                            lectura.
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
                                        <td class="text-nowrap">
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm m-r-5"
                                                @click="actualizar = true;
                                                mostrarRegistro(item, index);" title="Agendar producto">
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
                    <span>
                        <i class="ti-close right-side-toggle" id="btnCerralModalForm" @click="btnCerralModalForm()"></i>
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
                                                <select v-model="registro.medico_id" name="medico_id" id="medico_id"
                                                    class="form-control custom-select">
                                                    <option v-for="(ItemMedico, index) in medicos" :key="index"
                                                        :value="ItemMedico.id">
                                                        {{ ItemMedico.name }}
                                                    </option>
                                                </select>
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
</template>
<script>
export default {
    props: ['usuarioactual'],
    mounted() {
        this.listarMedicos();
        this.establecerSede();
    },
    data() {
        return {
            tituloModal: 'Nueva adminsión',
            busqueda: { sede_id: this.sedeActual, bus_nom_num_docu: "6341296", bus_fehc_ini: "", bus_fecha_fin: "" },
            registros: [],
            registro: {
                paciente_id: "", num_docu: "", nombres: "", seg_nom: "", pri_ape: "", seg_ape: "", sexo: "", fec_naci: "", direccion: "", tel: "",
                sede_id: this.sedeActual, quien_registro_id: this.usuarioactual.id, cel: "", atencion_id: null, productosAgenda: [], contrato: null, plan: null,
                admin_salud: null, quien_ordeno: null
            },
            errores: {},
            erroresBusqueda: {},
            medicos: [],
            sedeActual: sessionStorage.getItem('ST-sede'),
            productosAgenda: [],
        }
    },
    methods: {
        async listarAtenciones() {

            const res = await axios.post('/interface-sahi/listar/productos/atencion', this.busqueda);

            if (res.status == 200) {
                $("#example23").DataTable().destroy();

                this.registros = res.data;
                console.log(this.registros)
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
                // Enviar solicitud para guardar la ecografía
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
                    text: error.response?.data?.message || 'Ocurrió un error al guardar la agenda.',
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
        async listarMedicos() {
            try {
                const res = await axios.get("/user.listarUsuarios/Medico/2");
                this.medicos = res.data;
            } catch (error) {
                console.log(error);
            }
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
    width: 80%;
    display: block;
}
</style>