export async function establecerSede(context) {
    if(sessionStorage.getItem("ST-sede")) {
        context.registro.sede_id = sessionStorage.getItem("ST-sede");
    }
}