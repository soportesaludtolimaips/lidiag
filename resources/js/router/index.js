import { createRouter, createWebHistory } from "vue-router";
import { routes } from "./routes";
import { useAuth } from "../composables/useAuth";

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) {
            return savedPosition;
        }
        return { top: 0, behavior: "smooth" };
    },
});

/**
 * Guardia global de navegación para verificar permisos Spatie
 * y actualizar dinámicamente el título del documento HTML.
 *
 * Flujo:
 * 1. Verifica si la ruta destino requiere permiso específico (to.meta.permission).
 * 2. Si el usuario no tiene el permiso, redirige a /403 sin recargar.
 * 3. Actualiza document.title con el nombre del módulo.
 */
router.beforeEach((to, from, next) => {
    const auth = useAuth();

    // Verificación de permiso Spatie en cliente
    if (to.meta?.permission) {
        if (!auth.can(to.meta.permission)) {
            return next({ name: "forbidden", query: { from: to.fullPath } });
        }
    }

    // Actualización de título de pestaña
    const baseTitle = "LIDiag";
    if (to.meta?.title) {
        document.title = `${to.meta.title} — ${baseTitle}`;
    } else {
        document.title = baseTitle;
    }

    next();
});

export default router;
