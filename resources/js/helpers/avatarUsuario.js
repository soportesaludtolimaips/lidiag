import avatarNeutro from "@/admin-wrap/assets/images/users/avatar_default.png";
import avatarHombre from "@/admin-wrap/assets/images/users/male.png";
import avatarMujer from "@/admin-wrap/assets/images/users/woman.png";

/**
 * Foto que se muestra de un usuario: la que subió; si no, la silueta de mujer u hombre según su sexo (F / M);
 * si no tiene sexo registrado, la silueta neutra de siempre.
 *
 * @param {{imagen_perfil?: string|null, sexo?: string|null}|null|undefined} usuario
 * @returns {string} URL de la imagen
 */
export function urlAvatar(usuario) {
    if (usuario?.imagen_perfil) {
        return `/files/${usuario.imagen_perfil}`;
    }
    if (usuario?.sexo === "F") {
        return avatarMujer;
    }
    if (usuario?.sexo === "M") {
        return avatarHombre;
    }
    return avatarNeutro;
}
