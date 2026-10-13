import { ref } from "vue";
import axios from "axios";
import { confirmAction, toastMessage } from "@/helpers/alerts.js";

export function useConfigCrud({ resolveUrl, validate, messages = {} } = {}) {
    const loadingGuardar = ref(false);
    const loadingEliminarId = ref(null);

    function endpoint() {
        return typeof resolveUrl === "function" ? resolveUrl() : resolveUrl;
    }

    async function guardar(payload, { actualizar = false, id = 0 } = {}) {
        if (loadingGuardar.value) return { ok: false, errores: null };
        if (typeof validate === "function") {
            const errs = validate(payload) || {};
            if (Object.keys(errs).length > 0) {
                return { ok: false, errores: errs };
            }
        }
        loadingGuardar.value = true;
        try {
            const url = endpoint();
            const res = actualizar
                ? await axios.put(`${url}/${id}`, payload)
                : await axios.post(url, payload);
            if (res.status >= 200 && res.status < 300) {
                toastMessage({
                    heading: "Ok!!!",
                    text: actualizar
                        ? messages.update || "Registro actualizado correctamente."
                        : messages.create || "Registro guardado correctamente.",
                    icon: "success",
                });
                return { ok: true, errores: null, data: res.data };
            }
            return { ok: false, errores: null };
        } catch (error) {
            const errs = error?.response?.data?.errors;
            if (errs && Object.keys(errs).length > 0) {
                return { ok: false, errores: errs };
            }
            toastMessage({
                heading: "Error",
                text: error?.response?.data?.message || "No fue posible guardar el registro.",
                icon: "error",
            });
            return { ok: false, errores: null };
        } finally {
            loadingGuardar.value = false;
        }
    }

    async function eliminar(registroId) {
        if (loadingEliminarId.value) return false;
        const ok = await confirmAction({
            title: "¿Estas seguro?",
            text: "No podrás recuperar este registro!",
            icon: "warning",
        });
        if (!ok) return false;
        try {
            loadingEliminarId.value = registroId;
            const response = await axios.delete(`${endpoint()}/${registroId}`);
            toastMessage({
                heading: "Ok!",
                text: response?.data?.message || "Registro eliminado.",
                icon: "success",
            });
            return true;
        } catch {
            toastMessage({
                heading: "Error",
                text: "No fue posible eliminar el registro.",
                icon: "error",
            });
            return false;
        } finally {
            loadingEliminarId.value = null;
        }
    }

    return { loadingGuardar, loadingEliminarId, guardar, eliminar };
}
