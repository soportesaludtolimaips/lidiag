import { nextTick } from "vue";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";

const DEFAULT_OPTIONS = {
    dom: "Bfrtip",
    buttons: ["copy", "csv", "excel", "pdf", "print"],
};

export function useDataTable(getTarget, options = {}) {
    const opts = { ...DEFAULT_OPTIONS, ...options };

    function target() {
        return typeof getTarget === "function" ? getTarget() : getTarget;
    }

    function init() {
        return safeInitDataTable(target(), opts);
    }

    function destroy() {
        safeDestroyDataTable(target());
    }

    function refresh(active = true) {
        nextTick(() => {
            destroy();
            if (active) {
                init();
            }
        });
    }

    return { init, destroy, refresh };
}
