# UI/UX Improvement Phases — Guía para aplicar a otros módulos

Checklist sistemático para auditar y mejorar la UI/UX de cualquier módulo Vue del sistema LIDiag.

---

## Fase 1: Alto impacto, bajo riesgo (mecánico)

### H2 — `for` en labels
Cada `<label>` debe tener `for="<id>"` coincidiendo con el `id` del `<input>`, `<select>` o `<textarea>` asociado.

```diff
-<label class="control-label">Nombre</label>
-<input type="text" id="nombre">
+<label class="control-label" for="nombre">Nombre</label>
+<input type="text" id="nombre">
```

**Búsqueda:** `grep -n '<label class="control-label">'`

### H3 — Close button `<span>` → `<button>`
El ícono de cerrar sidebar debe ser un `<button>` semántico y accesible por teclado con atributo `aria-label`.

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
Si un input de búsqueda tiene botón "Buscar", debe también ejecutarse al presionar `Enter`.

```diff
-<input type="text" id="bus_nom_num_docu" ... />
+<input type="text" id="bus_nom_num_docu" ... @keyup.enter="metodoBuscar()" />
```

### L1 — Tipeos comunes
- `"aqi"` → `"aquí"`
- `"Télefono"` → `"Teléfono"`
- `"Cerral"` → `"Cerrar"` (en métodos/IDs)
- `"Quien asigno"` → `"Quién asignó"`

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

O usar `confirmAction` de `alerts.js` para confirmaciones accesibles no bloqueantes.

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
Extender a todos los campos del formulario de búsqueda, no solo al de texto:
```html
<input type="date" id="fecha_inicio" v-model="busqueda.fecha_inicio" @keyup.enter="buscarRegistros" />
<input type="date" id="fecha_fin" v-model="busqueda.fecha_fin" @keyup.enter="buscarRegistros" />
```

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

## Fase 5: Ergonomía Clínica Avanzada y Productividad

### E1 — Skeleton Loaders con Shimmer (`TableSkeletonLoader.vue`)
Sustituir los antiguos spinners bloqueantes por esqueletos con efecto shimmer en las tablas para eliminar el layout shift (CLS = 0):
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

### E6 — Transición Suave y Backdrop Overlay Desacoplador
Eliminar el parpadeo abrupto de `display: none/block` en `.right-sidebar` y modales:
- Control mediante `right: -80%` (o `-50%`) a `right: 0` y `visibility: hidden` / `visible` con transición `cubic-bezier(0.16, 1, 0.3, 1)` a 350ms.
- Añadir `.sidebar-backdrop` con `backdrop-filter: blur(2px)` y click-outside dismiss.
- En modales Vue/Bootstrap: `style="display: block"`, `@click.self` y `class="modal show"`.

### E7 — Resaltado Jerárquico de Identificación del Paciente (`num_docu`)
En bandejas clínicas de alta rotación (transcripción, lectura, asignación de estudios), el personal asistencial y los radiólogos realizan la búsqueda y confirmación cruzada mediante el documento de identidad del paciente:
- **Antipatrón detectado:** Documento relegado a una etiqueta `<small class="text-muted">` grisácea y diminuta debajo del nombre, dificultando el escaneo visual rápido en monitores de diagnóstico.
- **Patrón estandarizado en tablas:**
  ```html
  <!-- ✅ Documento visible, jerárquico y en azul institucional -->
  <div class="font-weight-bold text-dark">
      {{ item.nom_pacien }}
  </div>
  <div class="text-primary font-weight-bold" style="font-size: 15px">
      <i class="fa fa-id-card-o mr-1"></i>
      {{ item.num_docu }}
  </div>
  ```
- **Patrón en cabeceras de paneles / modales (`tituloModal`):**
  Acompañar siempre el nombre del paciente con su documento para evitar confusiones durante el reporte:
  ```js
  tituloModal.value = `Transcribir la lectura del paciente: ${data.nom_pacien} ${data.num_docu ? `(${data.num_docu})` : ""}`;
  ```

---

