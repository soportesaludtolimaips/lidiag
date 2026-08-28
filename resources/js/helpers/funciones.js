/**
 * Formatea un valor numerico como moneda colombiana (COP) sin decimales.
 *
 * @param number $value Valor numerico a formatear.
 *
 * @returns {string}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-28
 */
export function formatoMoneda(value) {
    return new Intl.NumberFormat("es-CO", {
        style: "currency",
        currency: "COP",
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
}
