<template>
    <div v-if="visible" class="text-center py-5">
        <i class="fa fa-spinner fa-spin fa-5x fa-fw" :style="{ color: color }"></i>
        <span class="sr-only">{{ mensaje }}</span>
    </div>
</template>
<script setup>
const props = defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
    color: {
        type: String,
        default: "#1e88e5",
    },
    mensaje: {
        type: String,
        default: "Cargando...",
    },
});
</script>
