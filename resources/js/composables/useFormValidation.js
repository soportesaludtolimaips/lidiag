import { ref } from "vue";

/**
 * Composable para gestión de validaciones reactivas inline, tracking de campos tocados (blur/input)
 * y retroalimentación visual de formularios clínicos y asistenciales.
 *
 * Flujo del proceso:
 * 1. Mantiene el registro reactivo de campos que han recibido interacción (`touched`).
 * 2. Provee validadores estándar para formatos clínicos comunes (documento, email, teléfono, fechas).
 * 3. Evalúa reglas locales y las combina con los errores retornados por el servidor (`serverErrors`).
 * 4. Determina si un campo debe mostrar clases de Bootstrap (`.is-invalid` o `.is-valid`) y su mensaje explicativo.
 *
 * @returns {Object} Métodos de validación y control de estado de campos.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-19
 */
export function useFormValidation() {
    const touched = ref({});

    /**
     * Marca un campo como tocado/interactuado por el usuario.
     *
     * @param {string} field Nombre del campo.
     */
    const touch = (field) => {
        touched.value[field] = true;
    };

    /**
     * Marca múltiples campos como interactuados (útil antes de submit).
     *
     * @param {Array<string>} fields Lista de nombres de campos.
     */
    const touchAll = (fields) => {
        fields.forEach((f) => {
            touched.value[f] = true;
        });
    };

    /**
     * Reinicia el estado de campos tocados.
     */
    const resetTouched = () => {
        touched.value = {};
    };

    /**
     * Verifica si un campo ha sido tocado por el usuario.
     *
     * @param {string} field Nombre del campo.
     * @returns {boolean}
     */
    const isTouched = (field) => Boolean(touched.value[field]);

    // Validadores clínicos estándar
    const validators = {
        required: (val, msg = "Este campo es obligatorio") => {
            const empty = val === null || val === undefined || String(val).trim() === "";
            return empty ? msg : null;
        },
        minLength: (min, msg) => (val) => {
            if (!val) return null;
            const valid = String(val).trim().length >= min;
            return valid ? null : msg || `Debe tener al menos ${min} caracteres`;
        },
        documento: (val, msg = "Número de documento no válido (mínimo 4 caracteres)") => {
            if (!val) return null;
            const valid = /^[a-zA-Z0-9-]{4,20}$/.test(String(val).trim());
            return valid ? null : msg;
        },
        email: (val, msg = "Ingrese un correo electrónico válido") => {
            if (!val) return null;
            const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(val).trim());
            return valid ? null : msg;
        },
        telefono: (val, msg = "Ingrese un teléfono válido (7 a 10 dígitos)") => {
            if (!val) return null;
            const clean = String(val).replace(/[\s-]/g, "");
            const valid = /^[0-9]{7,10}$/.test(clean);
            return valid ? null : msg;
        },
        fechaNoFutura: (val, msg = "La fecha no puede ser posterior a hoy") => {
            if (!val) return null;
            const hoy = new Date();
            hoy.setHours(23, 59, 59, 999);
            const fechaInput = new Date(val);
            return fechaInput <= hoy ? null : msg;
        },
    };

    /**
     * Evalúa una lista de reglas de validación sobre un valor dado.
     *
     * @param {*} value Valor a validar.
     * @param {Array<Function>} rules Lista de funciones validadoras que retornan string de error o null.
     * @returns {string|null} Primer mensaje de error encontrado, o null si todo es válido.
     */
    const validate = (value, rules = []) => {
        for (const rule of rules) {
            if (typeof rule === "function") {
                const error = rule(value);
                if (error) return error;
            }
        }
        return null;
    };

    /**
     * Calcula el estado visual y mensaje para un campo combinando reglas locales y errores del servidor.
     *
     * @param {string} field Nombre del campo.
     * @param {*} value Valor actual del campo.
     * @param {Array<Function>} [rules=[]] Reglas de validación aplicables.
     * @param {Object} [serverErrors={}] Errores devueltos por el backend (ej: response.data.errors).
     * @returns {{ isInvalid: boolean, isValid: boolean, message: string }}
     */
    const getFieldStatus = (field, value, rules = [], serverErrors = {}) => {
        // 1. Error proveniente del servidor (máxima prioridad)
        const serverErr = serverErrors?.[field];
        if (serverErr && (Array.isArray(serverErr) ? serverErr.length > 0 : Boolean(serverErr))) {
            const msg = Array.isArray(serverErr) ? serverErr[0] : String(serverErr);
            return { isInvalid: true, isValid: false, message: msg };
        }

        // 2. Si el usuario aún no ha interactuado con el campo, no se muestra nada invasivo
        if (!isTouched(field)) {
            return { isInvalid: false, isValid: false, message: "" };
        }

        // 3. Validación de reglas cliente
        const clientError = validate(value, rules);
        if (clientError) {
            return { isInvalid: true, isValid: false, message: clientError };
        }

        // 4. Si fue tocado y tiene valor válido
        const hasContent = value !== null && value !== undefined && String(value).trim() !== "";
        if (hasContent) {
            return { isInvalid: false, isValid: true, message: "" };
        }

        return { isInvalid: false, isValid: false, message: "" };
    };

    /**
     * Obtiene la clase CSS de Bootstrap para el input ('is-invalid', 'is-valid' o vacía).
     *
     * @param {{ isInvalid: boolean, isValid: boolean }} status
     * @returns {string}
     */
    const getInputClass = (status) => {
        if (!status) return "";
        if (status.isInvalid) return "is-invalid";
        if (status.isValid) return "is-valid";
        return "";
    };

    return {
        touched,
        touch,
        touchAll,
        resetTouched,
        isTouched,
        validators,
        validate,
        getFieldStatus,
        getInputClass,
    };
}
