<template>
    <div class="wysiwyg-editor-wrapper">
        <!-- Banner de recuperación de borrador automático -->
        <div
            v-if="showDraftBanner"
            class="alert alert-info draft-banner d-flex align-items-center justify-content-between p-2 mb-2"
        >
            <div class="d-flex align-items-center">
                <i class="fa fa-history mr-2 text-info"></i>
                <span class="small">
                    Se encontró un <strong>borrador no guardado</strong> ({{ draftTimestamp }}).
                    ¿Deseas restaurarlo?
                </span>
            </div>
            <div class="draft-actions">
                <button
                    type="button"
                    class="btn btn-xs btn-info mr-1"
                    @click="restoreDraft"
                    title="Restaurar el texto del borrador automático"
                >
                    <i class="fa fa-undo mr-1"></i> Restaurar
                </button>
                <button
                    type="button"
                    class="btn btn-xs btn-outline-secondary"
                    @click="discardDraft"
                    title="Descartar este borrador"
                >
                    <i class="fa fa-times"></i> Descartar
                </button>
            </div>
        </div>

        <textarea
            ref="textareaRef"
            :id="editorId"
            class="form-control textarea_editor"
            :rows="rows"
            :placeholder="placeholder"
            :value="modelValue"
            @input="onNativeTextareaInput"
            @paste="onNativeTextareaPaste"
            @keydown="onNativeTextareaKeydown"
        ></textarea>
    </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, watch, nextTick } from "vue";
import { cleanPastedHtml, htmlToPlain, plainToHtml, CLINICAL_MACROS } from "@/helpers/wysiwyg.js";

let idCounter = 0;

const props = defineProps({
    modelValue: { type: String, default: "" },
    placeholder: { type: String, default: "" },
    rows: { type: Number, default: 4 },
    height: { type: String, default: "" },
    draftKey: { type: String, default: "" },
});

const emit = defineEmits(["update:modelValue", "draft-restored"]);

const textareaRef = ref(null);
const editorId = `wysiwyg-${++idCounter}`;
let observer = null;
let documentClickHandler = null;

// Estados reactivos para autoguardado de borradores
const showDraftBanner = ref(false);
const draftTimestamp = ref("");
let pendingDraftText = "";

/**
 * Maneja la entrada directa en el textarea nativo (modo fallback móvil o previo a wysihtml5).
 *
 * @param {Event} event - Evento input del textarea
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-30
 */
function onNativeTextareaInput(event) {
    const val = event?.target?.value ?? "";
    emit("update:modelValue", val);
    saveDraft(val);
}

/**
 * Intercepta pulsaciones en el textarea nativo para expandir macros al pulsar Espacio, Tab o Enter.
 *
 * @param {KeyboardEvent} e - Evento keydown nativo.
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-03
 */
function onNativeTextareaKeydown(e) {
    if (e.key === " " || e.key === "Tab" || e.key === "Enter") {
        const textarea = textareaRef.value;
        if (!textarea) return;

        const cursor = textarea.selectionStart ?? 0;
        const textBefore = textarea.value.substring(0, cursor);
        const match = textBefore.match(/(\.[a-z0-9_-]+)$/i);
        if (match) {
            const trigger = match[1].toLowerCase();
            if (CLINICAL_MACROS[trigger]) {
                e.preventDefault();
                const expansion = CLINICAL_MACROS[trigger];
                const startIdx = cursor - trigger.length;
                const before = textarea.value.substring(0, startIdx);
                const after = textarea.value.substring(cursor);

                textarea.value = before + expansion + (e.key === "Enter" ? "\n" : " ") + after;
                const newPos = startIdx + expansion.length + 1;
                textarea.selectionStart = textarea.selectionEnd = newPos;
                emit("update:modelValue", textarea.value);
                saveDraft(textarea.value);
            }
        }
    }
}

/**
 * Intercepta el evento de pegado en el textarea nativo (modo móvil o fallback)
 * para normalizar saltos de línea y limpiar texto pegado desde Word.
 *
 * @param {ClipboardEvent} e - Evento paste nativo.
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-03
 */
function onNativeTextareaPaste(e) {
    const clipboardData = e.clipboardData || window.clipboardData;
    if (!clipboardData) return;

    const htmlData = clipboardData.getData("text/html");
    const textData = clipboardData.getData("text/plain");

    let textToInsert = "";
    if (htmlData && htmlData.trim() !== "") {
        textToInsert = htmlToPlain(cleanPastedHtml(htmlData));
    } else if (textData) {
        textToInsert = textData.replace(/\r\n|\r/g, "\n");
    }

    if (textToInsert) {
        e.preventDefault();
        const textarea = textareaRef.value;
        if (!textarea) return;

        const start = textarea.selectionStart ?? textarea.value.length;
        const end = textarea.selectionEnd ?? textarea.value.length;
        const val = textarea.value || "";

        textarea.value = val.substring(0, start) + textToInsert + val.substring(end);
        textarea.selectionStart = textarea.selectionEnd = start + textToInsert.length;
        emit("update:modelValue", textarea.value);
        saveDraft(textarea.value);
    }
}

