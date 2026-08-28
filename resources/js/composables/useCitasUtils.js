/**
 * Formatea una fecha en formato legible DD/MM/AAAA HH:mm.
 *
 * @param {*} fecha Valor de fecha a formatear (cadena, Date o timestamp).
 *
 * @returns {string} Fecha formateada o guion (-) si no se proporciona o es invalida.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-09
 */
export function formatFecha(fecha) {
    if (!fecha) return "-";
    const d = new Date(fecha);
    if (isNaN(d.getTime())) return fecha;
    const dia = String(d.getDate()).padStart(2, "0");
    const mes = String(d.getMonth() + 1).padStart(2, "0");
    const anio = d.getFullYear();
    const horas = String(d.getHours()).padStart(2, "0");
    const minutos = String(d.getMinutes()).padStart(2, "0");
    return `${dia}/${mes}/${anio} ${horas}:${minutos}`;
}

/**
 * Devuelve la etiqueta legible en espanol correspondiente a un estado de cita.
 *
 * @param {string} estado Clave del estado de la cita (solicitada, asignada, confirmada, etc.).
 *
 * @returns {string} Etiqueta del estado o el mismo valor recibido si no se reconoce.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-09
 */
export function getEstadoLabel(estado) {
    const labels = {
        solicitada: "Solicitada",
        asignada: "Asignada",
        confirmada: "Confirmada",
        cumplida: "Cumplida",
        cancelada: "Cancelada",
        no_asistio: "No Asistió",
        pendiente: "Pendiente",
        vencida: "Vencida",
    };
    return labels[estado] || estado;
}

/**
 * Devuelve la clase CSS (badge) asociada a un estado de cita.
 *
 * @param {string} estado Clave del estado de la cita.
 *
 * @returns {string} Clase CSS del badge correspondiente al estado.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-09
 */
export function getEstadoClase(estado) {
    const clases = {
        solicitada: "badge-warning",
        asignada: "badge-info",
        confirmada: "badge-success",
        cumplida: "badge-primary",
        cancelada: "badge-danger",
        no_asistio: "badge-secondary",
        pendiente: "badge-warning",
        vencida: "badge-dark",
    };
    return clases[estado] || "badge-secondary";
}
