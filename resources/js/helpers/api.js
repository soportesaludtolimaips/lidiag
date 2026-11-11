const BASE = "/api";

/**
 * Cliente de API para el modulo de Helpdesk (catalogos, tickets, reportes y notificaciones).
 *
 * @returns {object}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-09
 */
export const helpdesk = {
    // Catálogos
    /**
     * Obtiene la lista de categorias del helpdesk.
     *
     * @returns {Promise}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-09
     */
    categorias: () => axios.get(`${BASE}/helpdesk-categorias`),
    /**
     * Obtiene la lista de prioridades del helpdesk.
     *
     * @returns {Promise}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-09
     */
    prioridades: () => axios.get(`${BASE}/helpdesk-prioridades`),
    /**
     * Obtiene la lista de estados del helpdesk.
     *
     * @returns {Promise}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-09
     */
    estados: () => axios.get(`${BASE}/helpdesk-estados`),
    /**
     * Obtiene la lista de usuarios asignables del helpdesk.
     *
     * @returns {Promise}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-09
     */
    asignables: () => axios.get(`${BASE}/helpdesk-usuarios-asignables`),
    /**
     * Obtiene la lista de sedes activas.
     *
     * @returns {Promise}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-09
     */
    sedesActivas: () => axios.get(`${BASE}/config-sedes/listar/sedes/activas`),

    // Tickets
    /**
     * Lista los tickets del helpdesk segun los filtros dados.
     *
     * @param object $params Parametros de filtrado y paginacion.
     *
     * @returns {Promise}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-09
     */
    tickets: (params) => axios.get(`${BASE}/helpdesk-tickets`, { params }),
    /**
     * Obtiene el detalle de un ticket por su id.
     *
     * @param mixed $id Identificador del ticket.
     *
     * @returns {Promise}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-09
     */
    ticket: (id) => axios.get(`${BASE}/helpdesk-tickets/${id}`),
    /**
     * Crea un nuevo ticket en el helpdesk.
     *
     * @param object $data Datos del ticket a crear.
     *
     * @returns {Promise}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-09
     */
    crearTicket: (data) => axios.post(`${BASE}/helpdesk-tickets`, data),
    /**
     * Agrega un comentario a un ticket.
     *
     * @param mixed $id Identificador del ticket.
     * @param object $data Datos del comentario.
     *
     * @returns {Promise}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-09
     */
    comentar: (id, data) => axios.post(`${BASE}/helpdesk-tickets/${id}/comment`, data),
    /**
     * Cambia el estado de un ticket.
     *
     * @param mixed $id Identificador del ticket.
     * @param object $data Nuevo estado y datos asociados.
     *
     * @returns {Promise}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-09
     */
    cambiarEstado: (id, data) => axios.post(`${BASE}/helpdesk-tickets/${id}/status`, data),
    /**
     * Reasigna un ticket a otro usuario.
     *
     * @param mixed $id Identificador del ticket.
     * @param object $data Datos de reasignacion.
     *
     * @returns {Promise}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-09
     */
    reasignar: (id, data) => axios.post(`${BASE}/helpdesk-tickets/${id}/reassign`, data),
    /**
     * Sube adjuntos a un ticket mediante FormData.
     *
     * @param mixed $id Identificador del ticket.
     * @param object $fd FormData con los archivos.
     *
     * @returns {Promise}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-09
     */
    subirAdjuntos: (id, fd) =>
        axios.post(`${BASE}/helpdesk-tickets/${id}/attachments`, fd, {
            headers: { "Content-Type": "multipart/form-data" },
        }),
    /**
     * Devuelve la URL de descarga de un adjunto.
     *
     * @param mixed $id Identificador del adjunto.
     *
     * @returns {string}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-09
     */
    descargarAdjunto: (id) => `${BASE}/helpdesk-adjuntos/${id}/download`,

    // Reportes
    /**
     * Obtiene indicadores del reporte del helpdesk.
     *
     * @param object $params Parametros del reporte.
     *
     * @returns {Promise}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-09
     */
    indicadores: (params) => axios.get(`${BASE}/helpdesk-reportes/indicadores`, { params }),
    /**
     * Obtiene el reporte detallado del helpdesk.
     *
     * @param object $params Parametros del reporte.
     *
     * @returns {Promise}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-09
     */
    detallado: (params) => axios.get(`${BASE}/helpdesk-reportes/detallado`, { params }),
    /**
     * Devuelve la URL de exportacion a Excel del reporte.
     *
     * @param object $params Parametros del reporte.
     *
     * @returns {string}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-09
     */
    exportExcelUrl: (params) => `${BASE}/helpdesk-reportes/export/excel?${params.toString()}`,
    /**
     * Devuelve la URL de exportacion a PDF del reporte.
     *
     * @param object $params Parametros del reporte.
     *
     * @returns {string}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-09
     */
    exportPdfUrl: (params) => `${BASE}/helpdesk-reportes/export/pdf?${params.toString()}`,

    // Notificaciones
    /**
     * Obtiene las notificaciones del helpdesk.
     *
     * @param object $params Parametros de filtrado.
     *
     * @returns {Promise}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-09
     */
    notificaciones: (params) => axios.get(`${BASE}/helpdesk-notificaciones`, { params }),
    /**
     * Marca una notificacion como leida.
     *
     * @param mixed $id Identificador de la notificacion.
     *
     * @returns {Promise}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-09
     */
    marcarLeida: (id) => axios.post(`${BASE}/helpdesk-notificaciones/${id}/read`),
    /**
     * Marca todas las notificaciones como leidas.
     *
     * @returns {Promise}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-09
     */
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
    estadProducto: (params) =>
        axios.get(`${BASE}/consentimientos/estadisticas/producto`, { params }),
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
