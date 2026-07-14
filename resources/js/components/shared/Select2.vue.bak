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

<script>
export default {
    name: "Select2",
    props: {
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
    },
    mounted() {
        this.$nextTick(() => this.initSelect2());
    },
    beforeDestroy() {
        this.destroySelect2();
    },
    watch: {
        options() {
            this.$nextTick(() => this.rebuild());
        },
        value(newVal) {
            this.$nextTick(() => {
                const $el = this.$select();
                if (!$el) return;
                $el.val(newVal ?? (this.multiple ? [] : null)).trigger("change");
            });
        },
    },
    methods: {
        $select() {
            const el = this.$refs.el;
            if (!el || !window.$ || !$.fn || !$.fn.select2) return null;
            return $(el);
        },
        formatLabel(opt) {
            const parts = this.labelFields.map((f) => opt && opt[f]).filter(Boolean);
            return parts.join(" - ");
        },
        normalizeText(term) {
            return String(term || "")
                .toLowerCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .replace(/\s+/g, "");
        },
        initSelect2() {
            const $el = this.$select();
            if (!$el) return;
            if ($el.data("select2")) {
                $el.off("select2:select");
                $el.off("select2:unselect");
                $el.select2("destroy");
            }

            const matcher = (params, data) => {
                if ($.trim(params.term) === "") return data;
                if (typeof data.text === "undefined") return null;
                if (!this.accentInsensitive) {
                    return data.text.toLowerCase().includes(String(params.term || "").toLowerCase())
                        ? data
                        : null;
                }
                const term = this.normalizeText(params.term || "");
                const text = this.normalizeText(data.text || "");
                return text.indexOf(term) > -1 ? data : null;
            };

            const parent = $(this.dropdownParentSelector);
            $el.select2({
                width: this.width,
                dropdownParent: parent && parent.length ? parent : undefined,
                minimumInputLength: this.minimumInputLength,
                allowClear: this.allowClear,
                matcher,
            });

            // Inicializar valor actual
            $el.val(this.value ?? (this.multiple ? [] : null)).trigger("change");

            // Emitir eventos
            const buildOutput = () => {
                const raw = $el.val();
                const ids = Array.isArray(raw)
                    ? raw
                    : this.returnArray || this.multiple
                      ? [raw]
                      : raw;
                if (this.returnObject) {
                    if (Array.isArray(ids)) {
                        const objs = ids
                            .map((id) =>
                                this.options.find((o) => String(o[this.optionValue]) === String(id))
                            )
                            .filter(Boolean);
                        return this.returnArray || this.multiple ? objs : (objs[0] ?? null);
                    } else {
                        return (
                            this.options.find((o) => String(o[this.optionValue]) === String(ids)) ??
                            null
                        );
                    }
                } else {
                    return ids;
                }
            };

            $el.on("select2:select", () => {
                const out = buildOutput();
                this.$emit("input", out);
                this.$emit("select", out);
            });

            // Manejar deselección en modo múltiple
            $el.on("select2:unselect", () => {
                const out = buildOutput();
                this.$emit("input", out);
                this.$emit("select", out);
            });
        },
        destroySelect2() {
            const $el = this.$select();
            if ($el && $el.data("select2")) {
                $el.off("select2:select");
                $el.off("select2:unselect");
                $el.select2("destroy");
            }
        },
        rebuild() {
            // Re-init to refresh options while preserving current value
            const current = this.value;
            this.initSelect2();
            const $el = this.$select();
            if ($el && current != null) {
                $el.val(current).trigger("change");
            }
        },
    },
};
</script>

<style scoped>
/* Opcional: estilos de integración si es necesario */
</style>
