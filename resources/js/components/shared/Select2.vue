<template>
    <select
        ref="el"
        :id="selectId"
        class="select2 form-control custom-select"
        :style="{ width, height: height + 'px' }"
        :multiple="multiple"
    >
        <option v-if="placeholder" value="0">{{ placeholder }}</option>
        <option v-for="opt in options" :key="opt[optionValue]" :value="opt[optionValue]">
            {{ formatLabel(opt) }}
        </option>
    </select>
</template>
<script setup>
import { nextTick, onMounted, onBeforeUnmount, ref, watch } from "vue";

const el = ref(null);

// backward-compat: acepta tanto value (legacy) como modelValue (v-model)
const props = defineProps({
    selectId: { type: String, default: "" },
    modelValue: { type: [String, Number, Array, null], default: undefined },
    value: { type: [String, Number, Array, null], default: undefined },
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
    placeholder: { type: String, default: "" },
});

const emit = defineEmits(["input", "select", "update:modelValue"]);

/**
 * Obtiene el elemento jQuery envolvente para interactuar con la API de Select2.
 *
 * @returns {jQuery|null} Elemento jQuery o null si Select2 no está disponible.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2025-11-04
 */
function $select() {
    const $native = el.value;
    if (!$native || !window.$ || !$.fn || !$.fn.select2) return null;
    return $($native);
}
/**
 * Formatea la etiqueta de una opción combinando los campos definidos en labelFields.
 *
 * @param {Object} opt - Objeto opción con las propiedades definidas en labelFields.
 * @returns {string} Etiqueta formateada separada por ' - '.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2025-11-04
 */
function formatLabel(opt) {
    const parts = props.labelFields.map((f) => opt && opt[f]).filter(Boolean);
    return parts.join(" - ");
}
/**
 * Normaliza un texto para búsqueda insensible a acentos:
 * convierte a minúsculas, elimina tildes y espacios extra.
 *
 * @param {string} term - Término de búsqueda a normalizar.
 * @returns {string} Texto normalizado sin acentos ni espacios.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2025-11-04
 */
function normalizeText(term) {
    return String(term || "")
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/\s+/g, "");
}
/**
 * Inicializa el widget Select2 en el elemento nativo <select>,
 * configurando matcher personalizado, eventos de selección/deselección
 * y sincronizando el valor actual con el reactive model de Vue.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2025-11-04
 */
function initSelect2() {
    const $el = $select();
    if (!$el) return;
    if ($el.is(":hidden")) return;
    const $parent = $el.closest(".right-sidebar");
    if ($parent.length && $parent.is(":hidden")) return;
    if ($el.data("select2")) {
        $el.off("select2:select");
        $el.off("select2:unselect");
        $el.select2("destroy");
    }

    const matcher = (params, data) => {
        if ($.trim(params.term) === "") return data;
        if (typeof data.text === "undefined") return null;
        if (!props.accentInsensitive) {
            return data.text.toLowerCase().includes(String(params.term || "").toLowerCase())
                ? data
                : null;
        }
        const term = normalizeText(params.term || "");
        const text = normalizeText(data.text || "");
        return text.indexOf(term) > -1 ? data : null;
    };

    const parent = $(props.dropdownParentSelector);
    $el.select2({
        width: props.width,
        dropdownParent: parent && parent.length ? parent : undefined,
        minimumInputLength: props.minimumInputLength,
        allowClear: props.allowClear,
        matcher,
    });

    // Inicializar valor actual
    $el.val(props.modelValue ?? props.value ?? (props.multiple ? [] : null)).trigger("change");

    // Emitir eventos
    const buildOutput = () => {
        const raw = $el.val();
        const ids = Array.isArray(raw) ? raw : props.returnArray || props.multiple ? [raw] : raw;
        if (props.returnObject) {
            if (Array.isArray(ids)) {
                const objs = ids
                    .map((id) =>
                        props.options.find((o) => String(o[props.optionValue]) === String(id))
                    )
                    .filter(Boolean);
                return props.returnArray || props.multiple ? objs : (objs[0] ?? null);
            } else {
                return (
                    props.options.find((o) => String(o[props.optionValue]) === String(ids)) ?? null
                );
            }
        } else {
            return ids;
        }
    };

    $el.on("select2:select", () => {
        const out = buildOutput();
        console.log("[DEBUG Select2] select2:select fired, out:", out);
        emit("input", out);
        emit("update:modelValue", out);
        emit("select", out);
    });

    // Manejar deselección en modo múltiple
    $el.on("select2:unselect", () => {
        const out = buildOutput();
        emit("input", out);
        emit("update:modelValue", out);
        emit("select", out);
    });
}
/**
 * Destruye la instancia de Select2 y remueve los listeners asociados
 * para evitar fugas de memoria al desmontar el componente.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2025-11-04
 */
function destroySelect2() {
    const $el = $select();
    if ($el && $el.data("select2")) {
        $el.off("select2:select");
        $el.off("select2:unselect");
        $el.select2("destroy");
    }
}
/**
 * Reconstruye el widget Select2 preservando el valor seleccionado actual.
 * Útil para refrescar las opciones sin perder la selección.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2025-11-04
 */
function rebuild() {
    // Re-init to refresh options while preserving current value
    const current = props.modelValue ?? props.value;
    initSelect2();
    const $el = $select();
    if ($el && current != null) {
        $el.val(current).trigger("change");
    }
}

/**
 * Lifecycle hook que inicializa Select2 después del nextTick
 * para asegurar que el DOM esté listo.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2025-11-04
 */
onMounted(() => {
    nextTick(() => initSelect2());
});

/**
 * Lifecycle hook que destruye Select2 al desmontar el componente.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2025-11-04
 */
onBeforeUnmount(() => {
    destroySelect2();
});

/**
 * Watcher que observa cambios en las opciones y reinicializa Select2
 * para reflejar las nuevas opciones disponibles.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2025-11-04
 */
watch(
    () => props.options,
    (newOpts) => {
        console.log(
            "[DEBUG Select2] watch options fired, new count:",
            newOpts?.length,
            "options:",
            newOpts
        );
        nextTick(() => initSelect2());
    }
);

defineExpose({ rebuild });
</script>
<style>
.select2-results__option {
    padding: 8px 12px;
    line-height: 1.5;
}
</style>