/**
 * Sincroniza de forma segura el valor texto plano hacia el textarea nativo y el composer de wysihtml5.
 * Evita llamar a parent.parse() cuando composer o su iframe aún no están inicializados (previniendo
 * TypeError en navegadores móviles donde wysihtml5 desactiva composer o carga asíncronamente).
 *
 * @param {string} rawVal - Texto plano a mostrar en el editor
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-30
 */
function syncEditorValue(rawVal) {
    const el = textareaRef.value;
    if (!el) return;
    const plain = rawVal || "";

    if (el.value !== plain) {
        el.value = plain;
    }

    if (!window.$) return;
    const inst = $(el).data("wysihtml5");
    if (!inst?.editor) return;

    const editor = inst.editor;
    if (editor.composer && editor.composer.element) {
        const current = htmlToPlain(editor.composer.getValue(false));
        if (current !== plain) {
            if (plain) {
                try {
                    editor.fire("unset_placeholder");
                } catch (_) {
                    /* noop */
                }
                editor.composer.setValue(plainToHtml(plain), false);
                editor.composer.element.classList?.remove("placeholder");
            } else {
                editor.composer.clear();
                try {
                    editor.fire("set_placeholder");
                } catch (_) {
                    /* noop */
                }
            }
        }
        applyHeight();
    } else if (editor.textarea?.element) {
        if (editor.textarea.element.value !== plain) {
            editor.textarea.element.value = plain;
        }
    }
}

/**
 * Guarda en localStorage el contenido actual del editor de forma silenciosa.
 *
 * @param {string} content - Texto plano a respaldar.
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-03
 */
function saveDraft(content) {
    if (!props.draftKey || !content || !content.trim()) return;
    try {
        localStorage.setItem(
            `lidiag_draft_${props.draftKey}`,
            JSON.stringify({ text: content, timestamp: Date.now() })
        );
    } catch (_) {
        /* noop */
    }
}

/**
 * Verifica si existe un borrador previo no guardado en localStorage para la clave activa.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-03
 */
function checkDraft() {
    if (!props.draftKey) return;
    try {
        const stored = localStorage.getItem(`lidiag_draft_${props.draftKey}`);
        if (!stored) return;

        const parsed = JSON.parse(stored);
        if (parsed?.text && parsed.text.trim() !== (props.modelValue || "").trim()) {
            pendingDraftText = parsed.text;
            draftTimestamp.value = new Date(parsed.timestamp).toLocaleTimeString([], {
                hour: "2-digit",
                minute: "2-digit",
            });
            showDraftBanner.value = true;
        }
    } catch (_) {
        /* noop */
    }
}

/**
 * Restaura el borrador automático recuperado en el editor.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-03
 */
function restoreDraft() {
    if (!pendingDraftText) return;
    syncEditorValue(pendingDraftText);
    emit("update:modelValue", pendingDraftText);
    emit("draft-restored", pendingDraftText);
    showDraftBanner.value = false;
}

/**
 * Descarta y elimina el borrador automático de localStorage.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-03
 */
function discardDraft() {
    if (props.draftKey) {
        try {
            localStorage.removeItem(`lidiag_draft_${props.draftKey}`);
        } catch (_) {
            /* noop */
        }
    }
    showDraftBanner.value = false;
    pendingDraftText = "";
}

/**
 * Limpia el borrador tras guardar exitosamente en backend.
 * Expuesto a través de defineExpose para ser llamado por el componente padre.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-03
 */
function clearDraft() {
    discardDraft();
}

/**
 * Registra y extiende comandos personalizados en wysihtml5 para soportar:
 * 1. Selección de familias tipográficas / tipos de letra (fontFamily).
 * 2. Selección de tamaños de fuente (fontSize) tanto para selecciones activas como posición de cursor.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-03
 */
