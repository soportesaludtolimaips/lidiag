/**
 * Helpers para el editor Wysiwyg: detección de contenido vacío,
 * limpieza y normalización de HTML pegado desde Word/Office y
 * conversión bidireccional entre texto plano y HTML.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-03
 */

/**
 * Determina si un contenido HTML esta efectivamente vacio (sin texto visible).
 *
 * @param {string} html Contenido HTML a evaluar.
 * @returns {boolean}
 *
 * @author Jhon Javer Lozano Arce
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

/**
 * Limpia y normaliza el HTML pegado desde Microsoft Word, Google Docs u otras fuentes web
 * para el editor Wysiwyg (wysihtml5).
 *
 * Flujo del proceso:
 * 1. Extrae el fragmento HTML útil si viene delimitado por StartFragment/EndFragment (formato Office/Windows).
 * 2. Elimina comentarios condicionales (<!--[if ...]>), estilos CSS, scripts y metadatos de Office (xml, w:, o:, m:).
 * 3. Identifica párrafos o bloques vacíos de Word (ej. <p class=MsoNormal><o:p>&nbsp;</o:p></p>) y los convierte en saltos de línea <br>.
 * 4. Convierte los cierres de párrafos y bloques (<p>, <div>, <h1-6>, <tr>, <li>) a <br> para asegurar la preservación de saltos de línea.
 * 5. Conserva el formato básico de texto permitido (<b>, <strong>, <i>, <em>, <u>) y elimina etiquetas contenedoras innecesarias (<span>, <font>, etc.).
 * 6. Normaliza espacios de no separación (&nbsp;, \u00A0) a espacios estándar.
 * 7. Limpia saltos de línea duplicados al inicio o final del texto insertado.
 *
 * @param {string} html - HTML crudo obtenido del portapapeles.
 * @returns {string} HTML sanitizado con saltos <br> y formato limpio para el editor.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-03
 */
export function cleanPastedHtml(html) {
    if (!html || typeof html !== "string") return "";

    let cleaned = html;

    // 1. Extraer fragmento si existe (StartFragment / EndFragment de Windows/Office)
    const fragmentMatch = cleaned.match(/<!--StartFragment-->([\s\S]*?)<!--EndFragment-->/i);
    if (fragmentMatch && fragmentMatch[1]) {
        cleaned = fragmentMatch[1];
    }

    // 2. Eliminar comentarios condicionales y bloques no visuales de Office
    cleaned = cleaned.replace(/<!--[\s\S]*?-->/g, "");
    cleaned = cleaned.replace(/<style[\s\S]*?<\/style>/gi, "");
    cleaned = cleaned.replace(/<script[\s\S]*?<\/script>/gi, "");
    cleaned = cleaned.replace(/<xml[\s\S]*?<\/xml>/gi, "");
    cleaned = cleaned.replace(/<\/?(?:o|w|m|v):[^>]*>/gi, "");

    // 3. Normalizar párrafos/divs vacíos (que solo contienen &nbsp;, espacios o <o:p>)
    cleaned = cleaned.replace(
        /<(p|div|h[1-6])[^>]*>\s*(?:&nbsp;|\u00A0|\s|<br\s*\/?>)*<\/\1>/gi,
        "<br>"
    );

    // 4. Convertir cierres de bloques a saltos <br>
    cleaned = cleaned.replace(/<\/(p|div|h[1-6]|tr|li|blockquote)>/gi, "<br>");
    cleaned = cleaned.replace(/<(p|div|h[1-6]|tr|li|blockquote)[^>]*>/gi, "");

    // 5. Normalizar tags permitidas básicas (b, strong, i, em, u)
    cleaned = cleaned.replace(/<(b|strong)\b[^>]*>/gi, "<b>");
    cleaned = cleaned.replace(/<\/(b|strong)>/gi, "</b>");
    cleaned = cleaned.replace(/<(i|em)\b[^>]*>/gi, "<i>");
    cleaned = cleaned.replace(/<\/(i|em)>/gi, "</i>");
    cleaned = cleaned.replace(/<u\b[^>]*>/gi, "<u>");
    cleaned = cleaned.replace(/<\/u>/gi, "</u>");

    // 6. Eliminar tags no deseadas manteniendo su contenido (span, a, font, etc.)
    cleaned = cleaned.replace(
        /<\/?(?:span|font|header|footer|section|article|table|tbody|thead|td|th|ul|ol|body|html)[^>]*>/gi,
        ""
    );

    // 7. Normalizar saltos de línea físicos a espacio o nada para no duplicar con los <br> generados
    cleaned = cleaned.replace(/\r\n|\r|\n/g, "");

    // 8. Normalizar espacios de no separación
    cleaned = cleaned.replace(/&nbsp;/gi, " ");
    cleaned = cleaned.replace(/[\u00A0\u1680\u2000-\u200B\u202F\u205F\u3000]/g, " ");

    // 9. Colapsar más de 2 <br> seguidos en máximo 2 (un renglón en blanco)
    cleaned = cleaned.replace(/(?:<br\s*\/?>\s*){3,}/gi, "<br><br>");

    // 10. Limpiar saltos al inicio y al final
    cleaned = cleaned.replace(/^(?:<br\s*\/?>\s*)+/gi, "");
    cleaned = cleaned.replace(/(?:<br\s*\/?>\s*)+$/gi, "");

    return cleaned.trim();
}

