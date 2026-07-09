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
