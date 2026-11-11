import { ref } from "vue";

/**
 * Composable para gestionar un diccionario medico: carga y busqueda de terminos (implementacion en desarrollo).
 *
 * @returns {Object} Estado reactivo dictionary (Ref), isLoading (Ref) y funciones loadDictionary y search.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-04-07
 */
export function useMedicalDictionary() {
    const dictionary = ref([]);
    const isLoading = ref(false);

    const loadDictionary = async () => {
        isLoading.value = true;
        // Placeholder - implementar según necesidad
        isLoading.value = false;
    };

    const search = (term) => {
        if (!term) return [];
        return dictionary.value.filter((item) => item.toLowerCase().includes(term.toLowerCase()));
    };

    return {
        dictionary,
        isLoading,
        loadDictionary,
        search,
    };
}
