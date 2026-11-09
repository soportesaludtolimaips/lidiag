// Polyfill defensivo para bug interno de SweetAlert 1 (ReferenceError: logStr is not defined)
if (typeof window !== "undefined" && typeof window.logStr === "undefined") {
    window.logStr = function (msg) {
        if (typeof console !== "undefined" && console.warn) {
            console.warn("SweetAlert:", msg);
        }
    };
}

const VALID_SWAL_TYPES = ["warning", "error", "info", "success", "input", "prompt"];

/**
 * Normaliza y valida el tipo/icono para SweetAlert 1.
 * Evita excepciones internas por tipos desconocidos.
 *
 * @param {string} [type] Tipo o icono sugerido.
 * @param {string} [fallback] Tipo por defecto en caso de invalidez.
 *
 * @returns {string} Tipo compatible con SweetAlert 1.
 */
function normalizeSwalType(type, fallback = "warning") {
    if (!type || typeof type !== "string") return fallback;
    const lower = type.toLowerCase().trim();
    if (lower === "danger") return "error";
    if (lower === "question") return "info";
    return VALID_SWAL_TYPES.includes(lower) ? lower : fallback;
}

/**
 * Wrapper simple para SweetAlert (swal) con fallback.
 * La plantilla carga `swal` globalmente; en caso contrario usamos alert/confirm.
 */

/**
 * Obtiene la funcion SweetAlert global (swal o Swal) o null si no esta disponible.
 *
 * @returns {function|null}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-03
 */
function getSwal() {
    if (typeof window === "undefined") return null;
    return window.swal || window.Swal || null;
}

/**
 * Obtiene la funcion $.toast global si esta disponible, o null en caso contrario.
 *
 * @returns {function|null}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-03
 */
function getToast() {
    if (typeof window === "undefined") return null;
    const $ = window.$ || window.jQuery;
    if (!$ || typeof $.toast !== "function") return null;
    return $.toast;
}

/**
 * Muestra una alerta de exito no invasiva tipo toast flotante por defecto; si no esta disponible usa SweetAlert/alert.
 *
 * @param string $title Titulo de la alerta.
 * @param string $text Texto descriptivo de la alerta.
 * @param string $icon Tipo de icono a mostrar (por defecto success).
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-19
 */
export function alertSuccess(title = "Ok", text = "", icon = "success") {
    const swalIcon = normalizeSwalType(icon, "success");
    const toast = getToast();
    if (toast) {
        toastMessage({
            heading: title,
            text,
            icon: swalIcon,
            position: "top-right",
            hideAfter: 3500,
        });
        return;
    }
    const swal = getSwal();
    if (swal) return swal(title, text, swalIcon);
    window.alert([title, text].filter(Boolean).join("\n"));
}

/**
 * Muestra una alerta modal interactiva de SweetAlert obligatoriamente (sin redirigir a toast).
 *
 * @param {string} title Titulo de la alerta.
 * @param {string} text Texto descriptivo de la alerta.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-27
 */
export function swalSuccess(title = "¡Completado!", text = "") {
    const swal = getSwal();
    if (swal) {
        return swal({
            title,
            text,
            type: "success",
            confirmButtonColor: "#26c6da",
            confirmButtonText: "Aceptar",
        });
    }
    window.alert([title, text].filter(Boolean).join("\n"));
}

/**
 * Muestra una alerta de error utilizando SweetAlert si esta disponible; de lo contrario usa alert().
 *
 * @param string $title Titulo de la alerta.
 * @param string $text Texto descriptivo de la alerta.
 * @param string $icon Tipo de icono a mostrar (por defecto error).
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-03
 */
export function alertError(title = "Error", text = "", icon = "error") {
    const swal = getSwal();
    const swalIcon = normalizeSwalType(icon, "error");
    if (swal) return swal(title, text, swalIcon);
    window.alert([title, text].filter(Boolean).join("\n"));
}

/**
 * Muestra una alerta informativa utilizando SweetAlert si esta disponible; de lo contrario usa alert().
 *
 * @param string $title Titulo de la alerta.
 * @param string $text Texto descriptivo de la alerta.
 * @param string $icon Tipo de icono a mostrar (por defecto info).
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-03
 */
export function alertInfo(title = "Info", text = "", icon = "info") {
    const swal = getSwal();
    const swalIcon = normalizeSwalType(icon, "info");
    if (swal) return swal(title, text, swalIcon);
    window.alert([title, text].filter(Boolean).join("\n"));
}

/**
 * Muestra una alerta de advertencia utilizando SweetAlert si esta disponible; de lo contrario usa alert().
 *
 * @param string $title Titulo de la alerta.
 * @param string $text Texto descriptivo de la alerta.
 * @param string $icon Tipo de icono a mostrar (por defecto warning).
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-16
 */
