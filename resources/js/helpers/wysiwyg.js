/**
 * Determina si un contenido HTML esta efectivamente vacio (sin texto visible).
 *
 * @param string $html Contenido HTML a evaluar.
 *
 * @returns {boolean}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-09
 */
export function isHtmlEffectivelyEmpty(html) {
    const text = String(html || "")
        .replace(/<[^>]*>/g, " ")
        .replace(/&nbsp;/g, " ")
        .replace(/\s+/g, " ")
        .trim();
    return !text;
}
