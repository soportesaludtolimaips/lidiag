import { ref } from "vue";
import axios from "axios";

export function useSede() {
    const sedeActual = ref(null);
    const sedeInterface = ref(false);
    const isLoading = ref(false);

    const establecerSede = async () => {
        const id = sessionStorage.getItem("ST-sede");
        if (!id) return;
        sedeActual.value = id;
        isLoading.value = true;
        try {
            const res = await axios.get(`/api/config-sedes/${id}/buscarPorId`);
            sedeInterface.value = res?.data?.interface_software === "SAHI";
        } catch {
            sedeInterface.value = false;
        } finally {
            isLoading.value = false;
        }
    };

    return { sedeActual, sedeInterface, isLoading, establecerSede };
}
