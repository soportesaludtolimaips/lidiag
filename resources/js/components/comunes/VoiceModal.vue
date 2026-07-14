<template>
    <div
        class="modal fade bs-example-modal-lg"
        tabindex="-1"
        role="dialog"
        aria-labelledby="voiceModalLabel"
        aria-hidden="true"
        style="display: none"
    >
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="voiceModalLabel">Comandos de voz</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                        ×
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-2">Mayúsculas</h5>
                            <ul>
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
                            <h5 class="mb-2 mt-3">Saltos de línea</h5>
                            <ul>
                                <li>punto aparte</li>
                                <li>nuevo párrafo</li>
                                <li>nueva sección</li>
                                <li>separador</li>
                                <li>salto de línea</li>
                                <li>doble salto de línea</li>
                                <li>enter / doble enter</li>
                                <li>línea en blanco / doble línea en blanco</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5 class="mb-2">Puntuación</h5>
                            <ul>
                                <li>punto / punto y seguido / punto seguido</li>
                                <li>coma</li>
                                <li>dos puntos</li>
                                <li>punto y coma</li>
                                <li>nueva oración</li>
                                <li>abrir/cerrar paréntesis</li>
                                <li>abrir/cerrar corchetes</li>
                                <li>abrir/cerrar llaves</li>
                                <li>abrir/cerrar comillas</li>
                                <li>abrir/cerrar comillas simples</li>
                            </ul>
                            <h5 class="mb-2 mt-3">Símbolos</h5>
                            <ul>
                                <li>guion</li>
                                <li>guion bajo</li>
                                <li>barra diagonal</li>
                                <li>barra invertida</li>
                                <li>dar espacio / un espacio / dos espacio / doble espacio</li>
                                <li>por ciento (%)</li>
                                <li>menos / más / igual</li>
                                <li>multiplicar (×) / dividir (÷)</li>
                                <li>asterisco / numeral / ampersand / arroba</li>
                                <li>dólar / euro</li>
                            </ul>
                            <h5 class="mb-2 mt-3">Atajos</h5>
                            <ul>
                                <li><strong>F2</strong> - Iniciar/detener grabación</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-danger waves-effect text-left"
                        data-dismiss="modal"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup></script>
