# Task 1: Create useSede.js composable

## Files
- Create: `resources/js/composables/useSede.js`

## Requirements
Create a Vue 3 composable that:
1. Provides `sedeActual` (ref) — current sede ID from sessionStorage
2. Provides `sedeInterface` (ref) — true when `interface_software === "SAHI"`
3. Provides `isLoading` (ref) — loading state
4. Provides `establecerSede()` async function that:
   - Reads `sessionStorage.getItem("ST-sede")`
   - If no ID, returns early
   - Sets `isLoading.value = true`
   - Calls `axios.get('/api/config-sedes/${id}/buscarPorId')`
   - Sets `sedeInterface.value = res?.data?.interface_software === "SAHI"`
   - On error, sets `sedeInterface.value = false`
   - Always sets `isLoading.value = false` in finally

## Exact code to write
```js
import { ref } from "vue";
import axios from "axios";

export function useSede() {
    const sedeActual = ref(null);
    const sedeInterface = ref(false);
    const isLoading = ref(false);

    const establecerSede = async () => {
        const id = sessionStorage.getItem("ST-sede");
        if (!id) return;
        sedeActual.value = id;
        isLoading.value = true;
        try {
            const res = await axios.get(`/api/config-sedes/${id}/buscarPorId`);
            sedeInterface.value = res?.data?.interface_software === "SAHI";
        } catch {
            sedeInterface.value = false;
        } finally {
            isLoading.value = false;
        }
    };

    return { sedeActual, sedeInterface, isLoading, establecerSede };
}
```

## Verification
1. File exists at `resources/js/composables/useSede.js`
2. No syntax errors (valid JS)
3. Follows same pattern as `resources/js/composables/useMedicalDictionary.js` (named export, ref-based, no default export)

## No tests needed
This project has no Vue/JS test infrastructure. Manual verification only.
