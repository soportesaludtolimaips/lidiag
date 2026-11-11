# Sistema de Consentimientos Informados - Lidiag

## 1. Generalidades

### Descripción
Sistema robusto para gestión de consentimientos informados con configuración por producto, firmas digitales y validación de flujos médicos.

### Objetivos
- Configurar consentimientos informados por productos
- Gestionar variables dinámicas en plantillas
- Permitir carga de imágenes en consentimientos
- Habilitar firma digital del paciente/acompañante
- Traer firma del médico desde Lidiag
- Control de permisos de usuarios
- Bloqueo de lecturas hasta completar firmas

### Alcance del Sistema
El sistema contempla cuatro módulos principales:
1. **Configuración (Admin)**: Gestión de plantillas HTML con editor WYSIWYG, asignación a productos y versionado.
2. **Generación (PC Admin)**: Creación de consentimientos para pacientes, marcas en imágenes y gestión de adjuntos.
3. **Firma (Tablet Paciente)**: Interfaz táctil para que el paciente/acompañante firme con canvas.
4. **Firma Médica (Consultorio)**: Firmas médicas automáticas con firma Lidiag y bloqueo de lecturas.

### Principios de Diseño
- **Seguridad**: Auditoría completa, tokens únicos, validación de identidad.
- **Usabilidad**: Interfaces simples, feedback visual, polling eficiente.
- **Escalabilidad**: Estructura modular, APIs REST, storage configurable.
- **Legalidad**: Documentos firmados con timestamps, PDF generado, trazabilidad completa.

---

## 2. Variables del Sistema

### Placeholders Predefinidos
| Variable | Descripción |
|----------|-------------|
| `${num_docu}` | Número de documento del paciente |
| `${nombres}` | Nombres del paciente |
| `${apellidos}` | Apellidos del paciente |
| `${servicio}` | Nombre del servicio (tipo de estudio) |
| `${producto}` | Nombre detallado del producto |
| `${firma_paciente}` | Imagen de firma del paciente |
| `${firma_acompañante}` | Imagen de firma del acudiente |
| `${fecha}` | Fecha del procedimiento |
| `${hora}` | Hora del procedimiento |
| `${nombre_medico}` | Nombre del médico |
| `${observaciones}` | Observaciones adicionales |

---

## 3. Módulos del Sistema

### 3.1 Módulo de Configuración (Admin)

#### Estructura
- **Tabs**: Plantillas | Consentimientos | Configuración
- **Filtros**: Producto, Sede, Estado, Búsqueda
- **CRUD completo** de plantillas HTML

#### Funcionalidades
1. **Editor WYSIWYG completo** para crear plantillas
   - Negrita, cursiva, listas, enlaces
   - Tablas, encabezados, columnas
   - Canvas para dibujar/marcar zonas
   - Insertar imágenes
   - Variables `${...}` con highlight y autocompletar

2. **Gestión de Imágenes**
   - Imágenes decorativas/estáticas en el contenido
   - Imágenes con zonas interactivas para marcar
   - Upload drag & drop
   - Biblioteca de imágenes del admin
   - Tipos de marca: punto, círculo, flecha, mano alzada

3. **Asignación de Productos**
   - Un consentimiento → Múltiples productos
   - Selector múltiple de productos
   - Opción de requerir firma médica o no

4. **Plantillas**
   - Duplicar plantilla existente
   - Vista previa del template
   - Historial de versiones
   - Clonar para nueva versión

### 3.2 Módulo de Generación (PC Admin)

#### Ubicación
- Admisiones/recepción

#### Flujo
1. Buscar atención/paciente por número de documento
2. Listar productos con consentimientos configurados
3. Generar consentimiento para productos que lo tengan
4. Ver consentimiento pendiente de firma
5. Marcar zonas en imágenes según indica el paciente

#### Funcionalidades
- Búsqueda con autocompletar de pacientes
- Indicador visual de productos con/sin consentimiento
- Countdown de expiración (24 horas)
- Filtro: pendientes vs expirados
- Marcas del administrativo en imágenes

### 3.3 Módulo de Firma (Tablet Paciente)

#### Ubicación
- Admisiones/recepción (tablet dedicada)

#### Interfaz
- Pantalla limpia (sin distracciones)
- Contenido scrolleable del consentimiento
- Imágenes con zonas marcadas por administrativo
- Canvas táctil grande para firma
- Botón "Limpiar" para rehacer firma
- Checkbox: "Soy el paciente" / "Soy el acudiente"
- Botón grande "FIRMAR Y CONFIRMAR"
- Indicador de progreso si hay múltiples consentimientos

#### Sincronización
- Polling automático cada 15 segundos
- Marcar como "Firmado por paciente" al completar
- Timestamp de la firma

### 3.4 Módulo de Firmas Médicas (Consultorio)

#### Ubicación
- Consultorio médico

#### Funcionalidades
- Lista de consentimientos pendientes por firmar
- Firma automática con firma predefinida de Lidiag
- Confirmación one-click para firmar todos
- Ver/descargar PDF del consentimiento

---

## 4. Estados del Consentimiento

| Estado | Descripción | Color UI |
|--------|-------------|----------|
| Generado | Creado, pendiente de firma del paciente | Amarillo |
| Firmado por paciente | Paciente/acompañante firmó | Verde |
| Firmado por médico | Completamente firmado | Azul |
| Anulado | Cancelado/invalidado | Rojo |
| Expirado | Pasaron 24h sin firma | Gris |

---

## 5. Permisos de Usuarios (Spatie)

| Permiso | Descripción | Roles |
|---------|-------------|-------|
| config.consentimiento.plantilla.create | Crear/configurar plantillas | Admin |
| config.consentimiento.plantilla.edit | Editar plantillas | Admin |
| config.consentimiento.plantilla.delete | Eliminar plantillas | Admin |
| config.consentimiento.plantilla.view | Ver plantillas | Admin |
| config.consentimiento.configurar | Configurar consentimiento por producto | Admin |
| consentimiento.generar | Generar consentimiento para atención | Admin, Recepción |
| consentimiento.view | Ver consentimiento | Admin, Médico |
| consentimiento.sign-patient | Firmar como paciente/acompañante | Paciente, Acudiente |
| consentimiento.sign-doctor | Firmar como médico | Médico |
| consentimiento.anular | Anular consentimiento | Admin |
| consentimiento.export | Exportar/descargar PDF | Admin, Médico |
| consentimiento.marcar | Marcar zonas en imágenes | Admin, Recepción |

