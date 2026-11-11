import axios from "axios";

/**
 * Infiere el id del modulo actual a partir de la ruta (ecografias=2, estudios=1).
 *
 * @param string $pathname Ruta de la URL actual.
 *
 * @returns {string}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-05-14
 */
function inferModuloIdFromPath(pathname = "") {
    const p = String(pathname || "").toLowerCase();
    // Heurística actual del proyecto:
    // - ecografías suelen usar módulo 2
    // - estudios suelen usar módulo 1
    if (p.includes("ecografia")) return "2";
    if (p.includes("estudios") || p.includes("study")) return "1";
    if (p.includes("tele-radiologia") || p.includes("tele-radiologia")) return "1";
    return "1";
}

/**
 * Obtiene el id del modulo actual desde sessionStorage o lo infiere de la ruta.
 *
 * @returns {string}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-05-14
 */
export function getModuloIdActual() {
    try {
        const stored = sessionStorage.getItem("ST-modulo");
        if (stored) return String(stored);
        const inferred = inferModuloIdFromPath(window?.location?.pathname || "");
        sessionStorage.setItem("ST-modulo", inferred);
        return inferred;
    } catch (_) {
        return "1";
    }
}

/**
 * Lista los usuarios asignadores (Administrador, Administrativo, Transcriptor) y médicos del módulo actual y los asigna al contexto.
 *
 * Flujo del proceso:
 * 1. Determina el módulo actual (a través de options.moduloId o inferido de sessionStorage/ruta).
 * 2. Si se suministra options.sedeId, prepara el parámetro de consulta para acotar los asignadores a dicha sede.
 * 3. Consulta los usuarios asignadores mediante endpoint /api/user.listarUsuarios/Administrador,Administrativo,Transcriptor/{moduloId}?solo_activos=1(&sede_id=X).
 * 4. Si no obtiene datos o falla la consulta, recurre al fallback de Transcriptor respetando el filtro de sede.
 * 5. Consulta los médicos asociados al módulo vía /api/user.listarUsuarios/Medico/{moduloId}?solo_activos=1(&sede_id=X).
 * 6. Normaliza y asigna de forma segura los datos en context.quienes_asignaron y context.medicos.
 *
 * @param {Object} context Contexto con setters/propiedades quienes_asignaron y medicos.
 * @param {Object} options Opciones de configuración (ej. moduloId, sedeId).
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-29
 */
export async function listarUsuariosYSedes(context, options = {}) {
    const moduloId = String(options.moduloId ?? getModuloIdActual() ?? "1");
    const sedeParam = options.sedeId ? `&sede_id=${encodeURIComponent(options.sedeId)}` : "";

    try {
        const res = await axios.get(
            `/api/user.listarUsuarios/Administrador,Administrativo,Transcriptor,Transcriptor RX/${moduloId}?solo_activos=1${sedeParam}`
        );
        const data = Array.isArray(res?.data?.data)
            ? res.data.data
            : Array.isArray(res?.data)
              ? res.data
              : [];

        if (data.length > 0) {
            context.quienes_asignaron = data;
        } else {
            const fallbackRes = await axios.get(
                `/api/user.listarUsuarios/Transcriptor,Transcriptor RX/${moduloId}?solo_activos=1${sedeParam}`
            );
            context.quienes_asignaron = Array.isArray(fallbackRes?.data?.data)
                ? fallbackRes.data.data
                : Array.isArray(fallbackRes?.data)
                  ? fallbackRes.data
                  : [];
        }
    } catch (_) {
        try {
            const fallbackRes = await axios.get(
                `/api/user.listarUsuarios/Transcriptor,Transcriptor RX/${moduloId}?solo_activos=1${sedeParam}`
            );
            context.quienes_asignaron = Array.isArray(fallbackRes?.data?.data)
                ? fallbackRes.data.data
                : Array.isArray(fallbackRes?.data)
                  ? fallbackRes.data
                  : [];
        } catch (_) {
            context.quienes_asignaron = [];
        }
    }

    try {
        const resMedicos = await axios.get(
            `/api/user.listarUsuarios/Medico/${moduloId}?solo_activos=1${sedeParam}`
        );
        context.medicos = Array.isArray(resMedicos?.data?.data)
            ? resMedicos.data.data
            : Array.isArray(resMedicos?.data)
              ? resMedicos.data
              : [];
    } catch (_) {
        context.medicos = [];
    }
}
