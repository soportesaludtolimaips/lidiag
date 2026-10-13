/**
 * Helpers para inicializar/destruir DataTables de forma segura
 * (cuando DataTables viene cargado globalmente por la plantilla).
 */

/**
 * Devuelve la instancia de jQuery global si esta disponible, o null en caso contrario.
 *
 * @returns {object|null}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-03
 */
function getJquery() {
    if (typeof window === "undefined") return null;
    const $ = window.$ || window.jQuery;
    if (!$ || !$.fn) return null;
    return $;
}

/**
 * Resuelve el selector de DataTables en un objeto jQuery valido (string, Element o similar).
 *
 * @param object $jQuery Instancia de jQuery.
 * @param mixed $target Selector, elemento o nodo a resolver.
 *
 * @returns {object}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-03
 */
function resolveTarget($, target) {
    // target puede ser: selector string, Element, o un array-like (ej: Vue ref raro)
    if (!target) return null;
    if (typeof target === "string") return $(target);
    // Element / HTMLElement
    return $(target);
}

/**
 * Obtiene la instancia de DataTables asociada a un selector si ya fue inicializada.
 *
 * @param string $target Selector o elemento de la tabla (por defecto #example23).
 *
 * @returns {object|null}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-03
 */
export function safeGetDataTable(target = "#example23") {
    const $ = getJquery();
    if (!$ || !$.fn.DataTable) return null;

    const $t = resolveTarget($, target);
    if (!$t || !$t.length) return null;

    try {
        if ($.fn.DataTable.isDataTable($t)) {
            return $t.DataTable();
        }
    } catch (_) {}
    return null;
}

/**
 * Elimina una fila de la DataTables de forma segura y redibuja la tabla.
 *
 * @param string $target Selector o elemento de la tabla.
 * @param mixed $rowSelectorOrNode Selector o nodo de la fila a eliminar.
 *
 * @returns {boolean}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-03
 */
export function safeRemoveDataTableRow(target = "#example23", rowSelectorOrNode) {
    const dt = safeGetDataTable(target);
    if (!dt) return false;

    try {
        dt.row(rowSelectorOrNode).remove().draw(false);
        return true;
    } catch (_) {
        return false;
    }
}

/**
 * Destruye la instancia de DataTables asociada al selector, evitando vaciar el tbody en tablas de origen DOM.
 *
 * @param string $target Selector o elemento de la tabla.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-03
 */
export function safeDestroyDataTable(target = "#example23") {
    const $ = getJquery();
    if (!$ || !$.fn.DataTable) return;

    const $t = resolveTarget($, target);
    if (!$t.length) return;

    try {
        if ($.fn.DataTable.isDataTable($t)) {
            // No usar .clear(): cuando la tabla es "DOM-sourced" (Vue renderiza <tr>),
            // clear() puede vaciar el tbody y dejar la UI sin filas aunque el componente
            // aún tenga registros pendientes.
            $t.DataTable().destroy();
        }
    } catch (_) {
        // no-op: evitar que una tabla rota tumbe la vista
    }
}

/**
 * Inicializa DataTables sobre el selector o devuelve la instancia existente si ya esta inicializada.
 *
 * @param string $target Selector o elemento de la tabla.
 * @param object $options Opciones de configuracion de DataTables.
 *
 * @returns {object|null}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-02-03
 */
export function safeInitDataTable(target = "#example23", options = {}) {
    const $ = getJquery();
    if (!$ || !$.fn.DataTable) return null;

    const $t = resolveTarget($, target);
    if (!$t.length) return null;

    try {
        if ($.fn.DataTable.isDataTable($t)) {
            return $t.DataTable();
        }
        return $t.DataTable(options);
    } catch (error) {
        console.error("DataTables init error:", error);
        return null;
    }
}