## Fase 6: Estandarización y Diseño de Tablas Clínicas

### T1 — Preservación del DOM en DataTables (`v-show` vs `v-if`)
- **Problema previo:** El uso de `<table-skeleton-loader v-if="loading">` y `<div v-else>` destruía el elemento `<table>` del DOM durante cada consulta. Al renderizar los nuevos datos, DataTables perdía la referencia al elemento montado, dejando las tablas sin paginar o con botones rotos.
- **Patrón obligatorio:**
  ```html
  <!-- ✅ El skeleton loader y la tabla conviven sin desmontar el <table> del DOM -->
  <table-skeleton-loader :visible="loadingBuscar" :rows="6" :columns="8" />

  <div class="table-responsive" v-show="!loadingBuscar && registros.length > 0">
      <table class="table table-bordered table-striped" id="tablaRegistros">
          ...
      </table>
  </div>
  ```

### T2 — Ciclo de Vida Limpio de DataTables (`safeDestroyDataTable` + `nextTick`)
- **Destrucción preventiva:** Invocar la destrucción segura de la instancia antes de limpiar o consultar los datos del API.
- **Reconstrucción:** En el bloque `finally` o tras recibir la respuesta, esperar la reconciliación reactiva de filas con `await nextTick()` antes de llamar a `initDataTable()`.
- **Eliminar `retrieve: true`:** Siempre destruir e inicializar limpiamente para que DataTables recalcule el número real de páginas y botones de paginación.

### T3 — Semáforo y Badges Semánticos de Prioridad y Estado
- **Prioridades Clínicas:** Sustituir letras ambiguas (`A`, `M`, `B`, `S`) por etiquetas semánticas universales:
  * **Alta / Urgente:** `badge badge-danger` / `label label-danger` con ícono `fa-bolt` o `fa-exclamation-triangle`.
  * **Media:** `badge badge-warning` / `label label-warning` con ícono `fa-clock-o`.
  * **Baja / Normal:** `badge badge-info` / `badge badge-secondary`.
- **Estados Asistenciales:**
  * **Con Lectura / Listo:** `label label-success` con ícono `fa-check` y hora de recepción visible debajo.
  * **En Espera / Sin Lectura:** `label label-warning` con ícono `fa-clock-o`.

### T4 — Quick Actions en Fila (Acceso Directo al Visor DICOM/PACS)
- Evitar que el médico o transcriptor deba abrir el panel lateral completo solo para revisar las imágenes:
  ```html
  <button
      type="button"
      class="btn btn-sm btn-outline-info mr-1"
      title="Ver Imágenes en Visor PACS/DICOM"
      @click="verImagenDirecta(item)"
  >
      <i class="fa fa-desktop"></i>
  </button>
  ```
- **Ancho fijo en columna de acciones:** Definir `min-width: 100px` - `120px` y `columnDefs: [{ targets: [-1], orderable: false }]` para evitar saltos o saltos de línea de botones.

### T5 — Tablas en Modo Oscuro (Dark Mode Radiológico)
- Garantizar que las tablas bajo `body.theme-dark` cuenten con:
  * Filas alternadas (`table-striped`) en tonos carbón suave (`#1f222a` y `#252932`).
  * Bordes sutiles `#2e3340` (eliminando bordes blancos deslumbrantes).
  * Efecto `hover` interactivo en filas para seguimiento visual de lectura médica.
  * Encabezados (`thead th`) con texto blanco y fondo pizarra `#181a20`.

### T6 — Tarjetas KPI Interactivas Vinculadas a la Tabla
- En cabeceras de tablas con alto volumen asistencial, incluir tarjetas dinámicas (*Total*, *Con Lectura*, *En Espera*, *Prioridad Alta*) que filtren las filas de la tabla instantáneamente al hacer clic sobre ellas sin peticiones adicionales al backend.

---

## Fase 7: Modo Oscuro Global y Cobertura Integral de Paneles

