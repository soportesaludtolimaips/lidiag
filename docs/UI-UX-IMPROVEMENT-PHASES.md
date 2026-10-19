# UI/UX Improvement Phases — Guía para aplicar a otros módulos

Checklist sistemático para auditar y mejorar la UI/UX de cualquier módulo Vue del sistema.

---

## Fase 1: Alto impacto, bajo riesgo (mecánico)

### H2 — `for` en labels
Cada `<label>` debe tener `for="<id>"` coincidiendo con el `id` del `<input>` o `<select>` asociado.

```diff
-<label class="control-label">Nombre</label>
-<input type="text" id="nombre">
+<label class="control-label" for="nombre">Nombre</label>
+<input type="text" id="nombre">
```

**Búsqueda:** `grep -n '<label class="control-label">'`

### H3 — Close button `<span>` → `<button>`
El ícono de cerrar sidebar debe ser un `<button>` accesible por teclado.

```diff
-<span><i class="ti-close right-side-toggle" @click="cerrarModal()"></i></span>
+<button type="button" class="right-side-toggle" @click="cerrarModal()" aria-label="Cerrar panel">
+    <i class="ti-close"></i>
+</button>
```

Si hay llamadas jQuery como `$("#btnCerralModalForm").click()`, reemplazar con `this.btnCerralModalForm()`.

### H4 — Micrófono `<a>` → `<button>`
```diff
-<a class="" @click="toggleSpeechRecognition" style="width: 30px; height: 30px;">
+<button type="button" @click="toggleSpeechRecognition"
+    :aria-label="recognizing ? 'Detener grabación' : 'Iniciar grabación'"
+    :aria-pressed="String(recognizing)"
+    style="width: 44px; height: 44px; padding: 0; border: none; background: none; cursor: pointer;">
```

**Target size mínimo:** 44×44px (WCAG 2.5.5).

### H7 — `@keyup.enter` en search inputs
Si un input de búsqueda tiene botón "Buscar", debe también ejecutarse con Enter.

```diff
-<input type="text" id="bus_nom_num_docu" ... />
+<input type="text" id="bus_nom_num_docu" ... @keyup.enter="metodoBuscar()" />
```

### L1 — Tipeos comunes
- `"aqi"` → `"aquí"`
- `"Télefono"` → `"Teléfono"`
- `"Cerral"` → `"Cerrar"` (en métodos/IDs)

### L2 — CSS duplicado
Buscar `overflow-x: hidden; overflow-y: scroll; overflow-x: hidden` y eliminar el duplicado.

---

## Fase 2: Sidebar unificado

### H1 — Ancho responsivo unificado
Todos los sidebars deben usar el mismo ancho con responsive breakpoint.

```diff
 .right-sidebar {
     position: fixed;
-    right: -80%;   /* o -95%, -100%, -40% */
-    width: 80%;    /* inconsistente por componente */
+    right: -80%;
+    width: 80%;
+
+    @media (max-width: 767px) {
+        right: -100%;
+        width: 100%;
+    }
 }

 .shw-rside {
     right: 0px;
-    width: 80%;
+    width: 80%;
+
+    @media (max-width: 767px) {
+        width: 100%;
+    }
 }
```

**Ancho estándar:** 80% desktop, 100% mobile. Eliminar variaciones (40%, 95%, 100%).

### M7 — Open/close consistente
Unificar la lógica de apertura/cierre del sidebar:

```js
// ✅ Patrón correcto
abrirModal() {
    $(".right-sidebar").slideDown(50);
    $(".right-sidebar").addClass("shw-rside");
},
cerrarModal() {
    $(".right-sidebar").slideUp(50);
    $(".right-sidebar").removeClass("shw-rside");
}
```

```js
// ❌ Patrón incorrecto (toggleClass usado como close)
btnCerralModalForm() {
    $(".right-sidebar").slideDown(50);
    $(".right-sidebar").toggleClass("shw-rside"); // No cierra si ya está abierto
}
```

### M2 — Autofocus al abrir sidebar
```diff
 abrirModal() {
     $(".right-sidebar").slideDown(50);
     $(".right-sidebar").addClass("shw-rside");
+    this.$nextTick(() => { this.$refs.primerInput?.focus(); });
 },
```

Agregar `ref="primerInput"` al primer campo del formulario.

---

## Fase 3: Feedback visual

### H5 — Restaurar validación inline comentada
Buscar bloques como:

```html
<!-- <span class="text-danger" v-if="errores.campo">
    {{ errores.campo[0] }}
</span> -->
```

Descomentarlos y verificar que las claves de validación del backend coincidan.

### M3 — Indicadores de campos obligatorios
```html
<label class="control-label" for="campo">
    Nombre <span class="text-danger">*</span>
</label>
```

O usar `aria-required="true"` en los inputs requeridos.

### M4 — Corregir headings de toasts
```diff
-// ❌ Engañoso: "Ok!!!" con mensaje "espere"
-heading: "Ok!!!", text: "Generando reporte, por favor espere.", icon: "info"

+// ✅ Según contexto
+heading: "Procesando...", text: "Generando reporte, por favor espere.", icon: "info"
+heading: "Completado", text: "Reporte generado correctamente.", icon: "success"
+heading: "Error", text: "No se pudo generar el reporte.", icon: "error"
```

### M8 — Loading states en botones de acción
```diff
-<button @click="verImagen()">Ver Imagen</button>
+<button @click="verImagen()" :disabled="loadingImagen">
+    <i v-if="loadingImagen" class="fa fa-spinner fa-spin"></i>
+    <i v-else class="fa fa-file-image-o"></i>
+    Ver Imagen
+</button>
```

