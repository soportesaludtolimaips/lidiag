<template>
    <textarea
        ref="textareaRef"
        :id="editorId"
        class="form-control textarea_editor"
        :rows="rows"
        :placeholder="placeholder"
        :value="modelValue"
        @input="onNativeTextareaInput"
        @paste="onNativeTextareaPaste"
    ></textarea>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, watch, nextTick } from "vue";
import { cleanPastedHtml, htmlToPlain, plainToHtml } from "@/helpers/wysiwyg.js";

let idCounter = 0;

const props = defineProps({
    modelValue: { type: String, default: "" },
    placeholder: { type: String, default: "" },
    rows: { type: Number, default: 4 },
    height: { type: String, default: "" },
});

const emit = defineEmits(["update:modelValue"]);

const textareaRef = ref(null);
const editorId = `wysiwyg-${++idCounter}`;
let observer = null;

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
    emit("update:modelValue", event?.target?.value ?? "");
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
 * Inicializa el editor Wysiwyg (wysihtml5) en el textarea,
 * configurando soporte táctil móvil, reglas de parseo con soporte de párrafos y divs,
 * valor inicial, evento load/blur y observer de mutaciones.
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

    // wysihtml5 0.3.0 detecta erróneamente Chrome Android como iOS < 5 incompatible.
    // Habilitamos explícitamente el soporte en navegadores móviles modernos.
    if (window.wysihtml5?.browser) {
        window.wysihtml5.browser.supported = () => true;
    }

    if (props.modelValue) {
        el.value = props.modelValue;
    }

    $el.wysihtml5({
        stylesheets: ["/lib/css/wysiwyg-color.css"],
        supportTouchDevices: true,
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
        const onEditorReady = () => {
            syncEditorValue(props.modelValue);
            attachObserver(inst.editor);
            applyHeight();
            setTimeout(applyHeight, 300);
        };

        inst.editor.observe("load", onEditorReady);

        inst.editor.on("blur", () => {
            const val = inst.editor.composer?.element
                ? htmlToPlain(inst.editor.composer.getValue(false))
                : el.value || "";
            emit("update:modelValue", val);
        });

        inst.editor.on("change_view", () => {
            setTimeout(applyHeight, 50);
        });

        nextTick(onEditorReady);
    }
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
 * Destruye el editor Wysiwyg: desconecta el observer, elimina la barra
 * de herramientas, el sandbox y los elementos auxiliares del DOM.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-18
 */
function destroyEditor() {
    const el = textareaRef.value;
    if (!el) return;
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
 * cambios en el contenido y sincronizarlos con el modelo v-model.
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
                emit("update:modelValue", htmlToPlain(rawHtml));
                pending = false;
            }, 300);
        });

        observer.observe(iframe.contentDocument.body, {
            childList: true,
            subtree: true,
            characterData: true,
        });

        attachKeydownForwarding(iframe);
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
                });
            }
        });
    } catch (_) {
        /* noop */
    }
}

/**
 * Reenvía atajos de teclado clínicos (Ctrl+S, F2, Esc) desde el iframe hacia window.
 *
 * @param {HTMLIFrameElement} iframe Elemento iframe del sandbox wysihtml5.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-19
 */
function attachKeydownForwarding(iframe) {
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
