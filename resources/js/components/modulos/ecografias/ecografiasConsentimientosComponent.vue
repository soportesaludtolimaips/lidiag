<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-6 align-self-center">
                <h3 class="text-themecolor">Ecografías</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <button
                            type="button"
                            class="btn btn-link p-0"
                            style="vertical-align: baseline"
                        >
                            Inicio
                        </button>
                    </li>
                    <li class="breadcrumb-item">
                        <button
                            type="button"
                            class="btn btn-link p-0"
                            style="vertical-align: baseline"
                        >
                            Ecografías
                        </button>
                    </li>
                    <li class="breadcrumb-item active">Consentimientos</li>
                </ol>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Buscar consentimientos</h4>
                <div class="row">
                    <div class="col-md-4">
                        <label for="filtros_q">Paciente / Documento</label>
                        <input
                            id="filtros_q"
                            class="form-control"
                            v-model="filtros.q"
                            placeholder="Nombre o documento"
                        />
                    </div>
                    <div class="col-md-3">
                        <label for="filtros_desde">Fecha inicio</label>
                        <input
                            id="filtros_desde"
                            type="date"
                            class="form-control"
                            v-model="filtros.desde"
                        />
                    </div>
                    <div class="col-md-3">
                        <label for="filtros_hasta">Fecha fin</label>
                        <input
                            id="filtros_hasta"
                            type="date"
                            class="form-control"
                            v-model="filtros.hasta"
                        />
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button
                        class="btn btn-success"
                        type="button"
                        @click="buscar"
                        :disabled="loading"
                    >
                        <i class="fa" :class="loading ? 'fa-spinner fa-spin' : 'fa-search'"></i>
                        Buscar
                    </button>

                    <button
                        v-if="can('ecografias.consentimientos.pdf.generar')"
                        class="btn btn-info"
                        type="button"
                        @click="generarPdfSeleccionado"
                        :disabled="loading || selectedIds.length !== 1"
                    >
                        Generar PDF
                    </button>

                    <button
                        v-if="can('ecografias.consentimientos.pdf.descargar')"
                        class="btn btn-primary"
                        type="button"
                        @click="descargarPdfSeleccionado"
                        :disabled="
                            loading || selectedIds.length !== 1 || !selectedConsentimiento?.pdf_path
                        "
                    >
                        Descargar PDF
                    </button>

                    <button
                        v-if="can('ecografias.consentimientos.zip.generar')"
                        class="btn btn-warning"
                        type="button"
                        @click="generarZip"
                        :disabled="loading || selectedIds.length < 2"
                    >
                        Descargar ZIP
                    </button>

                    <button
                        v-if="can('ecografias.consentimientos.anular')"
                        class="btn btn-danger"
                        type="button"
                        @click="anularSeleccionado"
                        :disabled="loading || selectedIds.length !== 1"
                    >
                        Anular
                    </button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Resultados</h4>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th width="40">
                                    <input
                                        type="checkbox"
                                        :checked="allSelectableSelected"
                                        @change="toggleSelectAll"
                                        aria-label="Seleccionar todos"
                                    />
                                </th>
                                <th>Paciente</th>
                                <th>Documento</th>
                                <th>Examen</th>
                                <th>Estado</th>
                                <th>PDF</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in registros" :key="c.id">
                                <td>
                                    <input
                                        type="checkbox"
                                        :disabled="!esSeleccionable(c)"
                                        :checked="selectedMap[c.id] === true"
                                        @change="toggleSelect(c)"
                                        aria-label="Seleccionar consentimiento"
                                    />
                                </td>
                                <td>{{ pacienteNombre(c) }}</td>
                                <td>
                                    {{ c.ecografia_producto?.ecografia?.paciente?.num_docu || "—" }}
                                </td>
                                <td>{{ c.producto?.nom_produc || "—" }}</td>
                                <td>
                                    <span v-if="c.estado === 'COMPLETO'" class="text-success"
                                        >COMPLETO</span
                                    >
                                    <span v-else class="text-danger">{{ c.estado }}</span>
                                </td>
                                <td>
                                    <span v-if="c.pdf_path" class="text-success">Generado</span>
                                    <span v-else class="text-muted">—</span>
                                </td>
                            </tr>
                            <tr v-if="registros.length === 0">
                                <td colspan="6" class="text-center text-muted">Sin resultados.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { handleAxiosErrorToast } from "@/helpers/alerts";