function registerCustomWysihtml5Commands() {
    if (!window.wysihtml5) return;

    const REG_EXP_FONT = /wysiwyg-font-family-[a-z0-9-]+/g;
    if (!window.wysihtml5.commands?.fontFamily) {
        window.wysihtml5.commands.fontFamily = {
            exec: function (composer, command, font) {
                const range = composer.selection?.getRange();
                if (range && !range.collapsed) {
                    return window.wysihtml5.commands.formatInline.exec(
                        composer,
                        command,
                        "span",
                        `wysiwyg-font-family-${font}`,
                        REG_EXP_FONT
                    );
                } else if (composer.element) {
                    const node = composer.selection?.getSelectedNode();
                    const target =
                        node && node.nodeType === 1 && node !== composer.element
                            ? node
                            : composer.element;
                    target.className = `${(target.className || "").replace(REG_EXP_FONT, "").trim()} wysiwyg-font-family-${font}`;
                    return true;
                }
                return false;
            },
            state: function (composer, command, font) {
                return window.wysihtml5.commands.formatInline.state(
                    composer,
                    command,
                    "span",
                    `wysiwyg-font-family-${font}`,
                    REG_EXP_FONT
                );
            },
            value: function () {
                return undefined;
            },
        };
    }

    if (window.wysihtml5.commands?.fontSize && !window.wysihtml5.commands.fontSize._lidiagPatched) {
        const originalFontSizeExec = window.wysihtml5.commands.fontSize.exec;
        const REG_EXP_SIZE = /wysiwyg-font-size-[a-z0-9-]+/g;
        window.wysihtml5.commands.fontSize = {
            _lidiagPatched: true,
            exec: function (composer, command, size) {
                const range = composer.selection?.getRange();
                if (range && !range.collapsed) {
                    return originalFontSizeExec(composer, command, size);
                } else if (composer.element) {
                    const node = composer.selection?.getSelectedNode();
                    const target =
                        node && node.nodeType === 1 && node !== composer.element
                            ? node
                            : composer.element;
                    target.className = `${(target.className || "").replace(REG_EXP_SIZE, "").trim()} wysiwyg-font-size-${size}`;
                    return true;
                }
                return false;
            },
            state: window.wysihtml5.commands.fontSize.state,
            value: window.wysihtml5.commands.fontSize.value,
        };
    }
}

/**
 * Inicializa el editor Wysiwyg (wysihtml5) en el textarea,
 * configurando soporte táctil móvil, selectores de tipografía (fuente, tamaño, encabezados),
 * macros clínicas, reglas de parseo y autoguardado de borradores.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-03
 */
