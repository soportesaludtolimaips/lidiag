<template>
    <div>
        <a
            class="nav-link dropdown-toggle waves-effect waves-dark"
            href=""
            data-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false"
        >
            <i class="flag-icon flag-icon-us"></i> {{ nomSucursal }}
        </a>
        <div class="dropdown-menu dropdown-menu-right animated bounceInDown">
            <a
                class="dropdown-item"
                href="#"
                v-for="(item, index) in registros"
                :key="index"
                @click="establecerSucursal(item)"
            >
                <i class="flag-icon flag-icon-in"></i> {{ item.sucursal }}
            </a>
        </div>
    </div>
</template>
<script setup>
const { proxy } = getCurrentInstance();
const emitter = proxy.emitter;
import { onMounted, ref, getCurrentInstance } from "vue";

import { handleAxiosErrorToast } from "@/helpers/alerts";
import axios from "axios";

const sucursalActual = ref(1);
const nomSucursal = ref("");
const registros = ref([]);

async function listarSucursales() {
    try {
        const res = await axios.get("/api/config-sedes");
        registros.value = res.data.data;
        if (Array.isArray(registros.value) && registros.value.length > 0 && !nomSucursal.value) {
            nomSucursal.value = registros.value[0].sucursal || "";
            sessionStorage.setItem("ST-sede", registros.value[0].id);
            emitter.emit("sedeSeleccionada", { idSede: registros.value[0].id });
        }
    } catch (error) {
        handleAxiosErrorToast(error, "No fue posible listar sucursales.");
        registros.value = [];
    }
}
function establecerSucursal(data = {}) {
    nomSucursal.value = data.sucursal;
    sucursalActual.value = data.id;
    sessionStorage.setItem("ST-sede", data.id);
    emitter.emit("sedeSeleccionada", { idSede: data.id });
}

onMounted(() => {
    listarSucursales();
});
</script>
