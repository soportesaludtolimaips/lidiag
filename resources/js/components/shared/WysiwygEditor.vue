<template>
    <textarea
        ref="textareaRef"
        :id="editorId"
        class="form-control textarea_editor"
        :rows="rows"
        :placeholder="placeholder"
    ></textarea>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, watch, nextTick } from "vue";

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
 * Convierte saltos de linea plano a HTML para wysihtml5.
 * @param {string} text - Texto plano con \n
 * @returns {string} HTML con <br> y <p>
 */
function plainToHtml(text) {
    if (!text) return "";
    return text.replace(/\n/g, "<br>");
}

/**
 * Convierte HTML del editor a texto plano para el backend/SAHI.
 * Reemplaza <br>, </p><p>, </p>, <p> por saltos de linea \n.
 * @param {string} html - HTML del editor
 * @returns {string} Texto plano con \n
 */
function htmlToPlain(html) {
    if (!html) return "";
    return html
        .replace(/<br\s*\/?>/gi, "\n")
        .replace(/<\/p><p>/gi, "\n\n")
        .replace(/<\/p>/gi, "\n")
        .replace(/<p>/gi, "")
        .replace(/<[^>]+>/g, "")
        .trim();
}

/**
 * Inicializa el editor Wysiwyg (wysihtml5) en el textarea,
 * configurando el valor inicial, evento blur y observer de mutaciones.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-08-18
 */
function initEditor() {
    const el = textareaRef.value;
    if (!el || !window.$ || !$.fn || !$.fn.wysihtml5) return;

    const $el = $(el);
    if ($el.data("wysihtml5")) return;
    if ($el.prevAll(".wysihtml5-toolbar").length) return;

    $el.wysihtml5({
        stylesheets: ["/lib/css/wysiwyg-color.css"],
    });

    const inst = $el.data("wysihtml5");
    if (inst?.editor) {
        if (props.modelValue) {
            inst.editor.setValue(plainToHtml(props.modelValue), true);
        }

        inst.editor.on("blur", () => {
            const val = htmlToPlain(inst.editor.getValue());
            emit("update:modelValue", val);
        });

        nextTick(() => {
            attachObserver(inst.editor);
            applyHeight();
            setTimeout(applyHeight, 500);
        });
    }
}

/**
 * Aplica el alto al iframe visible del editor (wysihtml5 oculta el textarea,
 * por eso el atributo rows solo no tiene efecto visual).
 * Prioridad: prop height > alto derivado de rows (solo si rows > 4) > defecto del plugin.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-09-23
 */
function applyHeight() {
    try {
        const height = props.height || (props.rows > 4 ? `${props.rows * 22}px` : "");
        if (!height) return;
        const iframe = textareaRef.value?.parentElement?.querySelector(".wysihtml5-sandbox");
        if (iframe) {
            iframe.style.setProperty("height", height, "important");
            iframe.style.setProperty("min-height", height, "important");
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
                emit("update:modelValue", htmlToPlain(editor.getValue()));
                pending = false;
            }, 300);
        });

        observer.observe(iframe.contentDocument.body, {
            childList: true,
            subtree: true,
            characterData: true,
        });

        attachKeydownForwarding(iframe);
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
        const el = textareaRef.value;
        if (!el) return;
        const inst = $(el).data("wysihtml5");
        if (inst?.editor) {
            const current = htmlToPlain(inst.editor.getValue());
            const plain = newVal || "";
            if (current !== plain) {
                inst.editor.setValue(plainToHtml(plain), true);
            }
        }
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
