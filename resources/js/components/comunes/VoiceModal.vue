<template>
    <div
        v-if="modelValue"
        class="modal fade bs-example-modal-lg show"
        tabindex="-1"
        role="dialog"
        aria-labelledby="voiceModalLabel"
        aria-modal="true"
        style="display: block; background: rgba(0, 0, 0, 0.5)"
        @click.self="cerrar()"
    >
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="voiceModalLabel">
                        <i class="fa fa-microphone text-info mr-2"></i>Comandos de voz
                    </h4>
                    <button type="button" class="close" @click="cerrar()" aria-label="Cerrar">
                        ×
                    </button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="text-info font-weight-bold mb-2">Mayúsculas</h5>
                            <ul class="text-muted small">
                                <li>iniciar mayúscula sostenida</li>
                                <li>iniciar mayúsculas sostenida</li>
                                <li>iniciar mayúsculas / mayúscula</li>
                                <li>activar mayúsculas / mayúscula</li>
                                <li>habilitar mayúsculas / mayúscula</li>
                                <li>desactivar mayúsculas / mayúscula</li>
                                <li>deshabilitar mayúsculas / mayúscula</li>
                                <li>terminar mayúsculas / mayúscula</li>
                                <li>fin mayúsculas / mayúscula</li>
                                <li>finalizar mayúsculas / mayúscula</li>
                            </ul>
                            <h5 class="text-info font-weight-bold mb-2 mt-3">Saltos de línea</h5>
                            <ul class="text-muted small">
                                <li>punto aparte / punto y aparte</li>
                                <li>nuevo párrafo</li>
                                <li>nueva sección / separador (---)</li>
                                <li>salto de línea / doble salto de línea</li>
                                <li>enter / doble enter</li>
                                <li>línea en blanco / doble línea en blanco</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5 class="text-info font-weight-bold mb-2">Puntuación</h5>
                            <ul class="text-muted small">
                                <li>punto / punto y seguido / punto seguido</li>
                                <li>coma</li>
                                <li>dos puntos / dos puntos aparte</li>
                                <li>punto y coma</li>
                                <li>nueva oración</li>
                                <li>abrir / cerrar paréntesis</li>
                                <li>abrir / cerrar corchetes</li>
                                <li>abrir / cerrar llaves</li>
                                <li>abrir / cerrar comillas / simples</li>
                            </ul>
                            <h5 class="text-info font-weight-bold mb-2 mt-3">
                                Símbolos y Operadores
                            </h5>
                            <ul class="text-muted small">
                                <li>guion / guion medio / guion bajo</li>
                                <li>barra diagonal / barra / barra invertida</li>
                                <li>dar espacio / un espacio / dos espacios</li>
                                <li>por ciento / porciento (%)</li>
                                <li>menos (-) / más (+) / igual (=)</li>
                                <li>multiplicar (×) / dividir (÷)</li>
                                <li>asterisco (*) / numeral (#) / arroba (@)</li>
                                <li>dólar ($) / pesos ($) / euro (€)</li>
                            </ul>
                            <h5 class="text-info font-weight-bold mb-2 mt-3">Atajos de Teclado</h5>
                            <ul class="text-muted small">
                                <li><strong>F2</strong> - Iniciar / pausar grabación de voz</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-secondary waves-effect text-left"
                        @click="cerrar()"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
/**
 * Modal informativo de comandos de voz para dictado clínico.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-06-12
 */
defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["update:modelValue", "close"]);

function cerrar() {
    emit("update:modelValue", false);
    emit("close");
}
</script>
