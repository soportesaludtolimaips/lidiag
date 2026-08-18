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
});

const emit = defineEmits(["update:modelValue"]);

const textareaRef = ref(null);
const editorId = `wysiwyg-${++idCounter}`;
let observer = null;

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
            inst.editor.setValue(props.modelValue, true);
        }

        inst.editor.on("blur", () => {
            const val = inst.editor.getValue();
            emit("update:modelValue", val);
        });

        nextTick(() => attachObserver(inst.editor));
    }
}

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
                emit("update:modelValue", editor.getValue());
                pending = false;
            }, 300);
        });

        observer.observe(iframe.contentDocument.body, {
            childList: true,
            subtree: true,
            characterData: true,
        });
    } catch (_) {
        /* noop */
    }
}

function disconnectObserver() {
    if (observer) {
        observer.disconnect();
        observer = null;
    }
}

watch(
    () => props.modelValue,
    (newVal) => {
        const el = textareaRef.value;
        if (!el) return;
        const inst = $(el).data("wysihtml5");
        if (inst?.editor) {
            const current = inst.editor.getValue();
            if (current !== (newVal || "")) {
                inst.editor.setValue(newVal || "", true);
            }
        }
    }
);

onMounted(() => nextTick(initEditor));
onBeforeUnmount(destroyEditor);
</script>
