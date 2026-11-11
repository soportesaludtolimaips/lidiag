<template>
    <footer class="footer">
        © 2017 Adminwrap by wrappixel.com

        <div class="float-right">
            <h4 class="text-info">Usted esta en la sede: {{ nomSede }}</h4>
            <h4 class="text-info" v-if="mostrarMedico">
                {{
                    nomMedicoEcografia
                        ? "Médico ecografía: " + nomMedicoEcografia
                        : "No ha seleccionado médico"
                }}
            </h4>
        </div>
    </footer>
</template>
<script setup>
import { getCurrentInstance, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";

const { proxy } = getCurrentInstance();
const emitter = proxy.emitter;
const route = useRoute();

function obtenerNomSede() {
    return sessionStorage.getItem("ST-nomsede") || localStorage.getItem("ST-nomsede") || "";
}

const nomSede = ref(obtenerNomSede());
const nomMedicoEcografia = ref(localStorage.getItem("ST-ecografia-nomMedico"));
const mostrarMedico = ref(false);

const rutasEcografias = [
    "/ecografias.admisiones",
    "/ecografias.admisionadas",
    "/ecografias-pendientes-lecturas",
    "/ecografias-consentimientos",
    "/ecografias-consentimientos-plantillas",
];

/**
 * Actualiza el nombre de la sede desde storage.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2024-04-04
 */
function establecerSede() {
    nomSede.value = obtenerNomSede();
}
/**
 * Actualiza el nombre del médico de ecografía desde localStorage.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2024-04-04
 */
function establecerMedico() {
    nomMedicoEcografia.value = localStorage.getItem("ST-ecografia-nomMedico");
}

function onSedeSeleccionada(data) {
    const rawSede = data?.sede ?? (typeof data === "object" ? data.idSede || data.id : data);
    const nomSedeVal =
        data?.nomSede ??
        sessionStorage.getItem("ST-nomsede") ??
        localStorage.getItem("ST-nomsede") ??
        "";

    if (rawSede) {
        sessionStorage.setItem("ST-sede", String(rawSede));
        localStorage.setItem("ST-sede", String(rawSede));
    }
    if (nomSedeVal) {
        sessionStorage.setItem("ST-nomsede", nomSedeVal);
        localStorage.setItem("ST-nomsede", nomSedeVal);
        nomSede.value = nomSedeVal;
    }
}

function onMedicoSeleccionado(data) {
    if (data?.idMedico) localStorage.setItem("ST-ecografia-medico", data.idMedico);
    if (data?.nomMedico) {
        localStorage.setItem("ST-ecografia-nomMedico", data.nomMedico);
        nomMedicoEcografia.value = data.nomMedico;
    }
}

watch(
    () => route?.path,
    (path) => {
        mostrarMedico.value = rutasEcografias.includes(path || window.location.pathname);
    },
    { immediate: true }
);

/**
 * Lifecycle hook que inicializa sede y médico, y escucha eventos del emitter
 * para actualizar la interfaz cuando se selecciona una sede o médico.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2024-04-04
 */
onMounted(() => {
    establecerSede();
    establecerMedico();

    if (!nomSede.value && emitter) {
        emitter.emit("solicitarSedeActiva");
    }

    if (emitter) {
        emitter.on("sedeSeleccionada", onSedeSeleccionada);
        emitter.on("medicoSeleccionado", onMedicoSeleccionado);
    }
});

onBeforeUnmount(() => {
    if (emitter) {
        emitter.off("sedeSeleccionada", onSedeSeleccionada);
        emitter.off("medicoSeleccionado", onMedicoSeleccionado);
    }
});
</script>
