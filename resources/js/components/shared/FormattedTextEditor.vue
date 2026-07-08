<template>
    <div class="formatted-editor">
        <div class="formatted-toolbar">
            <button
                type="button"
                class="btn btn-sm btn-light"
                title="Negrita"
                @click="insertFormat('*', '*')"
            >
                <b>B</b>
            </button>
            <button
                type="button"
                class="btn btn-sm btn-light"
                title="Cursiva"
                @click="insertFormat('_', '_')"
            >
                <i>I</i>
            </button>
            <button
                type="button"
                class="btn btn-sm btn-light"
                title="Tachado"
                @click="insertFormat('~', '~')"
            >
                <s>S</s>
            </button>
            <button
                type="button"
                class="btn btn-sm btn-light"
                title="Código"
                @click="insertFormat('```', '```')"
            >
                <code>{ }</code>
            </button>
            <span class="formatted-separator">|</span>
            <EmojiPicker @select="insertEmoji" />
        </div>
        <textarea
            ref="textarea"
            :value="modelValue"
            @input="onInput"
            class="form-control"
            :rows="rows"
            :placeholder="placeholder"
        ></textarea>
    </div>
</template>

<script>
import EmojiPicker from "@/components/shared/EmojiPicker.vue";

export default {
    components: { EmojiPicker },
    props: {
        modelValue: { type: String, default: "" },
        placeholder: { type: String, default: "" },
        rows: { type: [Number, String], default: 8 },
    },
    emits: ["update:modelValue"],
    methods: {
        onInput(e) {
            this.$emit("update:modelValue", e.target.value);
        },
        insertFormat(before, after) {
            const ta = this.$refs.textarea;
            if (!ta) return;
            const start = ta.selectionStart;
            const end = ta.selectionEnd;
            const text = this.modelValue || "";
            const selected = text.substring(start, end);
            const replacement = before + selected + after;
            this.$emit(
                "update:modelValue",
                text.substring(0, start) + replacement + text.substring(end)
            );
            this.$nextTick(() => {
                ta.focus();
                ta.selectionStart = start + before.length;
                ta.selectionEnd = start + before.length + selected.length;
            });
        },
        insertEmoji(emoji) {
            const ta = this.$refs.textarea;
            if (!ta) return;
            const start = ta.selectionStart;
            const end = ta.selectionEnd;
            const text = this.modelValue || "";
            this.$emit("update:modelValue", text.substring(0, start) + emoji + text.substring(end));
            this.$nextTick(() => {
                ta.focus();
                const pos = start + emoji.length;
                ta.selectionStart = pos;
                ta.selectionEnd = pos;
            });
        },
    },
};
</script>

<style scoped>
.formatted-editor {
    position: relative;
}

.formatted-toolbar {
    display: flex;
    align-items: center;
    gap: 2px;
    padding: 6px 8px;
    border: 1px solid #ced4da;
    border-bottom: none;
    border-radius: 4px 4px 0 0;
    background: #f8f9fa;
    flex-wrap: wrap;
    position: relative;
}

.formatted-toolbar .btn-light {
    border: 1px solid transparent;
    padding: 2px 10px;
    font-size: 14px;
    line-height: 1.5;
    min-width: 32px;
}

.formatted-toolbar .btn-light:hover {
    border-color: #ced4da;
    background: #e9ecef;
}

.formatted-toolbar .btn-light b {
    font-weight: 700;
}

.formatted-separator {
    color: #ced4da;
    padding: 0 4px;
    user-select: none;
}

.formatted-editor textarea {
    border-top-left-radius: 0;
    border-top-right-radius: 0;
}
</style>
