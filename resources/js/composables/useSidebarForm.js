import { ref, nextTick, onBeforeUnmount } from "vue";
import { toastMessage } from "@/helpers/alerts.js";

export function useSidebarForm() {
    const sidebarRef = ref(null);
    const sidebarVisible = ref(false);

    function abrirSidebar() {
        sidebarVisible.value = true;
        nextTick(() => {
            if (sidebarRef.value) {
                sidebarRef.value.classList.add("shw-rside");
            }
        });
    }

    function cerrarSidebar() {
        sidebarVisible.value = false;
        if (sidebarRef.value) {
            sidebarRef.value.classList.remove("shw-rside");
        }
    }

    onBeforeUnmount(() => {
        if (sidebarRef.value) {
            sidebarRef.value.classList.remove("shw-rside");
        }
    });

    return {
        sidebarRef,
        sidebarVisible,
        abrirSidebar,
        cerrarSidebar,
    };
}

export function useFormErrors() {
    const errores = ref({});

    function clearErrors() {
        errores.value = {};
    }

    function setErrors(newErrors) {
        errores.value = newErrors || {};
    }

    function hasError(field) {
        return !!errores.value[field];
    }

    function getError(field) {
        return errores.value[field]?.[0];
    }

    return {
        errores,
        clearErrors,
        setErrors,
        hasError,
        getError,
    };
}

export function useFormState(initialState = {}) {
    const originalState = { ...initialState };
    const state = ref({ ...initialState });

    function reset() {
        state.value = { ...originalState };
    }

    function update(partial) {
        state.value = { ...state.value, ...partial };
    }

    function setState(newState) {
        state.value = { ...newState };
    }

    function setOriginal(newOriginal) {
        Object.assign(originalState, newOriginal);
        reset();
    }

    return {
        state,
        reset,
        update,
        setState,
        setOriginal,
    };
}

export function useCrudActions({ apiUrl, listFn, resetFormFn, successMessages = {} }) {
    const loadingGuardar = ref(false);
    const loadingEliminarId = ref(null);
    const actualizar = ref(false);
    const id = ref(0);

    async function guardarRegistro(payload = null) {
        if (loadingGuardar.value) return;
        loadingGuardar.value = true;
        try {
            let res;
            const data = payload || getFormData();

            if (actualizar.value === false) {
                res = await axios.post(apiUrl, data);
                toastMessage({
                    heading: "Ok!!!",
                    text: successMessages.create || "Registro creado correctamente.",
                    icon: "success",
                });
            } else {
                res = await axios.put(`${apiUrl}/${id.value}`, data);
                toastMessage({
                    heading: "Ok!!!",
                    text: successMessages.update || "Registro actualizado correctamente.",
                    icon: "success",
                });
            }

            if (res.status >= 200 && res.status < 300) {
                listFn();
                resetFormFn?.();
                return res;
            }
        } finally {
            loadingGuardar.value = false;
        }
    }

    async function eliminarRegistro(idEliminar, nombre = "") {
        const ok = await confirmAction({
            title: nombre ? `¿Estás seguro de eliminar ${nombre}?` : "¿Estás seguro?",
            text: "No podrás recuperar este registro!",
            icon: "warning",
        });
        if (!ok) return;

        if (loadingEliminarId.value) return;
        loadingEliminarId.value = idEliminar;
        try {
            const response = await axios.delete(`${apiUrl}/${idEliminar}`);
            toastMessage({
                heading: "Ok!",
                text: response?.data?.message || "Registro eliminado.",
                icon: "success",
            });
            listFn();
        } catch (error) {
            toastMessage({
                heading: "Error",
                text: error.response?.data?.message || "No fue posible eliminar el registro.",
                icon: "error",
            });
        } finally {
            loadingEliminarId.value = null;
        }
    }

    function prepararEdicion(item) {
        actualizar.value = true;
        id.value = item.id;
    }

    function prepararNuevo() {
        actualizar.value = false;
        id.value = 0;
    }

    function getFormData() {
        return {};
    }

    return {
        loadingGuardar,
        loadingEliminarId,
        actualizar,
        id,
        guardarRegistro,
        eliminarRegistro,
        prepararEdicion,
        prepararNuevo,
    };
}

import axios from "axios";
import { confirmAction } from "@/helpers/alerts.js";
