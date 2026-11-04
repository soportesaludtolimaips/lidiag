<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Seguridad</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <button
                            type="button"
                            class="btn btn-link p-0"
                            style="vertical-align: baseline; text-decoration: none; color: inherit"
                        >
                            Inicio
                        </button>
                    </li>
                    <li class="breadcrumb-item">
                        <button
                            type="button"
                            class="btn btn-link p-0"
                            style="vertical-align: baseline; text-decoration: none; color: inherit"
                        >
                            Seguridad
                        </button>
                    </li>
                    <li class="breadcrumb-item active">Tokens de Sedes</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-outline-secondary m-r-5"
                    @click="cargarDatos"
                    :disabled="cargando"
                    title="Actualizar listado"
                >
                    <i class="fa" :class="cargando ? 'fa-spinner fa-spin' : 'fa-refresh'"></i>
                    Refrescar
                </button>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Tarjetas de Resumen KPI -->
        <!-- ============================================================== -->
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex p-10 no-block">
                            <div class="align-self-center display-6 m-r-20">
                                <i class="text-info fa fa-hospital-o"></i>
                            </div>
                            <div class="align-slef-center">
                                <h2 class="m-b-0">
                                    <span v-if="cargando">...</span>
                                    <span v-else>{{ resumen.total_sedes || 0 }}</span>
                                </h2>
                                <h6 class="text-muted m-b-0">Total sedes activas</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex p-10 no-block">
                            <div class="align-self-center display-6 m-r-20">
                                <i class="text-success fa fa-check-circle"></i>
                            </div>
                            <div class="align-slef-center">
                                <h2 class="m-b-0">
                                    <span v-if="cargando">...</span>
                                    <span v-else>{{ resumen.sedes_con_token || 0 }}</span>
                                </h2>
                                <h6 class="text-muted m-b-0">Sedes con token activo</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex p-10 no-block">
                            <div class="align-self-center display-6 m-r-20">
                                <i class="text-warning fa fa-exclamation-triangle"></i>
                            </div>
                            <div class="align-slef-center">
                                <h2 class="m-b-0">
                                    <span v-if="cargando">...</span>
                                    <span v-else>{{ sedesSinToken || 0 }}</span>
                                </h2>
                                <h6 class="text-muted m-b-0">Sedes sin token</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex p-10 no-block">
                            <div class="align-self-center display-6 m-r-20">
                                <i class="text-primary fa fa-key"></i>
                            </div>
                            <div class="align-slef-center">
                                <h2 class="m-b-0">
                                    <span v-if="cargando">...</span>
                                    <span v-else>{{ porcentajeActivos }}%</span>
                                </h2>
                                <h6 class="text-muted m-b-0">Cobertura de tokens</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- Start Page Content -->
        <!-- ============================================================== -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Listado de tokens por sede</h4>
                        <h6 class="card-subtitle">
                            Administración de credenciales de acceso para los agentes locales de
                            dosimetría y copias de seguridad de imágenes diagnósticas.
                        </h6>

                        <table-skeleton-loader :visible="cargando" :rows="5" :columns="8" />

                        <empty-state-component
                            v-if="!cargando && sedes.length === 0"
                            icon="fa fa-key"
                            title="No hay sedes activas"
                            description="No se encontraron sedes activas configuradas en el sistema para gestionar tokens."
                            action-text="Recargar datos"
                            action-icon="fa fa-refresh"
                            @action="cargarDatos"
                        />

                        <div v-show="!cargando && sedes.length > 0" class="table-responsive m-t-40">
                            <table
                                class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th>Sede</th>
                                        <th>Host PACS / BD</th>
                                        <th>Estado</th>
                                        <th>Identificador de Token</th>
                                        <th class="text-center">DB DCM</th>
                                        <th class="text-center">Img Dicom</th>
                                        <th class="text-center">Dosi</th>
                                        <th class="text-nowrap text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="sede in sedes" :key="sede.sede_id">
                                        <td>
                                            <strong>{{ sede.nom_sede }}</strong>
                                            <small class="text-muted d-block">
                                                BD: {{ sede.bd_dcm || "pacsdb" }}
                                            </small>
                                        </td>
                                        <td>
                                            {{ sede.ip_dcm || "127.0.0.1" }}:{{
                                                sede.puerto_dcm || 3306
                                            }}
                                        </td>
                                        <td>
                                            <span
                                                v-if="sede.tiene_token"
                                                class="label label-success"
                                            >
                                                Activo
                                            </span>
                                            <span v-else class="label label-danger">
                                                Sin Token
                                            </span>
                                        </td>
                                        <td>
                                            <code v-if="sede.token_name">{{
                                                sede.token_name
                                            }}</code>
                                            <span v-else class="text-muted">No emitido</span>
                                        </td>

                                        <!-- DB DCM (Backup de Base de Datos) -->
                                        <td class="text-center">
                                            <div
                                                v-if="
                                                    sede.agente_backup_db &&
                                                    sede.agente_backup_db.fecha
                                                "
                                            >
                                                <span
                                                    :class="[
                                                        'label',
                                                        sede.agente_backup_db.estado === 'OK'
                                                            ? 'label-success'
                                                            : sede.agente_backup_db.estado ===
                                                                'ADVERTENCIA'
                                                              ? 'label-warning'
                                                              : 'label-danger',
                                                    ]"
                                                    :title="
                                                        sede.agente_backup_db.mensaje ||
                                                        'Backup Base de Datos'
                                                    "
                                                >
                                                    {{ sede.agente_backup_db.estado }}
                                                </span>
                                                <small class="text-muted d-block mt-1">
                                                    {{ sede.agente_backup_db.tiempo_humano }}
                                                </small>
                                            </div>
                                            <span v-else class="label label-light-inverse"
                                                >Sin datos</span
                                            >
                                        </td>

                                        <!-- Img Dicom (Sincronización de Imágenes) -->
                                        <td class="text-center">
                                            <div
                                                v-if="
                                                    sede.agente_sync_imagenes &&
                                                    sede.agente_sync_imagenes.fecha
                                                "
                                            >
                                                <span
                                                    :class="[
                                                        'label',
                                                        sede.agente_sync_imagenes.estado === 'OK'
                                                            ? 'label-success'
                                                            : sede.agente_sync_imagenes.estado ===
                                                                'ADVERTENCIA'
                                                              ? 'label-warning'
                                                              : 'label-danger',
                                                    ]"
                                                    :title="
                                                        sede.agente_sync_imagenes.mensaje ||
                                                        'Sincronización de Imágenes DICOM'
                                                    "
                                                >
                                                    {{ sede.agente_sync_imagenes.estado }}
                                                </span>
                                                <small class="text-muted d-block mt-1">
                                                    {{ sede.agente_sync_imagenes.tiempo_humano }}
                                                </small>
                                            </div>
                                            <span v-else class="label label-light-inverse"
                                                >Sin datos</span
                                            >
                                        </td>

                                        <!-- Dosi (Agente de Dosimetría) -->
                                        <td class="text-center">
                                            <div
                                                v-if="
                                                    sede.agente_dosimetria &&
                                                    sede.agente_dosimetria.fecha
                                                "
                                            >
                                                <span
                                                    :class="[
                                                        'label',
                                                        sede.agente_dosimetria.estado === 'OK'
                                                            ? 'label-success'
                                                            : sede.agente_dosimetria.estado ===
                                                                'ADVERTENCIA'
                                                              ? 'label-warning'
                                                              : 'label-danger',
                                                    ]"
                                                    :title="
                                                        sede.agente_dosimetria.mensaje ||
                                                        'Dosimetría Radiológica'
                                                    "
                                                >
                                                    {{ sede.agente_dosimetria.estado }}
                                                </span>
                                                <small class="text-muted d-block mt-1">
                                                    {{ sede.agente_dosimetria.tiempo_humano }}
                                                </small>
                                            </div>
                                            <span v-else class="label label-light-inverse"
                                                >Sin datos</span
                                            >
                                        </td>

                                        <td class="text-nowrap text-center">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-sm m-r-5"
                                                :class="
                                                    sede.tiene_token
                                                        ? 'btn-outline-warning'
                                                        : 'btn-outline-info'
                                                "
                                                @click="confirmarGeneracionToken(sede)"
                                                :disabled="procesandoSedeId === sede.sede_id"
                                                :title="
                                                    sede.tiene_token
                                                        ? 'Rotar token'
                                                        : 'Generar token'
                                                "
                                            >
                                                <i
                                                    class="fa"
                                                    :class="
                                                        procesandoSedeId === sede.sede_id
                                                            ? 'fa-spinner fa-spin'
                                                            : sede.tiene_token
                                                              ? 'fa-refresh'
                                                              : 'fa-key'
                                                    "
                                                ></i>
                                                {{ sede.tiene_token ? "Rotar" : "Generar" }}
                                            </button>
                                            <button
                                                v-if="sede.tiene_token"
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm m-r-5"
                                                @click="confirmarRevocacionToken(sede)"
                                                :disabled="procesandoSedeId === sede.sede_id"
                                                title="Revocar token"
                                            >
                                                <i class="fa fa-trash"></i>
                                            </button>
                                            <a
                                                :href="`/api/seguridad-tokens/${sede.sede_id}/config-json`"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-secondary btn-sm"
                                                target="_blank"
                                                download
                                                title="Descargar config.json base"
                                            >
                                                <i class="fa fa-download"></i>
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Page Content -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Modal: Entrega de Token Generado -->
        <!-- ============================================================== -->
        <div
            v-if="modalTokenVisible"
            class="modal show"
            style="display: block; background: rgba(0, 0, 0, 0.5)"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            @click.self="cerrarModalToken"
        >
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">
                            Token de Acceso — {{ tokenEmitido.sede?.nom_sede }}
                        </h4>
                        <button
                            type="button"
                            class="close"
                            @click="cerrarModalToken"
                            aria-label="Cerrar modal"
                        >
                            ×
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fa fa-exclamation-triangle mr-2"></i>
                            <strong>Atención de Seguridad:</strong>
                            Copie o descargue este token ahora mismo. Por motivos de seguridad y
                            encriptación irreversible en Laravel Sanctum,
                            <strong>este valor no podrá volver a ser visualizado jamás</strong>.
                        </div>

                        <div class="form-group">
                            <label class="control-label">
                                API Token de Producción (Bearer Token)
                            </label>
                            <div class="input-group">
                                <input
                                    type="text"
                                    class="form-control"
                                    :value="tokenEmitido.plain_text_token"
                                    readonly
                                    ref="inputTokenRef"
                                />
                                <div class="input-group-append">
                                    <button
                                        type="button"
                                        class="btn btn-info waves-effect waves-light"
                                        @click="copiarTokenPortapapeles"
                                    >
                                        <i
                                            :class="[
                                                'fa mr-1',
                                                tokenCopiado ? 'fa-check' : 'fa-copy',
                                            ]"
                                        ></i>
                                        {{ tokenCopiado ? "¡Copiado!" : "Copiar Token" }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label">
                                Archivo de Configuración Preconfigurado
                            </label>
                            <div>
                                <button
                                    type="button"
                                    class="btn btn-success waves-effect waves-light"
                                    @click="descargarConfigJsonGenerado"
                                >
                                    <i class="fa fa-download mr-1"></i> Descargar config.json
                                </button>
                            </div>
                            <small class="form-text text-muted">
                                Incluye el
                                <code>sede_id: {{ tokenEmitido.sede?.id }}</code
                                >, endpoints institucionales y este nuevo token listo para ubicar en
                                <code>C:\agente_backups_dcm\config.json</code>.
                            </small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-inverse waves-effect"
                            @click="cerrarModalToken"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Modal -->
        <!-- ============================================================== -->
    </div>
</template>

<script setup>
/**
 * Componente Vue 3 para la administración y generación de tokens de acceso Sanctum por sede.
 * Implementa rotación segura, copia en 1 clic y descarga de plantilla config.json institucional.
 * Estilo visual 100% alineado a AdminWrap / Bootstrap 4.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-03
 */

import { ref, computed, onMounted } from "vue";
import axios from "axios";
import { confirmAction, alertError, alertSuccess } from "@/helpers/alerts";

// Estado de la tabla y resumen KPI
const cargando = ref(false);
const sedes = ref([]);
const procesandoSedeId = ref(null);
const usuarioServicio = ref({});
const resumen = ref({
    total_sedes: 0,
    sedes_con_token: 0,
});

// Computados de resumen
const porcentajeActivos = computed(() => {
    if (!resumen.value.total_sedes) return 0;
    return Math.round((resumen.value.sedes_con_token / resumen.value.total_sedes) * 100);
});

const sedesSinToken = computed(() => {
    return Math.max(0, resumen.value.total_sedes - resumen.value.sedes_con_token);
});

// Estado del modal de entrega de token
const modalTokenVisible = ref(false);
const tokenEmitido = ref({
    sede: null,
    token_id: null,
    token_name: "",
    plain_text_token: "",
    config_json: null,
});
const tokenCopiado = ref(false);
const inputTokenRef = ref(null);

/**
 * Consulta la lista consolidada de sedes y el estado de sus tokens.
 */
async function cargarDatos() {
    cargando.value = true;
    try {
        const res = await axios.get("/api/seguridad-tokens");
        if (res.data && res.data.success) {
            const data = res.data.data;
            sedes.value = (data?.sedes || []).filter((s) => s.estado_sede !== false);
            usuarioServicio.value = data?.usuario_servicio || {};
            resumen.value = {
                total_sedes: data?.total_sedes || sedes.value.length,
                sedes_con_token: data?.sedes_con_token || 0,
            };
        }
    } catch (error) {
        alertError(
            error.response?.data?.message || "No se pudo cargar el listado de tokens de las sedes."
        );
    } finally {
        cargando.value = false;
    }
}

/**
 * Solicita confirmación antes de generar o rotar el token de una sede.
 */
async function confirmarGeneracionToken(sede) {
    const esRotacion = sede.tiene_token;
    const mensaje = esRotacion
        ? `La sede '${sede.nom_sede}' ya cuenta con un token activo. Si continúas, se generará un nuevo token y el token anterior será revocado automáticamente. ¿Deseas rotar la credencial?`
        : `Se generará un token unificado de acceso permanente para el Agente Edge de la sede '${sede.nom_sede}'. ¿Deseas continuar?`;

    const confirmado = await confirmAction({
        title: esRotacion ? "¿Rotar Token de la Sede?" : "¿Generar Token de Acceso?",
        text: mensaje,
        icon: esRotacion ? "warning" : "info",
    });

    if (confirmado) {
        await emitirToken(sede.sede_id);
    }
}

/**
 * Emite la petición a la API para crear el token con rotación automática.
 */
async function emitirToken(sedeId) {
    procesandoSedeId.value = sedeId;
    try {
        const res = await axios.post("/api/seguridad-tokens", { sede_id: sedeId });
        if (res.data && res.data.success) {
            tokenEmitido.value = res.data.data;
            tokenCopiado.value = false;
            modalTokenVisible.value = true;
            await cargarDatos();
            alertSuccess("Token generado exitosamente con rotación automática.");
        }
    } catch (error) {
        alertError(error.response?.data?.message || "Ocurrió un error al generar el token.");
    } finally {
        procesandoSedeId.value = null;
    }
}

/**
 * Solicita confirmación y revoca el token de una sede.
 */
async function confirmarRevocacionToken(sede) {
    if (!sede.token_id) return;

    const confirmado = await confirmAction({
        title: "¿Revocar Token?",
        text: `¿Estás seguro de revocar el token de '${sede.nom_sede}'? El Agente Edge no podrá volver a transmitir hasta que se configure un nuevo token.`,
        icon: "warning",
    });

    if (confirmado) {
        procesandoSedeId.value = sede.sede_id;
        try {
            const res = await axios.delete(`/api/seguridad-tokens/${sede.token_id}`);
            if (res.data && res.data.success) {
                alertSuccess("Token revocado correctamente.");
                await cargarDatos();
            }
        } catch (error) {
            alertError(error.response?.data?.message || "No se pudo revocar el token.");
        } finally {
            procesandoSedeId.value = null;
        }
    }
}

/**
 * Copia el token plano al portapapeles.
 */
async function copiarTokenPortapapeles() {
    if (!tokenEmitido.value.plain_text_token) return;

    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(tokenEmitido.value.plain_text_token);
        } else if (inputTokenRef.value) {
            inputTokenRef.value.select();
            document.execCommand("copy");
        }
        tokenCopiado.value = true;
        setTimeout(() => {
            tokenCopiado.value = false;
        }, 3000);
    } catch {
        alertError("No se pudo copiar automáticamente. Por favor selecciónalo y presiona Ctrl+C.");
    }
}

/**
 * Descarga el config.json generado con el nuevo token incrustado.
 */
function descargarConfigJsonGenerado() {
    if (!tokenEmitido.value.config_json) return;

    const contenido = JSON.stringify(tokenEmitido.value.config_json, null, 4);
    const blob = new Blob([contenido], { type: "application/json" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    const sedeId = tokenEmitido.value.sede?.id || "sede";
    a.href = url;
    a.download = `config_sede_${sedeId}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

/**
 * Cierra el modal de entrega de token.
 */
function cerrarModalToken() {
    modalTokenVisible.value = false;
    tokenEmitido.value = {
        sede: null,
        token_id: null,
        token_name: "",
        plain_text_token: "",
        config_json: null,
    };
}

onMounted(() => {
    cargarDatos();
});
</script>
