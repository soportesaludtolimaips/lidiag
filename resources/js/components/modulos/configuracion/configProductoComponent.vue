<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
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
                    <li class="breadcrumb-item active">Productos</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button type="button" class="btn btn-info btnDesplegarRigthSidebar"
                    @click="actualizar = false; mostrarRegistro()">
                    <i class="fa fa-plus-circle m-r-5"></i> Nuevo registro
                </button>
            </div>
            <!-- <div class="">
                <button class="right-side-toggle btn-info btn btn-circle btn-sm">
                    <i class="fa fa-plus-circle m-r-5"></i>
                </button>
            </div> -->
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
                        <h4 class="card-title">Listado de productos</h4>
                        <h6 class="card-subtitle">Administración de productos para la prestación del servicio de
                            imagenes diagnosticas.</h6>
                        <div class="table-responsive m-t-40">
                            <table id="example23" class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <th>Cups</th>
                                        <th>Producto</th>
                                        <th>%</th>
                                        <th>Val. Produc</th>
                                        <th>Lectura</th>
                                        <th>Oral</th>
                                        <th>Venoso</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.cod_cups }}</td>
                                        <td>{{ item.nom_produc }}</td>
                                        <td>{{ item.porcen }}</td>
                                        <td>{{ item.val_produc }}</td>
                                        <td>{{ item.requi_lectura }}</td>
                                        <td>{{ item.tipo_oral }}</td>
                                        <td>{{ item.tipo_venoso }}</td>
                                        <td>
                                            <span class="label label-success" v-if="item.estado">Activo</span>
                                            <span class="label label-danger" v-if="!item.estado">Inactivo</span>
                                        </td>
                                        <td class="text-nowrap">
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                @click="actualizar = true; mostrarRegistro(item)">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                @click="elimnarRegistro(item.id)">
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
        </div>
        <!-- ============================================================== -->
        <!-- End PAge Content -->
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
                        <i class="ti-close right-side-toggle" id="btnCerralModalForm"></i>
                    </span>
                </div>
                <div class="r-panel-body">
                    <!-- ============================================================== -->
                    <!-- Aqui va el contenido de los formularios -->
                    <!-- ============================================================== -->
                    <form action="#">
                        <div class="form-body">
                            <div class="row p-t-20">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Cups</label>
                                        <input type="text" id="cod_cups" name="cod_cups" v-model="registro.cod_cups"
                                            class="form-control" placeholder="Código cups">
                                        <span class="text-danger" v-if="errores.cod_cups">{{ errores.cod_cups[0]
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Producto</label>
                                        <input type="text" id="nom_produc" name="nom_produc" v-model="registro.nom_produc"
                                            class="form-control" placeholder="Ingrese aquí el nombre del producto">
                                        <span class="text-danger" v-if="errores.nom_produc">{{ errores.nom_produc[0]
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">%</label>
                                        <input type="text" id="porcen" name="porcen" v-model="registro.porcen"
                                            class="form-control" placeholder="% de ganancia">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Precio</label>
                                        <input type="text" id="val_produc" name="val_produc" v-model="registro.val_produc"
                                            class="form-control" placeholder="Ingrese aquí el precio del producto">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="checkbox checkbox-success">
                                            <input type="checkbox" id="requi_lectura" name="requi_lectura"
                                                v-model="registro.requi_lectura" :checked="registro.requi_lectura"
                                                value="1">
                                            <label for="requi_lectura"> Requiere Lectura </label>
                                        </div>
                                        <div class="checkbox checkbox-success">
                                            <input type="checkbox" id="tipo_oral" name="tipo_oral"
                                                v-model="registro.tipo_oral" :checked="registro.tipo_oral" value="1">
                                            <label for="tipo_oral"> Tipo Oral </label>
                                        </div>
                                        <div class="checkbox checkbox-success">
                                            <input type="checkbox" id="tipo_venoso" name="tipo_venoso"
                                                v-model="registro.tipo_venoso" :checked="registro.tipo_venoso" value="1">
                                            <label for="tipo_venoso"> Tipo Venoso </label>
                                        </div>
                                        <div class="checkbox checkbox-success">
                                            <input type="checkbox" id="factura" name="factura" v-model="registro.factura"
                                                :checked="registro.factura" value="1">
                                            <label for="factura"> Genera
                                                factura </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="checkbox checkbox-success">
                                <input type="checkbox" id="estado" name="estado" v-model="registro.estado"
                                    :checked="registro.estado" value="1">
                                <label for="estado"> Activo </label>
                            </div>

                            <div class="form-actions mt-3">
                                <button type="button" class="btn btn-success m-r-5" @click="guardarRegistro()"
                                    v-if="!actualizar">
                                    <i class="fa fa-check"></i> Guardar
                                </button>

                                <button type="button" class="btn btn-warning m-r-5" v-if="actualizar"
                                    @click="guardarRegistro()">
                                    <i class="fa fa-pencil"></i> Actualizar
                                </button>

                                <button type="button" class="btn btn-inverse" @click="btnCerralModalForm()">Cancel</button>
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
    mounted() {
        this.ListarDatos();
    },
    data() {
        return {
            id: 0,
            registros: [],
            registro: { cod_cups: '', nom_produc: '', porcen: 0, val_produc: 0, requi_lectura: 0, tipo_oral: 0, tipo_venoso: 0, factura: 0, estado: 1 },
            tituloModal: 'Nuevo registro',
            actualizar: false,
            errores: {},
        };
    },
    methods: {
        async ListarDatos() {
            const res = await axios.get("/config-productos");

            $('#example23').DataTable().destroy();

            this.registros = res.data;
            console.log(res.data);
            this.$nextTick(() => {
                $('#example23').DataTable({
                    dom: 'Bfrtip',
                    buttons: [
                        'copy', 'csv', 'excel', 'pdf', 'print'
                    ]
                });
            });
        },
        async guardarRegistro() {
            try {
                if (this.actualizar === false) {
                    const res = await axios.post('/config-productos', this.registro);

                    if (res.status == 200) {

                        this.ListarDatos()

                        $.toast({
                            heading: 'Ok!!!',
                            text: res.data.message,
                            position: 'top-right',
                            loaderBg: '#ff6849',
                            icon: 'success',
                            hideAfter: 3500,
                            stack: 6
                        });

                        this.btnCerralModalForm();
                        this.limpiar();
                    }
                } else {
                    const res = await axios.put('/config-productos/' + this.id, this.registro);
                    if (res.status == 200) {

                        this.ListarDatos()

                        $.toast({
                            heading: 'Ok!!!',
                            text: res.data.message,
                            position: 'top-right',
                            loaderBg: '#ff6849',
                            icon: 'success',
                            hideAfter: 3500,
                            stack: 6
                        });

                        this.btnCerralModalForm();
                        this.limpiar();

                    }
                }
            } catch (error) {
                console.log(error);
                this.errores = error.response.data.errors;
            }
        },
        elimnarRegistro(id) {
            var $this = this;
            swal({
                title: "Estas seguro?",
                text: "No podrás recuperar este registro!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "si, eliminarlo!",
                closeOnConfirm: false
            }, function (isConfirm) {
                if (isConfirm) {
                    axios.delete('/config-productos/' + id).then(response => {
                        swal("Ok!!!!", response.data.message, "success");
                        $this.ListarDatos()
                    }).catch(error => {
                        console.log(error)
                    })
                }
            });
        },
        mostrarRegistro(data = {}) {
            if (this.actualizar == true) {
                this.tituloModal = "Actualizar el registro: " + data.nom_produc;
                this.id = data.id;
                this.registro.cod_cups = data.cod_cups;
                this.registro.nom_produc = data.nom_produc;
                this.registro.porcen = data.porcen;
                this.registro.val_produc = data.val_produc;
                this.registro.requi_lectura = data.requi_lectura;
                this.registro.tipo_oral = data.tipo_oral;
                this.registro.tipo_venoso = data.tipo_venoso;
                this.registro.factura = data.factura;

            } else {
                this.actualizar = false;
                this.tituloModal = "Nuevo registro";
                this.id = 0;
                this.registro.cod_cups = "";
                this.registro.nom_produc = "";
                this.registro.porcen = "";
                this.registro.val_produc = "";
                this.registro.requi_lectura = "";
                this.registro.tipo_oral = "";
                this.registro.tipo_venoso = "";
                this.registro.factura = "";

            }
        },
        limpiar() {
            this.actualizar = false;
            this.tituloModal = "Nuevo registro";
            this.id = 0;
            this.registro.cod_cups = "";
            this.registro.nom_produc = "";
            this.registro.porcen = "";
            this.registro.val_produc = "";
            this.registro.requi_lectura = "";
            this.registro.tipo_oral = "";
            this.registro.tipo_venoso = "";
            this.registro.factura = "";
        },
        btnCerralModalForm() {
            $(".right-sidebar").slideDown(50);
            $(".right-sidebar").toggleClass("shw-rside");
        }
    },
};
</script>
<style scoped>
.right-sidebar {
    position: fixed;
    right: -50%;
    width: 50%;
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
    width: 50%;
    display: block;
}
</style>