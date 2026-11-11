<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Configuración</h3>
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
                            Configuración
                        </button>
                    </li>
                    <li class="breadcrumb-item active">Helpdesk - Estados</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    @click="listar"
                    :disabled="loadingListar"
                    title="Actualizar listado"
                >
                    <i class="fa" :class="loadingListar ? 'fa-spinner fa-spin' : 'fa-refresh'"></i>
                    Refrescar
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Listado de estados</h4>
                        <h6 class="card-subtitle">Estados del flujo de Helpdesk.</h6>
                        <table-skeleton-loader :visible="loadingListar" :rows="5" :columns="3" />
                        <empty-state-component
                            v-if="!loadingListar && registros.length === 0"
                            title="No hay estados registrados"
                            description="No se encontraron registros de estados de Helpdesk."
                            icon="fa-tasks"
                        />
                        <div
                            v-show="!loadingListar && registros.length > 0"
                            class="table-responsive m-t-40"
                        >
                            <table
                                id="tablaHelpdeskEstados"
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
import { toastMessage } from "@/helpers/alerts.js";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts.js";
import axios from "axios";

const dt = ref(null);
const registros = ref([]);
const loadingListar = ref(false);

async function listar() {
    if (loadingListar.value) return;
    loadingListar.value = true;
    safeDestroyDataTable(dt.value || "#tablaHelpdeskEstados");
    try {
        const res = await axios.get("/api/helpdesk-estados");
        let data = res?.data?.data || res?.data || [];
        if (typeof data === "string") {
            try {
                data = JSON.parse(data);
            } catch (_e) {
                data = [];
            }
        }
        registros.value = Array.isArray(data) ? data : [];
    } catch {
        toastMessage({
            heading: "Error",
            text: "No fue posible listar los estados.",
            icon: "error",
        });
        registros.value = [];
    } finally {
        loadingListar.value = false;
        await nextTick();
        if (registros.value.length > 0) {
            safeDestroyDataTable(dt.value || "#tablaHelpdeskEstados");
            safeInitDataTable(dt.value || "#tablaHelpdeskEstados", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
                autoWidth: false,
            });
        }
    }
}

useKeyboardShortcuts({
    onSearch: listar,
});

onMounted(() => {
    listar();
});

onBeforeUnmount(() => {
    safeDestroyDataTable(dt.value || "#tablaHelpdeskEstados");
});
</script>
