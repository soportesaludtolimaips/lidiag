/**
 * Catálogo y procesador directo de comandos de voz para dictado clínico en LIDIAG.
 *
 * Flujo del proceso:
 * 1. Define y cataloga la totalidad de comandos de voz disponibles en el sistema (mayúsculas,
 *    saltos de línea, párrafos, puntuación, símbolos, operadores matemáticos y formato clínico).
 * 2. Normaliza variantes fonéticas comunes de Google Chrome (como dígitos '2 puntos' a 'dos puntos'
 *    y contracciones acústicas de 'punto a parte' con el signo '.').
 * 3. Ejecuta los comandos de estado (activar/desactivar mayúsculas sostenidas) y extrae las palabras de control.
 * 4. Sustituye limpiamente las frases de comando por sus equivalentes tipográficos correspondientes.
 * 5. Corrige espaciado redundante antes de signos de puntuación y capitaliza nuevas oraciones/párrafos.
 * 6. Retorna el texto final limpio y los estados actualizados (uppercase, capitalizeNext) sin retardos ni búfers artificiales.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-23
 */

/**
 * Comandos para activar el modo de mayúsculas sostenidas.
 */
export const UPPERCASE_ENABLE_COMMANDS = [
    "iniciar mayúscula sostenida",
    "iniciar mayuscula sostenida",
    "iniciar mayúsculas sostenida",
    "iniciar mayusculas sostenida",
    "inicia mayúscula sostenida",
    "inicia mayuscula sostenida",
    "inicia mayúsculas sostenida",
    "inicia mayusculas sostenida",
    "iniciar mayúsculas sostenidas",
    "iniciar mayusculas sostenidas",
    "inicia mayúsculas sostenidas",
    "inicia mayusculas sostenidas",
    "iniciar mayúsculas",
    "iniciar mayusculas",
    "iniciar mayúscula",
    "iniciar mayuscula",
    "inicia mayúsculas",
    "inicia mayusculas",
    "inicia mayúscula",
    "inicia mayuscula",
    "inicie mayúsculas",
    "inicie mayusculas",
    "inicie mayúscula",
    "inicie mayuscula",
    "mayúsculas sostenidas",
    "mayusculas sostenidas",
    "mayúsculas sostenida",
    "mayusculas sostenida",
    "mayúscula sostenida",
    "mayuscula sostenida",
    "activar mayúsculas",
    "activar mayusculas",
    "activar mayúscula",
    "activar mayuscula",
    "activa mayúsculas",
    "activa mayusculas",
    "activa mayúscula",
    "activa mayuscula",
    "active mayúsculas",
    "active mayusculas",
    "active mayúscula",
    "active mayuscula",
    "habilitar mayúsculas",
    "habilitar mayusculas",
    "habilitar mayúscula",
    "habilitar mayuscula",
    "habilita mayúsculas",
    "habilita mayusculas",
    "habilita mayúscula",
    "habilita mayuscula",
    "habilite mayúsculas",
    "habilite mayusculas",
    "habilite mayúscula",
    "habilite mayuscula",
    "activare mayúsculas",
    "activare mayusculas",
    "activare mayúscula",
    "activare mayuscula",
    "poner mayúsculas",
    "poner mayusculas",
    "poner mayúscula",
    "poner mayuscula",
    "todo mayúsculas",
    "todo mayusculas",
    "todo mayúscula",
    "todo mayuscula",
    "en mayúsculas",
    "en mayusculas",
    "en mayúscula",
    "en mayuscula",
];

/**
 * Comandos para capitalizar únicamente la siguiente palabra o frase (mayúscula inicial).
 */
export const CAPITALIZE_NEXT_COMMANDS = [
    "inicial mayúscula",
    "inicial mayuscula",
    "inicial mayúsculas",
    "inicial mayusculas",
    "iniciales mayúsculas",
    "iniciales mayusculas",
    "mayúscula inicial",
    "mayuscula inicial",
    "mayúsculas iniciales",
    "mayusculas iniciales",
    "primera mayúscula",
    "primera mayuscula",
    "letra mayúscula",
    "letra mayuscula",
];

/**
 * Comandos para desactivar el modo de mayúsculas sostenidas.
 */