function initEditor() {
    const el = textareaRef.value;
    if (!el || !window.$ || !$.fn || !$.fn.wysihtml5) return;

    const $el = $(el);
    if ($el.data("wysihtml5")) return;
    if ($el.prevAll(".wysihtml5-toolbar").length) return;

    if (window.wysihtml5?.browser) {
        window.wysihtml5.browser.supported = () => true;
    }

    registerCustomWysihtml5Commands();

    if (props.modelValue) {
        el.value = props.modelValue;
    }

    $el.wysihtml5({
        stylesheets: ["/lib/css/wysiwyg-color.css"],
        supportTouchDevices: true,
        "font-styles": true,
        emphasis: true,
        lists: true,
        html: false,
        link: false,
        image: false,
        color: false,
        customTemplates: {
            "font-styles": function () {
                return `
                    <li class="dropdown">
                        <a class="btn dropdown-toggle" data-toggle="dropdown" href="javascript:;" role="button" aria-haspopup="true" aria-expanded="false" title="Estilo de párrafo o encabezado">
                            <i class="fa fa-header"></i>&nbsp;<span class="current-font">Párrafo</span>&nbsp;<b class="caret"></b>
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="javascript:;" data-wysihtml5-command="formatBlock" data-wysihtml5-command-value="div">Párrafo normal</a>
                            <a class="dropdown-item" href="javascript:;" data-wysihtml5-command="formatBlock" data-wysihtml5-command-value="h1"><span style="font-size: 1.2rem; font-weight: bold;">Título 1</span></a>
                            <a class="dropdown-item" href="javascript:;" data-wysihtml5-command="formatBlock" data-wysihtml5-command-value="h2"><span style="font-size: 1.05rem; font-weight: bold;">Título 2</span></a>
                            <a class="dropdown-item" href="javascript:;" data-wysihtml5-command="formatBlock" data-wysihtml5-command-value="h3"><span style="font-size: 0.95rem; font-weight: bold;">Título 3</span></a>
                        </div>
                    </li>
                `;
            },
            emphasis: function () {
                return `
                    <li>
                        <div class="btn-group">
                            <a class="btn" data-wysihtml5-command="bold" title="Negrita (Ctrl+B)"><i class="fa fa-bold"></i></a>
                            <a class="btn" data-wysihtml5-command="italic" title="Cursiva (Ctrl+I)"><i class="fa fa-italic"></i></a>
                            <a class="btn" data-wysihtml5-command="underline" title="Subrayado (Ctrl+U)"><i class="fa fa-underline"></i></a>
                        </div>
                    </li>
                `;
            },
            lists: function () {
                return `
                    <li>
                        <div class="btn-group">
                            <a class="btn" data-wysihtml5-command="insertUnorderedList" title="Viñetas"><i class="fa fa-list-ul"></i></a>
                            <a class="btn" data-wysihtml5-command="insertOrderedList" title="Lista numerada"><i class="fa fa-list-ol"></i></a>
                            <a class="btn" data-wysihtml5-command="Outdent" title="Reducir sangría"><i class="fa fa-outdent"></i></a>
                            <a class="btn" data-wysihtml5-command="Indent" title="Aumentar sangría"><i class="fa fa-indent"></i></a>
                        </div>
                    </li>
                `;
            },
        },
        toolbar: {
            "font-family": `
                <li class="dropdown">
                    <a class="btn dropdown-toggle" data-toggle="dropdown" href="javascript:;" role="button" aria-haspopup="true" aria-expanded="false" title="Tipo de fuente">
                        <i class="fa fa-font"></i>&nbsp;<span class="current-font-family">Fuente</span>&nbsp;<b class="caret"></b>
                    </a>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="javascript:;" data-wysihtml5-command="fontFamily" data-wysihtml5-command-value="arial" style="font-family: Arial, sans-serif;">Arial</a>
                        <a class="dropdown-item" href="javascript:;" data-wysihtml5-command="fontFamily" data-wysihtml5-command-value="calibri" style="font-family: Calibri, sans-serif;">Calibri</a>
                        <a class="dropdown-item" href="javascript:;" data-wysihtml5-command="fontFamily" data-wysihtml5-command-value="times" style="font-family: 'Times New Roman', serif;">Times New Roman</a>
                        <a class="dropdown-item" href="javascript:;" data-wysihtml5-command="fontFamily" data-wysihtml5-command-value="roboto" style="font-family: Roboto, sans-serif;">Roboto</a>
                        <a class="dropdown-item" href="javascript:;" data-wysihtml5-command="fontFamily" data-wysihtml5-command-value="verdana" style="font-family: Verdana, sans-serif;">Verdana</a>
                        <a class="dropdown-item" href="javascript:;" data-wysihtml5-command="fontFamily" data-wysihtml5-command-value="tahoma" style="font-family: Tahoma, sans-serif;">Tahoma</a>
                        <a class="dropdown-item" href="javascript:;" data-wysihtml5-command="fontFamily" data-wysihtml5-command-value="courier" style="font-family: 'Courier New', monospace;">Courier New</a>
                    </div>
                </li>
            `,
            "font-size": `
                <li class="dropdown">
                    <a class="btn dropdown-toggle" data-toggle="dropdown" href="javascript:;" role="button" aria-haspopup="true" aria-expanded="false" title="Tamaño de fuente">
                        <i class="fa fa-text-height"></i>&nbsp;<span class="current-font-size">Tamaño</span>&nbsp;<b class="caret"></b>
                    </a>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="javascript:;" data-wysihtml5-command="fontSize" data-wysihtml5-command-value="small" style="font-size: 11px;">Pequeño (11px)</a>
                        <a class="dropdown-item" href="javascript:;" data-wysihtml5-command="fontSize" data-wysihtml5-command-value="normal" style="font-size: 14px;">Normal (14px)</a>
                        <a class="dropdown-item" href="javascript:;" data-wysihtml5-command="fontSize" data-wysihtml5-command-value="medium" style="font-size: 16px;">Mediano (16px)</a>
                        <a class="dropdown-item" href="javascript:;" data-wysihtml5-command="fontSize" data-wysihtml5-command-value="large" style="font-size: 20px;">Grande (20px)</a>
                        <a class="dropdown-item" href="javascript:;" data-wysihtml5-command="fontSize" data-wysihtml5-command-value="xlarge" style="font-size: 24px;">Muy grande (24px)</a>
                    </div>
                </li>
            `,
            macros: `
                <li class="dropdown">
                    <a class="btn dropdown-toggle" data-toggle="dropdown" href="javascript:;" role="button" aria-haspopup="true" aria-expanded="false" title="Plantillas y Macros Rápidas">
                        <i class="fa fa-magic text-warning"></i>&nbsp;<span class="current-macro">Macros</span>&nbsp;<b class="caret"></b>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" style="min-width: 250px;">
                        <h6 class="dropdown-header text-muted" style="font-size: 11px; padding: 4px 14px;">Escribe el atajo o haz clic:</h6>
                        <a class="dropdown-item macro-item" href="javascript:;" data-macro-key=".normal"><strong>.normal</strong> — Normal estándar</a>
                        <a class="dropdown-item macro-item" href="javascript:;" data-macro-key=".torax"><strong>.torax</strong> — Tórax normal</a>
                        <a class="dropdown-item macro-item" href="javascript:;" data-macro-key=".craneo"><strong>.craneo</strong> — Cráneo simple normal</a>
                        <a class="dropdown-item macro-item" href="javascript:;" data-macro-key=".abdomen"><strong>.abdomen</strong> — Abdomen normal</a>
                        <a class="dropdown-item macro-item" href="javascript:;" data-macro-key=".columna"><strong>.columna</strong> — Columna normal</a>
                        <a class="dropdown-item macro-item" href="javascript:;" data-macro-key=".sinpatologia"><strong>.sinpatologia</strong> — Sin patología aguda</a>
                        <a class="dropdown-item macro-item" href="javascript:;" data-macro-key=".conclusion"><strong>.conclusion</strong> — Conclusión estándar</a>
                    </div>
                </li>
            `,
        },
        parserRules: {
            classes: {
                "wysiwyg-color-silver": 1,
                "wysiwyg-color-gray": 1,
                "wysiwyg-color-white": 1,
                "wysiwyg-color-maroon": 1,
                "wysiwyg-color-red": 1,
                "wysiwyg-color-purple": 1,
                "wysiwyg-color-fuchsia": 1,
                "wysiwyg-color-green": 1,
                "wysiwyg-color-lime": 1,
                "wysiwyg-color-olive": 1,
                "wysiwyg-color-yellow": 1,
                "wysiwyg-color-navy": 1,
                "wysiwyg-color-blue": 1,
                "wysiwyg-color-teal": 1,
                "wysiwyg-color-aqua": 1,
                "wysiwyg-color-orange": 1,

                // Clases de tamaño de fuente
                "wysiwyg-font-size-small": 1,
                "wysiwyg-font-size-normal": 1,
                "wysiwyg-font-size-medium": 1,
                "wysiwyg-font-size-large": 1,
                "wysiwyg-font-size-xlarge": 1,

                // Clases de familia tipográfica
                "wysiwyg-font-family-arial": 1,
                "wysiwyg-font-family-calibri": 1,
                "wysiwyg-font-family-times": 1,
                "wysiwyg-font-family-roboto": 1,
                "wysiwyg-font-family-verdana": 1,
                "wysiwyg-font-family-tahoma": 1,
                "wysiwyg-font-family-courier": 1,
            },
            tags: {
                b: {},
                strong: {},
                i: {},
                em: {},
                u: 1,
                br: {},
                p: {},
                div: {},
                span: 1,
                ol: {},
                ul: {},
                li: {},
                h1: {},
                h2: {},
                h3: {},
                blockquote: {},
            },
        },
    });

    const inst = $el.data("wysihtml5");
    if (inst?.editor) {
        setupToolbarInteractions($el, inst.editor);

        const onEditorReady = () => {
            syncEditorValue(props.modelValue);
            attachObserver(inst.editor);
            applyHeight();
            checkDraft();
            setTimeout(applyHeight, 300);
        };

        inst.editor.observe("load", onEditorReady);

        inst.editor.on("blur", () => {
            const val = inst.editor.composer?.element
                ? htmlToPlain(inst.editor.composer.getValue(false))
                : el.value || "";
            emit("update:modelValue", val);
            saveDraft(val);
        });

        inst.editor.on("change_view", () => {
            setTimeout(applyHeight, 50);
        });

        nextTick(onEditorReady);
    }
}