export function alertWarning(title = "Advertencia", text = "", icon = "warning") {
    const swal = getSwal();
    const swalIcon = normalizeSwalType(icon, "warning");
    if (swal) return swal(title, text, swalIcon);
    window.alert([title, text].filter(Boolean).join("\n"));
}

/**
 * Solicita confirmacion al usuario y devuelve una promesa que resuelve true o false segun la decision.
 * Admite tanto llamada por objeto de opciones `confirmAction({ title, text, icon })` como
 * llamada posicional `confirmAction(title, text, [icon])` o `confirmAction(text, [icon])`.
 *
 * @param {object|string} options Opciones de la confirmacion o titulo/texto.
 * @param {string} [textOrIcon] Texto descriptivo o icono.
 * @param {string} [maybeIcon] Icono si se pasaron 3 argumentos.
 *
 * @returns {Promise<boolean>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-03
 */
export function confirmAction(options, textOrIcon, maybeIcon) {
    let config = {
        title: "¿Confirmar Acción?",
        text: "¿Estás seguro?",
        icon: "warning",
        confirmButtonText: "Sí, continuar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#DD6B55",
    };

    if (typeof options === "string") {
        if (
            typeof textOrIcon === "string" &&
            !VALID_SWAL_TYPES.includes(textOrIcon.toLowerCase().trim())
        ) {
            // Se invoco como confirmAction(title, text, [icon])
            config.title = options;
            config.text = textOrIcon;
            config.icon = normalizeSwalType(maybeIcon, "warning");
        } else {
            // Se invoco como confirmAction(text, [icon])
            config.text = options;
            config.icon = normalizeSwalType(textOrIcon, "warning");
        }
    } else if (typeof options === "object" && options !== null) {
        config = {
            title: options.title || config.title,
            text: options.text || config.text,
            icon: normalizeSwalType(options.icon || options.type, "warning"),
            confirmButtonText: options.confirmButtonText || config.confirmButtonText,
            cancelButtonText: options.cancelButtonText || config.cancelButtonText,
            confirmButtonColor: options.confirmButtonColor || config.confirmButtonColor,
            html: Boolean(options.html),
        };
    }

    const swal = getSwal();
    if (!swal) {
        return Promise.resolve(window.confirm(config.text || config.title || "¿Estás seguro?"));
    }
    return new Promise((resolve) => {
        swal(
            {
                title: config.title,
                text: config.text,
                type: config.icon,
                html: config.html,
                showCancelButton: true,
                confirmButtonColor: config.confirmButtonColor,
                confirmButtonText: config.confirmButtonText,
                cancelButtonText: config.cancelButtonText,
                closeOnConfirm: true,
            },
            function (isConfirm) {
                // <--- SE USA UN CALLBACK COMO SEGUNDO PARÁMETRO
                resolve(Boolean(isConfirm));
            }
        );
    });
}

/**
 * Muestra una notificacion tipo toast si la plantilla carga $.toast; de lo contrario no hace nada.
 *
 * @param object $options Opciones del toast (heading, text, icon, position, hideAfter).
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-03
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
    /(\/[\w-]+){2,}/,
    /[Tt]race/,
    /#\d+\s+[\w\\]+/,
    /SQLSTATE|syntax error|unexpected|stack trace/i,
];

/**
 * Sanea un mensaje de servidor eliminando texto que pueda contener informacion sensible (PII).
 *
 * @param string $message Mensaje recibido del servidor.
 *
 * @returns {string|null}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-03
 */
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

/**
 * Devuelve un mensaje de error seguro para el usuario, filtrando informacion sensible del servidor.
 *
 * @param object $error Objeto de error (tipicamente de Axios).
 * @param string $fallback Mensaje por defecto si no se puede extraer uno seguro.
 *
 * @returns {string}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-03
 */
export function getUserSafeError(error, fallback = "Ocurrió un error.") {
    const message = error?.response?.data?.message || error?.message || "";
    return sanitizeServerMessage(message) || fallback;
}

/**
 * (Obsoleto) Registra una advertencia de deprecacion para errores de Axios; el interceptor de bootstrap.js ya maneja el toast automaticamente.
 *
 * @param object $error Objeto de error de Axios.
 * @param string _fallbackMessage Mensaje de respaldo (no utilizado).
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-03
 */
export function handleAxiosErrorToast(error, _fallbackMessage = "Ocurrió un error.") {
    // Deprecated — the Axios interceptor in bootstrap.js now handles toast automatically.
    // Kept for compatibility with existing callers.
    console.warn(
        "[alerts] handleAxiosErrorToast is deprecated. Remove this call.",
        error?.config?.url
    );
}
