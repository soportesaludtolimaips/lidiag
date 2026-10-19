import { onBeforeUnmount, onMounted, unref } from "vue";

/**
 * Composable para captura accesible de atajos de teclado clínicos (Ctrl+S para guardado rápido, Esc para cerrar).
 *
 * Flujo del proceso:
 * 1. Registra un listener de 'keydown' en window con prevención de colisiones con navegadores.
 * 2. Verifica si la combinación pulsada es Ctrl+S / Cmd+S, cancelando la descarga HTML por defecto de Chrome/Edge.
 * 3. Ejecuta el callback onSave únicamente si el panel/formulario está activo y no se encuentra procesando una petición.
 * 4. Procesa la tecla Escape para cerrar ordenadamente modales o sidebars abiertos.
 * 5. Remueve de forma limpia los event listeners al desmontar el componente.
 *
 * @param {Object} options Opciones de configuración de atajos.
 * @param {Function} [options.onSave] Función a ejecutar al presionar Ctrl+S o Cmd+S.
 * @param {Function} [options.onEscape] Función a ejecutar al presionar Escape.
 * @param {Function} [options.onDictate] Función a ejecutar al presionar F2 (dictado por voz).
 * @param {import('vue').Ref<boolean>|Function|boolean} [options.isActive=true] Indicador de activación de los atajos (ej: sidebar abierto).
 * @param {import('vue').Ref<boolean>} [options.isLoading] Ref reactivo de estado de carga para evitar ejecuciones concurrentes.
 * @returns {{ teardown: Function, setup: Function }} Métodos de control manual de listeners.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-19
 */
export function useKeyboardShortcuts({
    onSave,
    onEscape,
    onDictate,
    isActive = true,
    isLoading = null,
} = {}) {
    let keydownHandler = null;

    const isConditionActive = () => {
        if (typeof isActive === "function") return Boolean(isActive());
        return Boolean(unref(isActive));
    };

    const handleKeydown = (e) => {
        if (!isConditionActive()) return;

        // Atajo Guardar: Ctrl+S o Cmd+S
        const isSaveCombo = (e.ctrlKey || e.metaKey) && (e.key === "s" || e.key === "S");
        if (isSaveCombo) {
            e.preventDefault();
            e.stopPropagation();

            if (isLoading && unref(isLoading)) {
                return;
            }

            if (typeof onSave === "function") {
                onSave();
            }
            return;
        }

        // Atajo Dictado por Voz: F2
        if (e.key === "F2") {
            if (typeof onDictate === "function") {
                e.preventDefault();
                e.stopPropagation();
                onDictate();
            }
            return;
        }

        // Atajo Cerrar: Escape
        if (e.key === "Escape") {
            // Ignorar si el usuario está en un select abierto o similar con dropdown nativo
            if (typeof onEscape === "function") {
                e.preventDefault();
                onEscape();
            }
        }
    };

    const setup = () => {
        teardown();
        keydownHandler = handleKeydown;
        window.addEventListener("keydown", keydownHandler, { capture: true });
    };

    const teardown = () => {
        if (keydownHandler) {
            window.removeEventListener("keydown", keydownHandler, {
                capture: true,
            });
            keydownHandler = null;
        }
    };

    onMounted(() => {
        setup();
    });

    onBeforeUnmount(() => {
        teardown();
    });

    return {
        teardown,
        setup,
    };
}