/**
 * Configura los eventos de clic e interacciones de los menús desplegables
 * de la barra de herramientas (Fuentes, Tamaños, Estilos y Macros clínicas).
 *
 * @param {Object} $el - Elemento jQuery del textarea.
 * @param {Object} editor - Instancia de wysihtml5.Editor.
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-03
 */
function setupToolbarInteractions($el, editor) {
    const $toolbar = $el.prev(".wysihtml5-toolbar");
    if (!$toolbar.length) return;

    $toolbar.find("a[data-wysihtml5-command='fontFamily']").on("click", function () {
        const text = $(this).text().trim();
        $toolbar.find(".current-font-family").text(text);
        $toolbar.find(".dropdown.show, .dropdown-menu.show").removeClass("show");
    });

    $toolbar.find("a[data-wysihtml5-command='fontSize']").on("click", function () {
        const text = $(this).text().trim();
        $toolbar.find(".current-font-size").text(text);
        $toolbar.find(".dropdown.show, .dropdown-menu.show").removeClass("show");
    });

    $toolbar.find("a[data-wysihtml5-command='formatBlock']").on("click", function () {
        const text = $(this).text().trim();
        $toolbar.find(".current-font").text(text);
        $toolbar.find(".dropdown.show, .dropdown-menu.show").removeClass("show");
    });

    // Inserción interactiva de macros desde el menú desplegable
    $toolbar.find("a[data-macro-key]").on("click", function () {
        const key = $(this).data("macro-key");
        insertMacroAtCaret(key, editor);
        $toolbar.find(".dropdown.show, .dropdown-menu.show").removeClass("show");
    });

    $toolbar.find("[data-toggle='dropdown']").on("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        const $parent = $(this).closest(".dropdown");
        const $menu = $parent.find(".dropdown-menu");
        const isOpen = $parent.hasClass("show");

        $toolbar.find(".dropdown.show, .dropdown-menu.show").removeClass("show");

        if (!isOpen) {
            $parent.addClass("show");
            $menu.addClass("show");
        }
    });

    if (documentClickHandler) {
        $(document).off("click", documentClickHandler);
    }

    documentClickHandler = (e) => {
        if (!window.$) return;
        if (!$(e.target).closest(".wysihtml5-toolbar .dropdown").length) {
            $toolbar.find(".dropdown.show, .dropdown-menu.show").removeClass("show");
        }
    };
    $(document).on("click", documentClickHandler);
}

