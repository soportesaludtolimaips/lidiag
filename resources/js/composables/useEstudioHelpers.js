import { ref } from "vue";
import axios from "axios";
import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import { toastMessage } from "@/helpers/alerts";
import { safeRemoveDataTableRow } from "@/helpers/datatables";

dayjs.extend(utc);

export function useEstudioHelpers() {
    const loadingDescarga = ref(null);
    const loadingAudios = ref(false);
    const loadingImprimir = ref(null);
    const loadingAnular = ref(null);
    const loadingVerImagen = ref(null);
    const audiosDeEstudio = ref([]);

    function formatDate(timestamp) {
        return dayjs.utc(timestamp).local().format("YYYY-MM-DD HH:mm:ss");
    }

    function descargaSoporte(nomArchivoOriginal, nomArchivoEncriptado) {
        if (loadingDescarga.value) return;
        loadingDescarga.value = nomArchivoEncriptado;
        const url = `/api/descarga-soportes-hc/${nomArchivoEncriptado}`;

        axios
            .get(url, { responseType: "blob" })
            .then((response) => {
                const blobUrl = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement("a");
                link.href = blobUrl;
                link.setAttribute("download", nomArchivoOriginal || nomArchivoEncriptado);
                document.body.appendChild(link);
                link.click();
                setTimeout(() => window.URL.revokeObjectURL(blobUrl), 2000);
            })
            .catch((error) => {})
            .finally(() => {
                loadingDescarga.value = null;
            });
    }

    async function listarAudios(estudioProductoId) {
        if (loadingAudios.value) return;
        if (!estudioProductoId) {
            audiosDeEstudio.value = [];
            return;
        }
        loadingAudios.value = true;
        try {
            const res = await axios.get("/api/listar-audios-estudio/" + estudioProductoId);
            audiosDeEstudio.value = res.data.data || [];
        } catch (e) {
            audiosDeEstudio.value = [];
        } finally {
            loadingAudios.value = false;
        }
    }

    async function imprimirReporte(estudio) {
        if (loadingImprimir.value) return;
        loadingImprimir.value = estudio.estudio_producto_id;
        try {
            if (typeof $ !== "undefined" && $.toast) {
                $.toast({
                    heading: "Procesando...",
                    text: "Generando reportes, por favor espere",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "info",
                    hideAfter: 3500,
                    stack: 6,
                });
            }

            const res = await axios.post("/api/edit-word", estudio);

            window.open(
                `/descargar.pdf.lectura/${res.data.data.plantilla}`,
                "_blank",
                "noopener,noreferrer"
            );
        } catch (error) {
        } finally {
            loadingImprimir.value = null;
        }
    }

    async function anularAsignacion(estudio, dtRef, onExito) {
        if (loadingAnular.value) return;

        const confirmar = confirm("¿Está seguro que desea anular la asignación de este estudio?");
        if (!confirmar) return;

        loadingAnular.value = estudio.estudio_producto_id;
        try {
            const res = await axios.post("/api/estudio-anular-asignacion", {
                id: estudio.estudio_producto_id,
            });

            if (res.status === 200) {
                toastMessage({
                    heading: "Completado",
                    text: res.data.message,
                    icon: "success",
                });

                safeRemoveDataTableRow(dtRef, "#row-" + estudio.estudio_producto_id);

                if (typeof onExito === "function") {
                    onExito(estudio);
                }
            }
        } catch (error) {
        } finally {
            loadingAnular.value = null;
        }
    }

    function verImagen(url, patId, studyUID, serverName, estudioProductoId = null) {
        if (estudioProductoId !== null) {
            if (loadingVerImagen.value) return;
            loadingVerImagen.value = estudioProductoId;
        } else {
            if (loadingVerImagen.value) return;
            loadingVerImagen.value = true;
        }

        try {
            const params = new URLSearchParams({
                patientID: String(patId ?? ""),
                studyUID: String(studyUID ?? ""),
                serverName: String(serverName ?? ""),
            });
            window.open(`${url}?${params.toString()}`, "_blank", "noopener,noreferrer");
        } finally {
            setTimeout(() => {
                loadingVerImagen.value = null;
            }, 500);
        }
    }

    return {
        loadingDescarga,
        loadingAudios,
        loadingImprimir,
        loadingAnular,
        loadingVerImagen,
        audiosDeEstudio,
        formatDate,
        descargaSoporte,
        listarAudios,
        imprimirReporte,
        anularAsignacion,
        verImagen,
    };
}
