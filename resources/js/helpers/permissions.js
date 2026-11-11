/**
 * Crea un conjunto de helpers para evaluar permisos sobre una lista dada.
 *
 * @param array $permisos Lista de permisos del usuario.
 *
 * @returns {object}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-28
 */
export default function usePermissions(permisos) {
    return {
        can: (permiso) => permisos.includes(permiso),

        canAny: (lista) => lista.some((p) => permisos.includes(p)),

        canAll: (lista) => lista.every((p) => permisos.includes(p)),
    };
}