export const UPPERCASE_DISABLE_COMMANDS = [
    "desactivar mayúsculas",
    "desactivar mayusculas",
    "desactivar mayúscula",
    "desactivar mayuscula",
    "desactiva mayúsculas",
    "desactiva mayusculas",
    "desactiva mayúscula",
    "desactiva mayuscula",
    "desactive mayúsculas",
    "desactive mayusculas",
    "desactive mayúscula",
    "desactive mayuscula",
    "deshabilitar mayúsculas",
    "deshabilitar mayusculas",
    "deshabilitar mayúscula",
    "deshabilitar mayuscula",
    "deshabilita mayúsculas",
    "deshabilita mayusculas",
    "deshabilita mayúscula",
    "deshabilita mayuscula",
    "deshabilite mayúsculas",
    "deshabilite mayusculas",
    "deshabilite mayúscula",
    "deshabilite mayuscula",
    "terminar mayúsculas",
    "terminar mayusculas",
    "terminar mayúscula",
    "terminar mayuscula",
    "termina mayúsculas",
    "termina mayusculas",
    "termina mayúscula",
    "termina mayuscula",
    "termine mayúsculas",
    "termine mayusculas",
    "termine mayúscula",
    "termine mayuscula",
    "fin mayúsculas",
    "fin mayusculas",
    "fin mayúscula",
    "fin mayuscula",
    "finaliza mayúsculas",
    "finaliza mayusculas",
    "finaliza mayúscula",
    "finaliza mayuscula",
    "finalizar mayúsculas",
    "finalizar mayusculas",
    "finalizar mayúscula",
    "finalizar mayuscula",
    "finalize mayúsculas",
    "finalize mayusculas",
    "finalize mayúscula",
    "finalize mayuscula",
];

/**
 * Comandos de saltos de línea, párrafos y separadores.
 */
export const LINE_BREAK_COMMANDS = {
    "punto aparte": ".\n\n",
    "punto a parte": ".\n\n",
    "punto y aparte": ".\n\n",
    "punto y salto de línea": ".\n",
    "punto y salto de linea": ".\n",
    "punto y falta de línea": ".\n",
    "punto y falta de linea": ".\n",
    "punto salto de línea": ".\n",
    "punto salto de linea": ".\n",
    "dos puntos aparte": ":\n\n",
    "dos puntos y aparte": ":\n\n",
    "dos puntos salto de línea": ":\n",
    "dos puntos salto de linea": ":\n",
    "nuevo párrafo": "\n\n",
    "nuevo parrafo": "\n\n",
    "un nuevo párrafo": "\n\n",
    "un nuevo parrafo": "\n\n",
    "nueva sección": "\n\n---\n\n",
    "nueva seccion": "\n\n---\n\n",
    separador: "\n\n---\n\n",
    "doble línea en blanco": "\n\n\n\n",
    "doble linea en blanco": "\n\n\n\n",
    "dos líneas en blanco": "\n\n",
    "dos lineas en blanco": "\n\n",
    "doble salto de línea": "\n\n",
    "doble salto de linea": "\n\n",
    "dos saltos de línea": "\n\n",
    "dos saltos de linea": "\n\n",
    "doble salto línea": "\n\n",
    "doble salto linea": "\n\n",
    "doble falta de línea": "\n\n",
    "doble falta de linea": "\n\n",
    "doble nueva línea": "\n\n",
    "doble nueva linea": "\n\n",
    "doble cambio de línea": "\n\n",
    "doble cambio de linea": "\n\n",
    "doble salto de renglón": "\n\n",
    "doble salto de renglon": "\n\n",
    "dos saltos de renglón": "\n\n",
    "dos saltos de renglon": "\n\n",
    "doble enter": "\n\n",
    "dos enter": "\n\n",
    "dos enters": "\n\n",
    "línea en blanco": "\n\n",
    "linea en blanco": "\n\n",
    "un renglón en blanco": "\n\n",
    "un renglon en blanco": "\n\n",
    "renglón en blanco": "\n\n",
    "renglon en blanco": "\n\n",
    "un salto de línea": "\n",
    "un salto de linea": "\n",
    "salto de línea": "\n",
    "salto de linea": "\n",
    "salto de lima": "\n",
    "salto de adelina": "\n",
    "salto adelina": "\n",
    "cartón de línea": "\n",
    "carton de linea": "\n",
    "cartones de línea": "\n",
    "cartones de linea": "\n",
    "salto línea": "\n",
    "salto linea": "\n",
    "saltar de línea": "\n",
    "saltar de linea": "\n",
    "saltar línea": "\n",
    "saltar linea": "\n",
    "salta de línea": "\n",
    "salta de linea": "\n",
    "salte de línea": "\n",
    "salte de linea": "\n",
    "salta línea": "\n",
    "salta linea": "\n",
    "salte línea": "\n",
    "salte linea": "\n",
    "salto una línea": "\n",
    "salto una linea": "\n",
    "salta una línea": "\n",
    "salta una linea": "\n",
    "salte una línea": "\n",
    "salte una linea": "\n",
    "saltar una línea": "\n",
    "saltar una linea": "\n",
    "saltos de línea": "\n",
    "saltos de linea": "\n",
    "salto delínea": "\n",
    "salto delinea": "\n",
    "un salto de renglón": "\n",
    "un salto de renglon": "\n",
    "salto de renglón": "\n",
    "salto de renglon": "\n",
    "salto renglón": "\n",
    "salto renglon": "\n",
    "falta de línea": "\n",
    "falta de linea": "\n",
    "falta de renglón": "\n",
    "falta de renglon": "\n",
    "una nueva línea": "\n",
    "una nueva linea": "\n",
    "nueva línea": "\n",
    "nueva linea": "\n",
    "un nuevo renglón": "\n",
    "un nuevo renglon": "\n",
    "nuevo renglón": "\n",
    "nuevo renglon": "\n",
    "siguiente línea": "\n",
    "siguiente linea": "\n",
    "siguiente renglón": "\n",
    "siguiente renglon": "\n",
    "pasar a la siguiente línea": "\n",
    "pasar a la siguiente linea": "\n",
    "pasar de línea": "\n",
    "pasar de linea": "\n",
    "bajar una línea": "\n",
    "bajar una linea": "\n",
    "baja una línea": "\n",
    "baja una linea": "\n",
    "bajar de línea": "\n",
    "bajar de linea": "\n",
    "baja de línea": "\n",
    "baja de linea": "\n",
    "bajar línea": "\n",
    "bajar linea": "\n",
    "baja línea": "\n",
    "baja linea": "\n",
    "bajar un renglón": "\n",
    "bajar un renglon": "\n",
    "baja un renglón": "\n",
    "baja un renglon": "\n",
    "bajar renglón": "\n",
    "bajar renglon": "\n",
    "baja renglón": "\n",
    "baja renglon": "\n",
    "cambio de línea": "\n",
    "cambio de linea": "\n",
    "cambio de renglón": "\n",
    "cambio de renglon": "\n",
    "presionar enter": "\n",
    "dar enter": "\n",
    "un enter": "\n",
    enter: "\n",
    intro: "\n",
};

