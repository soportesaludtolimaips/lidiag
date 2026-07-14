<template>
    <select
        ref="el"
        class="select2 form-control custom-select"
        :style="{ width, height: height + 'px' }"
        :multiple="multiple"
    >
        <option v-for="opt in options" :key="opt[optionValue]" :value="opt[optionValue]">
            {{ formatLabel(opt) }}
        </option>
    </select>
</template>
<script setup>
import { nextTick, onMounted } from "vue";

const props = defineProps({
    value: { type: [String, Number, Array, null], default: null },
    options: { type: Array, default: () => [] },
    optionValue: { type: String, default: "id" },
    labelFields: { type: Array, default: () => ["cod_cups", "nom_produc"] },
    width: { type: String, default: "100%" },
    minimumInputLength: { type: Number, default: 0 },
    dropdownParentSelector: { type: String, default: "body" },
    height: { type: [Number, String], default: 36 },
    accentInsensitive: { type: Boolean, default: true },
    allowClear: { type: Boolean, default: false },
    multiple: { type: Boolean, default: false },
    returnArray: { type: Boolean, default: false },
    returnObject: { type: Boolean, default: false },
});

function select() {
    const el = el.value;
    if (!el || !window.$ || !$.fn || !$.fn.select2) return null;
    return $(el);
}
function formatLabel(opt) {
    const parts = labelFields.map((f) => opt && opt[f]).filter(Boolean);
    return parts.join(" - ");
}
function normalizeText(term) {
    return String(term || "")
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/\s+/g, "");
}
function initSelect2() {
    const $el = $select();
    if (!$el) return;
    if ($el.data("select2")) {
        $el.off("select2:select");
        $el.off("select2:unselect");
        $el.select2("destroy");
    }

    const matcher = (params, data) => {
        if ($.trim(params.term) === "") return data;
        if (typeof data.text === "undefined") return null;
        if (!accentInsensitive) {
            return data.text.toLowerCase().includes(String(params.term || "").toLowerCase())
                ? data
                : null;
        }
        const term = normalizeText(params.term || "");
        const text = normalizeText(data.text || "");
        return text.indexOf(term) > -1 ? data : null;
    };

    const parent = $(dropdownParentSelector);
    $el.select2({
        width: width,
        dropdownParent: parent && parent.length ? parent : undefined,
        minimumInputLength: minimumInputLength,
        allowClear: allowClear,
        matcher,
    });

    // Inicializar valor actual
    $el.val(props.value ?? (multiple ? [] : null)).trigger("change");

    // Emitir eventos
    const buildOutput = () => {
        const raw = $el.val();
        const ids = Array.isArray(raw) ? raw : returnArray || multiple ? [raw] : raw;
        if (returnObject) {
            if (Array.isArray(ids)) {
                const objs = ids
                    .map((id) => options.find((o) => String(o[optionValue]) === String(id)))
                    .filter(Boolean);
                return returnArray || multiple ? objs : (objs[0] ?? null);
            } else {
                return options.find((o) => String(o[optionValue]) === String(ids)) ?? null;
            }
        } else {
            return ids;
        }
    };

    $el.on("select2:select", () => {
        const out = buildOutput();
        emit("input", out);
        emit("select", out);
    });

    // Manejar deselección en modo múltiple
    $el.on("select2:unselect", () => {
        const out = buildOutput();
        emit("input", out);
        emit("select", out);
    });
}
function destroySelect2() {
    const $el = $select();
    if ($el && $el.data("select2")) {
        $el.off("select2:select");
        $el.off("select2:unselect");
        $el.select2("destroy");
    }
}
function rebuild() {
    // Re-init to refresh options while preserving current value
    const current = props.value;
    initSelect2();
    const $el = $select();
    if ($el && current != null) {
        $el.val(current).trigger("change");
    }
}

onMounted(() => {
    nextTick(() => initSelect2());
});
</script>
<style scoped>
/* Opcional: estilos de integración si es necesario */
</style>