---

## 6. Flujo Completo del Proceso

```
┌─────────────────────────────────────────────────────────────────┐
│                        PC ADMIN (Admisiones)                    │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. Buscar atención/paciente                                    │
│  2. Listar productos con consentimientos configurados          │
│  3. Generar consentimiento para cada producto                 │
│  4. Marcar zonas en imágenes según indica paciente             │
│                                                                  │
│  Estado: GENERADO                                               │
│  Expira en: 24 horas                                           │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                        TABLET (Paciente)                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  5. Consentimiento aparece en lista de pendientes              │
│  6. Paciente lee el contenido (scrolleable)                     │
│  7. Paciente o acudiente dibuja firma en canvas                 │
│  8. Confirma firma (indica si es paciente o acudiente)         │
│                                                                  │
│  Estado: FIRMADO POR PACIENTE                                   │
│  Quien firmó: Paciente/Acudiente (registrado)                  │
│                                                                  │
│  Polling 15s → Actualiza estado en servidor                    │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                    CONSULTORIO (Médico)                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  9. Médico firma con su firma predefinida de Lidiag            │
│  10. Se genera PDF final con todos los datos                     │
│                                                                  │
│  Estado: FIRMADO POR MÉDICO                                     │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                 AL DÍA SIGUIENTE                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  11. Médico inicia sesión                                       │
│  12. Modal muestra consentimientos pendientes por firmar        │
│  13. Médico firma todos los pendientes (uno por uno o todos)     │
│  14. Se desbloquean las lecturas                                │
│                                                                  │
│  SI HAY PENDIENTES SIN FIRMAR:                                 │
│  - Bloqueo TOTAL de lecturas                                    │
│  - No se activan hasta tener todos los consentimientos          │
│    firmados por el médico                                       │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 7. Reglas de Negocio

### 7.1 Expiración de Consentimientos
- **Tiempo límite**: 24 horas desde la generación
- **Acción automática**: Si el paciente no firma en 24h, cambia a "Expirado"
- **Efecto**: Bloquea la lectura hasta que se complete o el admin libere

### 7.2 Firma del Médico
- **Opcional durante consulta**: El médico puede o no firmar
- **Obligatorio al día siguiente**: No puede leer hasta firmar todos
- **Firma automática**: Usa la firma predefinida en su perfil de Lidiag

### 7.3 Firma del Paciente/Acompañante
- **Flexible**: Puede firmar el paciente O el acudiente
- **Casos de uso**: Menores de edad, pacientes con acompañante
- **Evidencia**: Se registra quién firmó (paciente/acompañante) y timestamp

### 7.4 Bloqueo de Lecturas
- **Condición**: Si hay consentimientos sin firma médica
- **Alcance**: TODAS las lecturas del médico
- **Desbloqueo**: Solo cuando todos los consentimientos estén firmados
- **Notificación**: Modal al iniciar sesión + badge en menú

### 7.5 Marcas en Imágenes
- **Quién marca**: Administrativo (en PC admin)
- **Tipos de marca**: punto, círculo, flecha, mano alzada
- **Datos registrados**: posición (x,y), tipo, descripción, quién marcó, timestamp

---

## 8. Estructura de Base de Datos

### 8.1 Tablas Principales

#### consentimiento_plantillas
```php
$table->id();
$table->string('nombre')->comment('Nombre de la plantilla');
$table->text('contenido_html')->comment('Template HTML con variables ${...}');
$table->text('contenido_preview')->nullable()->comment('Preview renderizado');
$table->unsignedBigInteger('usuario_crea_id');
$table->foreign('usuario_crea_id')->references('id')->on('users');
$table->boolean('estado')->default(true)->comment('Activo/Inactivo');
$table->unsignedInteger('version')->default(1)->comment('Versión de la plantilla');
$table->timestamps();
```

#### consentimiento_plantilla_versiones
```php
$table->id();
$table->unsignedBigInteger('plantilla_id');
$table->foreign('plantilla_id')->references('id')->on('consentimiento_plantillas')->onDelete('cascade');
$table->unsignedInteger('version_numero')->comment('Número de versión');
$table->text('contenido_html')->comment('Template HTML en esta versión');
$table->unsignedBigInteger('usuario_crea_id');
$table->foreign('usuario_crea_id')->references('id')->on('users');
$table->text('cambios')->nullable()->comment('Descripción de cambios');
$table->timestamp('fecha_version');
$table->timestamps();

$table->unique(['plantilla_id', 'version_numero']);
```

#### consentimiento_plantilla_imagenes
```php
$table->id();
$table->unsignedBigInteger('plantilla_id');
$table->foreign('plantilla_id')->references('id')->on('consentimiento_plantillas')->onDelete('cascade');
$table->string('archivo')->comment('Ruta de la imagen');
$table->string('nombre')->comment('Nombre/descripción de la imagen');
$table->unsignedInteger('orden')->default(0)->comment('Orden de visualización');
$table->boolean('es_para_marcar')->default(false)->comment('Si tiene zonas interactivas');
$table->timestamps();
```

#### consentimiento_productos
```php
$table->id();
$table->unsignedBigInteger('plantilla_id');
$table->foreign('plantilla_id')->references('id')->on('consentimiento_plantillas')->onDelete('cascade');
$table->unsignedBigInteger('producto_id');
$table->foreign('producto_id')->references('id')->on('config_productos');
$table->unsignedBigInteger('sede_id');
$table->foreign('sede_id')->references('id')->on('config_sedes');
$table->boolean('requiere_firma_medico')->default(true)->comment('Si requiere firma del médico');
$table->timestamps();

