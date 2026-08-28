import { ref } from "vue";
import axios from "axios";

/**
 * Gestiona la sede actual de la aplicacion obteniendola desde la sesion y consultando su configuracion, incluyendo la bandera de interfaz de software.
 *
 * @returns {Object} Referencias reactivas (Ref) y funcion establecerSede para cargar la sede.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-06-25
 */
export function useSede() {
    const sedeActual = ref(null);
    const sedeInterface = ref(false);
    const sedeNombre = ref("");
    const isLoading = ref(false);

    const establecerSede = async () => {
        const id = sessionStorage.getItem("ST-sede");
        if (!id) return;
        sedeActual.value = id;
        isLoading.value = true;
        try {
            const res = await axios.get(`/api/config-sedes/${id}/buscarPorId`);
            sedeInterface.value = res?.data?.data?.interface_software != null;
            sedeNombre.value = res?.data?.data?.nom_sede || "";
        } catch {
            sedeInterface.value = false;
            sedeNombre.value = "";
        } finally {
            isLoading.value = false;
        }
    };

    return { sedeActual, sedeInterface, sedeNombre, isLoading, establecerSede };
}