### M5 — Barra de progreso en uploads
```js
axios.post(url, formData, {
    onUploadProgress: (e) => {
        this.progresoUpload = Math.round((e.loaded * 100) / e.total);
    }
});
```

---

## Fase 4: Consistencia y bugs

### H6 — Normalizar nombres de campos API
Verificar que todos los componentes usen el mismo nombre de campo del paciente:

| Componente | Campo usado |
|-----------|------------|
| AsignarEstudio | `pat_name` |
| notificarEstudio | `pat_name` y `nom_pacien` (inconsistente) |
| estudioAsignados | `nom_pacien` |
| transcribirEstudio | `nom_pacien` |

Estandarizar a `nom_pacien` (el que usa la mayoría).

### M1 — Sin `\n` literal en títulos
```diff
-this.tituloModal = "Paciente: " + data.nom_pacien + "Producto: \n" + data.nom_produc;
+this.tituloModal = "Paciente: " + data.nom_pacien + " — Producto: " + data.nom_produc;
```

### M6 — Confirm dialog antes de eliminar
```diff
-quitarProducto(index) {
+quitarProducto(index) {
+    if (!confirm("¿Está seguro de eliminar este producto?")) return;
     this.registro.splice(index, 1);
}
```

O usar el mismo patrón de confirmación que `anularAsignacion` en los componentes que ya lo implementan.

### L3 — WYSIWYG vs textarea
Buscar inicialización de `textarea_editor` con wysihtml5. Si no se usa activamente, eliminar la inicialización. Si se usa, aplicar consistentemente.

### L4 — Empty state en tablas
```html
<tbody v-if="registros.length === 0">
    <tr><td colspan="99" class="text-center text-muted">Sin resultados</td></tr>
</tbody>
<tbody v-else>
    <tr v-for="item in registros">...</tr>
</tbody>
```

### L5 — IDs únicos en tablas DataTable
No usar `id="example23"` hardcodeado. Usar `ref` o ID dinámico.

### L6 — `@keyup.enter` en campos de fecha
Extender a todos los campos del formulario de búsqueda, no solo al de texto.

### L10 — Keyboard shortcut F2
Si el módulo tiene dictado por voz, agregar listener F2:

```js
mounted() {
    window.addEventListener('keydown', this.handleKeydown);
},
beforeUnmount() {
    window.removeEventListener('keydown', this.handleKeydown);
},
methods: {
    handleKeydown(e) {
        if (e.key === 'F2') {
            e.preventDefault();
            this.toggleSpeechRecognition();
        }
    }
}
```

---

## Fase 5: Ergonomía Clínica Avanzada y Productividad (2026)

### E1 — Skeleton Loaders con Shimmer (`TableSkeletonLoader.vue`)
Sustituir los antiguos spinners de pantalla por esqueletos con efecto shimmer en las tablas para eliminar el layout shift (CLS = 0):
```html
<table-skeleton-loader :visible="loadingBuscar" :rows="6" :columns="8" />
```
Y combinar con `<empty-state-component>` al no haber registros tras buscar.

### E2 — Toasts Unificados y No Invasivos (`toastMessage`)
Evitar popups modales bloqueantes de SweetAlert para operaciones exitosas comunes (guardar, actualizar, notificar). Usar `toastMessage` de `alerts.js`:
```js
toastMessage({
    heading: "Completado",
    text: "El estudio fue asignado correctamente.",
    icon: "success",
});
```

### E3 — Validación Visual Inline (`useFormValidation.js`)
Proporcionar feedback visual en tiempo real en los campos del formulario asistencial:
- Clases `.is-invalid` y `.is-valid` según interacción (`touched`).
- Alertas contextuales con `.invalid-feedback`.
- Pre-validación defensiva en el método de envío antes de disparar llamadas a la API.

### E4 — Atajos de Teclado Clínicos y Foco Accesible (`useKeyboardShortcuts.js`)
- `Ctrl+S` / `Cmd+S`: Guardado rápido del registro activo con prevención de la descarga del navegador.
- `Escape`: Cancelar y cerrar sidebars/modales abiertos.
- `F2`: Activar o detener el dictado médico por voz.
- **Puente WYSIWYG:** Escuchar eventos dentro del iframe de `wysihtml5` y reenviarlos a `window`.
- **Foco Accesible (`a11y`):** `nextTick(() => { primerInput.value?.focus(); })` al abrir cualquier panel.

### E5 — Modo Oscuro Radiológico de Alto Contraste (`dark-theme.css`)
Garantizar contraste clínico óptimo (WCAG AAA) bajo `body.theme-dark` en modales, botones, inputs de formulario y badges para estaciones de diagnóstico.

---

## Resumen por severidad

| Prioridad | Área | Items |
|-----------|------|-------|
| 🔴 Alta | Accesibilidad, validación, bugs | H2, H3, H4, H5, H6, H7, E3, E4 |
| 🟠 Media | Feedback, consistencia, rendimiento | M1-M8, E1, E2, E5 |
| 🔵 Baja | Limpieza, tipografía | L1-L10 |

## Orden de ejecución sugerido

1. **Fase 1** — Mecánico, bajo riesgo (~15 min por módulo)
2. **Fase 2** — Sidebar unificado (~10 min por módulo)
3. **Fase 3** — Feedback visual (~20 min por módulo)
4. **Fase 4** — Consistencia (~15 min por módulo)
5. **Fase 5** — Ergonomía clínica y accesibilidad (~25 min por módulo)

---

*Basado en auditoría UI/UX y modernización asistencial de LIDiag (Estudios, Citas, Ecografías).*

