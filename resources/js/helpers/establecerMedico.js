/**
 * Establece el medico actual en el contexto a partir de sessionStorage (ST-medico).
 *
 * @param object $context Contexto del componente que contiene registro.medico_id.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-24
 */
export async function establecerMedico(_context) {
    console.log("Medico del storage: ", sessionStorage.getItem("ST-medico"));
    /* if (sessionStorage.getItem("ST-medico")) {
        context.registro.medico_id = sessionStorage.getItem("ST-medico");
    } */
}