/**
 * Inserta el contenido expandido de una macro clínica en la posición del cursor
 * dentro del composer de wysihtml5 o textarea nativo.
 *
 * @param {string} triggerKey - Clave de la macro (ej. .torax, .normal).
 * @param {Object} editor - Instancia del editor wysihtml5.
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-03
 */
function insertMacroAtCaret(triggerKey, editor) {
    const text = CLINICAL_MACROS[triggerKey];
    if (!text) return;

    const iframe = textareaRef.value?.parentElement?.querySelector(".wysihtml5-sandbox");
    const htmlExpansion = plainToHtml(text);

    let inserted = false;
    try {
        if (editor?.composer?.commands?.support?.("insertHTML")) {
            const separator = editor.composer.getValue(false) ? "<br>" : "";
            editor.composer.commands.exec("insertHTML", `${separator + htmlExpansion} `);
            inserted = true;
        } else if (iframe?.contentDocument?.execCommand) {
            iframe.contentDocument.execCommand("insertHTML", false, `${htmlExpansion} `);
            inserted = true;
        }
    } catch (_) {
        inserted = false;
    }

    if (!inserted) {
        const val = textareaRef.value?.value || "";
        const newVal = (val ? `${val}\n` : "") + text;
        if (textareaRef.value) textareaRef.value.value = newVal;
        emit("update:modelValue", newVal);
        saveDraft(newVal);
        return;
    }

    nextTick(() => {
        const current = editor?.composer?.element
            ? htmlToPlain(editor.composer.getValue(false))
            : textareaRef.value?.value || "";
        emit("update:modelValue", current);
        saveDraft(current);
    });
}

/**
 * Aplica el ancho (100%) y el alto al iframe visible del editor (wysihtml5 oculta el textarea,
 * por eso el atributo rows solo no tiene efecto visual).
 * En pantallas móviles ajusta una altura proporcional cómoda para no desbordar el viewport.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-30
 */
function applyHeight() {
    try {
        const isMobile = typeof window !== "undefined" && window.innerWidth < 768;
        const computedRowsHeight =
            props.rows > 4 ? (isMobile && props.rows > 12 ? "260px" : `${props.rows * 22}px`) : "";
        const height = props.height || computedRowsHeight;
        const iframe = textareaRef.value?.parentElement?.querySelector(".wysihtml5-sandbox");
        if (iframe) {
            iframe.style.setProperty("width", "100%", "important");
            if (height) {
                iframe.style.setProperty("height", height, "important");
                iframe.style.setProperty("min-height", height, "important");
            }
        }
    } catch (_) {
        /* noop */
    }
}

/**
 * Destruye el editor Wysiwyg: desconecta el observer, remueve escuchadores globales,
 * elimina la barra de herramientas, el sandbox y los elementos auxiliares del DOM.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-03
 */
function destroyEditor() {
    const el = textareaRef.value;
    if (!el) return;

    if (documentClickHandler && window.$) {
        $(document).off("click", documentClickHandler);
        documentClickHandler = null;
    }

    const $el = $(el);
    const inst = $el.data("wysihtml5");

    disconnectObserver();

    try {
        if (inst?.editor?.composer?.sandbox?.destroy) {
            inst.editor.composer.sandbox.destroy();
        }
    } catch (_) {
        /* noop */
    }

    $el.parent().find(".wysihtml5-toolbar").remove();
    $el.parent().find(".wysihtml5-sandbox").remove();
    $el.parent().find('input[type="hidden"][name="_wysihtml5_mode"]').remove();

    $el.show();
    $el.removeData("wysihtml5");
}

/**
 * Adjunta un MutationObserver al body del iframe del editor para detectar
 * cambios en el contenido y sincronizarlos con el modelo v-model y con el borrador local.
 *
 * @param {Object} editor - Instancia del editor wysihtml5.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-18
 */
