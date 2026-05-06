import { ref } from "vue";

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
