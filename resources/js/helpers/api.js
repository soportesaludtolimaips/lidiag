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

export const consentimientos = {
    // Combos
    productos: () => axios.get(`${BASE}/config-productos`),
    sedesActivas: () => axios.get(`${BASE}/config-sedes/listar/sedes/activas`),

    // Documentos
    buscarPaciente: (numDocu) =>
        axios.get(`${BASE}/consentimientos/buscar-paciente`, { params: { num_docu: numDocu } }),
    medicos: () => axios.get(`${BASE}/consentimientos/medicos`),
    listar: (params) => axios.get(`${BASE}/consentimientos`, { params }),
    ver: (id) => axios.get(`${BASE}/consentimientos/${id}`),
    generar: (data) => axios.post(`${BASE}/consentimientos`, data),
    crearToken: (data) => axios.post(`${BASE}/consentimientos/token`, data),
    firmarMedico: (data) => axios.post(`${BASE}/consentimientos/firmar-medico`, data),
    anular: (id, data) => axios.post(`${BASE}/consentimientos/${id}/anular`, data),
    restaurar: (id) => axios.post(`${BASE}/consentimientos/${id}/restaurar`),
    generarPdf: (id) => axios.post(`${BASE}/consentimientos/${id}/pdf`),
    descargarPdfUrl: (id) => `${BASE}/consentimientos/${id}/pdf/download`,

    // Marcas
    marcas: (docId) => axios.get(`${BASE}/consentimientos/${docId}/marcas`),
    crearMarca: (docId, data) => axios.post(`${BASE}/consentimientos/${docId}/marcas`, data),
    editarMarca: (id, data) => axios.put(`${BASE}/consentimientos/marcas/${id}`, data),
    eliminarMarca: (id) => axios.delete(`${BASE}/consentimientos/marcas/${id}`),

    // Adjuntos
    adjuntos: (docId) => axios.get(`${BASE}/consentimientos/${docId}/adjuntos`),
    subirAdjunto: (docId, fd) =>
        axios.post(`${BASE}/consentimientos/${docId}/adjuntos`, fd, {
            headers: { "Content-Type": "multipart/form-data" },
        }),
    descargarAdjuntoUrl: (id) => `${BASE}/consentimientos/adjuntos/${id}/download`,
    eliminarAdjunto: (id) => axios.delete(`${BASE}/consentimientos/adjuntos/${id}`),

    // Plantillas + versiones + comparar
    modulos: () => axios.get(`${BASE}/config-modulos-listar`),
    plantillas: (params) => axios.get(`${BASE}/consentimientos-plantillas`, { params }),
    plantillaVersiones: (productoId, params) =>
        axios.get(`${BASE}/consentimientos-plantillas/producto/${productoId}/versions`, { params }),
    plantillaComparar: (v1, v2) =>
        axios.get(`${BASE}/consentimientos-plantillas/comparar`, { params: { v1, v2 } }),
    actualizarFormato: (id, data) =>
        axios.put(`${BASE}/consentimientos-plantillas/${id}/formato`, data),

    // Asignación plantilla ↔ productos/sedes (M:N)
    asignaciones: (plantillaId) =>
        axios.get(`${BASE}/consentimientos-plantillas/${plantillaId}/productos`),
    asignar: (plantillaId, data) =>
        axios.post(`${BASE}/consentimientos-plantillas/${plantillaId}/productos`, data),
    desasignar: (id) => axios.delete(`${BASE}/consentimientos-plantillas/productos/${id}`),

    // Imágenes de plantilla (marcables)
    imagenes: (plantillaId) =>
        axios.get(`${BASE}/consentimientos-plantillas/${plantillaId}/imagenes`),
    subirImagen: (plantillaId, fd) =>
        axios.post(`${BASE}/consentimientos-plantillas/${plantillaId}/imagenes`, fd, {
            headers: { "Content-Type": "multipart/form-data" },
        }),
    verImagenUrl: (id) => `${BASE}/consentimientos-plantillas/imagenes/${id}/ver`,
    eliminarImagen: (id) => axios.delete(`${BASE}/consentimientos-plantillas/imagenes/${id}`),

    // Bloqueo médico
    bloqueoEstado: () => axios.get(`${BASE}/consentimientos/bloqueo/estado`),
    liberarBloqueo: (data) => axios.post(`${BASE}/consentimientos/bloqueo/liberar`, data),

    // Estadísticas
    estadResumen: (params) => axios.get(`${BASE}/consentimientos/estadisticas/resumen`, { params }),
    estadSede: (params) => axios.get(`${BASE}/consentimientos/estadisticas/sede`, { params }),
    estadProducto: (params) => axios.get(`${BASE}/consentimientos/estadisticas/producto`, { params }),
    estadMedico: (params) => axios.get(`${BASE}/consentimientos/estadisticas/medico`, { params }),
    estadTiempo: (params) =>
        axios.get(`${BASE}/consentimientos/estadisticas/tiempo-promedio`, { params }),
    estadPopulares: (params) =>
        axios.get(`${BASE}/consentimientos/estadisticas/productos-populares`, { params }),
    estadExportarUrl: (params) => {
        const qs = new URLSearchParams(params ?? {}).toString();
        return `${BASE}/consentimientos/estadisticas/exportar${qs ? "?" + qs : ""}`;
    },
};
