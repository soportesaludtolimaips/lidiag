/**
 * Shared SAHI utility helpers used across ecografías components.
 */

/**
 * Set a value in a reactive object on a Vue component instance,
 * using $set when available (Vue 2 compatibility).
 *
 * @param {object} vm       - Vue component instance (this)
 * @param {string} mapName  - key on vm.data, e.g. "isConsultandoSahi"
 * @param {*}      key      - property key to set on the map
 * @param {*}      value    - value to assign
 */
export function setMapValue(vm, mapName, key, value) {
    if (!key) return;
    if (typeof vm.$set === "function") {
        vm.$set(vm[mapName], key, value);
    } else {
        vm[mapName][key] = value;
    }
}
