import _ from "lodash";
window._ = _;

import "bootstrap";

import axios from "axios";
window.axios = axios;

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
window.axios.defaults.withCredentials = true;

function refreshCsrfToken() {
    const name = "XSRF-TOKEN";
    const match = document.cookie.match(new RegExp("(^| )" + name + "=([^;]+)"));
    if (match && match[2]) {
        window.axios.defaults.headers.common["X-XSRF-TOKEN"] = decodeURIComponent(match[2]);
    }
}

const metaToken = document.head?.querySelector('meta[name="csrf-token"]');
if (metaToken && metaToken.content) {
    window.axios.defaults.headers.common["X-CSRF-TOKEN"] = metaToken.content;
}

refreshCsrfToken();

let _redirectingToLogin = false;
let _verifyingSession = false;

async function confirmSessionExpired() {
    if (_verifyingSession) return false;
    _verifyingSession = true;
    try {
        const check = await axios.get("/api/debug-session", {
            headers: { "X-Verify-Session": "1" },
        });
        return check.data?.auth_check === false;
    } catch {
        return true;
    } finally {
        _verifyingSession = false;
    }
}

function redirectToLogin() {
    if (_redirectingToLogin) return;
    _redirectingToLogin = true;
    const toast = window.$?.toast;
    if (toast) {
        toast({
            heading: "Sesión expirada",
            text: "Tu sesión expiró. Redirigiendo al login...",
            position: "top-right",
            loaderBg: "#ff6849",
            icon: "warning",
            hideAfter: 3000,
            stack: 6,
        });
    }
    setTimeout(function () {
        window.location.href = "/login";
    }, 3000);
}

window.axios.interceptors.response.use(
    function (response) {
        refreshCsrfToken();

        return response;
    },
    function (error) {
        const status = error?.response?.status;
        const isVerifyRequest = error?.config?.headers?.["X-Verify-Session"] === "1";

        if (status === 401 && !_redirectingToLogin && !isVerifyRequest) {
            confirmSessionExpired().then((expired) => {
                if (expired) {
                    redirectToLogin();
                } else {
                    console.warn("[Auth] 401 en", error.config?.url, "- sesión aún válida");
                }
            });
            return Promise.reject(error);
        }

        if (status) {
            const map = {
                400: { heading: "Solicitud incorrecta", icon: "warning" },
                401: { heading: "Sesión expirada", icon: "warning" },
                403: { heading: "No autorizado", icon: "error" },
                404: { heading: "No encontrado", icon: "warning" },
                422: { heading: "Error de validación", icon: "warning" },
                429: { heading: "Demasiadas solicitudes", icon: "warning" },
                500: { heading: "Error interno", icon: "error" },
                503: { heading: "Servicio no disponible", icon: "error" },
            };

            const entry = map[status] || { heading: "Error inesperado", icon: "error" };
            const text = error.response?.data?.message || "";

            import("@/composables/useToast.js").then(({ useToast }) => {
                const toast = useToast();
                if (status === 422 && error.response?.data?.errors) {
                    const firstError = Object.values(error.response.data.errors).flat()[0];
                    toast.warning(firstError || text, entry.heading);
                } else {
                    toast[entry.icon]?.(text || entry.heading);
                }
            });
        } else {
            import("@/composables/useToast.js").then(({ useToast }) => {
                const toast = useToast();
                toast.error("Error de conexión. Verifica tu red.");
            });
        }

        return Promise.reject(error);
    }
);
