import { computed, ref } from "vue";

/**
 * Composable para persistencia en sessionStorage y colapsabilidad de paneles de filtros de búsqueda.
 *
 * Flujo del proceso:
 * 1. Inicializa los filtros combinando valores por defecto con los previamente almacenados en sessionStorage.
 * 2. Gestiona el estado de visibilidad (expandido / colapsado) del panel de filtros.
 * 3. Proporciona cálculo reactivo de cuántos filtros activos están aplicados actualmente.
 * 4. Expone métodos para guardar, limpiar y alternar visibilidad con sincronización a sessionStorage.
 *
 * @param {string} storageKey Clave única de almacenamiento en sessionStorage.
 * @param {Object} defaultFilters Objeto plano con los campos y valores iniciales de los filtros.
 * @param {Array<string>} [ignoredCountKeys=[]] Lista de claves que no deben contabilizarse como filtros activos (ej. sede_id, flags internos).
 * @returns {Object} Objeto con refs reactivos y métodos de control de persistencia.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-19
 */
export function useFilterPersistence(storageKey, defaultFilters = {}, ignoredCountKeys = []) {
    let hasRestoredFilters = false;
    let initialFilters = { ...defaultFilters };

    try {
        const stored = sessionStorage.getItem(storageKey);
        if (stored) {
            const parsed = JSON.parse(stored);
            if (parsed && typeof parsed === "object") {
                initialFilters = { ...defaultFilters, ...parsed };
                // Verificar si al menos una clave relevante tiene valor no vacío
                hasRestoredFilters = Object.keys(parsed).some(
                    (k) =>
                        !ignoredCountKeys.includes(k) &&
                        parsed[k] !== "" &&
                        parsed[k] !== null &&
                        parsed[k] !== undefined
                );
            }
        }
    } catch {
        // En caso de error de parseo, se usan defaults
    }

    const filtros = ref(initialFilters);

    // Estado colapsado/expandido
    let initialVisible = true;
    try {
        const storedVisible = sessionStorage.getItem(`${storageKey}_visible`);
        if (storedVisible !== null) {
            initialVisible = storedVisible === "true";
        }
    } catch {
        // fallback
    }
    const filtrosVisible = ref(initialVisible);

    /**
     * Calcula la cantidad de filtros activos aplicados.
     */
    const filtrosActivosCount = computed(() => {
        let count = 0;
        const current = filtros.value || {};
        for (const [key, value] of Object.entries(current)) {
            if (ignoredCountKeys.includes(key)) continue;
            if (
                value !== "" &&
                value !== null &&
                value !== undefined &&
                value !== defaultFilters[key]
            ) {
                count++;
            }
        }
        return count;
    });

    /**
     * Guarda el estado actual de los filtros en sessionStorage.
     */
    function guardarFiltros() {
        try {
            sessionStorage.setItem(storageKey, JSON.stringify(filtros.value));
        } catch {
            // best-effort
        }
    }

    /**
     * Alterna la visibilidad del panel y persiste el estado.
     */
    function toggleFiltros() {
        filtrosVisible.value = !filtrosVisible.value;
        try {
            sessionStorage.setItem(`${storageKey}_visible`, String(filtrosVisible.value));
        } catch {
            // best-effort
        }
    }

    /**
     * Restablece los filtros a sus valores predeterminados y limpia sessionStorage.
     */
    function limpiarFiltrosStorage() {
        try {
            sessionStorage.removeItem(storageKey);
        } catch {
            // best-effort
        }
        filtros.value = { ...defaultFilters };
    }

    return {
        filtros,
        filtrosVisible,
        filtrosActivosCount,
        hasRestoredFilters,
        guardarFiltros,
        toggleFiltros,
        limpiarFiltrosStorage,
    };
}
