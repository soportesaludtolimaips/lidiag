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
                    <li class="breadcrumb-item active">WhatsApp Bot</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-outline-secondary m-r-5"
                    @click="listarDatos"
                    :disabled="loadingListar"
                    title="Actualizar listado activo"
                >
                    <i class="fa" :class="loadingListar ? 'fa-spinner fa-spin' : 'fa-refresh'"></i>
                    Refrescar
                </button>
                <button
                    type="button"
                    class="btn btn-info btnDesplegarRigthSidebar"
                    @click="nuevoRegistro()"
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
                                    :class="{ active: tabActivo === 'recomendaciones' }"
                                    href="#"
                                    @click.prevent="cambiarTab('recomendaciones')"
                                >
                                    <i class="fa fa-list-alt"></i> Recomendaciones por Producto
                                </a>
                            </li>
                        </ul>

                        <ConfigWhatsappNumeros
                            v-if="tabActivo === 'numeros'"
                            ref="numerosRef"
                            :sedes="sedes"
                        />
                        <ConfigWhatsappSaludos
                            v-if="tabActivo === 'saludos'"
                            ref="saludosRef"
                            :sede-id="sedeId"
                        />
                        <RecomendacionProductoComponent
                            v-if="tabActivo === 'recomendaciones'"
                            ref="recomendacionesRef"
                            :embedded="true"
                            :sede-id="sedeId"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { getCurrentInstance, onBeforeUnmount, onMounted, ref } from "vue";
import axios from "axios";

import { toastMessage } from "@/helpers/alerts.js";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts.js";
import ConfigWhatsappNumeros from "./configWhatsappNumeros.vue";
import ConfigWhatsappSaludos from "./configWhatsappSaludos.vue";
import RecomendacionProductoComponent from "@/components/modulos/citas/recomendacionProductoComponent.vue";

const { proxy } = getCurrentInstance();
const emitter = proxy.emitter;

function obtenerSedeActiva() {
    return sessionStorage.getItem("ST-sede") || localStorage.getItem("ST-sede") || "";
}

const tabActivo = ref("numeros");
const sedes = ref([]);
const sedeId = ref(obtenerSedeActiva());
const numerosRef = ref(null);
const saludosRef = ref(null);
const recomendacionesRef = ref(null);
const loadingListar = ref(false);

async function listarDatos() {
    if (loadingListar.value) return;
    loadingListar.value = true;
    try {
        if (tabActivo.value === "numeros") {
            await numerosRef.value?.listar?.();
        } else if (tabActivo.value === "saludos") {
            await saludosRef.value?.listar?.();
        } else if (tabActivo.value === "recomendaciones") {
            await recomendacionesRef.value?.buscar?.();
        }
    } finally {
        loadingListar.value = false;
    }
}

useKeyboardShortcuts({
    onSearch: listarDatos,
});

function cambiarTab(tab) {
    tabActivo.value = tab;
}
function nuevoRegistro() {
    if (tabActivo.value === "numeros") {
        numerosRef.value?.nuevo();
    } else if (tabActivo.value === "saludos") {
        saludosRef.value?.nuevo();
    } else if (tabActivo.value === "recomendaciones") {
        recomendacionesRef.value?.mostrarRegistro?.(null);
    }
}
async function listarSedes() {
    try {
        const res = await axios.get("/api/config-sedes/listar/sedes/activas");
        sedes.value = res?.data?.data || res?.data || [];
    } catch {
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar las sedes.",
            icon: "error",
        });
    }
}
function onSedeSeleccionada(payload) {
    const raw =
        payload?.sede ??
        (typeof payload === "object" ? payload.idSede || payload.id : payload) ??
        obtenerSedeActiva();
    sedeId.value = raw ? String(raw) : "";
}

onMounted(() => {
    listarSedes();
    if (!sedeId.value) {
        emitter?.emit?.("solicitarSedeActiva");
    }
    emitter.on("sedeSeleccionada", onSedeSeleccionada);
});

onBeforeUnmount(() => {
    emitter.off("sedeSeleccionada", onSedeSeleccionada);
});
</script>
