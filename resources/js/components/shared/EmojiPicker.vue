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
<script setup>
import { nextTick, onBeforeUnmount, ref } from "vue";

import { EMOJIS } from "@/helpers/emojis.js";
const emit = defineEmits(["select"]);

const emojis = ref(EMOJIS);
const abierto = ref(false);

/**
 * Alterna la visibilidad del panel de emojis.
 * Registra o remueve el listener de click fuera para cerrar al hacer clic externo.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-07-08
 */
function toggle() {
    abierto.value = !abierto.value;
    if (abierto.value) {
        nextTick(() => {
            document.addEventListener("click", clickFuera);
        });
    } else {
        document.removeEventListener("click", clickFuera);
    }
}
/**
 * Cierra el panel de emojis y remueve el listener de click fuera del DOM.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-07-08
 */
function cerrar() {
    abierto.value = false;
    document.removeEventListener("click", clickFuera);
}
/**
 * Handler de click global que cierra el picker si el clic fue fuera del wrapper.
 *
 * @param {MouseEvent} e - Evento de clic del documento.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-07-08
 */
function clickFuera(e) {
    const wrapper = wrapper.value;
    if (wrapper && !wrapper.contains(e.target)) {
        cerrar();
    }
}
/**
 * Emite el evento select con el emoji elegido y cierra el panel.
 *
 * @param {string} emoji - Emoji seleccionado por el usuario.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-07-08
 */
function seleccionar(emoji) {
    emit("select", emoji);
}

/**
 * Lifecycle hook que remueve el listener de click fuera del DOM
 * al desmontar el componente para evitar fugas de memoria.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-07-08
 */
onBeforeUnmount(() => {
    document.removeEventListener("click", clickFuera);
});
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
