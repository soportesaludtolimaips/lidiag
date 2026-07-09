<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Helpdesk</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Inicio</a></li>
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
                                            <textarea
                                                id="crearDescripcion"
                                                v-model="form.descripcion"
                                                class="form-control textarea_editor"
                                                rows="6"
                                            ></textarea>
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

<script>
import { toastMessage, handleAxiosErrorToast } from "@/helpers/alerts.js";
import { initWysiwyg, getWysiwygValue, isHtmlEffectivelyEmpty } from "@/helpers/wysiwyg.js";
import { helpdesk } from "@/helpers/api.js";

export default {
    beforeUnmount() {
        try {
            const $root = $(this.$el);
            $root.find(".textarea_editor").each((_, el) => {
                const $el = $(el);
                const inst = $el.data("wysihtml5");
                if (inst && inst.editor) inst.editor.destroy();
            });
        } catch (_) {}
    },
    data() {
        return {
            loading: false,
            categorias: [],
            prioridades: [],
            asignables: [],
            form: {
                titulo: "",
                descripcion: "",
                categoria_id: "",
                prioridad_id: "",
                asignado_id: "",
                sede_id: "",
            },
        };
    },
    async mounted() {
        this.$nextTick(() => this.initWysiwyg());

        try {
            const sedeActiva = sessionStorage.getItem("ST-sede");
            if (sedeActiva) this.form.sede_id = sedeActiva;
        } catch (e) {
            console.warn("No se pudo leer sede activa de sessionStorage", e);
        }

        await this.cargar();
    },
    methods: {
        initWysiwyg() {
            initWysiwyg(this.$el);
        },
        getDescripcionFromEditor() {
            return getWysiwygValue(this.$el) || this.form.descripcion || "";
        },
        isHtmlEffectivelyEmpty(html) {
            return isHtmlEffectivelyEmpty(html);
        },
        async cargar() {
            try {
                const [cats, prios, asignables] = await Promise.all([
                    helpdesk.categorias(),
                    helpdesk.prioridades(),
                    helpdesk.asignables(),
                ]);
                this.categorias = (cats.data || []).filter((x) => x.activo);
                this.prioridades = (prios.data || []).filter((x) => x.activo);
                this.asignables = asignables.data || [];
            } catch (error) {
                handleAxiosErrorToast(error, "No se pudieron cargar datos para el formulario.");
            }
        },
        async guardar() {
            if (!this.form.sede_id) {
                toastMessage({
                    heading: "Upss!!!",
                    text: "No hay sede activa seleccionada.",
                    icon: "warning",
                });
                return;
            }
            if (!String(this.form.titulo || "").trim()) {
                toastMessage({
                    heading: "Upss!!!",
                    text: "El título es obligatorio.",
                    icon: "warning",
                });
                this.$nextTick(() => this.$refs.primerCampo?.focus());
                return;
            }
            if (!this.form.categoria_id) {
                toastMessage({
                    heading: "Upss!!!",
                    text: "La categoría es obligatoria.",
                    icon: "warning",
                });
                this.$nextTick(() => document.getElementById("crearCategoria")?.focus());
                return;
            }
            if (!this.form.prioridad_id) {
                toastMessage({
                    heading: "Upss!!!",
                    text: "La prioridad es obligatoria.",
                    icon: "warning",
                });
                this.$nextTick(() => document.getElementById("crearPrioridad")?.focus());
                return;
            }
            if (!this.form.asignado_id) {
                toastMessage({
                    heading: "Upss!!!",
                    text: "Debes seleccionar a quién asignar.",
                    icon: "warning",
                });
                this.$nextTick(() => document.getElementById("crearAsignado")?.focus());
                return;
            }
            // Importante: WYSIHTML5 usa iframe; sincronizamos el valor real antes de validar.
            this.form.descripcion = this.getDescripcionFromEditor();
            if (this.isHtmlEffectivelyEmpty(this.form.descripcion)) {
                toastMessage({
                    heading: "Upss!!!",
                    text: "La descripción es obligatoria.",
                    icon: "warning",
                });
                this.$nextTick(() => document.getElementById("crearDescripcion")?.focus());
                return;
            }

            this.loading = true;
            try {
                const res = await helpdesk.crearTicket({
                    titulo: this.form.titulo,
                    descripcion: this.form.descripcion,
                    categoria_id: this.form.categoria_id,
                    prioridad_id: this.form.prioridad_id,
                    sede_id: this.form.sede_id,
                    asignado_id: this.form.asignado_id,
                });

                const ticket = res.data;

                // Adjuntos iniciales (si hay)
                const input = this.$refs.files;
                const files = input && input.files ? Array.from(input.files) : [];
                if (files.length) {
                    const fd = new FormData();
                    files.forEach((f) => fd.append("files[]", f));
                    fd.append("comentario", "Adjuntos iniciales.");
                    await helpdesk.subirAdjuntos(ticket.id, fd);
                }

                toastMessage({
                    heading: "Ok",
                    text: `Se creó el ticket ${ticket.folio || "#" + ticket.id}.`,
                    icon: "success",
                });
                window.location.href = `/helpdesk-tickets/${ticket.id}`;
            } catch (error) {
                handleAxiosErrorToast(error, "No se pudo crear el ticket.");
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
