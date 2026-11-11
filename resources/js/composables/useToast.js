const DEFAULTS = { position: "top-right", hideAfter: 3500, stack: 6 };

const LOADER_COLORS = {
    success: "#28a745",
    error: "#dc3545",
    warning: "#ffc107",
    info: "#17a2b8",
};

function show(heading, text, icon) {
    const $ = window.$ || window.jQuery;
    if (!$ || typeof $.toast !== "function") return;

    $.toast({
        heading,
        text: text || "",
        icon: icon || "info",
        position: DEFAULTS.position,
        loaderBg: LOADER_COLORS[icon] || "#ff6849",
        hideAfter: DEFAULTS.hideAfter,
        stack: DEFAULTS.stack,
    });
}

/**
 * Proporciona funciones para mostrar notificaciones (toast) de exito, error, advertencia e informacion mediante jQuery Toast.
 *
 * @returns {Object} Funciones show, success, error, warning e info para mostrar notificaciones.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-14
 */
export function useToast() {
    function success(text, heading = "Ok") {
        show(heading, text, "success");
    }

    function error(text, heading = "Error") {
        show(heading, text, "error");
    }

    function warning(text, heading = "Atención") {
        show(heading, text, "warning");
    }

    function info(text, heading = "Información") {
        show(heading, text, "info");
    }

    return { show, success, error, warning, info };
}
