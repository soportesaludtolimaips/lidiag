import { ref } from "vue";

export function useConfigForm(initForms = {}) {
    const firstKey = Object.keys(initForms)[0];
    const form = ref({ ...(initForms[firstKey] || {}) });
    const errores = ref({});
    const tituloModal = ref("Nuevo registro");
    const actualizar = ref(false);
    const id = ref(0);

    function clearErrors() {
        errores.value = {};
    }

    function setErrors(errs) {
        errores.value = errs || {};
    }

    function resetForm(tab) {
        actualizar.value = false;
        tituloModal.value = "Nuevo registro";
        id.value = 0;
        errores.value = {};
        if (tab && initForms[tab]) {
            form.value = { ...initForms[tab] };
        }
    }

    function prepararNuevo(tab, titulo) {
        actualizar.value = false;
        id.value = 0;
        tituloModal.value = titulo;
        if (tab && initForms[tab]) {
            form.value = { ...initForms[tab] };
        }
    }

    function prepararEdicion(titulo, data, mapper) {
        actualizar.value = true;
        id.value = data?.id ?? 0;
        tituloModal.value = titulo;
        form.value = typeof mapper === "function" ? mapper(data) : { ...data };
    }

    return {
        form,
        errores,
        tituloModal,
        actualizar,
        id,
        clearErrors,
        setErrors,
        resetForm,
        prepararNuevo,
        prepararEdicion,
    };
}
