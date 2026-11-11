<template>
    <div class="empty-state-card text-center py-5 px-3">
        <div class="empty-state-icon-wrapper mb-3">
            <div class="empty-state-icon-circle">
                <i :class="[icon, 'empty-state-icon']" aria-hidden="true"></i>
            </div>
        </div>
        <h4 class="empty-state-title mb-2">{{ title }}</h4>
        <p class="empty-state-description text-muted mb-4 mx-auto" style="max-width: 480px">
            {{ description }}
        </p>
        <div v-if="actionText" class="empty-state-actions">
            <button type="button" :class="actionClass" @click="$emit('action')">
                <i v-if="actionIcon" :class="[actionIcon, 'mr-1']"></i>
                {{ actionText }}
            </button>
        </div>
    </div>
</template>

<script setup>
/**
 * Componente reutilizable para estados vacíos ilustrados (Empty States) en tablas y listados.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-19
 */
defineProps({
    icon: {
        type: String,
        default: "fa fa-folder-open-o",
    },
    title: {
        type: String,
        default: "No se encontraron registros",
    },
    description: {
        type: String,
        default:
            "Intente ajustar los filtros de búsqueda o restablecer los criterios para ver resultados.",
    },
    actionText: {
        type: String,
        default: "",
    },
    actionIcon: {
        type: String,
        default: "fa fa-refresh",
    },
    actionClass: {
        type: String,
        default: "btn btn-outline-primary",
    },
});

defineEmits(["action"]);
</script>

<style scoped>
.empty-state-card {
    background: transparent;
    border-radius: 8px;
}

.empty-state-icon-circle {
    width: 68px;
    height: 68px;
    border-radius: 50%;
    background: rgba(2, 132, 199, 0.08);
    border: 1px solid rgba(2, 132, 199, 0.18);
    color: #0284c7;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s ease;
}

.empty-state-card:hover .empty-state-icon-circle {
    transform: scale(1.05);
}

.empty-state-icon {
    font-size: 28px;
}

.empty-state-title {
    font-weight: 600;
    color: #1e293b;
    font-size: 1.15rem;
}

.empty-state-description {
    font-size: 0.92rem;
    line-height: 1.5;
    color: #64748b !important;
}

:global(body.theme-dark) .empty-state-icon-circle {
    background: rgba(56, 189, 248, 0.1) !important;
    border-color: rgba(56, 189, 248, 0.25) !important;
    color: #38bdf8 !important;
}

:global(body.theme-dark) .empty-state-title {
    color: #f1f5f9 !important;
}

:global(body.theme-dark) .empty-state-description {
    color: #94a3b8 !important;
}
</style>