/**
 * Comandos de puntuación, espaciado y delimitadores.
 */
export const PUNCTUATION_COMMANDS = {
    "dos puntos aparte": ":\n\n",
    "dos puntos": ": ",
    "dos punto": ": ",
    "2 puntos": ": ",
    "2 punto": ": ",
    "punto y seguido": ". ",
    "punto seguido": ". ",
    "punto y coma": "; ",
    "nueva oración": ". ",
    "nueva oracion": ". ",
    punto: ". ",
    puntos: ". ",
    coma: ", ",
    "dar espacio": " ",
    "un espacio": " ",
    "dos espacios": "  ",
    "dos espacio": "  ",
    "doble espacio": "  ",
    "abrir paréntesis": "(",
    "abrir parentesis": "(",
    "cerrar paréntesis": ")",
    "cerrar parentesis": ")",
    "abrir corchetes": "[",
    "abrir corchete": "[",
    "cerrar corchetes": "]",
    "cerrar corchete": "]",
    "abrir llaves": "{",
    "abrir llave": "{",
    "cerrar llaves": "}",
    "cerrar llave": "}",
    "abrir comillas simples": "'",
    "cerrar comillas simples": "'",
    "abrir comillas": '"',
    "cerrar comillas": '"',
};

/**
 * Comandos de símbolos, operadores matemáticos y monedas.
 */
export const SYMBOL_COMMANDS = {
    "guion medio": "-",
    "guion bajo": "_",
    guion: "-",
    "barra diagonal": "/",
    "barra invertida": "\\",
    barra: "/",
    "por ciento": "%",
    porciento: "%",
    "signo más": "+",
    "signo mas": "+",
    "signo de más": "+",
    "signo de mas": "+",
    "operador más": "+",
    "operador mas": "+",
    "signo menos": "-",
    "signo de menos": "-",
    "operador menos": "-",
    "signo igual": "=",
    "signo de igual": "=",
    "operador igual": "=",
    multiplicar: "×",
    dividir: "÷",
    asterisco: "*",
    numeral: "#",
    hashtag: "#",
    ampersand: "&",
    arroba: "@",
    dólar: "$",
    dolar: "$",
    pesos: "$",
    euro: "€",
};

