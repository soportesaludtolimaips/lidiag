/**
 * Shared SAHI utility helpers used across ecografías components.
 */

/**
 * Asigna un valor a una propiedad de un mapa reactivo en una instancia de Vue, usando $set si existe (compatibilidad Vue 2).
 *
 * @param object $vm Instancia del componente Vue (this).
 * @param string $mapName Nombre de la propiedad en vm que contiene el mapa.
 * @param mixed $key Clave dentro del mapa a asignar.
 * @param mixed $value Valor a asignar.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-10
 */
export function setMapValue(vm, mapName, key, value) {
    if (!key) return;
    if (typeof vm.$set === "function") {
        vm.$set(vm[mapName], key, value);
    } else {
        vm[mapName][key] = value;
    }
}
