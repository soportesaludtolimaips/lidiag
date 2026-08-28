/**
 * Establece la sede actual en el contexto a partir de sessionStorage (ST-sede).
 *
 * @param object $context Contexto del componente que contiene registro.sede_id.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-24
 */
export async function establecerSede(context) {
    if (sessionStorage.getItem("ST-sede")) {
        context.registro.sede_id = sessionStorage.getItem("ST-sede");
    }
}
