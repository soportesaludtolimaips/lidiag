const BASE = "/api";

export const helpdesk = {
    // Catálogos
    categorias: () => axios.get(`${BASE}/helpdesk-categorias`),
    prioridades: () => axios.get(`${BASE}/helpdesk-prioridades`),
    estados: () => axios.get(`${BASE}/helpdesk-estados`),
    asignables: () => axios.get(`${BASE}/helpdesk-usuarios-asignables`),
    sedesActivas: () => axios.get(`${BASE}/config-sedes/listar/sedes/activas`),

    // Tickets
    tickets: (params) => axios.get(`${BASE}/helpdesk-tickets`, { params }),
    ticket: (id) => axios.get(`${BASE}/helpdesk-tickets/${id}`),
    crearTicket: (data) => axios.post(`${BASE}/helpdesk-tickets`, data),
    comentar: (id, data) => axios.post(`${BASE}/helpdesk-tickets/${id}/comment`, data),
    cambiarEstado: (id, data) => axios.post(`${BASE}/helpdesk-tickets/${id}/status`, data),
    reasignar: (id, data) => axios.post(`${BASE}/helpdesk-tickets/${id}/reassign`, data),
    subirAdjuntos: (id, fd) =>
        axios.post(`${BASE}/helpdesk-tickets/${id}/attachments`, fd, {
            headers: { "Content-Type": "multipart/form-data" },
        }),
    descargarAdjunto: (id) => `${BASE}/helpdesk-adjuntos/${id}/download`,

    // Reportes
    indicadores: (params) => axios.get(`${BASE}/helpdesk-reportes/indicadores`, { params }),
    detallado: (params) => axios.get(`${BASE}/helpdesk-reportes/detallado`, { params }),
    exportExcelUrl: (params) => `${BASE}/helpdesk-reportes/export/excel?${params.toString()}`,
    exportPdfUrl: (params) => `${BASE}/helpdesk-reportes/export/pdf?${params.toString()}`,

    // Notificaciones
    notificaciones: (params) => axios.get(`${BASE}/helpdesk-notificaciones`, { params }),
    marcarLeida: (id) => axios.post(`${BASE}/helpdesk-notificaciones/${id}/read`),
    marcarTodasLeidas: () => axios.post(`${BASE}/helpdesk-notificaciones/read-all`),
};