/**
 * Comandos de formato clínico.
 */
export const FORMAT_COMMANDS = {
    "cerrar negrita": "**",
    negrita: "**",
    "cerrar cursiva": "_",
    cursiva: "_",
    "cerrar subrayado": "</u>",
    subrayado: "<u>",
};

/**
 * Retorna la lista plana de todos los comandos disponibles para auditoría o visualización.
 *
 * @returns {string[]}
 */
export function getAllAvailableCommandKeys() {
    return [
        ...UPPERCASE_ENABLE_COMMANDS,
        ...CAPITALIZE_NEXT_COMMANDS,
        ...UPPERCASE_DISABLE_COMMANDS,
        ...Object.keys(LINE_BREAK_COMMANDS),
        ...Object.keys(PUNCTUATION_COMMANDS),
        ...Object.keys(SYMBOL_COMMANDS),
        ...Object.keys(FORMAT_COMMANDS),
    ];
}

/**
 * Conjunto optimizado en minúsculas de todos los comandos de voz conocidos.
 * @type {Set<string>}
 */
const KNOWN_COMMANDS_SET = new Set(getAllAvailableCommandKeys().map((k) => k.trim().toLowerCase()));

/**
 * Determina si una frase o fragmento coincide con un comando de voz conocido en el catálogo.
 *
 * @param {string} phrase - Frase candidata a evaluar.
 * @returns {boolean}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-23
 */
export function isKnownVoiceCommand(phrase) {
    if (!phrase || typeof phrase !== "string") return false;
    const clean = normalizeDigitsBeforeCommands(phrase.trim().toLowerCase());
    return KNOWN_COMMANDS_SET.has(clean);
}

/**
 * Normaliza dígitos numéricos dictados ante palabras clave de comando.
 *
 * @param {string} text - Texto original.
 * @returns {string} Texto con dígitos reemplazados por palabras.
 */
function normalizeDigitsBeforeCommands(text) {
    if (!text) return "";
    return text
        .replace(/\b1\s+punto\b/gi, "un punto")
        .replace(/\b2\s+puntos?\b/gi, "dos puntos")
        .replace(/\b1\s+espacio\b/gi, "un espacio")
        .replace(/\b2\s+espacios?\b/gi, "dos espacios")
        .replace(/\b2\s+enters?\b/gi, "dos enter");
}

/**
 * Escapa caracteres especiales para utilizarlos de forma segura en RegExp.
 *
 * @param {string} str - Cadena a escapar.
 * @returns {string} Cadena escapada.
 */
