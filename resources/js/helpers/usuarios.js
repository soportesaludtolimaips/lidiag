import axios from "axios";

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

export async function listarUsuariosYSedes(context, options = {}) {
    const moduloId = String(options.moduloId ?? getModuloIdActual() ?? "1");

    const res = await axios.get(`/api/user.listarUsuarios/Transcriptor/${moduloId}`);
    context.quienes_asignaron = res.data;

    const resMedicos = await axios.get(`/api/user.listarUsuarios/Medico/${moduloId}`);
    context.medicos = resMedicos.data;
}
