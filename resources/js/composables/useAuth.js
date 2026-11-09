import { computed, ref } from "vue";
import axios from "axios";

// Estado singleton compartido en toda la aplicación
const initialState =
    typeof window !== "undefined" && window.__INITIAL_STATE__ ? window.__INITIAL_STATE__ : null;

const user = ref(initialState?.user || null);
const roles = ref(initialState?.roles || []);
const permissions = ref(initialState?.permissions || []);

const sedeId = ref(
    typeof sessionStorage !== "undefined" ? sessionStorage.getItem("ST-sede") : null
);
const nomSede = ref(
    typeof sessionStorage !== "undefined" ? sessionStorage.getItem("ST-nomsede") || "" : ""
);
const medicoId = ref(
    typeof sessionStorage !== "undefined" ? sessionStorage.getItem("ST-medico") : null
);
const nomMedico = ref(
    typeof sessionStorage !== "undefined" ? sessionStorage.getItem("ST-nomMedico") || "" : ""
);

const isRefreshing = ref(false);

/**
 * Composable useAuth — Gestión centralizada y reactiva de autenticación,
 * roles, permisos Spatie y contexto clínico (Sede y Médico) para LIDiag.
 *
 * Flujo del proceso:
 * 1. Inicializa el estado reactivo singleton a partir de window.__INITIAL_STATE__.
 * 2. Proporciona métodos de verificación de permisos Spatie (can, hasRole, hasAnyRole).
 * 3. Proporciona sincronización bidireccional de la Sede y Médico activos con sessionStorage y localStorage.
 * 4. Permite refrescar o actualizar el usuario autenticado reactivamente sin recargas de página.
 *
 * @returns {Object} Estado reactivo y métodos de autorización y contexto clínico.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-09
 */
export function useAuth() {
    const isAuthenticated = computed(() => !!user.value);

    /**
     * Evalúa si el usuario autenticado posee uno o varios permisos de Spatie.
     * Soporta nombres simples ("estudio.asignar"), arreglos (["permiso.a", "permiso.b"])
     * o sintaxis con pipe OR ("permiso.a|permiso.b") y ampersand AND ("permiso.a&permiso.b").
     *
     * @param {string|string[]} permission Permiso o lista de permisos a comprobar.
     * @returns {boolean} True si tiene el permiso o si es Administrador del sistema.
     */
    function can(permission) {
        if (!permission) return true;
        if (!user.value) return false;

        // Super-administrador / Administrador tiene acceso irrestricto
        const userRoles = roles.value || [];
        if (
            userRoles.includes("Administrador") ||
            userRoles.includes("Super Admin") ||
            userRoles.includes("super-admin")
        ) {
            return true;
        }

        const userPerms = permissions.value || [];

        // Si es un arreglo: OR por defecto (al menos uno debe coincidir)
        if (Array.isArray(permission)) {
            return permission.some((p) => userPerms.includes(p));
        }

        // Sintaxis con pipe OR: "permiso.a|permiso.b"
        if (typeof permission === "string" && permission.includes("|")) {
            return permission
                .split("|")
                .map((p) => p.trim())
                .some((p) => userPerms.includes(p));
        }

        // Sintaxis con ampersand AND: "permiso.a&permiso.b"
        if (typeof permission === "string" && permission.includes("&")) {
            return permission
                .split("&")
                .map((p) => p.trim())
                .every((p) => userPerms.includes(p));
        }

        return userPerms.includes(permission);
    }

    /**
     * Evalúa si el usuario posee un rol específico.
     *
     * @param {string|string[]} role Rol o lista de roles a verificar.
     * @returns {boolean} True si posee el rol.
     */
    function hasRole(role) {
        if (!role) return true;
        if (!user.value) return false;

        const userRoles = roles.value || [];

        if (Array.isArray(role)) {
            return role.some((r) => userRoles.includes(r));
        }

        if (typeof role === "string" && role.includes("|")) {
            return role
                .split("|")
                .map((r) => r.trim())
                .some((r) => userRoles.includes(r));
        }

        return userRoles.includes(role);
    }

    /**
     * Actualiza la sede activa en memoria y en sessionStorage.
     *
     * @param {string|number|null} id Identificador de la sede.
     * @param {string} nombre Nombre de la sede seleccionada.
     * @returns {void}
     */
    function setSede(id, nombre = "") {
        sedeId.value = id ? String(id) : null;
        nomSede.value = nombre || "";

        if (typeof sessionStorage !== "undefined") {
            if (id) {
                sessionStorage.setItem("ST-sede", String(id));
                if (nombre) sessionStorage.setItem("ST-nomsede", nombre);
            } else {
                sessionStorage.removeItem("ST-sede");
                sessionStorage.removeItem("ST-nomsede");
            }
        }
    }

    /**
     * Actualiza el médico activo en memoria, sessionStorage y localStorage.
     *
     * @param {string|number|null} id Identificador del médico.
     * @param {string} nombre Nombre del médico seleccionado.
     * @returns {void}
     */
    function setMedico(id, nombre = "") {
        medicoId.value = id ? String(id) : null;
        nomMedico.value = nombre || "";

        if (typeof sessionStorage !== "undefined") {
            if (id) {
                sessionStorage.setItem("ST-medico", String(id));
                if (nombre) sessionStorage.setItem("ST-nomMedico", nombre);
            } else {
                sessionStorage.removeItem("ST-medico");
                sessionStorage.removeItem("ST-nomMedico");
            }
        }

        if (typeof localStorage !== "undefined") {
            if (id) {
                localStorage.setItem("ST-ecografia-medico", String(id));
                if (nombre) localStorage.setItem("ST-ecografia-nomMedico", nombre);
            } else {
                localStorage.removeItem("ST-ecografia-medico");
                localStorage.removeItem("ST-ecografia-nomMedico");
            }
        }
    }

    /**
     * Refresca los datos del usuario autenticado consultando la API en segundo plano.
     *
     * @returns {Promise<boolean>} True si la sesión sigue activa y los datos fueron actualizados.
     */
    async function fetchUser() {
        if (isRefreshing.value) return false;
        isRefreshing.value = true;
        try {
            const res = await axios.get("/api/user");
            if (res?.data) {
                user.value = res.data;
                if (res.data.roles) {
                    roles.value = Array.isArray(res.data.roles)
                        ? res.data.roles.map((r) => (typeof r === "string" ? r : r.name))
                        : [];
                }
                if (res.data.permissions) {
                    permissions.value = Array.isArray(res.data.permissions)
                        ? res.data.permissions.map((p) => (typeof p === "string" ? p : p.name))
                        : [];
                }
                return true;
            }
            return false;
        } catch {
            return false;
        } finally {
            isRefreshing.value = false;
        }
    }

    return {
        user,
        roles,
        permissions,
        isAuthenticated,
        sedeId,
        nomSede,
        medicoId,
        nomMedico,
        isRefreshing,
        can,
        hasRole,
        setSede,
        setMedico,
        fetchUser,
    };
}
