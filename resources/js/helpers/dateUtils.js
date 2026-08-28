/**
 * Normaliza distintos formatos de fecha a YYYY-MM-DD, o null si no es valida.
 *
 * @param mixed $val Valor de fecha a normalizar.
 *
 * @returns {string|null}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-06-01
 */
export function normalizarFecha(val) {
    if (!val) return null;
    const str = String(val).trim();
    if (!str) return null;
    if (/^\d{8}$/.test(str)) {
        return `${str.slice(0, 4)}-${str.slice(4, 6)}-${str.slice(6, 8)}`;
    }
    const iso = str.match(/^(\d{4})[/.-](\d{2})[/.-](\d{2})/);
    if (iso) {
        return `${iso[1]}-${iso[2]}-${iso[3]}`;
    }
    const dmy = str.match(/^(\d{2})[/.-](\d{2})[/.-](\d{4})$/);
    if (dmy && parseInt(dmy[1], 10) <= 31 && parseInt(dmy[2], 10) <= 12) {
        return `${dmy[3]}-${dmy[2]}-${dmy[1]}`;
    }
    const d = new Date(str);
    if (!isNaN(d.getTime())) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, "0");
        const dia = String(d.getDate()).padStart(2, "0");
        return `${y}-${m}-${dia}`;
    }
    return null;
}

/**
 * Calcula la edad en anos a partir de una fecha de nacimiento.
 *
 * @param string $fechaNacimiento Fecha de nacimiento en cualquier formato reconocido.
 *
 * @returns {number|null}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-06-01
 */
export function calcularEdad(fechaNacimiento) {
    if (!fechaNacimiento) return null;
    const normalizada = normalizarFecha(fechaNacimiento);
    if (!normalizada) return null;
    const nacimiento = new Date(normalizada);
    if (isNaN(nacimiento.getTime())) return null;
    const hoy = new Date();
    let edad = hoy.getFullYear() - nacimiento.getFullYear();
    const m = hoy.getMonth() - nacimiento.getMonth();
    if (m < 0 || (m === 0 && hoy.getDate() < nacimiento.getDate())) {
        edad--;
    }
    return edad;
}

/**
 * Formatea una fecha y hora en la zona horaria America/Bogota como YYYY-MM-DD HH:mm.
 *
 * @param mixed $dt Fecha u objeto a formatear.
 *
 * @returns {string}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-06-01
 */
export function formatDateTime(dt) {
    if (!dt) return "";
    try {
        return window.dayjs(dt).tz("America/Bogota").format("YYYY-MM-DD HH:mm");
    } catch (_) {
        return String(dt);
    }
}
