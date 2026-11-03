<template>
    <div>
        <!-- Cabecera de Página y Migas de Pan -->
        <div class="row page-titles">
            <div class="col-md-7 col-8 align-self-center">
                <h3 class="text-themecolor mb-0 mt-0">
                    <i class="fa fa-ticket mr-2 text-primary"></i> Generador de Tokens por Sede
                </h3>
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
                    <li class="breadcrumb-item active">Tokens de Sedes (Agentes Edge)</li>
                </ol>
            </div>
            <div class="col-md-5 col-4 align-self-center text-right">
                <button
                    type="button"
                    class="btn btn-outline-secondary waves-effect waves-light"
                    @click="cargarDatos()"
                    :disabled="cargando"
                    title="Actualizar listado de tokens"
                >
                    <i :class="['fa fa-refresh mr-1', { 'fa-spin': cargando }]"></i>
                    Refrescar
                </button>
            </div>
        </div>

        <!-- Tarjetas de Resumen KPI -->
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="card card-outline-info shadow-sm h-100">
                    <div class="card-body">
                        <div
                            class="d-flex flex-row align-items-center justify-content-between mb-2"
                        >
                            <span class="round round-info"
                                ><i class="fa fa-hospital-o font-20"></i
                            ></span>
                            <span class="badge badge-info badge-pill"
                                >{{ resumen.total_sedes }} Sedes</span
                            >
                        </div>
                        <h6 class="text-muted mb-1 text-uppercase font-12 font-weight-bold">
                            Total Sedes Hospitalarias
                        </h6>
                        <h4 class="font-weight-bold mb-0 text-dark">
                            {{ resumen.total_sedes }} Red Asistencial
                        </h4>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card card-outline-success shadow-sm h-100">
                    <div class="card-body">
                        <div
                            class="d-flex flex-row align-items-center justify-content-between mb-2"
                        >
                            <span class="round round-success"
                                ><i class="fa fa-check-circle font-20"></i
                            ></span>
                            <span class="badge badge-success badge-pill"
                                >{{ resumen.sedes_con_token }} / {{ resumen.total_sedes }}</span
                            >
                        </div>
                        <h6 class="text-muted mb-1 text-uppercase font-12 font-weight-bold">
                            Sedes con Token Activo
                        </h6>
                        <h4 class="font-weight-bold mb-0 text-success">
                            {{ porcentajeActivos }}% Conectadas
                        </h4>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card card-outline-warning shadow-sm h-100">
                    <div class="card-body">
                        <div
                            class="d-flex flex-row align-items-center justify-content-between mb-2"
                        >
                            <span class="round round-warning"
                                ><i class="fa fa-exclamation-triangle font-20"></i
                            ></span>
                            <span class="badge badge-warning badge-pill"
                                >{{ sedesSinToken }} Pendientes</span
                            >
                        </div>
                        <h6 class="text-muted mb-1 text-uppercase font-12 font-weight-bold">
                            Sedes sin Token
                        </h6>
                        <h4 class="font-weight-bold mb-0 text-warning">
                            {{ sedesSinToken }} Por Configurar
                        </h4>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card card-outline-primary shadow-sm h-100">
                    <div class="card-body">
                        <div
                            class="d-flex flex-row align-items-center justify-content-between mb-2"
                        >
                            <span class="round round-primary"
                                ><i class="fa fa-user-secret font-20"></i
                            ></span>
                            <span class="badge badge-primary badge-pill">Sanctum</span>
                        </div>
                        <h6 class="text-muted mb-1 text-uppercase font-12 font-weight-bold">
                            Cuenta de Servicio Central
                        </h6>
                        <h5
                            class="font-weight-bold mb-0 text-truncate text-dark"
                            :title="usuarioServicio.email"
                        >
                            {{ usuarioServicio.name || "Agente Edge LIDiag" }}
                        </h5>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Sedes y Gestión de Tokens -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h4 class="card-title mb-1 font-weight-bold">
                                    <i class="fa fa-shield text-info mr-2"></i> Tokens de Acceso
                                    Edge por Sede
                                </h4>
                                <p class="card-subtitle mb-0 text-muted">
                                    Genera, rota y descarga los archivos
                                    <code>config.json</code> con credenciales unificadas para los
                                    Agentes Edge (Dosimetría y Backups DCM).
                                </p>
                            </div>
                        </div>

                        <!-- Skeleton Loader durante la carga -->
                        <table-skeleton-loader v-if="cargando" :rows="7" :columns="6" />

                        <!-- Tabla Principal -->
                        <div v-else class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 60px" class="text-center">ID</th>
                                        <th>Sede Hospitalaria</th>
                                        <th>Host PACS / DB</th>
                                        <th>Estado del Token</th>
                                        <th>Identificador de Credencial</th>
                                        <th>Última Transmisión</th>
                                        <th style="width: 280px" class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="sede in sedes" :key="sede.sede_id">
                                        <!-- ID -->
                                        <td class="text-center font-weight-bold">
                                            <span class="badge badge-secondary font-12"
                                                >#{{ sede.sede_id }}</span
                                            >
                                        </td>

                                        <!-- Sede -->
                                        <td>
                                            <div class="font-weight-bold text-dark font-14">
                                                {{ sede.nom_sede }}
                                            </div>
                                            <small class="text-muted">
                                                <i class="fa fa-database mr-1"></i>
                                                {{ sede.bd_dcm || "pacsdb" }}
                                            </small>
                                        </td>

                                        <!-- Host PACS -->
                                        <td>
                                            <span
                                                class="badge badge-light border text-dark font-12"
                                            >
                                                <i class="fa fa-server mr-1 text-primary"></i>
                                                {{ sede.ip_dcm || "127.0.0.1" }}:{{
                                                    sede.puerto_dcm || 3306
                                                }}
                                            </span>
                                        </td>

                                        <!-- Estado Token -->
                                        <td>
                                            <span
                                                v-if="sede.tiene_token"
                                                class="badge badge-success font-12 py-1 px-2"
                                            >
                                                <i class="fa fa-check-circle mr-1"></i> Activo
                                                (Unificado)
                                            </span>
                                            <span
                                                v-else
                                                class="badge badge-danger font-12 py-1 px-2"
                                            >
                                                <i class="fa fa-times-circle mr-1"></i> Sin Token
                                            </span>
                                        </td>

                                        <!-- Identificador Token -->
                                        <td>
                                            <code
                                                v-if="sede.token_name"
                                                class="font-12 text-primary"
                                            >
                                                {{ sede.token_name }}
                                            </code>
                                            <span v-else class="text-muted font-italic font-12"
                                                >No emitido</span
                                            >
                                        </td>

                                        <!-- Última Transmisión -->
                                        <td>
                                            <div v-if="sede.last_used_at">
                                                <span class="font-weight-bold text-dark font-12">{{
                                                    sede.ultimo_uso_legible
                                                }}</span>
                                                <br />
                                                <small class="text-muted">{{
                                                    sede.last_used_at
                                                }}</small>
                                            </div>
                                            <span v-else class="badge badge-secondary font-11"
                                                >Nunca reportado</span
                                            >
                                        </td>

                                        <!-- Acciones -->
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <!-- Generar / Rotar Token -->
                                                <button
                                                    type="button"
                                                    :class="[
                                                        'btn btn-sm waves-effect waves-light',
                                                        sede.tiene_token
                                                            ? 'btn-outline-warning'
                                                            : 'btn-success',
                                                    ]"
                                                    @click="confirmarGeneracionToken(sede)"
                                                    :disabled="procesandoSedeId === sede.sede_id"
                                                    :title="
                                                        sede.tiene_token
                                                            ? 'Rotar token (revocará el anterior)'
                                                            : 'Generar token por primera vez'
                                                    "
                                                >
                                                    <i
                                                        :class="[
                                                            'fa mr-1',
                                                            procesandoSedeId === sede.sede_id
                                                                ? 'fa-spinner fa-spin'
                                                                : sede.tiene_token
                                                                  ? 'fa-refresh'
                                                                  : 'fa-key',
                                                        ]"
                                                    ></i>
                                                    {{
                                                        sede.tiene_token
                                                            ? "Rotar Token"
                                                            : "Generar Token"
                                                    }}
                                                </button>

                                                <!-- Revocar Token -->
                                                <button
                                                    v-if="sede.tiene_token"
                                                    type="button"
                                                    class="btn btn-sm btn-outline-danger waves-effect waves-light"
                                                    @click="confirmarRevocacionToken(sede)"
                                                    :disabled="procesandoSedeId === sede.sede_id"
                                                    title="Revocar credencial"
                                                >
                                                    <i class="fa fa-trash"></i>
                                                </button>

                                                <!-- Descargar config.json base -->
                                                <a
                                                    :href="`/api/seguridad-tokens/${sede.sede_id}/config-json`"
                                                    class="btn btn-sm btn-outline-secondary waves-effect waves-light"
                                                    target="_blank"
                                                    download
                                                    title="Descargar plantilla config.json de esta sede"
                                                >
                                                    <i class="fa fa-download"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Alta Seguridad: Entrega de Token Generado -->
        <div
            v-if="modalTokenVisible"
            class="modal fade show"
            style="display: block; background: rgba(0, 0, 0, 0.65)"
            tabindex="-1"
            role="dialog"
            aria-labelledby="modalTokenLabel"
            aria-modal="true"
        >
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content shadow-lg border-0">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title font-weight-bold" id="modalTokenLabel">
                            <i class="fa fa-shield mr-2"></i> Token Emitido Exitosamente — Sede
                            {{ tokenEmitido.sede?.nom_sede }}
                        </h5>
                        <button
                            type="button"
                            class="close text-white"
                            @click="cerrarModalToken()"
                            aria-label="Cerrar"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body p-4">
                        <!-- Alerta Crítica de Un Solo Visionado -->
                        <div
                            class="alert alert-warning border-left border-warning d-flex align-items-center mb-4"
                            role="alert"
                        >
                            <i class="fa fa-exclamation-triangle fa-2x mr-3 text-warning"></i>
                            <div>
                                <strong class="d-block font-14"
                                    >Atención de Seguridad Obligatoria:</strong
                                >
                                Copia o descarga este token ahora mismo. Por motivos de seguridad y
                                encriptación irreversible en Laravel Sanctum,
                                <strong>este valor no podrá volver a ser visualizado jamás</strong>.
                            </div>
                        </div>

                        <!-- Valor del Token en Texto Plano con Botón de Copiar -->
                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-dark font-13">
                                <i class="fa fa-key text-success mr-1"></i> API Token de Producción
                                (Bearer Token):
                            </label>
                            <div class="input-group">
                                <input
                                    type="text"
                                    class="form-control font-weight-bold font-monospace bg-light"
                                    style="letter-spacing: 0.5px; font-size: 13px"
                                    :value="tokenEmitido.plain_text_token"
                                    readonly
                                    ref="inputTokenRef"
                                />
                                <div class="input-group-append">
                                    <button
                                        type="button"
                                        class="btn btn-primary waves-effect waves-light"
                                        @click="copiarTokenPortapapeles()"
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

                        <!-- Panel de Descarga del config.json listo para producción -->
                        <div class="card bg-light border mb-3">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="font-weight-bold mb-1 text-dark">
                                            <i class="fa fa-file-code-o text-primary mr-1"></i>
                                            Archivo <code>config.json</code> Preconfigurado
                                        </h6>
                                        <small class="text-muted">
                                            Incluye el
                                            <code>sede_id: {{ tokenEmitido.sede?.id }}</code
                                            >, nombre, endpoints institucionales y este nuevo token
                                            listo para usar.
                                        </small>
                                    </div>
                                    <button
                                        type="button"
                                        class="btn btn-success waves-effect waves-light font-weight-bold"
                                        @click="descargarConfigJsonGenerado()"
                                    >
                                        <i class="fa fa-download mr-1"></i> Descargar config.json
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Instrucciones de Despliegue -->
                        <div class="bg-light p-3 rounded font-12 text-muted">
                            <strong class="text-dark d-block mb-1"
                                >Instrucciones de Despliegue en la Sede:</strong
                            >
                            <ol class="mb-0 pl-3">
                                <li>
                                    Descarga el archivo <code>config.json</code> con el botón de
                                    arriba.
                                </li>
                                <li>
                                    Copia el archivo a la carpeta del agente:
                                    <code>C:\agente_backups_dcm\config.json</code> en el servidor
                                    local.
                                </li>
                                <li>
                                    Ejecuta el agente con:
                                    <code>python agente_dosimetria.py --dias 7 --limit 500</code>
                                    para validar la transmisión.
                                </li>
                            </ol>
                        </div>
                    </div>

                    <div class="modal-footer bg-light">
                        <button
                            type="button"
                            class="btn btn-secondary waves-effect"
                            @click="cerrarModalToken()"
                        >
                            Entendido y Guardado
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
/**
 * Componente Vue 3 para la administración y generación de tokens de acceso Sanctum por sede.
 * Implementa rotación segura, copia en 1 clic y descarga de plantilla config.json institucional.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-03
 */

