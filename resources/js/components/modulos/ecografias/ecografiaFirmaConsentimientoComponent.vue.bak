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

<script>
import DOMPurify from "dompurify";

export default {
    props: {
        token: { type: String, required: true },
    },
    data() {
        return {
            loading: true,
            saving: false,
            error: null,
            okMsg: "",
            html: "",
            info: null,
            canvasW: 900,
            canvasH: 220,
            drawing: false,
            last: null,
            hasInk: false,
            timer: null,
            nowTs: Date.now(),
        };
    },
    computed: {
        pacienteLabel() {
            const p = this.info?.paciente;
            const prod = this.info?.producto;
            const nom = p?.nombres || "Paciente";
            const doc = p?.num_docu ? ` (${p.num_docu})` : "";
            const ex = prod?.nombre ? ` · ${prod.nombre}` : "";
            return `${nom}${doc}${ex}`;
        },
        tiempoRestanteLabel() {
            const exp = this.info?.expires_at;
            if (!exp) return "";
            const expTs = new Date(exp).getTime();
            const diff = expTs - this.nowTs;
            if (!Number.isFinite(diff) || diff <= 0) return "Enlace expirado";
            const sec = Math.floor(diff / 1000);
            const mm = String(Math.floor(sec / 60)).padStart(2, "0");
            const ss = String(sec % 60).padStart(2, "0");
            return `Tiempo restante: ${mm}:${ss}`;
        },
    },
    mounted() {
        this.fetchInfo();
        this.initCanvas();
        this.timer = setInterval(() => (this.nowTs = Date.now()), 1000);
        window.addEventListener("resize", this.resizeCanvas);
    },
    beforeUnmount() {
        if (this.timer) clearInterval(this.timer);
        window.removeEventListener("resize", this.resizeCanvas);
    },
    methods: {
        async fetchInfo() {
            this.loading = true;
            this.error = null;
            try {
                const res = await axios.get(`/firma/consentimiento-ecografia/${this.token}/info`);
                this.info = res.data;
                this.html = DOMPurify.sanitize(res.data?.html || "");
            } catch (e) {
                this.error =
                    e?.response?.data?.message || "No fue posible cargar el consentimiento.";
            } finally {
                this.loading = false;
            }
        },
        initCanvas() {
            this.$nextTick(() => {
                const c = this.$refs.canvas;
                if (!c) return;
                const ctx = c.getContext("2d");
                ctx.lineWidth = 2.5;
                ctx.lineCap = "round";
                ctx.strokeStyle = "#111";
                this.clear();

                c.addEventListener("pointerdown", this.onDown);
                c.addEventListener("pointermove", this.onMove);
                c.addEventListener("pointerup", this.onUp);
                c.addEventListener("pointercancel", this.onUp);
                this.resizeCanvas();
            });
        },
        resizeCanvas() {
            const c = this.$refs.canvas;
            if (!c) return;
            const parent = c.parentElement;
            if (!parent) return;
            // Mantener firma, pero por simplicidad reiniciamos al cambiar tamaño
            const w = Math.max(300, Math.floor(parent.clientWidth));
            c.width = w * 2; // más resolución
            c.height = this.canvasH;
            const ctx = c.getContext("2d");
            ctx.lineWidth = 2.5;
            ctx.lineCap = "round";
            ctx.strokeStyle = "#111";
            this.hasInk = false;
            ctx.clearRect(0, 0, c.width, c.height);
        },
        relPos(e) {
            const c = this.$refs.canvas;
            const rect = c.getBoundingClientRect();
            const x = (e.clientX - rect.left) * (c.width / rect.width);
            const y = (e.clientY - rect.top) * (c.height / rect.height);
            return { x, y };
        },
        onDown(e) {
            if (this.saving) return;
            this.drawing = true;
            this.last = this.relPos(e);
            e.preventDefault();
        },
        onMove(e) {
            if (!this.drawing || this.saving) return;
            const c = this.$refs.canvas;
            const ctx = c.getContext("2d");
            const p = this.relPos(e);
            ctx.beginPath();
            ctx.moveTo(this.last.x, this.last.y);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
            this.last = p;
            this.hasInk = true;
            e.preventDefault();
        },
        onUp(e) {
            this.drawing = false;
            this.last = null;
            e.preventDefault();
        },
        clear() {
            const c = this.$refs.canvas;
            if (!c) return;
            const ctx = c.getContext("2d");
            ctx.clearRect(0, 0, c.width, c.height);
            this.hasInk = false;
        },
        async submit() {
            if (!this.hasInk || this.saving) return;
            this.saving = true;
            this.error = null;
            this.okMsg = "";
            try {
                const c = this.$refs.canvas;
                const dataUrl = c.toDataURL("image/png");
                const res = await axios.post(
                    `/firma/consentimiento-ecografia/${this.token}/firmar`,
                    {
                        firma_png: dataUrl,
                    }
                );
                this.okMsg = res?.data?.message || "Firma registrada correctamente.";
            } catch (e) {
                this.error = e?.response?.data?.message || "No fue posible registrar la firma.";
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>
