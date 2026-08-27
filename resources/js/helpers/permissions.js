export default function usePermissions(permisos) {
    return {
        can: (permiso) => permisos.includes(permiso),

        canAny: (lista) => lista.some((p) => permisos.includes(p)),

        canAll: (lista) => lista.every((p) => permisos.includes(p)),
    };
}
