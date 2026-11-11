# Estándares de Documentación y Código — LIDiag

Este documento define las reglas de estilo, convenciones de nombres y estándares de documentación que tanto los desarrolladores como los agentes de IA deben cumplir rigurosamente en este repositorio.

---

## 1. Documentación de Backend (PHP / Laravel)

### 1.1. Bloques PHPDoc en Métodos
Cada método en los controladores, servicios o comandos personalizados debe incluir un bloque PHPDoc completo con la siguiente estructura exacta:
- **Descripción:** Resumen conciso de la funcionalidad.
- **Flujo del Proceso:** Lista numerada detallando los pasos lógicos que ejecuta el método.
- **Metadatos de Control:** `@param`, `@return`, `@author` y `@date`.

**Ejemplo de Controlador:**
```php
/**
 * Genera el reporte final de ecografía y gestiona su conversión.
 *
 * Flujo del proceso:
 * 1. Busca la plantilla asociada a la Sede del paciente.
 * 2. Carga los datos del médico radiólogo y el paciente.
 * 3. Reemplaza las variables dinámicas en el archivo .docx de origen.
 * 4. Convierte el documento resultante a PDF mediante LibreOffice/unoconv.
 *
 * @param Request $request Datos estructurados del estudio y el paciente.
 * @return \Illuminate\Http\JsonResponse JSON con la URL del PDF generado o error 400.
 * @author Jhon Javer Lozano Arce
 * @date `Fecha de creacion del archivo`
 */
public function generarReporte(Request $request) 
{
    // Código aquí...
}
```

### 1.2. Documentación Externa (`docs/CONTROLLERS.md`)
Cuando crees o elimines un controlador, debes actualizar su índice correspondiente en `docs/CONTROLLERS.md` bajo su módulo respectivo:
- **Formato:** Agrupado por Módulo del sistema.
- **Contenido:** Nombre del archivo `.php`, descripción breve de su responsabilidad y una lista de sus métodos activos en una sola línea.

---

## 2. Documentación y Estructura del Frontend (Vue 3 / JavaScript)

### 2.1. Estructura de Componentes
- **Paradigma:** Usa exclusivamente la **Composition API con la sintaxis `<script setup>`**.
- **Estilos de UI:** El diseño se apoya en Bootstrap 5. Utiliza las clases nativas para estados (`badge badge-success`, `badge badge-danger`, etc.).
- **Mensajes de Confirmación:** Al invocar la utilidad `confirmAction`, **nunca** asumas etiquetas genéricas para los botones. Hardcodea explícitamente el significado en la propiedad `text`.

### 2.2. Bloques JSDoc en Frontend
Todos los archivos `.vue` y archivos helpers dentro de `resources/js/` deben iniciar con un bloque JSDoc en la parte superior del tag `<script setup>` para mantener la autoría del código.

**Ejemplo de Componente Vue:**
```vue
<script setup>
/**
 * @file Visor de Estudios Radiológicos para médicos.
 * @description Permite reproducir audios dictados y gestionar la inserción de transcripciones Whisper.
 * @author Jhon Javer Lozano Arce
 * @date `Fecha de creacion del archivo`
 */
import { ref, onMounted } from 'vue';

// Lógica del componente utilizando Composition API...
</script>
```

### 2.3. Estado y Comunicación
- **Bus de Eventos:** La comunicación global entre componentes montados en Blade se realiza estrictamente a través de `mitt` mediante `app.config.globalProperties.emitter`. 
- **Restricción:** Queda terminantemente prohibido estructurar soluciones utilizando Pinia o Vuex.

---

## 3. Estilo de Código y Reglas de Formateo Automático

### 3.1. PHP (Laravel Pint & PHPStan)
- **Formateador:** Laravel Pint gestiona el formateo del backend. Su configuración (`pint.json`) impone el uso estricto de **comillas dobles** (`single_quote: false`).
- **Líneas de Fin de Archivo:** Pint exige finales de línea en formato **LF**. Asegúrate de que el editor guarde en LF, ignorando las conversiones locales automáticas de Windows (CRLF).

### 3.2. JavaScript (ESLint & Prettier)
- **Verificación:** Ejecuta siempre `npx eslint <file>` antes de dar una tarea por terminada para asegurar el cumplimiento de la configuración Flat de ESLint (`eslint.config.js`).