/**
 * Convierte saltos de linea de texto plano (\n o \r\n) a HTML (<br>) para wysihtml5.
 *
 * @param {string} text - Texto plano con saltos de línea.
 * @returns {string} HTML con etiquetas <br>.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-03
 */
export function plainToHtml(text) {
    if (!text) return "";
    return String(text)
        .replace(/\r\n|\r/g, "\n")
        .replace(/\n/g, "<br>");
}

/**
 * Convierte HTML del editor a texto plano para persistencia en base de datos e interfaz SAHI.
 *
 * Flujo del proceso:
 * 1. Convierte etiquetas de salto <br> y cierres de bloques (<p>, <div>, <h1-6>) a saltos de línea \n.
 * 2. Elimina cualquier etiqueta HTML residual.
 * 3. Decodifica entidades HTML estándar (&amp;, &lt;, &gt;, &quot;, &#39;, &nbsp;) y espacios especiales.
 * 4. Limpia espacios redundantes al inicio/final de cada renglón sin afectar los saltos \n.
 * 5. Colapsa saltos múltiples consecutivos a máximo dos (un renglón en blanco estándar).
 *
 * @param {string} html - HTML proveniente del editor wysihtml5.
 * @returns {string} Texto plano limpio con saltos \n exactos.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-03
 */
export function htmlToPlain(html) {
    if (!html) return "";

    let text = String(html);

    // Normalizar saltos HTML a saltos de línea \n
    text = text.replace(/<br\s*\/?>/gi, "\n");
    text = text.replace(/<\/(p|div|h[1-6]|tr|li|blockquote)>/gi, "\n");
    text = text.replace(/<(p|div|h[1-6]|tr|li|blockquote)[^>]*>/gi, "");

    // Eliminar cualquier otra etiqueta HTML restante
    text = text.replace(/<[^>]+>/g, "");

    // Decodificar entidades HTML comunes
    text = text
        .replace(/&nbsp;/gi, " ")
        .replace(/[\u00A0\u1680\u2000-\u200B\u202F\u205F\u3000]/g, " ")
        .replace(/&amp;/gi, "&")
        .replace(/&lt;/gi, "<")
        .replace(/&gt;/gi, ">")
        .replace(/&quot;/gi, '"')
        .replace(/&#39;/gi, "'");

    // Limpiar espacios horizontales en cada línea sin colapsar \n
    const lines = text.split("\n").map((line) => line.replace(/[ \t]+/g, " ").trim());
    text = lines.join("\n");

    // Colapsar más de 2 saltos seguidos a máximo 2 (\n\n = un renglón en blanco)
    text = text.replace(/\n{3,}/g, "\n\n");

    return text.trim();
}
