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
 * Lista los usuarios transcritores y medicos del modulo actual y los asigna al contexto.
 *
 * @param object $context Contexto donde se asignan quienes_asignaron y medicos.
 * @param object $options Opciones (moduloId opcional).
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-05-14
 */
export async function listarUsuariosYSedes(context, options = {}) {
    const moduloId = String(options.moduloId ?? getModuloIdActual() ?? "1");

    const res = await axios.get(`/api/user.listarUsuarios/Transcriptor/${moduloId}`);
    context.quienes_asignaron = res.data.data;

    const resMedicos = await axios.get(`/api/user.listarUsuarios/Medico/${moduloId}`);
    context.medicos = resMedicos.data.data;
}
