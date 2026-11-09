/**
 * Establece la sede actual en el contexto a partir de sessionStorage o localStorage (ST-sede).
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
    const sede = sessionStorage.getItem("ST-sede") || localStorage.getItem("ST-sede");
    if (sede && context?.registro) {
        context.registro.sede_id = sede;
    }
}