import usePermissions from "@/helpers/permissions.js";

export default {
    props: ["usuarioactual", "permisos"],
    setup(props) {
        const { can } = usePermissions(props.permisos || []);
        return { can };
    },
    data() {
        return {
            filtros: {
                q: "",
                desde: "",
                hasta: "",
                sede_id: sessionStorage.getItem("ST-sede") || "",
            },
            registros: [],
            loading: false,
            selectedMap: {},
        };
    },
    computed: {
        selectedIds() {
            return Object.keys(this.selectedMap)
                .filter((k) => this.selectedMap[k])
                .map((k) => Number(k));
        },
        selectedConsentimiento() {
            if (this.selectedIds.length !== 1) return null;
            const id = this.selectedIds[0];
            return this.registros.find((r) => Number(r.id) === Number(id)) || null;
        },
        allSelectableSelected() {
            const selectable = this.registros.filter(this.esSeleccionable);
            if (selectable.length === 0) return false;
            return selectable.every((r) => this.selectedMap[r.id] === true);
        },
    },
    mounted() {
        this.buscar();
    },
    methods: {
        pacienteNombre(c) {
            const p = c?.ecografia_producto?.ecografia?.paciente;
            if (!p) return "—";
            return String(p.nombres || "").trim();
        },
        esSeleccionable(c) {
            return c?.estado === "COMPLETO";
        },
        toggleSelect(c) {
            const id = Number(c.id);
            if (!this.esSeleccionable(c)) return;
            this.selectedMap[id] = !this.selectedMap[id];
        },
        toggleSelectAll(e) {
            const checked = !!e.target.checked;
            const next = { ...this.selectedMap };
            this.registros.forEach((r) => {
                if (!this.esSeleccionable(r)) return;
                next[r.id] = checked;
            });
            this.selectedMap = next;
        },
        async buscar() {
            this.loading = true;
            this.selectedMap = {};
            try {
                const res = await axios.get("/api/ecografias-consentimientos", {
                    params: this.filtros,
                });
                this.registros = Array.isArray(res.data) ? res.data : [];
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar consentimientos.");
                this.registros = [];
            } finally {
                this.loading = false;
            }
        },
        async generarPdfSeleccionado() {
            if (this.selectedIds.length !== 1) return;
            const id = this.selectedIds[0];
            this.loading = true;
            try {
                await axios.post(`/api/ecografias-consentimientos/${id}/pdf`);
                await this.buscar();
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible generar el PDF.");
            } finally {
                this.loading = false;
            }
        },
        descargarPdfSeleccionado() {
            const c = this.selectedConsentimiento;
            if (!c?.id || !c?.pdf_path) return;
            window.open(
                `/api/ecografias-consentimientos/${c.id}/pdf/download`,
                "_blank",
                "noopener,noreferrer"
            );
        },
        async generarZip() {
            if (this.selectedIds.length < 2) return;
            this.loading = true;
            try {
                const res = await axios.post(
                    "/api/ecografias-consentimientos/zip",
                    { ids: this.selectedIds },
                    { responseType: "blob" }
                );
                const blob = new Blob([res.data], { type: "application/zip" });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.href = url;
                a.download = "consentimientos.zip";
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible generar el ZIP.");
            } finally {
                this.loading = false;
            }
        },

        async anularSeleccionado() {
            if (this.selectedIds.length !== 1) return;
            const id = this.selectedIds[0];
            const obs = window.prompt("Observación de anulación (obligatoria):");
            if (!obs || !String(obs).trim()) return;
            this.loading = true;
            try {
                await axios.post(`/api/ecografias-consentimientos/${id}/anular`, {
                    observacion: String(obs).trim(),
                });
                await this.buscar();
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible anular el consentimiento.");
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