function attachObserver(editor) {
    disconnectObserver();
    try {
        const iframe = textareaRef.value?.parentElement?.querySelector(".wysihtml5-sandbox");
        if (!iframe?.contentDocument?.body) return;

        let pending = false;
        observer = new MutationObserver(() => {
            if (pending) return;
            pending = true;
            setTimeout(() => {
                const rawHtml = editor.composer?.element
                    ? editor.composer.getValue(false)
                    : editor.getValue(false);
                const plain = htmlToPlain(rawHtml);
                emit("update:modelValue", plain);
                saveDraft(plain);
                pending = false;
            }, 300);
        });

        observer.observe(iframe.contentDocument.body, {
            childList: true,
            subtree: true,
            characterData: true,
        });

        if (iframe.contentWindow && window.$) {
            $(iframe.contentWindow).on("focus.wysihtml5_dropdown", () => {
                $(textareaRef.value?.parentElement)
                    .find(
                        ".wysihtml5-toolbar .dropdown.show, .wysihtml5-toolbar .dropdown-menu.show"
                    )
                    .removeClass("show open");
            });
        }

        attachKeydownForwarding(iframe, editor);
        attachPasteForwarding(iframe, editor);
    } catch (_) {
        /* noop */
    }
}

/**
 * Intercepta el evento de pegado (paste) en el iframe del editor wysihtml5
 * para limpiar el HTML de Word u otras fuentes, preservando saltos de línea y formato seguro.
 *
 * @param {HTMLIFrameElement} iframe - Elemento iframe del sandbox.
 * @param {Object} editor - Instancia del editor wysihtml5.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-03
 */
function attachPasteForwarding(iframe, editor) {
    try {
        if (!iframe?.contentDocument) return;
        iframe.contentDocument.addEventListener("paste", (e) => {
            const clipboardData = e.clipboardData || window.clipboardData;
            if (!clipboardData) return;

            const htmlData = clipboardData.getData("text/html");
            const textData = clipboardData.getData("text/plain");

            let contentToInsert = "";
            if (htmlData && htmlData.trim() !== "") {
                contentToInsert = cleanPastedHtml(htmlData);
            } else if (textData) {
                contentToInsert = plainToHtml(textData);
            }

            if (contentToInsert) {
                e.preventDefault();
                e.stopImmediatePropagation();

                let inserted = false;
                try {
                    if (editor?.composer?.commands?.support?.("insertHTML")) {
                        editor.composer.commands.exec("insertHTML", contentToInsert);
                        inserted = true;
                    } else if (iframe.contentDocument.execCommand) {
                        inserted = iframe.contentDocument.execCommand(
                            "insertHTML",
                            false,
                            contentToInsert
                        );
                    }
                } catch (_) {
                    inserted = false;
                }

                if (!inserted && editor?.composer) {
                    editor.composer.setValue(contentToInsert, false);
                }

                nextTick(() => {
                    const current = editor.composer?.element
                        ? htmlToPlain(editor.composer.getValue(false))
                        : textareaRef.value?.value || "";
                    emit("update:modelValue", current);
                    saveDraft(current);
                });
            }
        });
    } catch (_) {
        /* noop */
    }
}

/**
 * Reenvía atajos de teclado clínicos (Ctrl+S, F2, Esc) desde el iframe hacia window
 * e intercepta macros clínicas (Espacio, Tab, Enter precedidos de .clave).
 *
 * @param {HTMLIFrameElement} iframe Elemento iframe del sandbox wysihtml5.
 * @param {Object} editor Instancia del editor wysihtml5.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-03
 */
function attachKeydownForwarding(iframe, editor) {
    try {
        if (!iframe?.contentDocument) return;
        iframe.contentDocument.addEventListener("keydown", (e) => {
            const isSave = (e.ctrlKey || e.metaKey) && (e.key === "s" || e.key === "S");
            const isF2 = e.key === "F2";
            const isEscape = e.key === "Escape";

            if (isSave || isF2 || isEscape) {
                e.preventDefault();
                const evt = new KeyboardEvent("keydown", {
                    key: e.key,
                    code: e.code,
                    ctrlKey: e.ctrlKey,
                    metaKey: e.metaKey,
                    altKey: e.altKey,
                    shiftKey: e.shiftKey,
                    bubbles: true,
                    cancelable: true,
                });
                window.dispatchEvent(evt);
                return;
            }

            // Expansión de macro al presionar Espacio, Tab o Enter
            if (e.key === " " || e.key === "Tab" || e.key === "Enter") {
                const sel = iframe.contentWindow?.getSelection();
                if (!sel || !sel.rangeCount) return;

                const range = sel.getRangeAt(0);
                const node = range.startContainer;
                if (node && node.nodeType === 3) {
                    const text = node.nodeValue || "";
                    const cursorOffset = range.startOffset;
                    const textBefore = text.substring(0, cursorOffset);
                    const match = textBefore.match(/(\.[a-z0-9_-]+)$/i);

                    if (match) {
                        const trigger = match[1].toLowerCase();
                        if (CLINICAL_MACROS[trigger]) {
                            e.preventDefault();
                            e.stopPropagation();

                            const expansion = CLINICAL_MACROS[trigger];
                            const startIdx = cursorOffset - trigger.length;

                            // Crear rango sobre la palabra clave de la macro
                            const macroRange = iframe.contentDocument.createRange();
                            macroRange.setStart(node, startIdx);
                            macroRange.setEnd(node, cursorOffset);
                            sel.removeAllRanges();
                            sel.addRange(macroRange);

                            const htmlExpansion = `${plainToHtml(expansion) + (e.key === "Enter" ? "<br>" : " ")}&nbsp;`;

                            if (editor?.composer?.commands?.support?.("insertHTML")) {
                                editor.composer.commands.exec("insertHTML", htmlExpansion);
                            } else {
                                iframe.contentDocument.execCommand(
                                    "insertHTML",
                                    false,
                                    htmlExpansion
                                );
                            }

                            nextTick(() => {
                                const current = editor?.composer?.element
                                    ? htmlToPlain(editor.composer.getValue(false))
                                    : "";
                                emit("update:modelValue", current);
                                saveDraft(current);
                            });
                        }
                    }
                }
            }
        });
    } catch (_) {
        /* noop */
    }
}

