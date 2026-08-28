<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Configuración</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Configuración</a></li>
                    <li class="breadcrumb-item active">Helpdesk - Estados</li>
                </ol>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Listado de estados</h4>
                        <h6 class="card-subtitle">Estados del flujo de Helpdesk.</h6>
                        <div class="table-responsive m-t-40">
                            <table
                                id="example23"
                                ref="dt"
                                class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Orden</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.nombre }}</td>
                                        <td>{{ item.orden }}</td>
                                        <td>
                                            <span class="label label-success" v-if="item.activo"
                                                >Activo</span
                                            >
                                            <span class="label label-danger" v-else>Inactivo</span>
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
</template>
<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from "vue";

import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import axios from "axios";

const dt = ref(null);
const registros = ref([]);
const loadingListar = ref(false);

/**
 * Lista los estados del helpdesk y reconstruye la tabla DataTables.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
async function listar() {
    if (loadingListar.value) return;
    loadingListar.value = true;
    try {
        const res = await axios.get("/api/helpdesk-estados");
        safeDestroyDataTable(dt.value || "#example23");
        registros.value = res.data.data || [];
        nextTick(() => {
            safeInitDataTable(dt.value || "#example23", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
            });
        });
    } catch (error) {
    } finally {
        loadingListar.value = false;
    }
}

/**
 * Inicializa el componente listando los estados.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
onMounted(() => {
    listar();
});

/**
 * Destruye la instancia de DataTables al desmontar el componente.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
onBeforeUnmount(() => {
    safeDestroyDataTable(dt.value || "#example23");
});
</script>
