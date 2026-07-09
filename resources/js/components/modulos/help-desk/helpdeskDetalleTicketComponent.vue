<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-7 align-self-center">
                <h3 class="text-themecolor">Helpdesk</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/helpdesk-tickets">Tickets</a></li>
                    <li class="breadcrumb-item active">{{ ticket?.folio || "#" + ticketId }}</li>
                </ol>
            </div>
            <div class="col-md-5 align-self-center text-right">
                <a href="/helpdesk-tickets" class="btn btn-inverse btn-sm" tabindex="0">
                    <i class="fa fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <div class="row" v-if="loading">
            <div class="col-12">
                <div class="card">
                    <div class="card-body"><i class="fa fa-spinner fa-spin"></i> Cargando...</div>
                </div>
            </div>
        </div>

        <div class="row" v-if="ticket && !loading">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Detalle</h4>
                        <p class="m-b-5">
                            <strong>Folio:</strong> {{ ticket.folio || "#" + ticket.id }}
                        </p>
                        <p class="m-b-5"><strong>Título:</strong> {{ ticket.titulo }}</p>
                        <p class="m-b-5"><strong>Estado:</strong> {{ ticket.estado?.nombre }}</p>
                        <p class="m-b-5">
                            <strong>Prioridad:</strong> {{ ticket.prioridad?.nombre }}
                        </p>
                        <p class="m-b-5">
                            <strong>Categoría:</strong> {{ ticket.categoria?.nombre }}
                        </p>
                        <p class="m-b-5">
                            <strong>Pendiente de:</strong>
                            <span
                                class="badge"
                                :class="
                                    ticket.pendiente_de === 'SOPORTE'
                                        ? 'badge-warning'
                                        : 'badge-primary'
                                "
                            >
                                {{ ticket.pendiente_de }}
                            </span>
                        </p>
                        <p class="m-b-5"><strong>Sede:</strong> {{ ticket.sede?.nom_sede }}</p>
                        <p class="m-b-5">
                            <strong>Solicitante:</strong> {{ ticket.solicitante?.name }}
                        </p>
                        <p class="m-b-5"><strong>Asignado:</strong> {{ ticket.asignado?.name }}</p>
                        <p class="m-b-5">
                            <strong>Visto por soporte:</strong>
                            {{
                                ticket.asignado_visto_at
                                    ? formatDateTime(ticket.asignado_visto_at)
                                    : "No"
                            }}
                        </p>
                        <p class="m-b-5">
                            <strong>Creación:</strong> {{ formatDateTime(ticket.created_at) }}
                        </p>
                        <p class="m-b-5">
                            <strong>Actualización:</strong> {{ formatDateTime(ticket.updated_at) }}
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Acciones</h4>

                        <div class="form-group">
                            <label for="detalleEstado">Cambiar estado</label>
                            <select
                                id="detalleEstado"
                                v-model="accion.estado_id"
                                class="form-control custom-select"
                            >
                                <option value="">Seleccione...</option>
                                <option v-for="e in estados" :key="e.id" :value="e.id">
                                    {{ e.nombre }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group" v-if="accion.estado_id">
                            <label for="detalleComentarioEstado">Comentario (opcional)</label>
                            <textarea
                                id="detalleComentarioEstado"
                                v-model="accion.comentario"
                                class="form-control textarea_editor"
                                rows="4"
                            ></textarea>
                        </div>
                        <div class="form-group" v-if="estadoSeleccionadoNombre === 'Resuelto'">
                            <label for="detalleSolucion">Descripción solución (obligatoria)</label>
                            <textarea
                                id="detalleSolucion"
                                v-model="accion.descripcion_solucion"
                                class="form-control textarea_editor"
                                rows="4"
                            ></textarea>
                        </div>
                        <button
                            class="btn btn-info btn-sm"
                            type="button"
                            :disabled="savingEstado"
                            @click="cambiarEstado()"
                        >
                            <i v-if="savingEstado" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-exchange"></i>
                            Aplicar estado
                        </button>

                        <hr />

                        <div class="form-group">
                            <label for="detalleReasignar">Reasignar (solo manager/admin)</label>
                            <select
                                id="detalleReasignar"
                                v-model="reasignacion.asignado_id"
                                class="form-control custom-select"
                            >
                                <option value="">Seleccione...</option>
                                <option v-for="u in asignables" :key="u.id" :value="u.id">
                                    {{ u.name }} - {{ u.email }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="detalleComentarioReasignar">Comentario (obligatorio)</label>
                            <textarea
                                id="detalleComentarioReasignar"
                                v-model="reasignacion.comentario"
                                class="form-control"
                                rows="3"
                            ></textarea>
                        </div>
                        <button
                            class="btn btn-warning btn-sm"
                            type="button"
                            :disabled="savingReasignar"
                            @click="reasignar()"
                        >
                            <i v-if="savingReasignar" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-user"></i>
                            Reasignar
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Descripción</h4>
                        <div v-html="sanitize(ticket.descripcion)"></div>

                        <div v-if="ticket.descripcion_solucion" class="m-t-20">
                            <h5 class="text-success">Solución</h5>
                            <div v-html="sanitize(ticket.descripcion_solucion)"></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Agregar comentario</h4>
                        <textarea
                            id="detalleNuevoComentario"
                            v-model="nuevoComentario"
                            class="form-control textarea_editor"
                            rows="4"
                        ></textarea>
                        <button
                            class="btn btn-success btn-sm m-t-10"
                            type="button"
                            :disabled="savingComentario"
                            @click="comentar()"
                        >
                            <i v-if="savingComentario" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-comment"></i>
                            Enviar
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Adjuntos</h4>
                        <input
                            id="detalleAdjuntos"
                            ref="files"
                            type="file"
                            class="form-control"
                            multiple
                            accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx"
                        />
                        <button
                            class="btn btn-secondary btn-sm m-t-10"
                            type="button"
                            :disabled="savingAdjuntos"
                            @click="subirAdjuntos()"
                        >
                            <i v-if="savingAdjuntos" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-paperclip"></i>
                            Subir
                        </button>
                        <small class="text-muted d-block">Solo descarga (sin vista previa).</small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Historial</h4>
                        <div aria-live="polite" aria-atomic="true">
                            <div
                                v-if="!ticket.historial || !ticket.historial.length"
                                class="text-muted"
                            >
                                Sin historial.
                            </div>
                            <ul v-else class="list-group">
                                <li
                                    v-for="h in ticket.historial"
                                    :key="h.id"
                                    class="list-group-item"
                                >
                                    <div class="d-flex justify-content-between">
                                        <strong>{{ h.tipo }}</strong>
                                        <span class="text-muted">{{
                                            formatDateTime(h.created_at)
                                        }}</span>
                                    </div>
                                    <div class="text-muted">{{ h.user?.name }}</div>
                                    <div
                                        v-if="h.comentario"
                                        class="m-t-5"
                                        v-html="sanitize(h.comentario)"
                                    ></div>

                                    <div v-if="h.adjuntos && h.adjuntos.length" class="m-t-10">
                                        <div v-for="a in h.adjuntos" :key="a.id">
                                            <a
                                                :href="helpdesk.descargarAdjunto(a.id)"
                                                target="_blank"
                                            >
                                                {{ a.original_name }}
                                            </a>
                                            <span class="text-muted">
                                                - {{ formatDateTime(a.created_at) }}</span
                                            >
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import DOMPurify from "dompurify";
import { toastMessage, handleAxiosErrorToast, confirmAction } from "@/helpers/alerts.js";
import { formatDateTime } from "@/helpers/dateUtils.js";
import { initWysiwyg } from "@/helpers/wysiwyg.js";
import { helpdesk } from "@/helpers/api.js";

export default {
    props: ["ticketId"],
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
            savingComentario: false,
            savingEstado: false,
            savingReasignar: false,
            savingAdjuntos: false,
            ticket: null,
            estados: [],
            asignables: [],
            nuevoComentario: "",
            accion: {
                estado_id: "",
                comentario: "",
                descripcion_solucion: "",
            },
            reasignacion: {
                asignado_id: "",
                comentario: "",
            },
        };
    },
    computed: {
        estadoSeleccionadoNombre() {
            const e = this.estados.find((x) => String(x.id) === String(this.accion.estado_id));
            return e ? e.nombre : "";
        },
    },
    async mounted() {
        this.$nextTick(() => this.initWysiwyg());
        await this.cargar();
    },
    methods: {
        sanitize(dirty) {
            return DOMPurify.sanitize(dirty || "");
        },
        initWysiwyg() {
            initWysiwyg(this.$el);
        },
        formatDateTime(dt) {
            return formatDateTime(dt);
        },
        async recargarTicket() {
            try {
                const ticketRes = await helpdesk.ticket(this.ticketId);
                this.ticket = ticketRes.data;
            } catch (error) {
                handleAxiosErrorToast(error, "No se pudo actualizar el ticket.");
            }
        },
        async cargar() {
            this.loading = true;
            try {
                const ticketRes = await helpdesk.ticket(this.ticketId);
                this.ticket = ticketRes.data;

                // Estados (puede fallar por permisos; no bloquea el detalle)
                try {
                    const estadosRes = await helpdesk.estados();
                    this.estados = (estadosRes.data || []).filter((x) => x.activo);
                } catch (e) {
                    console.warn("No se pudieron cargar estados", e);
                    this.estados = [];
                }

                // Asignables (solo necesario para reasignación; no bloquea el detalle)
                try {
                    const asignablesRes = await helpdesk.asignables();
                    this.asignables = asignablesRes.data || [];
                } catch (e) {
                    console.warn("No se pudieron cargar usuarios asignables", e);
                    this.asignables = [];
                }
                this.$nextTick(() => this.initWysiwyg());
            } catch (error) {
                handleAxiosErrorToast(error, "No se pudo cargar el ticket.");
            } finally {
                this.loading = false;
            }
        },
        async comentar() {
            if (!this.nuevoComentario) return;
            this.savingComentario = true;
            try {
                await helpdesk.comentar(this.ticketId, { comentario: this.nuevoComentario });
                this.nuevoComentario = "";
                toastMessage({ heading: "Ok", text: "Comentario agregado.", icon: "success" });
                await this.recargarTicket();
            } catch (error) {
                handleAxiosErrorToast(error, "No se pudo guardar el comentario.");
            } finally {
                this.savingComentario = false;
            }
        },
        async cambiarEstado() {
            if (!this.accion.estado_id) return;
            const ok = await confirmAction({
                title: "Cambiar estado",
                text: "¿Estás seguro de aplicar este cambio de estado?",
                icon: "warning",
            });
            if (!ok) return;
            this.savingEstado = true;
            try {
                await helpdesk.cambiarEstado(this.ticketId, {
                    estado_id: this.accion.estado_id,
                    comentario: this.accion.comentario || null,
                    descripcion_solucion: this.accion.descripcion_solucion || null,
                });
                this.accion = { estado_id: "", comentario: "", descripcion_solucion: "" };
                toastMessage({ heading: "Ok", text: "Estado actualizado.", icon: "success" });
                await this.recargarTicket();
            } catch (error) {
                handleAxiosErrorToast(error, "No se pudo cambiar el estado.");
            } finally {
                this.savingEstado = false;
            }
        },
        async reasignar() {
            if (!this.reasignacion.asignado_id || !this.reasignacion.comentario) return;
            const ok = await confirmAction({
                title: "Reasignar ticket",
                text: "¿Estás seguro de reasignar este ticket?",
                icon: "warning",
            });
            if (!ok) return;
            this.savingReasignar = true;
            try {
                await helpdesk.reasignar(this.ticketId, {
                    asignado_id: this.reasignacion.asignado_id,
                    comentario: this.reasignacion.comentario,
                });
                this.reasignacion = { asignado_id: "", comentario: "" };
                toastMessage({ heading: "Ok", text: "Ticket reasignado.", icon: "success" });
                await this.recargarTicket();
            } catch (error) {
                handleAxiosErrorToast(error, "No se pudo reasignar.");
            } finally {
                this.savingReasignar = false;
            }
        },
        async subirAdjuntos() {
            const input = this.$refs.files;
            const files = input && input.files ? Array.from(input.files) : [];
            if (!files.length) return;

            this.savingAdjuntos = true;
            try {
                const fd = new FormData();
                files.forEach((f) => fd.append("files[]", f));
                fd.append("comentario", "Adjuntos agregados.");
                await helpdesk.subirAdjuntos(this.ticketId, fd);
                if (input) input.value = "";
                toastMessage({ heading: "Ok", text: "Adjuntos subidos.", icon: "success" });
                await this.recargarTicket();
            } catch (error) {
                handleAxiosErrorToast(error, "No se pudieron subir adjuntos.");
            } finally {
                this.savingAdjuntos = false;
            }
        },
    },
};
</script>