import { ref, computed, onMounted } from "vue";
import axios from "axios";
import { confirmAction, alertError, alertSuccess } from "@/helpers/alerts";

// Estados reactivos principales
const cargando = ref(false);
const procesandoSedeId = ref(null);
const sedes = ref([]);
const usuarioServicio = ref({});
const resumen = ref({
    total_sedes: 0,
    sedes_con_token: 0,
});

// Modal de visualización de token
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

// Computados
const porcentajeActivos = computed(() => {
    if (!resumen.value.total_sedes) return 0;
    return Math.round((resumen.value.sedes_con_token / resumen.value.total_sedes) * 100);
});

const sedesSinToken = computed(() => {
    return Math.max(0, resumen.value.total_sedes - resumen.value.sedes_con_token);
});

/**
 * Consulta la lista consolidada de sedes y el estado de sus tokens.
 */
async function cargarDatos() {
    cargando.value = true;
    try {
        const res = await axios.get("/api/seguridad-tokens");
        if (res.data && res.data.success) {
            const data = res.data.data;
            sedes.value = data.sedes || [];
            usuarioServicio.value = data.usuario_servicio || {};
            resumen.value = {
                total_sedes: data.total_sedes || sedes.value.length,
                sedes_con_token: data.sedes_con_token || 0,
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

    const confirmado = await confirmAction(mensaje, esRotacion ? "warning" : "info");

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

    const confirmado = await confirmAction(
        `¿Estás seguro de revocar el token de '${sede.nom_sede}'? El Agente Edge no podrá volver a transmitir hasta que se configure un nuevo token.`,
        "error"
    );

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
 * Cierra el modal de alta seguridad.
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

<style scoped>
.font-monospace {
    font-family:
        SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
}
.round {
    line-height: 48px;
    color: #ffffff;
    width: 48px;
    height: 48px;
    display: inline-block;
    font-weight: 400;
    text-align: center;
    border-radius: 100%;
    background: #1e88e5;
}
.round.round-info {
    background: #00bcd4;
}
.round.round-warning {
    background: #ffb22b;
}
.round.round-danger {
    background: #fc4b6c;
}
.round.round-primary {
    background: #7460ee;
}
.round.round-success {
    background: #26c6da;
}
</style>
