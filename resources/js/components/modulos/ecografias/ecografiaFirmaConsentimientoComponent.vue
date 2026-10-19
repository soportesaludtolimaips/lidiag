<template>
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="card-title mb-0">
                    <i class="fa fa-file-text-o text-primary mr-1"></i>
                    Consentimiento informado
                </h4>
                <div v-if="tiempoRestanteLabel && !okMsg" class="d-flex align-items-center">
                    <span
                        class="badge"
                        :class="
                            esTiempoCritico
                                ? 'badge-danger animate-pulse'
                                : 'badge-light border text-dark'
                        "
                        title="Tiempo restante para firmar este consentimiento"
                    >
                        <i class="fa fa-clock-o mr-1"></i>
                        {{ tiempoRestanteLabel }}
                    </span>
                </div>
            </div>

            <div v-if="loading" class="text-center py-5 text-muted">
                <i class="fa fa-circle-o-notch fa-spin fa-2x text-primary mb-2"></i>
                <div>Cargando consentimiento informado...</div>
            </div>

            <div v-else>
                <div v-if="error" class="alert alert-danger d-flex align-items-center">
                    <i class="fa fa-exclamation-triangle fa-2x mr-3"></i>
                    <div>{{ error }}</div>
                </div>

                <div v-else-if="okMsg" class="text-center py-5">
                    <div class="mb-3">
                        <i class="fa fa-check-circle text-success" style="font-size: 4rem"></i>
                    </div>
                    <h3 class="font-weight-bold text-success mb-2">¡Consentimiento Firmado!</h3>
                    <p class="text-muted mb-4">{{ okMsg }}</p>
                    <div class="alert alert-info d-inline-block text-left py-2 px-3">
                        <small>
                            <i class="fa fa-info-circle mr-1"></i>
                            El documento ha sido archivado electrónicamente con la firma capturada.
                            Ya puede cerrar esta ventana.
                        </small>
                    </div>
                </div>

                <template v-else>
                    <div
                        class="alert alert-light border py-2 mb-3 d-flex align-items-center justify-content-between"
                    >
                        <div>
                            <span class="font-weight-bold text-dark">
                                <i class="fa fa-user-circle mr-1"></i>
                                {{ pacienteLabel }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="border rounded p-3 mb-3 bg-white clinical-doc-box"
                        style="max-height: 45vh; overflow-y: auto"
                    >
                        <!-- eslint-disable-next-line vue/no-v-html -- html sanitizado con DOMPurify al cargar -->
                        <div v-html="html"></div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="font-weight-bold mb-0">
                            <i class="fa fa-pencil mr-1"></i>
                            Firma del Paciente / Representante
                        </label>
                        <span v-if="hasInk" class="badge badge-success">
                            <i class="fa fa-check mr-1"></i>Trazado capturado
                        </span>
                        <span v-else class="text-muted small">
                            Firme dentro del recuadro inferior
                        </span>
                    </div>

                    <div class="signature-canvas-container border rounded position-relative">
                        <div v-if="!hasInk" class="signature-watermark">
                            <i class="fa fa-pencil-square-o mr-1"></i> Firme sobre la línea
                        </div>
                        <canvas
                            ref="canvas"
                            :width="canvasW"
                            :height="canvasH"
                            class="w-100"
                        ></canvas>
                        <div class="signature-baseline"></div>
                    </div>

                    <div
                        class="mt-3 d-flex flex-wrap gap-2 justify-content-between align-items-center"
                    >
                        <button
                            class="btn btn-outline-secondary btn-action-clinical"
                            type="button"
                            @click="clear"
                            :disabled="saving || !hasInk"
                            title="Limpiar trazo (Esc)"
                        >
                            <i class="fa fa-eraser mr-1"></i>
                            Limpiar
                            <span class="badge badge-light border ml-1">Esc</span>
                        </button>
                        <button
                            class="btn btn-success btn-action-clinical"
                            type="button"
                            @click="submit"
                            :disabled="saving || !hasInk"
                            title="Confirmar y registrar firma (Ctrl+S)"
                        >
                            <i
                                class="fa mr-1"
                                :class="saving ? 'fa-spinner fa-spin' : 'fa-check'"
                            ></i>
                            <span v-if="saving">Registrando firma...</span>
                            <span v-else>Confirmar Firma</span>
                            <span class="badge badge-light border ml-1">Ctrl+S</span>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>
<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";

import DOMPurify from "dompurify";
import axios from "axios";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts.js";

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
const canvas = ref(null);
const drawing = ref(false);
const last = ref(null);
const hasInk = ref(false);
const timer = ref(null);
const nowTs = ref(Date.now());

/**
 * Construye la etiqueta descriptiva del paciente combinando nombres, número de documento y nombre del producto/examen.
 *
 * @returns {string}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
const pacienteLabel = computed(() => {
    const p = info.value?.paciente;
    const prod = info.value?.producto;
    const nom = p?.nombres || "Paciente";
    const doc = p?.num_docu ? ` (${p.num_docu})` : "";
    const ex = prod?.nombre ? ` · ${prod.nombre}` : "";
    return `${nom}${doc}${ex}`;
});

/**
 * Determina si el tiempo restante antes de la expiración es crítico (menor a 5 minutos).
 *
 * @returns {boolean}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-19
 */
const esTiempoCritico = computed(() => {
    const exp = info.value?.expires_at;
    if (!exp) return false;
    const expTs = new Date(exp).getTime();
    const diff = expTs - nowTs.value;
    return Number.isFinite(diff) && diff > 0 && diff <= 300000;
});

/**
 * Calcula el tiempo restante antes de que expire el enlace del consentimiento y lo formatea como mm:ss.
 *
 * @returns {string}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
const tiempoRestanteLabel = computed(() => {
    const exp = info.value?.expires_at;
    if (!exp) return "";
    const expTs = new Date(exp).getTime();
    const diff = expTs - nowTs.value;
    if (!Number.isFinite(diff) || diff <= 0) return "Enlace expirado";
    const sec = Math.floor(diff / 1000);
    const mm = String(Math.floor(sec / 60)).padStart(2, "0");
    const ss = String(sec % 60).padStart(2, "0");
    return `${mm}:${ss}`;
});

/**
 * Obtiene desde el backend la información y el HTML del consentimiento de ecografía asociado al token.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
async function fetchInfo() {
    loading.value = true;
    error.value = null;
    try {
        const res = await axios.get(`/firma/consentimiento-ecografia/${props.token}/info`);
        info.value = res.data.data;
        html.value = DOMPurify.sanitize(res.data?.data?.html || "");
    } catch (err) {
        error.value = err?.response?.data?.message || "No fue posible cargar el consentimiento.";
    } finally {
        loading.value = false;
    }
}

/**
 * Inicializa el contexto del canvas, configura el trazo y registra los eventos de puntero para la firma.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
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

/**
 * Ajusta el ancho del canvas al contenedor padre (duplicando la resolución) y reinicia el trazo.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
function resizeCanvas() {
    const c = canvas.value;
    if (!c) return;
    const parent = c.parentElement;
    if (!parent) return;
    const w = Math.max(300, Math.floor(parent.clientWidth));
    c.width = w * 2;
    c.height = canvasH.value;
    const ctx = c.getContext("2d");
    ctx.lineWidth = 2.5;
    ctx.lineCap = "round";
    ctx.strokeStyle = "#111";
    hasInk.value = false;
    ctx.clearRect(0, 0, c.width, c.height);
}

/**
 * Calcula la posición relativa del puntero dentro del canvas escalando las coordenadas al tamaño real del lienzo.
 *
 * @param {PointerEvent} e Evento de puntero del que se obtienen las coordenadas del cliente.
 *
 * @returns {Object}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
function relPos(e) {
    const c = canvas.value;
    const rect = c.getBoundingClientRect();
    const x = (e.clientX - rect.left) * (c.width / rect.width);
    const y = (e.clientY - rect.top) * (c.height / rect.height);
    return { x, y };
}

/**
 * Maneja el inicio del trazo de firma al presionar el puntero sobre el canvas.
 *
 * @param {PointerEvent} e Evento de puntero que dispara el inicio del dibujo.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
function onDown(e) {
    if (saving.value) return;
    drawing.value = true;
    last.value = relPos(e);
    e.preventDefault();
}

/**
 * Dibuja el trazo de la firma mientras el puntero se desplaza sobre el canvas.
 *
 * @param {PointerEvent} e Evento de puntero que indica la posición actual del movimiento.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
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

/**
 * Finaliza el trazo de firma al soltar o cancelar el puntero sobre el canvas.
 *
 * @param {PointerEvent} e Evento de puntero que indica el fin del dibujo.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
function onUp(e) {
    drawing.value = false;
    last.value = null;
    e.preventDefault();
}

/**
 * Limpia el contenido del canvas y reinicia el indicador de presencia de tinta.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
function clear() {
    const c = canvas.value;
    if (!c) return;
    const ctx = c.getContext("2d");
    ctx.clearRect(0, 0, c.width, c.height);
    hasInk.value = false;
}

/**
 * Envía la firma capturada en el canvas (como imagen PNG) al backend para su registro.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
async function submit() {
    if (!hasInk.value || saving.value) return;
    saving.value = true;
    error.value = null;
    okMsg.value = "";
    try {
        const c = canvas.value;
        const dataUrl = c.toDataURL("image/png");
        const res = await axios.post(`/firma/consentimiento-ecografia/${props.token}/firmar`, {
            firma_png: dataUrl,
        });
        okMsg.value = res?.data?.message || "Firma registrada correctamente.";
    } catch (err) {
        error.value = err?.response?.data?.message || "No fue posible registrar la firma.";
    } finally {
        saving.value = false;
    }
}

useKeyboardShortcuts({
    onSave: () => {
        if (!okMsg.value && hasInk.value && !saving.value) {
            submit();
        }
    },
    onEscape: () => {
        if (!okMsg.value && hasInk.value && !saving.value) {
            clear();
        }
    },
});

/**
 * Hook de ciclo de vida que carga la información, inicializa el canvas y arranca el temporizador de expiración y el listener de redimensionamiento.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-13
 */
onMounted(() => {
    fetchInfo();
    initCanvas();
    timer.value = setInterval(() => (nowTs.value = Date.now()), 1000);
    window.addEventListener("resize", resizeCanvas);
});

onBeforeUnmount(() => {
    if (timer.value) clearInterval(timer.value);
    window.removeEventListener("resize", resizeCanvas);
    const c = canvas.value;
    if (c) {
        c.removeEventListener("pointerdown", onDown);
        c.removeEventListener("pointermove", onMove);
        c.removeEventListener("pointerup", onUp);
        c.removeEventListener("pointercancel", onUp);
    }
});
</script>
<style scoped>
.signature-canvas-container {
    background-color: #ffffff;
    touch-action: none;
    position: relative;
    user-select: none;
    overflow: hidden;
    cursor: crosshair;
}

.signature-watermark {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: #cbd5e1;
    font-size: 1.1rem;
    pointer-events: none;
    user-select: none;
}

.signature-baseline {
    position: absolute;
    bottom: 35px;
    left: 10%;
    right: 10%;
    height: 1px;
    border-bottom: 1px dashed #cbd5e1;
    pointer-events: none;
}

.btn-action-clinical {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease-in-out;
}

.btn-action-clinical:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow:
        0 4px 6px -1px rgba(0, 0, 0, 0.1),
        0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.clinical-doc-box {
    line-height: 1.6;
    font-size: 0.95rem;
}

.animate-pulse {
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
    100% {
        opacity: 1;
    }
}
</style>