/**
 * Desconecta y elimina el MutationObserver activo para evitar fugas de memoria.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-18
 */
function disconnectObserver() {
    if (observer) {
        observer.disconnect();
        observer = null;
    }
}

/**
 * Watcher que sincroniza cambios externos de modelValue hacia el editor Wysiwyg,
 * evitando ciclos infinitos comparando el contenido actual con el nuevo valor.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-18
 */
watch(
    () => props.modelValue,
    (newVal) => {
        syncEditorValue(newVal);
    }
);

/**
 * Watcher que evalúa la clave de borrador si cambia dinámicamente al abrir otro estudio.
 */
watch(
    () => props.draftKey,
    () => {
        checkDraft();
    }
);

/**
 * Expone métodos útiles al componente padre (limpiar borrador, restaurar, descartar).
 */
defineExpose({
    clearDraft,
    restoreDraft,
    discardDraft,
});

/**
 * Lifecycle hook que inicializa el editor Wysiwyg después del nextTick.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-18
 */
onMounted(() => nextTick(initEditor));

/**
 * Lifecycle hook que destruye el editor Wysiwyg al desmontar el componente.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-18
 */
onBeforeUnmount(destroyEditor);
</script>

<style scoped>
.wysihtml5-editor-wrapper {
    position: relative;
    width: 100%;
}

.draft-banner {
    background-color: #e8f4fd;
    border: 1px solid #b8daff;
    border-radius: 4px;
    color: #0c5460;
}

.btn-xs {
    padding: 2px 7px;
    font-size: 11px;
    border-radius: 3px;
    line-height: 1.3;
}

:deep(.wysihtml5-toolbar) {
    margin: 0;
    padding: 6px 8px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-bottom: 0;
    border-top-left-radius: 4px;
    border-top-right-radius: 4px;
    list-style: none;
}

:deep(.wysihtml5-toolbar > li) {
    float: none;
    display: inline-flex;
    align-items: center;
    margin: 0;
    list-style: none;
}

:deep(.wysihtml5-toolbar .btn) {
    padding: 5px 10px;
    font-size: 13px;
    border: 1px solid #ced4da;
    background-color: #ffffff;
    color: #495057;
    border-radius: 4px;
    line-height: 1.4;
    transition: all 0.15s ease-in-out;
}

:deep(.wysihtml5-toolbar .btn:hover) {
    background-color: #e2e6ea;
    border-color: #dae0e5;
    color: #212529;
}

:deep(.wysihtml5-toolbar .btn.wysihtml5-command-active) {
    background-color: #007bff;
    border-color: #007bff;
    color: #ffffff;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.15);
}

:deep(.wysihtml5-toolbar .dropdown-menu) {
    min-width: 180px;
    max-height: 280px;
    overflow-y: auto;
    font-size: 13px;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(0, 0, 0, 0.15);
    border-radius: 4px;
    padding: 4px 0;
    z-index: 1050;
}

:deep(.wysihtml5-toolbar .dropdown-item) {
    display: block;
    width: 100%;
    padding: 6px 14px;
    clear: both;
    font-weight: 400;
    color: #212529;
    text-align: inherit;
    white-space: nowrap;
    background-color: transparent;
    border: 0;
    cursor: pointer;
    text-decoration: none;
}

:deep(.wysihtml5-toolbar .dropdown-item:hover) {
    background-color: #f1f3f5;
    color: #16181b;
}

:deep(.wysihtml5-sandbox) {
    border-top-left-radius: 0 !important;
    border-top-right-radius: 0 !important;
}
</style>
