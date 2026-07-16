<template>
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Consentimiento informado</h4>

            <div v-if="loading" class="text-muted">Cargando…</div>

            <div v-else>
                <div v-if="error" class="alert alert-danger">
                    {{ error }}
                </div>

                <template v-else>
                    <div class="mb-2">
                        <small class="text-muted">
                            {{ pacienteLabel }}
                            <span v-if="tiempoRestanteLabel"> · {{ tiempoRestanteLabel }}</span>
                        </small>
                    </div>

                    <div class="border p-2 mb-3" style="max-height: 45vh; overflow: auto">
                        <div v-html="html"></div>
                    </div>

                    <div class="mb-2">
                        <strong>Firma</strong>
                    </div>

                    <div class="border" style="touch-action: none">
                        <canvas
                            ref="canvas"
                            :width="canvasW"
                            :height="canvasH"
                            class="w-100"
                        ></canvas>
                    </div>

                    <div class="mt-2 d-flex gap-2">
                        <button
                            class="btn btn-secondary"
                            type="button"
                            @click="clear"
                            :disabled="saving"
                        >
                            Limpiar
                        </button>
                        <button
                            class="btn btn-success"
                            type="button"
                            @click="submit"
                            :disabled="saving || !hasInk"
                        >
                            <span v-if="saving">Guardando…</span>
                            <span v-else>Firmar</span>
                        </button>
                    </div>

                    <div v-if="okMsg" class="alert alert-success mt-3">{{ okMsg }}</div>
                </template>
            </div>
        </div>
    </div>
</template>
<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";

import DOMPurify from "dompurify";
import axios from "axios";

const props = defineProps({
    token: { type: String, required: true },
});

const loading = ref(true);
const saving = ref(false);
const error = ref(null);
const okMsg = ref("");
const html = ref("");
const info = ref(null);
const canvasW = ref(900);
const canvasH = ref(220);
const drawing = ref(false);
const last = ref(null);
const hasInk = ref(false);
const timer = ref(null);
const nowTs = ref(Date.now());

const pacienteLabel = computed(() => {
    const p = info.value?.paciente;
    const prod = info.value?.producto;
    const nom = p?.nombres || "Paciente";
    const doc = p?.num_docu ? ` (${p.num_docu})` : "";
    const ex = prod?.nombre ? ` · ${prod.nombre}` : "";
    return `${nom}${doc}${ex}`;
});
const tiempoRestanteLabel = computed(() => {
    const exp = info.value?.expires_at;
    if (!exp) return "";
    const expTs = new Date(exp).getTime();
    const diff = expTs - nowTs.value;
    if (!Number.isFinite(diff) || diff <= 0) return "Enlace expirado";
    const sec = Math.floor(diff / 1000);
    const mm = String(Math.floor(sec / 60)).padStart(2, "0");
    const ss = String(sec % 60).padStart(2, "0");
    return `Tiempo restante: ${mm}:${ss}`;
});

async function fetchInfo() {
    loading.value = true;
    error.value = null;
    try {
        const res = await axios.get(`/firma/consentimiento-ecografia/${token}/info`);
        info.value = res.data.data;
        html.value = DOMPurify.sanitize(res.data?.data?.html || "");
    } catch (e) {
        error.value = e?.response?.data?.message || "No fue posible cargar el consentimiento.";
    } finally {
        loading.value = false;
    }
}
function initCanvas() {
    nextTick(() => {
        const c = canvas.value;
        if (!c) return;
        const ctx = c.getContext("2d");
        ctx.lineWidth = 2.5;
        ctx.lineCap = "round";
        ctx.strokeStyle = "#111";
        clear();

        c.addEventListener("pointerdown", onDown);
        c.addEventListener("pointermove", onMove);
        c.addEventListener("pointerup", onUp);
        c.addEventListener("pointercancel", onUp);
        resizeCanvas();
    });
}
function resizeCanvas() {
    const c = canvas.value;
    if (!c) return;
    const parent = c.parentElement;
    if (!parent) return;
    // Mantener firma, pero por simplicidad reiniciamos al cambiar tamaño
    const w = Math.max(300, Math.floor(parent.clientWidth));
    c.width = w * 2; // más resolución
    c.height = canvasH.value;
    const ctx = c.getContext("2d");
    ctx.lineWidth = 2.5;
    ctx.lineCap = "round";
    ctx.strokeStyle = "#111";
    hasInk.value = false;
    ctx.clearRect(0, 0, c.width, c.height);
}
function relPos(e) {
    const c = canvas.value;
    const rect = c.getBoundingClientRect();
    const x = (e.clientX - rect.left) * (c.width / rect.width);
    const y = (e.clientY - rect.top) * (c.height / rect.height);
    return { x, y };
}
function onDown(e) {
    if (saving.value) return;
    drawing.value = true;
    last.value = relPos(e);
    e.preventDefault();
}
function onMove(e) {
    if (!drawing.value || saving.value) return;
    const c = canvas.value;
    const ctx = c.getContext("2d");
    const p = relPos(e);
    ctx.beginPath();
    ctx.moveTo(last.value.x, last.value.y);
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
    last.value = p;
    hasInk.value = true;
    e.preventDefault();
}
function onUp(e) {
    drawing.value = false;
    last.value = null;
    e.preventDefault();
}
function clear() {
    const c = canvas.value;
    if (!c) return;
    const ctx = c.getContext("2d");
    ctx.clearRect(0, 0, c.width, c.height);
    hasInk.value = false;
}
async function submit() {
    if (!hasInk.value || saving.value) return;
    saving.value = true;
    error.value = null;
    okMsg.value = "";
    try {
        const c = canvas.value;
        const dataUrl = c.toDataURL("image/png");
        const res = await axios.post(`/firma/consentimiento-ecografia/${token}/firmar`, {
            firma_png: dataUrl,
        });
        okMsg.value = res?.data?.message || "Firma registrada correctamente.";
    } catch (e) {
        error.value = e?.response?.data?.message || "No fue posible registrar la firma.";
    } finally {
        saving.value = false;
    }
}

onMounted(() => {
    fetchInfo();
    initCanvas();
    timer.value = setInterval(() => (nowTs.value = Date.now()), 1000);
    window.addEventListener("resize", resizeCanvas);
});

onBeforeUnmount(() => {
    if (timer.value) clearInterval(timer.value);
    window.removeEventListener("resize", resizeCanvas);
});
</script>
