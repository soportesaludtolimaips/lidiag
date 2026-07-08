/**
 * Wrapper simple para SweetAlert (swal) con fallback.
 * La plantilla carga `swal` globalmente; en caso contrario usamos alert/confirm.
 */

function getSwal() {
    if (typeof window === "undefined") return null;
    return window.swal || window.Swal || null;
}

function getToast() {
    if (typeof window === "undefined") return null;
    const $ = window.$ || window.jQuery;
    if (!$ || typeof $.toast !== "function") return null;
    return $.toast;
}

export function alertSuccess(title = "Ok", text = "", icon = "success") {
    const swal = getSwal();
    if (swal) return swal(title, text, icon);
    window.alert([title, text].filter(Boolean).join("\n"));
}

export function alertError(title = "Error", text = "", icon = "error") {
    const swal = getSwal();
    if (swal) return swal(title, text, icon);
    window.alert([title, text].filter(Boolean).join("\n"));
}

export function alertInfo(title = "Info", text = "", icon = "info") {
    const swal = getSwal();
    if (swal) return swal(title, text, icon);
    window.alert([title, text].filter(Boolean).join("\n"));
}

/**
 * Confirmación clásica; devuelve Promise<boolean>.
 * Con swal: resuelve true/false según confirmación.
 */
export function confirmAction(options) {
    const swal = getSwal();
    return new Promise((resolve) => {
        swal(
            {
                title: options.title,
                text: options.text,
                type: options.icon || "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Sí, continuar",
                cancelButtonText: "Cancelar",
                closeOnConfirm: true,
            },
            function (isConfirm) {
                // <--- SE USA UN CALLBACK COMO SEGUNDO PARÁMETRO
                resolve(isConfirm);
            }
        );
    });
}

/**
 * Toast uniforme (si la plantilla carga $.toast).
 */
export function toastMessage({
    heading = "Info",
    text = "",
    icon = "info",
    position = "top-right",
    hideAfter = 3500,
} = {}) {
    const toast = getToast();
    if (!toast) return;

    toast({
        heading,
        text,
        position,
        loaderBg: "#ff6849",
        icon,
        hideAfter,
        stack: 6,
    });
}

const PATRONES_PII = [
    /^\s*\{/,
    /(\/[\w\-]+){2,}/,
    /[Tt]race/,
    /#\d+\s+[\w\\]+/,
    /SQLSTATE|syntax error|unexpected|stack trace/i,
];

function sanitizeServerMessage(message) {
    if (typeof message !== "string" || message.trim().length === 0) {
        return null;
    }
    if (message.length > 200) {
        return null;
    }
    if (PATRONES_PII.some((re) => re.test(message))) {
        return null;
    }
    return message.trim();
}

export function getUserSafeError(error, fallback = "Ocurrió un error.") {
    const message = error?.response?.data?.message || error?.message || "";
    return sanitizeServerMessage(message) || fallback;
}

/**
 * Manejo estándar de errores Axios (401/403 + fallback).
 */
export function handleAxiosErrorToast(error, fallbackMessage = "Ocurrió un error.") {
    const status = error?.response?.status;

    if (status === 401) {
        toastMessage({
            heading: "Sesión expirada",
            text: "Tu sesión expiró. Inicia sesión nuevamente.",
            icon: "warning",
        });
        return;
    }

    if (status === 403) {
        toastMessage({
            heading: "No autorizado",
            text: "No tienes permisos para realizar esta acción.",
            icon: "error",
        });
        return;
    }

    toastMessage({
        heading: "Upss!!!",
        text: getUserSafeError(error, fallbackMessage),
        icon: "error",
    });
}
