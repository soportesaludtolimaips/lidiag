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
                                    :class="{ active: tabActivo === 'productos' }"
                                    href="#"
                                    @click.prevent="cambiarTab('productos')"
                                >
                                    <i class="fa fa-cubes"></i> Productos por Servicio
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
                            :sedes="sedes"
                            :servicios="servicios"
                        />
                        <ConfigWhatsappProductos
                            v-if="tabActivo === 'productos'"
                            ref="productosRef"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { onMounted, ref } from "vue";
import axios from "axios";

import { toastMessage } from "@/helpers/alerts.js";
import ConfigWhatsappNumeros from "./configWhatsappNumeros.vue";
import ConfigWhatsappProductos from "./configWhatsappProductos.vue";
import ConfigWhatsappSaludos from "./configWhatsappSaludos.vue";

const tabActivo = ref("numeros");
const sedes = ref([]);
const servicios = ref([]);
const numerosRef = ref(null);
const saludosRef = ref(null);
const productosRef = ref(null);

function cambiarTab(tab) {
    tabActivo.value = tab;
}
function nuevoRegistro() {
    if (tabActivo.value === "numeros") {
        numerosRef.value?.nuevo();
    } else if (tabActivo.value === "saludos") {
        saludosRef.value?.nuevo();
    } else {
        productosRef.value?.agregar();
    }
}
async function listarSedes() {
    try {
        const res = await axios.get("/api/config-sedes/listar/sedes/activas");
        sedes.value = res.data.data;
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar las sedes.",
            icon: "error",
        });
    }
}
async function listarServicios() {
    try {
        const res = await axios.get("/api/config-servicio-citas");
        servicios.value = res.data.data;
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar los servicios.",
            icon: "error",
        });
    }
}

onMounted(() => {
    listarSedes();
    listarServicios();
});
</script>
