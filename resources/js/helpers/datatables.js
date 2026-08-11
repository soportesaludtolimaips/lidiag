/**
 * Helpers para inicializar/destruir DataTables de forma segura
 * (cuando DataTables viene cargado globalmente por la plantilla).
 */

function getJquery() {
    if (typeof window === "undefined") return null;
    const $ = window.$ || window.jQuery;
    if (!$ || !$.fn) return null;
    return $;
}

function resolveTarget($, target) {
    // target puede ser: selector string, Element, o un array-like (ej: Vue ref raro)
    if (!target) return null;
    if (typeof target === "string") return $(target);
    // Element / HTMLElement
    return $(target);
}

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
    } catch (e) {
        console.error("DataTables init error:", e);
        return null;
    }
}
