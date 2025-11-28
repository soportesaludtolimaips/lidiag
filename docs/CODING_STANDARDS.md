# Estándares de Documentación y Código

## Documentación de Controladores (PHP)

### 1. Documentación en Código (PHPDoc)

Cada método en los controladores debe tener un bloque PHPDoc detallado que incluya:

-   **Descripción**: Resumen claro de la funcionalidad.
-   **Flujo del Proceso**: Lista numerada explicativa de los pasos lógicos que realiza el método.
-   **Parámetros**: `@param` con tipo y descripción.
-   **Retorno**: `@return` con tipo y descripción.

**Ejemplo:**

```php
/**
 * Genera el reporte final de ecografía.
 *
 * Flujo del proceso:
 * 1. Busca la plantilla asociada a la Sede.
 * 2. Carga datos del médico y paciente.
 * 3. Reemplaza variables en el .docx.
 * 4. Convierte a PDF.
 *
 * @param Request $request Datos del estudio y paciente.
 * @return \Illuminate\Http\JsonResponse URL del PDF generado.
 */
public function metodoEjemplo(Request $request) { ... }
```

### 2. Documentación Externa (`docs/CONTROLLERS.md`)

El archivo de documentación general debe ser conciso y servir como índice.

-   **Formato**: Agrupado por Módulo.
-   **Contenido**:
    -   Nombre del Controlador.
    -   Descripción breve de su responsabilidad.
    -   Lista de métodos con una descripción de una sola línea.

**Ejemplo:**

```markdown
### `EjemploController.php`

Gestión de ejemplos.

-   **`metodoEjemplo`**: Genera el reporte final y lo convierte a PDF.
```
