<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Helpdesk</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="/helpdesk-tickets">Tickets</a></li>
                    <li class="breadcrumb-item active">Crear</li>
                </ol>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Crear ticket</h4>
                        <h6 class="card-subtitle text-info">
                            La sede se toma automáticamente de la sede activa del usuario.
                        </h6>

                        <form @submit.prevent="guardar()">
                            <div class="form-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="crearTitulo">Título</label>
                                            <input
                                                id="crearTitulo"
                                                ref="primerCampo"
                                                v-model="form.titulo"
                                                type="text"
                                                class="form-control"
                                                maxlength="255"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="crearCategoria">Categoría</label>
                                            <select
                                                id="crearCategoria"
                                                v-model="form.categoria_id"
                                                class="form-control custom-select"
                                            >
                                                <option value="">Seleccione...</option>
                                                <option
                                                    v-for="c in categorias"
                                                    :key="c.id"
                                                    :value="c.id"
                                                >
                                                    {{ c.nombre }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="crearPrioridad">Prioridad</label>
                                            <select
                                                id="crearPrioridad"
                                                v-model="form.prioridad_id"
                                                class="form-control custom-select"
                                            >
                                                <option value="">Seleccione...</option>
                                                <option
                                                    v-for="p in prioridades"
                                                    :key="p.id"
                                                    :value="p.id"
                                                >
                                                    {{ p.nombre }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="crearAsignado">Asignar a</label>
                                            <select
                                                id="crearAsignado"
                                                v-model="form.asignado_id"
                                                class="form-control custom-select"
                                            >
                                                <option value="">Seleccione...</option>
                                                <option
                                                    v-for="u in asignables"
                                                    :key="u.id"
                                                    :value="u.id"
                                                >
                                                    {{ u.name }} ({{ (u.roles || []).join(", ") }})
                                                    - {{ u.email }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="crearAdjuntos">Adjuntos (opcional)</label>
                                            <input
                                                id="crearAdjuntos"
                                                ref="files"
                                                type="file"
                                                class="form-control"
                                                multiple
                                                accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx"
                                            />
                                            <small class="text-muted">Máx 10MB por archivo.</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="crearDescripcion">Descripción</label>
                                            <WysiwygEditor v-model="form.descripcion" :rows="6" />
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <button
                                        type="submit"
                                        class="btn btn-success"
                                        :disabled="loading"
                                    >
                                        <i v-if="loading" class="fa fa-spinner fa-spin"></i>
                                        <i v-else class="fa fa-check"></i>
                                        Guardar
                                    </button>
                                    <a
                                        href="/helpdesk-tickets"
                                        class="btn btn-inverse m-l-5"
                                        :class="{ disabled: loading }"
                                        tabindex="0"
                                    >
                                        Cancelar
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { nextTick, onMounted, ref } from "vue";

import { toastMessage } from "@/helpers/alerts.js";
import { isHtmlEffectivelyEmpty } from "@/helpers/wysiwyg.js";
import WysiwygEditor from "@/components/shared/WysiwygEditor.vue";
import { helpdesk } from "@/helpers/api.js";

const loading = ref(false);
const categorias = ref([]);
const prioridades = ref([]);
const asignables = ref([]);
const files = ref(null);
const primerCampo = ref(null);
const form = ref({
    titulo: "",
    descripcion: "",
    categoria_id: "",
    prioridad_id: "",
    asignado_id: "",
    sede_id: "",
});

async function cargar() {
    try {
        const [cats, prios, asignablesRes] = await Promise.all([
            helpdesk.categorias(),
            helpdesk.prioridades(),
            helpdesk.asignables(),
        ]);
        categorias.value = (cats.data || []).filter((x) => x.activo);
        prioridades.value = (prios.data || []).filter((x) => x.activo);
        asignables.value = asignablesRes.data || [];
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No se pudieron cargar los catálogos.",
            icon: "error",
        });
    }
}

async function guardar() {
    if (!form.value.sede_id) {
        toastMessage({
            heading: "Upss!!!",
            text: "No hay sede activa seleccionada.",
            icon: "warning",
        });
        return;
    }
    if (!String(form.value.titulo || "").trim()) {
        toastMessage({
            heading: "Upss!!!",
            text: "El título es obligatorio.",
            icon: "warning",
        });
        nextTick(() => primerCampo.value?.focus());
        return;
    }
    if (!form.value.categoria_id) {
        toastMessage({
            heading: "Upss!!!",
            text: "La categoría es obligatoria.",
            icon: "warning",
        });
        nextTick(() => document.getElementById("crearCategoria")?.focus());
        return;
    }
    if (!form.value.prioridad_id) {
        toastMessage({
            heading: "Upss!!!",
            text: "La prioridad es obligatoria.",
            icon: "warning",
        });
        nextTick(() => document.getElementById("crearPrioridad")?.focus());
        return;
    }
    if (!form.value.asignado_id) {
        toastMessage({
            heading: "Upss!!!",
            text: "Debes seleccionar a quién asignar.",
            icon: "warning",
        });
        nextTick(() => document.getElementById("crearAsignado")?.focus());
        return;
    }
    if (isHtmlEffectivelyEmpty(form.value.descripcion)) {
        toastMessage({
            heading: "Upss!!!",
            text: "La descripción es obligatoria.",
            icon: "warning",
        });
        nextTick(() => document.getElementById("crearDescripcion")?.focus());
        return;
    }

    loading.value = true;
    try {
        const res = await helpdesk.crearTicket({
            titulo: form.value.titulo,
            descripcion: form.value.descripcion,
            categoria_id: form.value.categoria_id,
            prioridad_id: form.value.prioridad_id,
            sede_id: form.value.sede_id,
            asignado_id: form.value.asignado_id,
        });

        const ticket = res.data.data;

        // Adjuntos iniciales (si hay)
        const input = files.value;
        const rawFiles = input && input.files ? Array.from(input.files) : [];
        if (rawFiles.length) {
            const fd = new FormData();
            rawFiles.forEach((f) => fd.append("files[]", f));
            fd.append("comentario", "Adjuntos iniciales.");
            await helpdesk.subirAdjuntos(ticket.id, fd);
        }

        toastMessage({
            heading: "Listo",
            text: `Se creó el ticket ${ticket.folio || "#" + ticket.id}.`,
            icon: "success",
        });
        window.location.href = `/helpdesk-tickets/${ticket.id}`;
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No se pudo crear el ticket.",
            icon: "error",
        });
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    try {
        const sedeActiva = sessionStorage.getItem("ST-sede");
        if (sedeActiva) form.value.sede_id = sedeActiva;
    } catch (e) {
        console.warn("No se pudo leer sede activa de sessionStorage", e);
    }

    await cargar();
});
</script>
