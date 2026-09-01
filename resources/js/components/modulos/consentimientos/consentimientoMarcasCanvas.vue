<template>
    <div v-if="imagenes.length">
        <hr />
        <h6>Marcar zonas en imágenes</h6>

        <div class="mb-2 d-flex align-items-center gap-2">
            <label class="form-label mb-0">Tipo de marca:</label>
            <select v-model="tipo" class="form-select form-select-sm" style="width: auto">
                <option value="punto">Punto</option>
                <option value="circulo">Círculo</option>
                <option value="flecha">Flecha</option>
            </select>
            <small class="text-muted">Haz clic sobre la imagen para marcar.</small>
        </div>

        <div v-for="img in imagenes" :key="img.id" class="mb-3">
            <div class="fw-bold small mb-1">{{ img.nombre }}</div>
            <div class="position-relative d-inline-block border" @click="marcar(img.id, $event)">
                <img
                    :src="verImagenUrl(img.id)"
                    :alt="img.nombre"
                    style="max-width: 100%; display: block"
                />
                <span
                    v-for="m in marcasDe(img.id)"
                    :key="m.id"
                    class="marca"
                    :class="'marca-' + m.tipo_marca"
                    :style="{ left: m.posicion_x + '%', top: m.posicion_y + '%' }"
                    :title="m.tipo_marca"
                    @click.stop="eliminar(m.id)"
                    >{{ simbolo(m.tipo_marca) }}</span
                >
            </div>
        </div>

        <p class="text-muted small mb-0">Clic en una marca para eliminarla.</p>
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";

import { consentimientos as api } from "../../../helpers/api.js";
import { alertSuccess, handleAxiosErrorToast } from "../../../helpers/alerts.js";

const props = defineProps({
    documentoId: { type: [Number, String], required: true },
});

const imagenes = ref([]);
const marcas = ref([]);
const tipo = ref("punto");
const verImagenUrl = api.verImagenUrl;

function simbolo(t) {
    return t === "flecha" ? "➤" : t === "circulo" ? "◯" : "●";
}

function marcasDe(imagenId) {
    return marcas.value.filter((m) => Number(m.imagen_id) === Number(imagenId));
}

async function cargar() {
    try {
        const doc = await api.ver(props.documentoId);
        const plantillaId = doc.data?.data?.plantilla_id;
        if (!plantillaId) return;

        const imgs = await api.imagenes(plantillaId);
        imagenes.value = (imgs.data?.data ?? []).filter((i) => i.es_para_marcar);

        await cargarMarcas();
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible cargar imágenes marcables.");
    }
}

async function cargarMarcas() {
    try {
        const res = await api.marcas(props.documentoId);
        marcas.value = res.data?.data ?? [];
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible cargar marcas.");
    }
}

async function marcar(imagenId, event) {
    const rect = event.currentTarget.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 100;
    const y = ((event.clientY - rect.top) / rect.height) * 100;
    try {
        await api.crearMarca(props.documentoId, {
            imagen_id: imagenId,
            tipo_marca: tipo.value,
            posicion_x: Math.round(x * 100) / 100,
            posicion_y: Math.round(y * 100) / 100,
        });
        await cargarMarcas();
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible agregar la marca.");
    }
}

async function eliminar(id) {
    try {
        await api.eliminarMarca(id);
        await cargarMarcas();
        alertSuccess("Marca eliminada");
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible eliminar la marca.");
    }
}

onMounted(cargar);
</script>

<style scoped>
.marca {
    position: absolute;
    transform: translate(-50%, -50%);
    cursor: pointer;
    font-weight: bold;
    color: #d00;
    text-shadow: 0 0 2px #fff;
    user-select: none;
}
.marca-circulo {
    font-size: 22px;
}
.marca-flecha {
    font-size: 20px;
}
.marca-punto {
    font-size: 16px;
}
</style>
