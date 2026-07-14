<template>
    <span class="emoji-picker-wrapper" ref="wrapper">
        <button type="button" class="btn btn-sm btn-light" title="Insertar emoji" @click="toggle">
            😊
        </button>
        <div class="emoji-picker-dropdown" v-if="abierto" @mouseleave="cerrar">
            <button
                v-for="e in emojis"
                :key="e"
                type="button"
                class="btn btn-sm emoji-btn"
                @click="seleccionar(e)"
            >
                {{ e }}
            </button>
        </div>
    </span>
</template>

<script>
import { EMOJIS } from "@/helpers/emojis.js";

export default {
    emits: ["select"],
    data() {
        return {
            emojis: EMOJIS,
            abierto: false,
        };
    },
    methods: {
        toggle() {
            this.abierto = !this.abierto;
            if (this.abierto) {
                this.$nextTick(() => {
                    document.addEventListener("click", this.clickFuera);
                });
            } else {
                document.removeEventListener("click", this.clickFuera);
            }
        },
        cerrar() {
            this.abierto = false;
            document.removeEventListener("click", this.clickFuera);
        },
        clickFuera(e) {
            const wrapper = this.$refs.wrapper;
            if (wrapper && !wrapper.contains(e.target)) {
                this.cerrar();
            }
        },
        seleccionar(emoji) {
            this.$emit("select", emoji);
        },
    },
    beforeUnmount() {
        document.removeEventListener("click", this.clickFuera);
    },
};
</script>

<style scoped>
.emoji-picker-wrapper {
    position: relative;
    display: inline-block;
}

.emoji-picker-wrapper .btn-light {
    border: 1px solid transparent;
    padding: 2px 10px;
    font-size: 14px;
    line-height: 1.5;
    min-width: 32px;
}

.emoji-picker-wrapper .btn-light:hover {
    border-color: #ced4da;
    background: #e9ecef;
}

.emoji-picker-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1050;
    display: grid;
    grid-template-columns: repeat(10, 32px);
    gap: 2px;
    padding: 8px;
    background: #fff;
    border: 1px solid #ced4da;
    border-radius: 4px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    max-height: 260px;
    overflow-y: auto;
}

.emoji-picker-dropdown .emoji-btn {
    font-size: 20px;
    padding: 2px 4px;
    line-height: 1;
    background: none;
    border: none;
    cursor: pointer;
    border-radius: 4px;
    text-align: center;
}

.emoji-picker-dropdown .emoji-btn:hover {
    background: #e9ecef;
}
</style>
