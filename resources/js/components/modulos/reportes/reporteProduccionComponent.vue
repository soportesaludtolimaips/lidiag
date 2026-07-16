<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Reportes</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Reportes</a>
                    </li>
                    <li class="breadcrumb-item active">ProducciÃ³n</li>
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
            <div class="col-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-filter"></i> ProducciÃ³n
                        </h4>

                        <div class="row">
                            <div class="col-12">
                                <a
                                    href="javascript:void(0)"
                                    data-toggle="modal"
                                    data-target="#responsive-modal"
                                    @click="tipoReporte = 'Rx'"
                                >
                                    <i class="fa fa-check text-info"></i>
                                    ProducciÃ³n detallada (RX, TomografÃ­a, MamografÃ­a, Resonancia)
                                </a>
                            </div>
                            <div class="col-12" v-if="can('ecografias.reporte')">
                                <a
                                    href="javascript:void(0)"
                                    data-toggle="modal"
                                    data-target="#responsive-modal"
                                    @click="tipoReporte = 'Ecografia'"
                                >
                                    <i class="fa fa-check text-info"></i>
                                    ProducciÃ³n detallada (Ecografias)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- End Page Content -->
        <!-- ============================================================== -->

        <!-- sample modal content -->
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
                        <h4 class="modal-title">Filtro para el reporte</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                            Ã—
                        </button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="form-group">
                                <select
                                    class="form-control custom-select"
                                    style="width: 100%; height: 36px"
                                    v-model="datosReporte.sedeId"
                                    id="sedes"
                                    name="sedes"
                                >
                                    <option value="0">...::: Todas las sedes :::...</option>
                                    <option
                                        v-for="ItemSede in sedes"
                                        :key="ItemSede"
                                        :value="ItemSede.id"
                                    >
                                        {{ ItemSede.nom_sede }}
                                    </option>
                                </select>
                            </div>
                            <div class="form-group">
                                <select
                                    class="form-control custom-select"
                                    style="width: 100%; height: 36px"
                                    v-model="datosReporte.quien_asigno"
                                    id="sedes"
                                    name="sedes"
                                >
                                    <option value="0">...::: Todos quienes agendaron :::...</option>
                                    <option
                                        v-for="(ItemUsuario, index) in quienes_asignaron"
                                        :key="index"
                                        :value="ItemUsuario.id"
                                    >
                                        {{ ItemUsuario.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="form-group">
                                <select
                                    class="form-control custom-select"
                                    style="width: 100%; height: 36px"
                                    v-model="datosReporte.medico"
                                    id="sedes"
                                    name="sedes"
                                >
                                    <option value="" selected>
                                        ...::: Todos los medicos :::...
                                    </option>
                                    <option
                                        v-for="(ItemUsuario, index) in medicos"
                                        :key="index"
                                        :value="ItemUsuario.id"
                                    >
                                        {{ ItemUsuario.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <div class="example">
                                            <h5 class="box-title m-t-30">Desde</h5>
                                            <input
                                                class="form-control input-daterange-datepicker"
                                                type="date"
                                                id="fechaIni"
                                                name="fechaIni"
                                                v-model="datosReporte.fechaIni"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <div class="example">
                                            <h5 class="box-title m-t-30">Hasta</h5>
                                            <input
                                                class="form-control input-daterange-datepicker"
                                                type="date"
                                                id="fechaFin"
                                                name="fechaFin"
                                                v-model="datosReporte.fechaFin"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-default waves-effect"
                            data-dismiss="modal"
                        >
                            Close
                        </button>
                        <button
                            type="button"
                            class="btn btn-info waves-effect waves-light"
                            @click="descargarReporte()"
                            :disabled="loadingDescargar"
                        >
                            <i v-if="loadingDescargar" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-download"></i>
                            Descargar reportes
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.modal -->
    </div>
</template>
<script setup>
import { onMounted, ref } from "vue";

import { listarUsuariosYSedes } from "../../../helpers/usuarios";
import usePermissions from "@/helpers/permissions.js";
import { handleAxiosErrorToast } from "@/helpers/alerts";
import axios from "axios";

const props = defineProps({ usuarioactual: Object, permisos: Array });

const sedes = ref([]);
const datosReporte = ref({ sedeId: 0, quien_asigno: "", medico: "", fechaIni: "", fechaFin: "" });
const quienes_asignaron = ref([]);
const medicos = ref([]);
const tipoReporte = ref("Rx");
const loadingDescargar = ref(false);

async function listarSedes() {
    try {
        await listarUsuariosYSedes(this);

        const res = await axios.get("/api/config-sedes/listar/sedes/activas");
        sedes.value = res.data;
    } catch (error) {
        handleAxiosErrorToast(error, "No fue posible cargar sedes/usuarios.");
    }
}
async function descargarReporte() {
    if (loadingDescargar.value) return;
    loadingDescargar.value = true;
    try {
        if (!datosReporte.value.fechaIni || !datosReporte.value.fechaFin) {
            $.toast({
                heading: "Upsss!!!",
                text: "Debes seleccionar fecha inicio y fecha fin.",
                position: "top-right",
                loaderBg: "#ff6849",
                icon: "warning",
                hideAfter: 3500,
                stack: 6,
            });
            return;
        }
        if (datosReporte.value.fechaIni > datosReporte.value.fechaFin) {
            $.toast({
                heading: "Upsss!!!",
                text: "La fecha inicio no puede ser mayor a la fecha fin.",
                position: "top-right",
                loaderBg: "#ff6849",
                icon: "warning",
                hideAfter: 3500,
                stack: 6,
            });
            return;
        }

        const params = new URLSearchParams({
            tipo: String(tipoReporte.value || "Rx"),
            sedeId: String(datosReporte.value.sedeId ?? 0),
            quien_asigno: String(datosReporte.value.quien_asigno ?? 0),
            medico: String(datosReporte.value.medico ?? 0),
            fechaIni: String(datosReporte.value.fechaIni),
            fechaFin: String(datosReporte.value.fechaFin),
        });
        window.open(
            `/descarga-reportes-produccion?${params.toString()}`,
            "_blank",
            "noopener,noreferrer"
        );
    } catch (error) {
        handleAxiosErrorToast(error, "No fue posible descargar el reporte.");
    } finally {
        // pequeÃ±o delay para evitar doble click inmediato
        setTimeout(() => {
            loadingDescargar.value = false;
        }, 700);
    }
}

onMounted(() => {
    listarSedes();
});
</script>