$table->unique(['plantilla_id', 'producto_id', 'sede_id'], 'consentimiento_productos_unique');
```

#### consentimiento_documentos
```php
$table->id();
$table->unsignedBigInteger('plantilla_id');
$table->foreign('plantilla_id')->references('id')->on('consentimiento_plantillas');
$table->unsignedBigInteger('producto_id');
$table->foreign('producto_id')->references('id')->on('config_productos');
$table->unsignedBigInteger('sede_id');
$table->foreign('sede_id')->references('id')->on('config_sedes');
$table->unsignedBigInteger('atencion_id')->nullable()->comment('ID de la atención/admisión');
$table->unsignedBigInteger('paciente_id')->nullable();
$table->unsignedBigInteger('usuario_genera_id');
$table->foreign('usuario_genera_id')->references('id')->on('users');
$table->string('num_docu')->comment('Número de documento del paciente');
$table->string('nombres')->comment('Nombres del paciente');
$table->string('apellidos')->comment('Apellidos del paciente');
$table->string('medico_nombre')->nullable()->comment('Nombre del médico');
$table->unsignedBigInteger('medico_id')->nullable();
$table->foreign('medico_id')->references('id')->on('users');
$table->enum('estado', ['generado', 'firmado_paciente', 'firmado_medico', 'anulado', 'expirado'])
    ->default('generado');
$table->timestamp('fecha_expiracion')->nullable();
$table->timestamp('fecha_firma_paciente')->nullable();
$table->timestamp('fecha_firma_medico')->nullable();
$table->string('firma_paciente_archivo')->nullable()->comment('Imagen de firma del paciente');
$table->string('firma_acompañante_archivo')->nullable()->comment('Imagen de firma del acudiente');
$table->string('firma_medico_archivo')->nullable()->comment('Imagen de firma del médico');
$table->boolean('firma_tipo')->nullable()->comment('1=paciente, 2=acompañante');
$table->string('pdf_archivo')->nullable()->comment('Ruta del PDF generado');
$table->text('contenido_renderizado')->nullable()->comment('HTML con variables reemplazadas');
$table->timestamps();
```

#### consentimiento_documentos_auditoria
```php
$table->id();
$table->unsignedBigInteger('documento_id');
$table->foreign('documento_id')->references('id')->on('consentimiento_documentos')->onDelete('cascade');
$table->unsignedBigInteger('usuario_id')->nullable();
$table->foreign('usuario_id')->references('id')->on('users');
$table->string('accion')->comment('Accion realizada: create, view, edit, firma_paciente, firma_medico, anular, exportar');
$table->json('datos_anteriores')->nullable()->comment('Datos antes del cambio');
$table->json('datos_nuevos')->nullable()->comment('Datos después del cambio');
$table->string('ip_address')->nullable()->comment('IP del usuario');
$table->string('user_agent')->nullable()->comment('Navegador/dispositivo');
$table->text('descripcion')->nullable()->comment('Descripción adicional');
$table->timestamp('fecha_accion');
$table->timestamps();

$table->index(['documento_id', 'fecha_accion']);
$table->index(['usuario_id', 'fecha_accion']);
```

#### consentimiento_documentos_tokens
```php
$table->id();
$table->unsignedBigInteger('documento_id');
$table->foreign('documento_id')->references('id')->on('consentimiento_documentos')->onDelete('cascade');
$table->string('token')->unique()->comment('Token único de acceso');
$table->string('codigo_qr')->nullable()->comment('Código QR generado');
$table->unsignedInteger('usos_maximos')->default(1)->comment('Límite de usos del token');
$table->unsignedInteger('usos_actuales')->default(0)->comment('Usos actuales');
$table->timestamp('fecha_expiracion_token')->nullable()->comment('Expiración del token');
$table->boolean('activo')->default(true)->comment('Si el token está activo');
$table->timestamps();

$table->index(['token']);
$table->index(['documento_id', 'activo']);
```

#### consentimiento_documentos_adjuntos
```php
$table->id();
$table->unsignedBigInteger('documento_id');
$table->foreign('documento_id')->references('id')->on('consentimiento_documentos')->onDelete('cascade');
$table->unsignedBigInteger('usuario_sube_id')->nullable();
$table->foreign('usuario_sube_id')->references('id')->on('users');
$table->string('nombre_original')->comment('Nombre original del archivo');
$table->string('archivo')->comment('Ruta del archivo');
$table->string('tipo_mime')->comment('Tipo MIME del archivo');
$table->unsignedBigInteger('tamano')->comment('Tamaño en bytes');
$table->string('categoria')->comment('Categoría: documento_identidad, orden_medica, otro');
$table->timestamps();

$table->index(['documento_id']);
```

#### consentimiento_documento_marcas
```php
$table->id();
$table->unsignedBigInteger('documento_id');
$table->foreign('documento_id')->references('id')->on('consentimiento_documentos')->onDelete('cascade');
$table->unsignedBigInteger('imagen_id')->nullable();
$table->foreign('imagen_id')->references('id')->on('consentimiento_plantilla_imagenes');
$table->unsignedBigInteger('usuario_marca_id');
$table->foreign('usuario_marca_id')->references('id')->on('users');
$table->string('tipo_marca')->comment('punto, circulo, flecha, mano_alzada');
$table->float('posicion_x')->comment('Coordenada X');
$table->float('posicion_y')->comment('Coordenada Y');
$table->float('ancho')->nullable()->comment('Ancho para círculos/rectángulos');
$table->float('alto')->nullable()->comment('Alto para círculos/rectángulos');
$table->text('descripcion')->nullable()->comment('Descripción de la marca');
$table->text('datos_dibujo')->nullable()->comment('Datos JSON del dibujo libre');
$table->timestamp('fecha_marca');
$table->timestamps();
```

### 8.2 Índices
```php
// consentimiento_documentos
$table->index(['estado', 'fecha_expiracion']);
$table->index(['medico_id', 'estado']);
$table->index(['paciente_id', 'num_docu']);
$table->index(['sede_id', 'fecha_expiracion']);

