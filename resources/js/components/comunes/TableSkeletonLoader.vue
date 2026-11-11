<template>
    <div v-if="visible" class="table-skeleton-wrapper py-2">
        <div class="table-responsive">
            <table class="table table-bordered skeleton-table mb-0">
                <thead>
                    <tr>
                        <th v-for="c in columns" :key="'th-' + c" class="skeleton-header-cell">
                            <div class="skeleton-bar skeleton-bar-header"></div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in rows" :key="'tr-' + r">
                        <td v-for="c in columns" :key="'td-' + r + '-' + c" class="skeleton-cell">
                            <div class="skeleton-bar" :style="{ width: getCellWidth(r, c) }"></div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
/**
 * Componente reutilizable para skeleton loading en tablas (efecto shimmer).
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-19
 */
defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
    rows: {
        type: Number,
        default: 5,
    },
    columns: {
        type: Number,
        default: 6,
    },
});

const widths = ["65%", "85%", "45%", "90%", "75%", "55%", "70%"];

function getCellWidth(r, c) {
    const index = (r * 3 + c * 2) % widths.length;
    return widths[index];
}
</script>

<style scoped>
.table-skeleton-wrapper {
    width: 100%;
}

.skeleton-table {
    background: transparent;
}

.skeleton-cell,
.skeleton-header-cell {
    vertical-align: middle;
    padding: 12px 14px;
}

.skeleton-bar {
    height: 13px;
    border-radius: 4px;
    background: linear-gradient(90deg, #e2e8f0 25%, #f8fafc 50%, #e2e8f0 75%);
    background-size: 200% 100%;
    animation: skeleton-shimmer 1.5s infinite linear;
}

.skeleton-bar-header {
    height: 15px;
    background: linear-gradient(90deg, #cbd5e1 25%, #e2e8f0 50%, #cbd5e1 75%);
    background-size: 200% 100%;
}

@keyframes skeleton-shimmer {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

:global(body.theme-dark) .skeleton-bar {
    background: linear-gradient(90deg, #181d27 25%, #242c3b 50%, #181d27 75%) !important;
    background-size: 200% 100% !important;
}

:global(body.theme-dark) .skeleton-bar-header {
    background: linear-gradient(90deg, #202735 25%, #2e384b 50%, #202735 75%) !important;
    background-size: 200% 100% !important;
}

:global(body.theme-dark) .skeleton-table {
    border-color: #2d3343 !important;
}

:global(body.theme-dark) .skeleton-table th,
:global(body.theme-dark) .skeleton-table td {
    border-color: #2d3343 !important;
}
</style>
