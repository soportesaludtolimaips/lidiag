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

window.axios.interceptors.response.use(
    function (response) {
        refreshCsrfToken();
        return response;
    },
    function (error) {
        const status = error?.response?.status;
        if (status === 401 && !_redirectingToLogin) {
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
        return Promise.reject(error);
    }
);