// consentimiento_productos
$table->index(['producto_id', 'sede_id']);
$table->index(['plantilla_id']);
```

---

## 9. API Endpoints

### 9.1 Plantillas

| Método | Ruta | Descripción | Permiso |
|--------|------|-------------|---------|
| GET | /api/consentimiento/plantillas | Listar todas | config.consentimiento.plantilla.view |
| POST | /api/consentimiento/plantillas | Crear plantilla | config.consentimiento.plantilla.create |
| GET | /api/consentimiento/plantillas/{id} | Ver plantilla | config.consentimiento.plantilla.view |
| PUT | /api/consentimiento/plantillas/{id} | Editar plantilla | config.consentimiento.plantilla.edit |
| DELETE | /api/consentimiento/plantillas/{id} | Eliminar plantilla | config.consentimiento.plantilla.delete |
| POST | /api/consentimiento/plantillas/{id}/duplicate | Duplicar plantilla | config.consentimiento.plantilla.create |
| POST | /api/consentimiento/plantillas/{id}/imagenes | Subir imagen | config.consentimiento.plantilla.edit |
| DELETE | /api/consentimiento/plantillas/imagenes/{id} | Eliminar imagen | config.consentimiento.plantilla.edit |

### 9.2 Productos (Asignación)

| Método | Ruta | Descripción | Permiso |
|--------|------|-------------|---------|
| GET | /api/consentimiento/productos | Listar config por producto | config.consentimiento.configurar |
| POST | /api/consentimiento/productos | Asignar producto | config.consentimiento.configurar |
| PUT | /api/consentimiento/productos/{id} | Editar config | config.consentimiento.configurar |
| DELETE | /api/consentimiento/productos/{id} | Desasignar | config.consentimiento.configurar |
| GET | /api/consentimiento/productos/buscar/{producto_id} | Buscar config por producto | config.consentimiento.configurar |

### 9.3 Documentos

| Método | Ruta | Descripción | Permiso |
|--------|------|-------------|---------|
| GET | /api/consentimiento/documentos | Listar documentos | consentimiento.view |
| POST | /api/consentimiento/documentos | Generar consentimiento | consentimiento.generar |
| GET | /api/consentimiento/documentos/{id} | Ver documento | consentimiento.view |
| GET | /api/consentimiento/documentos/pendientes | Pendientes por firma médica | consentimiento.sign-doctor |
| GET | /api/consentimiento/documentos/pendientes-paciente | Pendientes por firma (PC admin) | consentimiento.generar |
| PUT | /api/consentimiento/documentos/{id}/firmar-paciente | Firmar como paciente | consentimiento.sign-patient |
| PUT | /api/consentimiento/documentos/{id}/firmar-medico | Firmar como médico | consentimiento.sign-doctor |
| PUT | /api/consentimiento/documentos/{id}/anular | Anular | consentimiento.anular |
| PUT | /api/consentimiento/documentos/{id}/restaurar | Restaurar expirado | consentimiento.anular |
| GET | /api/consentimiento/documentos/{id}/pdf | Descargar PDF | consentimiento.export |
| GET | /api/consentimiento/documentos/{id}/html | Ver HTML renderizado | consentimiento.view |

### 9.4 Marcas

| Método | Ruta | Descripción | Permiso |
|--------|------|-------------|---------|
| GET | /api/consentimiento/documentos/{id}/marcas | Listar marcas | consentimiento.marcar |
| POST | /api/consentimiento/documentos/{id}/marcas | Agregar marca | consentimiento.marcar |
| PUT | /api/consentimiento/marcas/{id} | Editar marca | consentimiento.marcar |
| DELETE | /api/consentimiento/marcas/{id} | Eliminar marca | consentimiento.marcar |

### 9.5 Utilidades

| Método | Ruta | Descripción | Permiso |
|--------|------|-------------|---------|
| GET | /api/consentimiento/variables | Listar variables disponibles | ninguno |
| POST | /api/consentimiento/preview | Preview de template | config.consentimiento.plantilla.view |

### 9.6 Tokens de Acceso

| Método | Ruta | Descripción | Permiso |
|--------|------|-------------|---------|
| POST | /api/consentimiento/documentos/{id}/generar-token | Generar token | consentimiento.generar |
| GET | /api/consentimiento/firmar/{token} | Página de firma por token | ninguno |
| POST | /api/consentimiento/firmar/{token} | Firmar con token | consentimiento.sign-patient |
| DELETE | /api/consentimiento/tokens/{id} | Invalidar token | consentimiento.anular |
| GET | /api/consentimiento/tokens/{id}/qr | Obtener QR del token | consentimiento.generar |

### 9.7 Auditoría

| Método | Ruta | Descripción | Permiso |
|--------|------|-------------|---------|
| GET | /api/consentimiento/documentos/{id}/auditoria | Ver historial de auditoría | consentimiento.view |

### 9.8 Adjuntos

| Método | Ruta | Descripción | Permiso |
|--------|------|-------------|---------|
| GET | /api/consentimiento/documentos/{id}/adjuntos | Listar adjuntos | consentimiento.view |
| POST | /api/consentimiento/documentos/{id}/adjuntos | Subir adjunto | consentimiento.generar |
| DELETE | /api/consentimiento/adjuntos/{id} | Eliminar adjunto | consentimiento.anular |
| GET | /api/consentimiento/adjuntos/{id}/descargar | Descargar adjunto | consentimiento.view |

### 9.9 Estadísticas

| Método | Ruta | Descripción | Permiso |
|--------|------|-------------|---------|
| GET | /api/consentimiento/estadisticas/resumen | Resumen general | config.consentimiento.configurar |
| GET | /api/consentimiento/estadisticas/sede/{sede_id} | Por sede | config.consentimiento.configurar |
| GET | /api/consentimiento/estadisticas/producto/{producto_id} | Por producto | config.consentimiento.configurar |
| GET | /api/consentimiento/estadisticas/medico/{medico_id} | Por médico | config.consentimiento.configurar |
| GET | /api/consentimiento/estadisticas/tiempo-promedio | Tiempos promedios | config.consentimiento.configurar |
| GET | /api/consentimiento/estadisticas/productos-populares | Productos más usados | config.consentimiento.configurar |

### 9.10 Versiones de Plantillas

| Método | Ruta | Descripción | Permiso |
|--------|------|-------------|---------|
| GET | /api/consentimiento/plantillas/{id}/versiones | Listar versiones | config.consentimiento.plantilla.view |
| GET | /api/consentimiento/plantillas/{id}/versiones/{version} | Ver versión | config.consentimiento.plantilla.view |
| POST | /api/consentimiento/plantillas/{id}/versiones | Crear nueva versión | config.consentimiento.plantilla.edit |
| PUT | /api/consentimiento/plantillas/{id}/versiones/{version}/restaurar | Restaurar versión | config.consentimiento.plantilla.edit |
| GET | /api/consentimiento/plantillas/{id}/versiones/comparar | Comparar versiones | config.consentimiento.plantilla.view |

---

## 10. Controladores

### 10.1 ConsentimientoPlantillaController
```php
// Métodos
index()          → Lista plantillas
store()          → Crear plantilla
show()           → Ver plantilla
update()         → Editar plantilla
destroy()         → Eliminar plantilla
duplicate()      → Duplicar plantilla
uploadImage()    → Subir imagen
deleteImage()    → Eliminar imagen
```

### 10.2 ConsentimientoProductoController
```php
// Métodos
index()          → Listar configs
store()          → Asignar producto
update()         → Editar config
destroy()        → Desasignar
findByProducto() → Buscar por producto
```

### 10.3 ConsentimientoDocumentoController
```php
// Métodos
index()              → Listar documentos
store()              → Generar consentimiento
show()               → Ver documento
getPendientes()      → Pendientes por firma médica
getPendientesPaciente() → Pendientes por firma (admin)
firmarPaciente()     → Firmar como paciente
firmarMedico()        → Firmar como médico
anular()              → Anular
restaurar()           → Restaurar expirado
generatePdf()         → Generar/descargar PDF
getHtml()            → Ver HTML renderizado
```

### 10.4 ConsentimientoMarcaController
```php
// Métodos
index()      → Listar marcas
store()      → Crear marca
update()     → Editar marca
destroy()    → Eliminar marca
```

### 10.5 ConsentimientoTokenController
```php
// Métodos
generate()   → Generar token
show()       → Ver info del token
invalidate() → Invalidar token
getQr()      → Obtener código QR
validate()   → Validar token para firma
```

### 10.6 ConsentimientoAuditoriaController
```php
// Métodos
index()      → Listar entradas de auditoría
store()     → Registrar auditoría
getByDocumento() → Auditoría por documento
getByUsuario() → Auditoría por usuario
```

### 10.7 ConsentimientoAdjuntoController
```php
// Métodos
index()      → Listar adjuntos
store()      → Subir adjunto
download()   → Descargar adjunto
destroy()    → Eliminar adjunto
```

### 10.8 ConsentimientoEstadisticaController
```php
// Métodos
resumen()    → Resumen general
porSede()    → Estadísticas por sede
porProducto() → Estadísticas por producto
porMedico()  → Estadísticas por médico
tiemposPromedio() → Tiempos promedios
productosPopulares() → Productos más usados
exportar()   → Exportar datos
```

### 10.9 ConsentimientoVersionController
```php
// Métodos
index()      → Listar versiones
show()       → Ver versión específica
store()      → Crear nueva versión
restaurar()  → Restaurar versión anterior
comparar()   → Comparar dos versiones
```

---

## 11. Form Requests (Validación)

### ConsentimientoPlantillaStoreRequest
```php
- nombre: required, string, max:255
- contenido_html: required, string
- estado: nullable, boolean
```

### ConsentimientoProductoStoreRequest
```php
- plantilla_id: required, exists:consentimiento_plantillas,id
- producto_id: required, exists:config_productos,id
- sede_id: required, exists:config_sedes,id
- requiere_firma_medico: nullable, boolean
```

### ConsentimientoDocumentoStoreRequest
```php
- atencion_id: nullable, integer
- producto_id: required, exists:config_productos,id
- num_docu: required, string, max:50
- nombres: required, string, max:255
- apellidos: required, string, max:255
- medico_id: nullable, exists:users,id
```

### ConsentimientoFirmaPacienteRequest
```php
- firma_archivo: required, image, max:2048 (base64)
- tipo_firma: required, in:1,2 (1=paciente, 2=acompañante)
```

### ConsentimientoTokenGenerateRequest
```php
- usos_maximos: nullable, integer, min:1, max:100
- fecha_expiracion: nullable, date, after:now
```

### ConsentimientoAdjuntoStoreRequest
```php
- archivo: required, file, max:10240 (10MB)
- nombre_original: required, string, max:255
- categoria: required, in:documento_identidad,orden_medica,historia_clinica,laboratorio,imagenes,otro
```

### ConsentimientoVersionStoreRequest
```php
- contenido_html: required, string
- cambios: nullable, string, max:500
```

### ConsentimientoEstadisticaRequest
```php
- sede_id: nullable, exists:config_sedes,id
- producto_id: nullable, exists:config_productos,id
- medico_id: nullable, exists:users,id
- fecha_inicio: nullable, date
- fecha_fin: nullable, date, after_or_equal:fecha_inicio
```

---

## 12. UI/UX

### 12.1 Pantalla Configuración (Admin)

#### Layout
```
┌─────────────────────────────────────────────────────────────────┐
│  Consentimientos Informados                                      │
│  ─────────────────────────                                      │
│  [Tabs: Plantillas | Configurar | Documentos]                  │
│                                                                  │
│  [Filtros: ▼ Producto | ▼ Sede | ▼ Estado | 🔍 Buscar...] [+ Nuevo]│
│  ──────────────────────────────────────────────────────────────  │
│  │Nombre         │Productos│Sede    │Estado│Versión│Acciones  │
│  │════════════════│═════════│════════│══════│════════│═════════ │
│  │Eco. Abdominal  │5 prod.  │Central │ Activo│   2   │[✏️👁📋🗑]│
│  │Mamografía     │2 prod.  │Norte   │ Activo│   1   │[✏️👁📋🗑]│
└─────────────────────────────────────────────────────────────────┘
```

#### Editor WYSIWYG (Modal/Tab)
- Toolbar flotante con todas las opciones
- Preview en tiempo real
- Variables `${...}` resaltadas en color azul
- Autocompletar al escribir `${`
- Upload de imágenes drag & drop
- Panel lateral para imágenes de la plantilla

### 12.2 Pantalla Generación (PC Admin)

#### Layout
```
┌─────────────────────────────────────────────────────────────────┐
│  Generar Consentamiento                                          │
│  ───────────────────                                             │
│  [Buscar paciente: ________________________ [🔍]]                │
│  ──────────────────────────────────────────────────────────────  │
│  Paciente: Juan Pérez  |  CC: 123456789  |  Edad: 35 años       │
│  ──────────────────────────────────────────────────────────────  │
│  │Producto              │Consentimiento      │Expira       │Acción│
│  │══════════════════════│════════════════════│═════════════│══════│
│  │Ecografía Abdominal   │✅ Configurado     │24h 15:30   │[Gen] │
│  │Mamografía            │⚠️ Sin config       │    —       │[—]  │
│  │Tomografía            │✅ Configurado     │24h 14:00   │[Gen] │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  Consentimientos Pendientes de Firma                            │
│  ──────────────────────────────────────────────────────────────  │
│  │Paciente   │Producto        │Generado     │Expira        │Acción│
│  │═══════════│════════════════│═════════════│══════════════│══════│
│  │Juan Pérez │Eco. Abdominal   │Hoy 2:30 PM  │Mañana 2:30PM │[Ver] │
│  │María López│Mamografía      │Hoy 1:15 PM  │Mañana 1:15PM │[Ver] │
└─────────────────────────────────────────────────────────────────┘
```

### 12.3 Pantalla Firma (Tablet)

#### Layout
```
┌─────────────────────────────────────────────────────────────────┐
│  📋 Lidiag - Consentimientos                                     │
│  ─────────────────────────                                      │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────────┐ │
│  │                                                             │ │
│  │  Consentimiento: Ecografía Abdominal                        │ │
│  │  ─────────────────────────                                  │ │
│  │                                                             │ │
│  │  [Contenido del consentimiento scrolleable]                 │ │
│  │                                                             │ │
│  │  ┌───────────────────────────────────────────────────────┐  │ │
│  │  │    📷 Imagen cuerpo humano                          │  │ │
│  │  │    [Zonas marcadas por admin]                      │  │ │
│  │  └───────────────────────────────────────────────────────┘  │ │
│  │                                                             │ │
│  │  ┌───────────────────────────────────────────────────────┐  │ │
│  │  │  Firma aquí:                                         │  │ │
│  │  │  ┌─────────────────────────────────────────┐        │  │ │
│  │  │  │                                         │        │  │ │
│  │  │  │         [Canvas firma táctil]         │        │  │ │
│  │  │  │                                         │        │  │ │
│  │  │  └─────────────────────────────────────────┘        │  │ │
│  │  │  [Limpiar]                                         │  │ │
│  │  └───────────────────────────────────────────────────────┘  │ │
│  │                                                             │ │
│  │  ☐ Soy el paciente    ☐ Soy el acudiente                  │ │
│  │                                                             │ │
│  │           [ ✓ FIRMAR Y CONFIRMAR ]                         │ │
│  └─────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

### 12.4 Pantalla Firmas Médicas (Consultorio)

#### Layout
```
┌─────────────────────────────────────────────────────────────────┐
│  Firmar Consentimientos                                          │
│  ───────────────────                                              │
│                                                                  │
│  ⚠️ Tiene 2 consentimientos pendientes por firmar              │
│  ──────────────────────────────────────────────────────────────  │
│  │Fecha      │Paciente    │Producto       │Médico    │Acción  │
│  │═══════════│════════════│═══════════════│══════════│════════│
│  │Hoy 9:00AM │Juan Pérez  │Eco. Abdominal  │Dr. Smith │[Firmar]│
│  │Hoy 8:30AM │María López │Mamografía     │Dr. Smith │[Ver PDF]│
│  ──────────────────────────────────────────────────────────────  │
│                      [Firmar Todos]                              │
└─────────────────────────────────────────────────────────────────┘
```

### 12.5 Modal Alerta (Médico al Iniciar Sesión)

```
┌─────────────────────────────────────────────────────────────────┐
│  ⚠️ Consentimientos Pendientes de Firma                          │
│  ──────────────────────────────────────────────────────────────  │
│                                                                  │
│  No puede realizar lecturas hasta que firme los siguientes:     │
│  ──────────────────────────────────────────────────────────────  │
│  │  📋 Consentimiento      │  Paciente        │  Tiempo        │
│  │  ───────────────────────│──────────────────│─────────────── │
│  │  ☐ Ecografía Abdominal  │  Juan Pérez      │  Hace 2 horas   │
│  │  ☐ Mamografía           │  María López     │  Hace 5 horas   │
│                                                                  │
│           [Firmar Todos]          [Ver detalles]                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 13. Flujo de Datos

### 13.1 Generación de Consentimiento
```
1. Admin busca atención/paciente
2. Sistema obtiene productos asociados
3. Para cada producto con consentimiento configurado:
   a. Crear documento en estado "generado"
   b. Establecer fecha_expiracion = now + 24h
   c. Renderizar contenido_html con variables
4. Listar en pendientes de firma
```

### 13.2 Firma del Paciente
```
1. Paciente ve consentimiento en tablet
2. Dibuja firma en canvas
3. Selecciona tipo (paciente/acompañante)
4. Confirma firma
5. Sistema:
   a. Guarda imagen de firma
   b. Actualiza estado = "firmado_paciente"
   c. Establece fecha_firma_paciente
   d. Registra tipo_firma
```

### 13.3 Firma del Médico
```
1. Médico ve consentimiento pendiente
2. Clic en "Firmar" (o "Firmar Todos")
3. Sistema usa firma predefinida del médico
4. Sistema:
   a. Guarda imagen de firma Lidiag
   b. Actualiza estado = "firmado_medico"
   c. Establece fecha_firma_medico
   d. Genera PDF final
   e. Almacena PDF
```

### 13.4 Generación de PDF
```
1. Obtener contenido_renderizado
2. Reemplazar placeholders con datos reales
3. Generar HTML completo con estilos
4. Convertir HTML a PDF (usando DomPDF/Snappy)
5. Almacenar en storage
6. Guardar ruta en pdf_archivo
```

---

## 14. Jobs/队列

### ConsentimientoExpiracionJob
- Se ejecuta cada 5 minutos
- Busca documentos en estado "generado" con fecha_expiracion < now
- Actualiza estado a "expirado"

### ConsentimientoNotificacionJob
- Notifica al médico de consentimientos pendientes
- Se ejecuta al iniciar sesión

---

## 15. Middleware

### CheckConsentimientosPendientes
```php
// En middleware del médico
1. Verificar si hay consentimientos pendientes
2. Si hay, redirigir a modal de firmas
3. Solo permitir acceso después de firmar todos
```

---

## 16. Consideraciones Técnicas

### 16.1 Almacenamiento
- Firmas: `storage/app/consentimientos/firmas/`
- PDFs: `storage/app/consentimientos/pdfs/`
- Imágenes plantillas: `storage/app/consentimientos/imagenes/`

### 16.2 Configuración Storage
```php
// config/filesystems.php
'disks' => [
    ...
    'consentimientos' => [
        'driver' => 'local',
        'root' => storage_path('app/consentimientos'),
        'url' => '/storage/consentimientos',
    ],
],
```

### 16.3 PDF Generation
- Usar DomPDF o Snappy (WkHtmlToPdf)
- Template CSS embebido
- Fuentes compatibles con español

### 16.4 Canvas Firma
- Librería: signature_pad o similar
- Exportar como PNG
- Comprimir antes de guardar

### 16.5 Optimizaciones
- Cache de plantillas renderizadas
- Índices en tablas frecuentes
- Polling eficiente (solo verificar pendientes)

---

## 18. Roadmap de Implementación

### Fase 1: Base de datos y Modelos
- [x] Diseño de 8 tablas (plantillas, versiones, imágenes, productos, documentos, marcas, auditoría, tokens, adjuntos)
- [ ] Crear migraciones
- [ ] Crear modelos Eloquent
- [ ] Configurar relaciones

### Fase 2: API Backend
- [ ] Controllers CRUD (4 controladores)
- [ ] Form Requests (validaciones)
- [ ] Rutas API
- [ ] Sistema de auditoría
- [ ] Generación de tokens

### Fase 3: Módulo Admin - Plantillas
- [ ] Editor WYSIWYG
- [ ] Gestión de plantillas
- [ ] Versionado de plantillas
- [ ] Asignación a productos
- [ ] Gestión de imágenes

### Fase 4: Módulo Generación
- [ ] Búsqueda de pacientes
- [ ] Generación de documentos
- [ ] Marcas en imágenes
- [ ] Sistema de adjuntos
- [ ] Tokens de acceso

### Fase 5: Módulo Firma Tablet
- [ ] Canvas de firma
- [ ] Sincronización polling
- [ ] UI responsiva
- [ ] Firma por token

### Fase 6: Módulo Médico
- [ ] Firmas médicas
- [ ] Modal de alertas
- [ ] Bloqueo de lecturas
- [ ] Dashboard de estadísticas

### Fase 7: PDF y Finalización
- [ ] Generación de PDF
- [ ] Exportación
- [ ] Auditoría completa
- [ ] Tests y polish

---

## 19. Mejoras UI/UX Recomendadas

### 19.1 Accesibilidad
- Contraste de colores WCAG AA
- Tamaño de fuentes mínimo 16px
- Áreas táctiles mínimo 44x44px (tablet)

### 19.2 Feedback Visual
- Animaciones sutiles al firmar
- Toast de confirmación
- Progress bar para múltiples consentimientos

### 19.3 Responsive
- Mobile-first para tablet
- Breakpoints: 768px, 1024px, 1440px
- Orientación portrait para tablet

### 19.4 Performance
- Lazy loading de imágenes
- Compresión de firmas
- Cache de templates

---

## 21. Características Adicionales Implementadas

### 21.1 Auditoría Completa

#### Descripción
Registro detallado de todos los eventos y cambios realizados en los consentimientos informados.

#### Eventos Registrados
| Acción | Descripción |
|--------|--------------|
| create | Creación del consentimiento |
| view | Visualización del consentimiento |
| edit | Edición de datos |
| firma_paciente | Firma del paciente/acompañante |
| firma_medico | Firma del médico |
| marcar | Marcas agregadas en imágenes |
| generar_token | Generación de token de acceso |
| usar_token | Uso del token para acceder |
| exportar | Exportación/descarga de PDF |
| anular | Anulación del consentimiento |
| restaurar | Restauración de consentimiento expirado |

#### Datos Registrados
- Usuario que realizó la acción
- Timestamp exacto
- IP address
- User agent (navegador/dispositivo)
- Datos anteriores (para cambios)
- Datos nuevos (para cambios)
- Descripción adicional

#### Endpoint de Auditoría
```
GET /api/consentimiento/documentos/{id}/auditoria → Ver historial de auditoría
```

---

### 21.2 Token de Acceso Único

#### Descripción
Generar links únicos para que el paciente firme desde cualquier dispositivo.

#### Características
- Token único generado con UUID
- Código QR para tablet
- Límite de usos configurable
- Expiración del token configurable
- Link compartible

#### Flujo
```
1. Admin genera token para consentimiento
2. Sistema genera:
   - Token único (UUID)
   - Código QR
   - Link: /consentimiento/firmar/{token}
3. Admin comparte link/código QR al paciente
4. Paciente accede y firma (verifica uso)
```

#### Endpoints
```
POST /api/consentimiento/documentos/{id}/generar-token → Generar token
GET /api/consentimiento/firmar/{token} → Página de firma por token
POST /api/consentimiento/firmar/{token} → Firmar con token
DELETE /api/consentimiento/tokens/{id} → Invalidar token
GET /api/consentimiento/tokens/{id}/qr → Obtener QR del token
```

#### Validaciones
- Token debe estar activo
- Token no debe estar expirado
- Usos máximos no alcanzado
- Consentimiento no expirado

---

### 21.3 Adjuntos/Extras

#### Descripción
El paciente o administrativo puede adjuntar documentos adicionales al consentimiento.

#### Categorías de Adjuntos
| Categoría | Descripción |
|-----------|--------------|
| documento_identidad | DNI, cédula, pasaporte |
| orden_medica | Orden médica del procedimiento |
| historia_clinica | Documentos de historia clínica |
| laboratorio | Resultados de laboratorios |
| imagenes | Imágenes relacionadas |
| otro | Otros documentos |

#### Características
- Múltiples archivos por consentimiento
- Tipos MIME permitidos configurables
- Límite de tamaño por archivo (10MB)
- Límite total por consentimiento (50MB)
- Previsualización de imágenes
- Descarga individual o agrupada

#### Endpoints
```
GET /api/consentimiento/documentos/{id}/adjuntos → Listar adjuntos
POST /api/consentimiento/documentos/{id}/adjuntos → Subir adjunto
DELETE /api/consentimiento/adjuntos/{id} → Eliminar adjunto
GET /api/consentimiento/adjuntos/{id}/descargar → Descargar adjunto
```

#### Almacenamiento
```
storage/app/consentimientos/adjuntos/{documento_id}/
├── documento_identidad_123456.pdf
├── orden_medica_789012.pdf
└── imagen_345678.jpg
```

---

### 21.4 Estadísticas/Dashboard

#### Descripción
Reportes y métricas del sistema de consentimientos.

#### Métricas Principales

##### Por Sede
| Métrica | Descripción |
|---------|-------------|
| total_generados | Total de consentimientos generados |
| tasa_firma_paciente | % que llegó a firma de paciente |
| tasa_firma_medico | % que llegó a firma de médico |
| tasa_expiracion | % que expiró sin firmar |
| tiempo_promedio_firma | Tiempo promedio de firma (paciente) |
| mas_utilizados | Productos con más consentimientos |

##### Por Producto
| Métrica | Descripción |
|---------|-------------|
| total | Total de consentimientos |
| firmados | Total firmados |
| expirados | Total expirados |
| tasa_conversion | % de firmas completadas |

##### Por Médico
| Métrica | Descripción |
|---------|-------------|
| pendientes | Consentimientos pendientes por firmar |
| firmados_hoy | Firmados hoy |
| tiempo_promedio_firma | Tiempo promedio en firmar |
| bloqueo_dias | Días con bloqueo de lecturas |

#### Dashboard UI
```
┌─────────────────────────────────────────────────────────────────┐
│  Dashboard Consentimientos Informados                            │
│  ──────────────────────────────────────────────────────────────  │
│  [Filtros: ▼ Sede | ▼ Producto | ▼ Médico | 📅 Rango fechas]    │
│  ──────────────────────────────────────────────────────────────  │
│                                                                  │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐          │
│  │ Generados│ │ Firmados │ │ Expirados│ │ Tasa     │          │
│  │   1,234   │ │   1,089   │ │    145   │ │  88.2%   │          │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘          │
│                                                                  │
│  [Gráfico: Consentimientos por día]                              │
│  [Gráfico: Tasa de conversión por producto]                      │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────────┐ │
│  │ Productos más utilizados                                    │ │
│  │ ────────────────────────                                    │ │
│  │ 1. Ecografía Abdominal       ████████████████████ 342      │ │
│  │ 2. Mamografía               ██████████████████ 289           │ │
│  │ 3. Tomografía               ████████████████ 198           │ │
│  └─────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

#### Endpoints
```
GET /api/consentimiento/estadisticas/resumen → Resumen general
GET /api/consentimiento/estadisticas/sede/{sede_id} → Por sede
GET /api/consentimiento/estadisticas/producto/{producto_id} → Por producto
GET /api/consentimiento/estadisticas/medico/{medico_id} → Por médico
GET /api/consentimiento/estadisticas/tiempo-promedio → Tiempos promedios
GET /api/consentimiento/estadisticas/productos-populares → Productos más usados
GET /api/consentimiento/estadisticas/exportar → Exportar en CSV/Excel
```

---

### 21.5 Versionado de Plantillas

#### Descripción
Historial completo de cambios en plantillas con capacidad de comparar y restaurar versiones anteriores.

#### Funcionalidades
1. **Crear nueva versión**
   - Cada edición crea una nueva versión
   - Se guarda snapshot del contenido completo
   - Se registra el usuario y fecha

2. **Historial de versiones**
   - Lista cronológica de todas las versiones
   - Muestra: número, fecha, usuario, descripción de cambios
   - Diferencia entre versiones

3. **Comparar versiones**
   - Diff visual entre dos versiones
   - Resalta cambios en contenido HTML
   - Resalta cambios en imágenes

4. **Restaurar versión**
   - Restaurar contenido de versión anterior
   - Crea nueva versión (no modifica historial)

5. **Versión activa**
   - Una versión está activa por defecto
   - Solo los documentos nuevos usan versión activa
   - Documentos existentes mantienen su versión

#### Estructura de Datos

##### consentimiento_plantilla_versiones (ya definida arriba)
```php
- plantilla_id → FK a plantilla
- version_numero → Entero secuencial
- contenido_html → Snapshot completo
- usuario_crea_id → Quién creó la versión
- cambios → Descripción de cambios
- fecha_version → Timestamp
```

#### Endpoints
```
GET /api/consentimiento/plantillas/{id}/versiones → Listar versiones
GET /api/consentimiento/plantillas/{id}/versiones/{version} → Ver versión
POST /api/consentimiento/plantillas/{id}/versiones → Crear nueva versión
PUT /api/consentimiento/plantillas/{id}/versiones/{version}/restaurar → Restaurar
GET /api/consentimiento/plantillas/{id}/versiones/comparar?v1={}&v2={} → Comparar
GET /api/consentimiento/plantillas/{id}/versiones/exportar → Exportar versión
```

#### UI de Versionado
```
┌─────────────────────────────────────────────────────────────────┐
│  Historial de Versiones - Consentimiento Ecografía              │
│  ──────────────────────────────────────────────────────────────  │
│                                                                  │
│  [Versión Actual: 3]  [Crear Nueva Versión] [Comparar]          │
│  ──────────────────────────────────────────────────────────────  │
│  │ Ver. │ Fecha         │ Usuario      │ Cambios               │
│  │══════│═══════════════│═══════════════│═══════════════════════│
│  │  3   │ 2026-05-10    │ Admin         │ Agregado párrafo X   │
│  │      │ 3:45 PM       │               │ [Ver] [Restaurar]    │
│  │──────│───────────────│───────────────│───────────────────────│
│  │  2   │ 2026-05-05    │ Admin         │ Corregido texto legal │
│  │      │ 10:20 AM      │               │ [Ver] [Restaurar]     │
│  │──────│───────────────│───────────────│───────────────────────│
│  │  1   │ 2026-04-20    │ Admin         │ Versión inicial       │
│  │      │ 2:00 PM       │               │ [Ver] [Restaurar]     │
│  ──────────────────────────────────────────────────────────────  │
│                                                                  │
│  [Ver Comparación v2 vs v3]                                    │
└─────────────────────────────────────────────────────────────────┘
```

---

## 22. Documentos Relacionados

- `configConsentimientoInformadoComponent.vue` - Componente Vue actual
- `ecografiasConsentimientosComponent.vue` - Módulo de consentimientos ecografías
- Tabla `consentimiento_informados` - Tabla actual (migración 2025_11_28)
- `ConsentimientoInformadoController.php` - Controlador actual

---

**Versión:** 2.0
**Fecha:** 2026-05-11
**Autor:** Lidiag