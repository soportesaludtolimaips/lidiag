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
                    <li class="breadcrumb-item active">Ecografias admiisionadas</li>
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
                            <i class="fa fa-filter"></i> Filtros para busqueda las ecografias adminisionadas.
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
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in registros" :key="index">
                                        <td>{{ item.created_at }}</td>
                                        <td>{{ item.paciente.num_docu }}</td>
                                        <td>{{ item.paciente.nombres }} {{ item.paciente.seg_nom }} {{
                                            item.paciente.pri_ape }}
                                            {{ item.paciente.seg_ape }}</td>
                                        <td>{{ item.nom_produc }}</td>
                                        <td>{{ item.ValProduc }}</td>
                                        <td>{{ item.admin_salud.nom_admin_salud }}</td>
                                        <td>{{ item.contrato }}</td>
                                        <td>{{ item.plan }}</td>
                                        <td>
                                            <span class="label label-info" v-if="item.estado === 'EN PROCESO'">{{
                                                item.estado
                                                }}</span>
                                            <span class="label label-success" v-if="item.estado === 'LEIDO'">{{
                                                item.estado
                                                }}</span>
                                            <span class="label label-danger" v-if="item.estado === 'ANULADO'">{{
                                                item.estado
                                                }}</span>
                                        </td>
                                        <td class="text-nowrap">
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm m-r-5"
                                                @click="actualizar = false; registroParaAnular(item);"
                                                title="Anular lectura" alt="default" data-toggle="modal"
                                                data-target="#responsive-modal" v-if="item.estado === 'EN PROCESO'">
                                                <i class=" fa fa-trash"></i>
                                            </button>

                                            <button type="button"
                                                class="btn waves-effect waves-info btn-rounded btn-outline-primary btn-sm m-r-5"
                                                @click="imprimirReporte(item.id);" title="Imprimir reporte">
                                                <i class=" fa fa-print"></i>
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
    data() {
        return {
            busqueda: { sede_id: this.sedeActual, medico_id: this.medicoActual, bus_nom_num_docu: "6341296", bus_fehc_ini: "", bus_fecha_fin: "" },
            registros: [],
            registroAnular: { ecografia_id: "", atencion_id: "", quien_anulo_id: this.usuarioactual.id, motivo_anula: '' },
            errores: {},
            erroresBusqueda: {},
        }
    },
    methods: {
        async listarAtenciones() {

            if (this.registroAnular.bus_nom_num_docu == "" && this.busqueda.bus_fehc_ini == "" && this.busqueda.bus_fecha_fin == "") {
                $.toast({
                    heading: '¡Atención!',
                    text: 'Debe ingresar almenos un criterio de busqueda.',
                    position: 'top-right',
                    loaderBg: '#ff6849',
                    icon: 'warning',
                    hideAfter: 3500,
                    stack: 6,
                });
                return;
            }

            const res = await axios.post('/ecografias-filtar', this.busqueda);

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
        async imprimirReporte(id) {
            try {
                const res = await axios.post(`/ecografia-generar-reporte/${id}`);
                console.log(res)
                /* const res = await axios.post(`/ecografia-generar-reporte/${id}`, {
                    responseType: 'blob'
                });

                const file = new Blob([res.data], { type: 'application/pdf' });
                const fileURL = URL.createObjectURL(file);
                window.open(fileURL); */
            } catch (error) {
                console.log(error);
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