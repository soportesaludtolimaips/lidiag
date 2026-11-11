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
import { getCurrentInstance, onMounted, ref } from "vue";
import axios from "axios";
import { toastMessage } from "@/helpers/alerts.js";

const { proxy } = getCurrentInstance();
const emitter = proxy.emitter;

const sucursalActual = ref(1);
const nomSucursal = ref("");
const registros = ref([]);

/**
 * Carga el listado de sedes desde la API y establece la sede guardada o la primera como predeterminada.
 * Emite el evento sedeSeleccionada al componente padre.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2024-04-04
 */
async function listarSucursales() {
    try {
        const res = await axios.get("/api/config-sedes");
        registros.value = res?.data?.data || res?.data || [];
        if (Array.isArray(registros.value) && registros.value.length > 0 && !nomSucursal.value) {
            const sedeGuardadaId = sessionStorage.getItem("ST-sede");
            const sedeEncontrada = sedeGuardadaId
                ? registros.value.find((s) => String(s.id) === String(sedeGuardadaId))
                : null;
            const sedeActiva = sedeEncontrada || registros.value[0];

            nomSucursal.value = sedeActiva.sucursal || "";
            sucursalActual.value = sedeActiva.id;
            sessionStorage.setItem("ST-sede", sedeActiva.id);
            emitter.emit("sedeSeleccionada", { idSede: sedeActiva.id });
        }
    } catch {
        registros.value = [];
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar las sedes disponibles.",
            icon: "error",
        });
    }
}
/**
 * Establece la sede seleccionada como actual.
 * Actualiza sessionStorage y emite el evento sedeSeleccionada.
 *
 * @param {Object} data - Objeto con id y sucursal de la sede seleccionada
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2024-04-04
 */
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
