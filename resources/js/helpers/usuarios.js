import axios from "axios";

export async function listarUsuariosYSedes(context) {
    const res = await axios.get("/user.listarUsuarios/Transcriptor/1");
    context.quienes_asignaron = res.data;

    const resMedicos = await axios.get("/user.listarUsuarios/Medico/1");
    context.medicos = resMedicos.data;
}