function escapeRegExp(str) {
    return str.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

/**
 * Procesa el texto bruto reconocido por SpeechRecognition aplicando todos los comandos de voz de manera limpia y predecible.
 *
 * @param {string} rawText - Texto bruto transcrito por la Web Speech API.
 * @param {Object} options - Opciones de estado (uppercase, capitalizeNext).
 * @param {boolean} [options.uppercase=false] - Si el modo mayúsculas sostenidas está actualmente encendido.
 * @param {boolean} [options.capitalizeNext=false] - Si la primera letra debe ser mayúscula (por punto anterior).
 * @returns {{ text: string, uppercase: boolean, capitalizeNext: boolean }} Objeto con el texto procesado y los nuevos estados.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-23
 */
export function processVoiceCommands(rawText, { uppercase = false, capitalizeNext = false } = {}) {
    let text = String(rawText || "").trim();
    if (!text) {
        return { text: "", uppercase, capitalizeNext };
    }

    let isUppercase = Boolean(uppercase);
    let shouldCapitalizeNext = Boolean(capitalizeNext);

    // 1. Normalizar números pegados a palabras de comando (ej. "2 puntos" -> "dos puntos")
    text = normalizeDigitsBeforeCommands(text);

    // 2. Normalizar pre-puntuaciones fonéticas de Chromium en español
    // (Chrome muchas veces ya convierte "punto" en "." antes de decir "a parte", "salto de línea", "falta de línea" o "seguido")
    text = text.replace(
        /(?:(?:\bpuntos?\b|\.)\s*(?:y\s+)?(?:a\s*parte|nuevo\s+p[áa]rrafo)\b|\bpuntos?\s+aparte\b)/gi,
        () => {
            shouldCapitalizeNext = true;
            return ".\n\n";
        }
    );
    text = text.replace(
        /(?:(?:\bpuntos?\b|\.)\s*(?:y\s+)?(?:salto\s+de\s+(?:l[íi]nea|lima|adelina)|falta\s+de\s+l[íi]nea|cambio\s+de\s+l[íi]nea|cart[óo]n(?:es)?\s+de\s+l[íi]nea|nueva\s+l[íi]nea|nuevo\s+rengl[óo]n)\b)/gi,
        () => {
            shouldCapitalizeNext = true;
            return ".\n";
        }
    );
    text = text.replace(
        /(?:(?::|\bdos\s+puntos?\b)\s*(?:y\s+)?(?:a\s*parte|nuevo\s+p[áa]rrafo)\b)/gi,
        () => {
            shouldCapitalizeNext = true;
            return ":\n\n";
        }
    );
    text = text.replace(
        /(?:(?::|\bdos\s+puntos?\b)\s*(?:y\s+)?(?:salto\s+de\s+(?:l[íi]nea|lima|adelina)|falta\s+de\s+l[íi]nea|cambio\s+de\s+l[íi]nea|cart[óo]n(?:es)?\s+de\s+l[íi]nea|nueva\s+l[íi]nea)\b)/gi,
        () => {
            shouldCapitalizeNext = true;
            return ":\n";
        }
    );
    text = text.replace(/(?:(?:\bpuntos?\b|\.)\s*(?:y\s+)?seguido\b)/gi, () => {
        shouldCapitalizeNext = true;
        return ". ";
    });

    // 3. Procesar comandos de estado de mayúsculas (activar / desactivar)
    // Caso compuesto: "iniciar mayúsculas [texto] desactivar mayúsculas" en una sola frase
    const enablePattern = `\\b(${UPPERCASE_ENABLE_COMMANDS.map(escapeRegExp).join("|")})\\b`;
    const disablePattern = `\\b(${UPPERCASE_DISABLE_COMMANDS.map(escapeRegExp).join("|")})\\b`;
    const hasEnable = new RegExp(enablePattern, "i").test(text);
    const hasDisable = new RegExp(disablePattern, "i").test(text);

    if (hasEnable && hasDisable) {
        // Mayúsculas en bloque cerrado dentro del mismo fragmento
        text = text.replace(
            new RegExp(
                `\\b(${UPPERCASE_ENABLE_COMMANDS.map(escapeRegExp).join("|")})\\b([\\s\\S]*?)\\b(${UPPERCASE_DISABLE_COMMANDS.map(escapeRegExp).join("|")})\\b`,
                "gi"
            ),
            (m, _start, content) => content.toUpperCase()
        );
    } else if (hasEnable) {
        isUppercase = true;
        text = text.replace(new RegExp(enablePattern, "gi"), "");
    } else if (hasDisable) {
        isUppercase = false;
        text = text.replace(new RegExp(disablePattern, "gi"), "");
    }

    // Procesar comando de mayúscula inicial (capitalizar únicamente la siguiente palabra)
    const capNextPattern = `\\b(${CAPITALIZE_NEXT_COMMANDS.map(escapeRegExp).join("|")})\\b`;
    if (new RegExp(capNextPattern, "i").test(text)) {
        shouldCapitalizeNext = true;
        text = text.replace(new RegExp(capNextPattern, "gi"), "");
    }

    // 4. Aplicar comandos de saltos de línea y párrafos (ordenados por longitud descendente)
    const lineBreakKeys = Object.keys(LINE_BREAK_COMMANDS).sort((a, b) => b.length - a.length);
    lineBreakKeys.forEach((cmd) => {
        const testRegex = new RegExp(`\\b${escapeRegExp(cmd)}\\b`, "i");
        if (testRegex.test(text)) {
            shouldCapitalizeNext = true;
            text = text.replace(
                new RegExp(`\\b${escapeRegExp(cmd)}\\b[.,;: ]*`, "gi"),
                LINE_BREAK_COMMANDS[cmd]
            );
        }
    });

    // 5. Aplicar comandos de puntuación y delimitadores
    const punctuationKeys = Object.keys(PUNCTUATION_COMMANDS).sort((a, b) => b.length - a.length);
    punctuationKeys.forEach((cmd) => {
        const testRegex = new RegExp(`\\b${escapeRegExp(cmd)}\\b`, "i");
        if (testRegex.test(text)) {
            const repl = PUNCTUATION_COMMANDS[cmd];
            if (repl.includes(".") || repl.includes(":")) {
                shouldCapitalizeNext = true;
            }
            text = text.replace(new RegExp(`\\b${escapeRegExp(cmd)}\\b`, "gi"), repl);
        }
    });

    // 6. Aplicar comandos de símbolos y operadores
    const symbolKeys = Object.keys(SYMBOL_COMMANDS).sort((a, b) => b.length - a.length);
    symbolKeys.forEach((cmd) => {
        text = text.replace(new RegExp(`\\b${escapeRegExp(cmd)}\\b`, "gi"), SYMBOL_COMMANDS[cmd]);
    });

    // 7. Aplicar comandos de formato clínico
    const formatKeys = Object.keys(FORMAT_COMMANDS).sort((a, b) => b.length - a.length);
    formatKeys.forEach((cmd) => {
        text = text.replace(new RegExp(`\\b${escapeRegExp(cmd)}\\b`, "gi"), FORMAT_COMMANDS[cmd]);
    });

    // 8. Limpiar espacios redundantes antes de signos de puntuación y asegurar separación
    text = text.replace(/[ \t]+([.,:;!?%])/g, "$1");
    // Asegurar que no queden palabras pegadas inmediatamente a signos de puntuación (ej. "Técnica.Falta" -> "Técnica. Falta")
    text = text.replace(/([.,:;!?])([A-Za-zÁÉÍÓÚáéíóúüñ])/g, "$1 $2");
    text = text.replace(/([.,:;!?])[ \t]*\n\n/g, (m, p1) => p1 + "\n\n");
    text = text.replace(/([.,:;!?])[ \t]*\n(?!\n)/g, (m, p1) => p1 + "\n");
    // Limpiar espacios horizontales tras saltos de línea y signos huérfanos tras saltos
    text = text.replace(/\n+[.,;:]+[ \t]*/g, "\n");
    text = text.replace(/\n+[.,;:]+$/g, (m) => m.replace(/[.,;:]+$/, ""));
    text = text.replace(/\n[ \t]+/g, "\n");
    text = text.replace(/[ \t]{2,}/g, " ");

    // 9. Aplicar mayúsculas sostenidas si está activado el estado
    if (isUppercase) {
        text = text.toUpperCase();
    }

    // 10. Capitalización inteligente de oraciones y párrafos
    if (shouldCapitalizeNext) {
        text = text.replace(/^([ \t]*)([a-záéíóúüñ])/i, (m, sp, ch) => {
            shouldCapitalizeNext = false;
            return sp + ch.toUpperCase();
        });
    }

    // Capitalizar letras tras saltos de línea dobles, simples o puntos internos
    text = text.replace(
        /(?:\n+|\.\s+)([a-záéíóúüñ])/gi,
        (m, ch) => m.slice(0, -1) + ch.toUpperCase()
    );

    // Si el texto finaliza en punto o salto de línea, marcar capitalizeNext para el próximo trozo
    if (/[.\n:]\s*$/.test(text)) {
        shouldCapitalizeNext = true;
    }

    // Si contiene saltos de línea (ej. comandos de salto "\n" o "\n\n" emitidos de forma aislada)
    if (/\n/.test(text)) {
        let cleanedBreaks = text.replace(/[ \t]+/g, " ").replace(/[ \t]*\n[ \t]*/g, "\n");
        cleanedBreaks = cleanedBreaks.replace(/\n+[.,;:]+$/g, (m) => m.replace(/[.,;:]+$/, ""));
        return {
            text: cleanedBreaks,
            uppercase: isUppercase,
            capitalizeNext: shouldCapitalizeNext,
        };
    }

    const trimmed = text.trim();
    if (!trimmed) {
        return { text: "", uppercase: isUppercase, capitalizeNext: shouldCapitalizeNext };
    }

    // Agregar espacio terminal solo si no termina en salto de línea
    const suffix = /\n$/.test(text) ? "" : " ";
    return {
        text: trimmed + suffix,
        uppercase: isUppercase,
        capitalizeNext: shouldCapitalizeNext,
    };
}
