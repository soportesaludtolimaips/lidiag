# BOT-WHATSAPP-CONFIG

Documentación paso a paso de la configuración del bot de WhatsApp Business para LIDiag (Laravel + Meta Cloud API).

---

## Tabla de Contenido

1. [Resumen](#resumen)
2. [Estado actual](#estado-actual)
3. [Arquitectura](#arquitectura)
4. [Credenciales](#credenciales)
5. [Configuración paso a paso](#configuración-paso-a-paso)
6. [Problemas encontrados y soluciones](#problemas-encontrados-y-soluciones)
7. [Archivos modificados](#archivos-modificados)
8. [Pruebas](#pruebas)
9. [Pendientes](#pendientes)

---

## Resumen

Bot conversacional de WhatsApp que recibe mensajes de pacientes en el número de la clínica y los guía a través de un flujo de agendamiento de citas médicas (servicio → documento → paciente → orden → autorización → confirmar).

**Stack:** Laravel 10 + Meta WhatsApp Cloud API v21.0
**Estado:** ✅ Funcional en entorno de desarrollo

---

## Estado actual

| Componente | Estado |
|---|---|
| Credenciales Meta en `.env` | ✅ Completas |
| Webhook configurado en Meta | ✅ Suscrito a `messages` |
| Túnel local (cloudflared) | ✅ Activo |
| Firma del webhook | ✅ Verificada correctamente |
| Suscripción WABA → App | ✅ App "Salud Tolima" suscrita |
| Flujo del bot | ✅ Responde "Hola" con menú de servicios |
| Servicios configurados | ⚠️ Falta poblar la tabla `config_servicio_citas` |
| Asociación número → sede | ⚠️ El número de prueba no tiene sede asignada |

---

## Arquitectura

```
[Paciente WhatsApp]
       ↓ envía mensaje
[Meta Cloud API] → [Túnel cloudflared] → [Webhook Laravel]
       ↓                                         ↓
[Response bot] ←──────────── [WhatsAppFlowService]
       ↓ procesa
[Cita creada] (estado: solicitada)
       ↓
[Personal admin recibe notificación]
```

### Archivos principales

| Archivo | Función |
|---|---|
| `app/Http/Controllers/Citas/CitaController.php` | Recibe el webhook (`webhook()`) |
| `app/Services/WhatsAppService.php` | API wrapper de Meta + verificación firma |
| `app/Services/WhatsAppFlowService.php` | State machine del flujo conversacional |
| `app/Models/Citas/WhatsAppConversation.php` | Estado de conversación por teléfono |
| `app/Models/Citas/Cita.php` | Modelo de la cita médica |
| `routes/api_citas.php` | Rutas del webhook y CRUD de citas |

---

## Credenciales

### Variables en `.env`

```ini
# Meta WhatsApp API
META_WHATSAPP_TOKEN=EAALxDxVEdb8BRoA7KOhkw5BsTew4LaQIYPXHDZB4Ap1haVcC9Ur5ZAeu9idhBYSiKiBgZCY4u9Cei44PVSDj4rtU5u5nUog5Nt77ScR3Np2cRT9ZCgfpz772boWNrLnlZCALTDoJHGBr6pLeGE1PsdIdZAF2OM6nkwXpwYlkkA60xad8DshGWtb5Q91zNobFsb3ZC3arZCZBRIICRQIxOokf5jJCK7chunPt8v8zhLZCoCA1vAQFZCOcM4d82yDeBbYPjfmqyIZBcNwhv5TqM0gTFPA5bRih
META_WHATSAPP_PHONE_ID=1090680644127207
META_WHATSAPP_APP_SECRET=2310dea4faf800829c7ec05ad3e6cca0
META_WHATSAPP_BUSINESS_ID=874213625694591
META_WHATSAPP_VERIFY_TOKEN=lidiag_whatsapp_2026
```

### Identificadores Meta

| Recurso | ID | Nombre |
|---|---|---|
| App | 827997037032895 | Salud Tolima |
| WhatsApp Business Account (WABA) | 874213625694591 | — |
| Phone Number | 1090680644127207 | +1 555-637-0875 (número de prueba) |
| Business ID | 1728761391615058 | — |

⚠️ El `META_WHATSAPP_TOKEN` es un token de usuario que **puede expirar**. Si vuelve a fallar con error 401, regenerar en [Meta Developers](https://developers.facebook.com/apps/) → App → WhatsApp → API Setup → "Generate Access Token".

---

## Configuración paso a paso

### 1. Obtener credenciales Meta

#### 1.1 Phone Number ID y Access Token
1. Ir a [https://developers.facebook.com/apps/](https://developers.facebook.com/apps/)
2. Seleccionar la app "Salud Tolima"
3. Menú izquierdo → **WhatsApp** → **API Setup**
4. Copiar:
   - **Phone Number ID** → `META_WHATSAPP_PHONE_ID`
   - **Access Token** → `META_WHATSAPP_TOKEN`

#### 1.2 App Secret
1. Menú izquierdo → **Configuración** → **Información básica**
2. Campo "App Secret" → clic en "Show"
3. Copiar → `META_WHATSAPP_APP_SECRET`

#### 1.3 Business Account ID
1. Ir a [https://business.facebook.com/](https://business.facebook.com/)
2. Settings → Business Settings → Business Info
3. Copiar "Business ID" → `META_WHATSAPP_BUSINESS_ID`

#### 1.4 Verify Token (personalizado)
- Definir un string único (ej: `lidiag_whatsapp_2026`)
- Asignar → `META_WHATSAPP_VERIFY_TOKEN`

### 2. Exponer el webhook localmente (cloudflared)

```bash
cloudflared tunnel --url http://localhost:80 --http-host-header lidiag.test
```

Salida esperada:
```
Your quick Tunnel has been created! Visit it at:
https://<random>.trycloudflare.com
```

> **Nota:** La URL cambia cada vez que se reinicia `cloudflared`. Si cambia, hay que actualizar el webhook en Meta.

### 3. Configurar webhook en Meta

#### Opción A: Desde Meta Business Manager
1. Ir a [https://business.facebook.com/](https://business.facebook.com/)
2. Settings → Business Settings → Accounts → WhatsApp Accounts
3. Seleccionar la cuenta
4. Sección **Webhook** → **Configure**
5. Ingresar:
   - **Callback URL**: `https://<tunnel>.trycloudflare.com/api/citas/webhook`
   - **Verify Token**: `lidiag_whatsapp_2026`
6. **Verify and save**
7. Suscribirse al campo **`messages`**

#### Opción B: Suscribir vía API (si no aparece en la UI)
```bash
curl -X POST \
  "https://graph.facebook.com/v21.0/$WABA_ID/subscribed_apps" \
  -H "Authorization: Bearer $META_WHATSAPP_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"subscribed_fields":["messages"]}'
```

### 4. Verificar instalación
```bash
# Verificar túnel
curl "https://<tunnel>.trycloudflare.com/api/citas/webhook?hub.mode=subscribe&hub.verify_token=lidiag_whatsapp_2026&hub.challenge=test"
# Debe responder: test
```

---

## Problemas encontrados y soluciones

### Problema #1: Token expirado (401)

**Síntoma:**
```json
{"error":{"message":"Authentication Error","code":190,"type":"OAuthException"}}
```

**Causa:** El `META_WHATSAPP_TOKEN` estaba revocado/expirado.

**Solución:** Regenerar token en Meta Developers → App → WhatsApp → API Setup → "Manage" → "Generate new token".

---

### Problema #2: Webhook no recibía mensajes (CRÍTICO)

**Síntoma:** Meta enviaba el webhook pero Laravel devolvía 401 y Meta dejaba de reenviar.

**Causa:** Bug en `WhatsAppService::verificarWebhook()`. El header `X-Hub-Signature-256` llega como `sha256=<hash>` pero el código comparaba solo el hash sin el prefijo.

**Código original (incorrecto):**
```php
$expected = hash_hmac('sha256', $payload, $secret);
return hash_equals($expected, $signature);  // ❌ nunca coincide
```

**Código corregido:**
```php
$expected = 'sha256='.hash_hmac('sha256', $payload, $secret);
return hash_equals($expected, $signature);  // ✅ coincide
```

**Archivo:** `app/Services/WhatsAppService.php:341`

---

### Problema #3: App no suscrita a WABA

**Síntoma:** Después de arreglar la firma, los webhooks de prueba llegaban pero los mensajes reales no.

**Causa:** La app "Salud Tolima" no estaba en `subscribed_apps` del WhatsApp Business Account.

**Diagnóstico:**
```bash
GET https://graph.facebook.com/v21.0/$WABA_ID/subscribed_apps
# Solo mostraba: "WA DevX Webhook Events 1P App" (interno de Meta)
```

**Solución:** Suscribir vía API:
```bash
POST https://graph.facebook.com/v21.0/$WABA_ID/subscribed_apps
Body: {"subscribed_fields":["messages"]}
# Respuesta: {"success":true}
```

**Verificación:**
```bash
GET https://graph.facebook.com/v21.0/$WABA_ID/subscribed_apps
# Ahora muestra: "Salud Tolima" + "WA DevX Webhook Events 1P App"
```

---

### Problema #4: "No hay servicios disponibles"

**Síntoma:** Bot responde con `"Lo sentimos, actualmente no hay servicios disponibles."`

**Causa:** La tabla `config_servicio_citas` no tiene registros con `estado=true` para la sede del número, o el seeder no se ha ejecutado.

**Solución (pendiente):**
1. Ejecutar `php artisan db:seed --class=ConfigServicioCitasSeeder`
2. O crear servicios manualmente desde el panel de administración
3. Asignar la sede al número de prueba en `config_sede_whatsapp_numbers`

---

## Archivos modificados

### `app/Services/WhatsAppService.php`
- **Línea 341**: Fix en `verificarWebhook()` — agregado prefijo `sha256=`

```php
// Antes
$expected = hash_hmac('sha256', $payload, $secret);

// Después
$expected = 'sha256='.hash_hmac('sha256', $payload, $secret);
```

### `app/Http/Controllers/Citas/CitaController.php`
- Webhook handler (sin cambios necesarios, ya tenía la estructura correcta)

### `.env`
- Agregadas variables faltantes: `META_WHATSAPP_APP_SECRET`, `META_WHATSAPP_BUSINESS_ID`
- Actualizado `META_WHATSAPP_TOKEN` (estaba expirado)

---

## Pruebas

### Test 1: Verificar webhook (GET)
```bash
curl "https://<tunnel>.trycloudflare.com/api/citas/webhook?hub.mode=subscribe&hub.verify_token=lidiag_whatsapp_2026&hub.challenge=hello"
```
**Respuesta esperada:** `hello`

### Test 2: Simular POST con firma válida
```bash
# PowerShell
$secret = "META_APP_SECRET"
$body = '{"entry":[{"changes":[{"value":{"messages":[{"from":"573001234567","id":"test","type":"text","text":{"body":"Hola"}}]}}]}]}'
$signature = 'sha256=' + [System.BitConverter]::ToString(
  [System.Security.Cryptography.HMACSHA256]::new(
    [System.Text.Encoding]::UTF8.GetBytes($secret)
  ).ComputeHash(
    [System.Text.Encoding]::UTF8.GetBytes($body)
  )
).Replace('-','').ToLower()

Invoke-WebRequest -Uri "https://<tunnel>.trycloudflare.com/api/citas/webhook" `
  -Method POST -Body $body -ContentType "application/json" `
  -Headers @{"X-Hub-Signature-256"=$signature}
```
**Respuesta esperada:** `200 OK` con `{"message":"OK"}`

### Test 3: Mensaje real desde WhatsApp
1. Abrir WhatsApp en el celular con el número verificado (+57-316-1754039)
2. Enviar "Hola" al número de prueba (+1-555-637-0875)
3. **Respuesta esperada del bot:** Menú con los servicios disponibles

### Logs para diagnóstico
```bash
# Ver logs en vivo
Get-Content C:\laragon\www\lidiag\storage\logs\laravel.log -Wait | Select-String -Pattern "WhatsApp"

# Solo errores
Get-Content C:\laragon\www\lidiag\storage\logs\laravel.log -Tail 20 | Select-String -Pattern "ERROR"
```

---

## Pendientes

### Inmediato
- [ ] **Poblar `config_servicio_citas`**: Ejecutar seeder o crear servicios manualmente
- [ ] **Asignar sede al número de prueba** en `config_sede_whatsapp_numbers`
- [ ] **Notificación al admin**: Cuando llega una cita `solicitada`, dashboard debe alertar al personal

### Mediano plazo
- [ ] **Webhook de producción**: Reemplazar túnel cloudflared con URL pública (lidiag.com/api/citas/webhook)
- [ ] **Plantillas de Meta**: Crear templates para mensajes iniciados por la clínica (recordatorios, confirmaciones)
- [ ] **Manejo de errores robusto**: Si el bot falla a mitad del flujo, debe poder reintentarse
- [ ] **Logs estructurados**: Guardar cada interacción en una tabla para auditoría

### Largo plazo
- [ ] **Pasar app a modo Live (producción)**: Verificación de empresa, métodos de pago
- [ ] **Migrar a System User token** (no expira)
- [ ] **HLS/menú interactivo**: Usar listas y botones de WhatsApp en vez de texto

---

## Recursos

- [Meta WhatsApp Cloud API Docs](https://developers.facebook.com/docs/whatsapp/cloud-api)
- [Webhook Reference](https://developers.facebook.com/docs/whatsapp/cloud-api/webhooks)
- [Error Codes](https://developers.facebook.com/docs/whatsapp/cloud-api/support/error-codes)
- [Cloudflare Tunnel](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/)

---

**Última actualización:** 2026-06-04
**Autor:** MiniMax (M3)