### D1 — Interruptor Maestro Centralizado
- Un único conmutador en la barra superior ([`AppHeader.vue`](file:///c:/laragon/www/lidiag/resources/js/layouts/AppHeader.vue)), sincronizado con `localStorage` (`lidiag_dark_mode`) y emitido mediante evento global `lidiagThemeChanged`.
- Eliminación de conmutadores locales desincronizados dentro de tablas o módulos específicos.

### D2 — Cobertura Universal en Drawers (`.right-sidebar`)
- Extensión obligatoria en [`dark-theme.css`](file:///c:/laragon/www/lidiag/resources/css/dark-theme.css) para que todos los paneles laterales hereden automáticamente:
  * Encabezados `.rpanel-title` y fondos pizarra `#181a20`.
  * Inputs, selects, textareas y contenedores con `.bg-light` o `.bg-white`.
  * **Soporte completo para Select2 en Modo Oscuro:** Dropdowns flotantes (`.select2-dropdown`), campos de búsqueda y opciones activas con contraste óptimo.
  * Editores WYSIHTML5 y alertas informativas en tonos oscuros sin destellos.

---

## Fase 8: Búsqueda Global y Paleta de Comandos (`Ctrl + K`)

### C1 — Acceso Inmediato por Teclado
- Paleta tipo *Spotlight* (`commandPaletteComponent.vue`) disponible globalmente mediante el atajo `Ctrl + K`.

### C2 — Filtrado Estricto de Permisos Spatie
- Cada ítem del catálogo debe validarse reactivamente contra los permisos y roles reales del usuario autenticado (`useAuth().can(item.permiso)`), impidiendo que operadores visualicen accesos no autorizados.

### C3 — Claves Únicas y Prevención de Errores de Render
- Uso obligatorio de `:key="item.id || (item.url + '_' + index)"` en resultados para evitar advertencias de Vue sobre duplicados en v-for.

---

## Fase 9: Navegación SPA y Resiliencia Multi-Sede

### S1 — Navegación SPA Nativa con Vue Router 4
- Eliminación de recargas completas de página: transición suave entre pantallas mediante `<router-link>` y `router.push()`.
- Configuración de aliases y redirecciones en `routes.js` y `routes/web.php` para evitar errores 404 en rutas históricas o marcadores.

### S2 — Sincronización Bidireccional de Sedes
- Persistencia simultánea en `sessionStorage` y `localStorage` (`ST-sede` y `ST-nomsede`).
- Manejo del bus de eventos con canal de contingencia `solicitarSedeActiva`: si un componente asíncrono monta en frío, solicita inmediatamente la sede activa al layout sin requerir que el usuario recargue la página.

---

## Resumen por severidad

| Prioridad | Área | Items |
|-----------|------|-------|
| 🔴 Alta | Accesibilidad, validación, seguridad del paciente | H2, H3, H4, H5, H6, H7, E3, E4, E7, T1, T2, C2, S2 |
| 🟠 Media | Feedback, ergonomía de tablas, modo oscuro | M1-M8, E1, E2, E5, E6, T3, T4, T5, T6, D1, D2, S1 |
| 🔵 Baja | Limpieza de código, tipografía y atajos menores | L1-L10, C1, C3 |

## Orden de ejecución sugerido al auditar un módulo

1. **Fase 1 & 4** — Mecánico, etiquetas semánticas `for`/`id`, atajos `@keyup.enter`, corrección de typos (~15 min).
2. **Fase 6** — Tablas: preservar DOM en DataTables (`v-show`), documento jerárquico (E7), badges semánticos y quick actions (~25 min).
3. **Fase 2 & 7** — Sidebars: botones `<button>` accesibles, ancho responsive, foco inicial y cobertura de modo oscuro (~15 min).
4. **Fase 3 & 5** — Feedback visual, toasts no invasivos, atajos clínicos (`Ctrl+S`, `F2`, `Esc`) (~20 min).
5. **Fase 8 & 9** — Navegación SPA y persistencia multi-sede resiliente (~10 min).

---

*Documento de estándares de ingeniería UI/UX y accesibilidad clínica — Plataforma LIDiag.*
