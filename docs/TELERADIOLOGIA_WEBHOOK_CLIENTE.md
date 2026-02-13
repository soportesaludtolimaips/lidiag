# Cliente: Envío de lecturas a LIDiag (TLIMAGEN → SaludTolima)

Endpoint público protegido por HMAC-SHA256. No requiere autenticación por sesión.

## URL

```
POST https://lidiag.com/api/tele-radiologia-recibir-lectura
```

(Local: `https://lidiag.test/api/tele-radiologia-recibir-lectura`)

## Variables

- `TELERADIOLOGIA_HMAC_SECRET`: secreto compartido entre TLIMAGEN y LIDiag (configurar en `.env` de LIDiag y en TLIMAGEN).
  ```
  d127b7584d2ed04aa3d7d5bea62fb5c7c938ce78ce3db2f28956d796fb8a7fb1
  ```
- `LIDIAG_URL`: base de LIDiag (ej. `https://lidiag.test`).

## Algoritmo de firma

1. Timestamp actual en segundos (`time()`).
2. Body = JSON exacto que se enviará (raw string).
3. `bodySha = SHA256(body)` (hex).
4. `stringToSign = METHOD + "\n" + PATH + "\n" + TIMESTAMP + "\n" + bodySha`
   - METHOD = `POST`
   - PATH = `/api/tele-radiologia-recibir-lectura` (con / inicial)
5. `signature = base64_encode(HMAC-SHA256(stringToSign, secret))`.

## Headers requeridos

- `Content-Type: application/json`
- `X-Timestamp`: timestamp usado en la firma (string).
- `X-Signature`: firma HMAC calculada (string).

## Ejemplo PHP

```php
<?php
$secret = getenv('TELERADIOLOGIA_HMAC_SECRET');
$baseUrl = 'https://lidiag.test';
$path = '/api/tele-radiologia-recibir-lectura';
$url = $baseUrl . $path;

$lectura = [
    'study_pk' => 208331,
    'study_iuid' => '1.2.392.200036.9116.2.6.1.3268.2051117318.1760616948.766943',
    'fec_estudio' => '2025-10-16 21:15:49',
    'accession_no' => '74281',
    'study_desc' => 'TC CRANEO SIMPLE/URG/WSA/6595425',
    'mods_in_study' => 'CT',
    'sexo' => 'M',
    'paciente' => [
        'num_docu' => '5919726',
        'nom_pacien' => 'RAMIREZ RODRIGUEZ SALOMON',
        'fec_naci' => null,
    ],
    'priori' => [
        'id' => 1,
        'nom_priori' => 'Alta',
    ],
    'cod_cups' => '879111',
    'nom_produc' => 'TOMOGRAFIA AXIAL COMPUTADA DE CRANEO SIMPLE',
    'fechor_lectura' => '2025-10-16 23:10:50',
    'lectura' => 'Hallazgos...',
    'medico' => [
        'reg_medico' => '',
        'num_docu' => '93284421',
        'name' => 'JOSE FABIO MARIN CASTAÑO',
        'firma_medico' => 'base64...',
    ],
    'tipo_resultado' => 1, // 1 Norma, 2 Patologico, 3 Critico
];

$body = json_encode($lectura, JSON_UNESCAPED_UNICODE);
$ts = (string) time();
$bodySha = hash('sha256', $body);
$stringToSign = implode("\n", ['POST', $path, $ts, $bodySha]);
$signature = base64_encode(hash_hmac('sha256', $stringToSign, $secret, true));

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Timestamp: ' . $ts,
        'X-Signature: ' . $signature,
    ],
]);

$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP $code\n";
echo $response;
```

## Ejemplo cURL (bash)

```bash
#!/bin/bash
SECRET="tu_teleradiologia_hmac_secret"
BASE_URL="https://lidiag.test"
PATH="/api/tele-radiologia-recibir-lectura"

BODY='{"study_pk":208331,"study_iuid":"1.2.392.200036.9116.2.6.1.3268.2051117318.1760616948.766943","fec_estudio":"2025-10-16 21:15:49","accession_no":"74281","study_desc":"TC CRANEO SIMPLE/URG/WSA/6595425","mods_in_study":"CT","sexo":"M","paciente":{"num_docu":"5919726","nom_pacien":"RAMIREZ RODRIGUEZ SALOMON","fec_naci":null},"priori":{"id":1,"nom_priori":"Alta"},"cod_cups":"879111","nom_produc":"TOMOGRAFIA AXIAL COMPUTADA DE CRANEO SIMPLE","fechor_lectura":"2025-10-16 23:10:50","lectura":"Hallazgos...","medico":{"reg_medico":"","num_docu":"93284421","name":"JOSE FABIO MARIN CASTAÑO","firma_medico":""},"tipo_resultado":1}'

TS=$(date +%s)
BODY_SHA=$(echo -n "$BODY" | sha256sum | cut -d' ' -f1)
STRING_TO_SIGN="POST
$PATH
$TS
$BODY_SHA"
SIG=$(echo -n "$STRING_TO_SIGN" | openssl dgst -sha256 -hmac "$SECRET" -binary | base64)

curl -X POST "$BASE_URL$PATH" \
  -H "Content-Type: application/json" \
  -H "X-Timestamp: $TS" \
  -H "X-Signature: $SIG" \
  -d "$BODY"
```

## Ejemplo Java

```java
import javax.crypto.Mac;
import javax.crypto.spec.SecretKeySpec;
import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.util.Base64;

// Java 11+
public class LidiagLecturaClient {

    private static final String SECRET = System.getenv("TELERADIOLOGIA_HMAC_SECRET");
    private static final String BASE_URL = "https://lidiag.test";
    private static final String PATH = "/api/tele-radiologia-recibir-lectura";

    public static void main(String[] args) throws Exception {
        String body = """
            {
                "study_pk": 208331,
                "study_iuid": "1.2.392.200036.9116.2.6.1.3268.2051117318.1760616948.766943",
                "fec_estudio": "2025-10-16 21:15:49",
                "accession_no": "74281",
                "study_desc": "TC CRANEO SIMPLE/URG/WSA/6595425",
                "mods_in_study": "CT",
                "sexo": "M",
                "paciente": {"num_docu": "5919726", "nom_pacien": "RAMIREZ RODRIGUEZ SALOMON", "fec_naci": null},
                "priori": {"id": 1, "nom_priori": "Alta"},
                "cod_cups": "879111",
                "nom_produc": "TOMOGRAFIA AXIAL COMPUTADA DE CRANEO SIMPLE",
                "fechor_lectura": "2025-10-16 23:10:50",
                "lectura": "Hallazgos...",
                "medico": {"reg_medico": "", "num_docu": "93284421", "name": "JOSE FABIO MARIN CASTAÑO", "firma_medico": ""},
                "tipo_resultado": 1
            }
            """.replaceAll("\\s+", " ").trim();

        String ts = String.valueOf(System.currentTimeMillis() / 1000);

        // SHA256 del body (hex)
        MessageDigest md = MessageDigest.getInstance("SHA-256");
        byte[] bodyShaBytes = md.digest(body.getBytes(StandardCharsets.UTF_8));
        StringBuilder bodyShaHex = new StringBuilder();
        for (byte b : bodyShaBytes) {
            bodyShaHex.append(String.format("%02x", b));
        }

        // stringToSign = POST + \n + path + \n + ts + \n + bodySha
        String stringToSign = "POST\n" + PATH + "\n" + ts + "\n" + bodyShaHex;

        // HMAC-SHA256, resultado en base64
        Mac hmac = Mac.getInstance("HmacSHA256");
        hmac.init(new SecretKeySpec(SECRET.getBytes(StandardCharsets.UTF_8), "HmacSHA256"));
        byte[] hmacBytes = hmac.doFinal(stringToSign.getBytes(StandardCharsets.UTF_8));
        String signature = Base64.getEncoder().encodeToString(hmacBytes);

        HttpClient client = HttpClient.newBuilder().build();
        HttpRequest request = HttpRequest.newBuilder()
                .uri(URI.create(BASE_URL + PATH))
                .header("Content-Type", "application/json")
                .header("X-Timestamp", ts)
                .header("X-Signature", signature)
                .POST(HttpRequest.BodyPublishers.ofString(body))
                .build();

        HttpResponse<String> response = client.send(request, HttpResponse.BodyHandlers.ofString());
        System.out.println("HTTP " + response.statusCode() + "\n" + response.body());
    }
}
```

Con JSON manual o Gson/Jackson:

```java
// Construir body (ej. con Gson o manual)
String body = "{\"study_pk\":208331,\"study_iuid\":\"1.2.392...\", ...}";

// El resto del algoritmo es igual
String ts = String.valueOf(System.currentTimeMillis() / 1000);
MessageDigest md = MessageDigest.getInstance("SHA-256");
String bodySha = bytesToHex(md.digest(body.getBytes(StandardCharsets.UTF_8)));
String stringToSign = "POST\n" + PATH + "\n" + ts + "\n" + bodySha;

Mac hmac = Mac.getInstance("HmacSHA256");
hmac.init(new SecretKeySpec(secret.getBytes(StandardCharsets.UTF_8), "HmacSHA256"));
String signature = Base64.getEncoder().encodeToString(hmac.doFinal(stringToSign.getBytes(StandardCharsets.UTF_8)));
```

## Ejemplo JavaScript (Node.js)

```javascript
const crypto = require('crypto');
const https = require('https');

const secret = process.env.TELERADIOLOGIA_HMAC_SECRET;
const baseUrl = 'https://lidiag.test';
const path = '/api/tele-radiologia-recibir-lectura';

const lectura = {
  study_pk: 208331,
  study_iuid: '1.2.392.200036.9116.2.6.1.3268.2051117318.1760616948.766943',
  fec_estudio: '2025-10-16 21:15:49',
  accession_no: '74281',
  study_desc: 'TC CRANEO SIMPLE/URG/WSA/6595425',
  mods_in_study: 'CT',
  sexo: 'M',
  paciente: {
    num_docu: '5919726',
    nom_pacien: 'RAMIREZ RODRIGUEZ SALOMON',
    fec_naci: null,
  },
  priori: { id: 1, nom_priori: 'Alta' },
  cod_cups: '879111',
  nom_produc: 'TOMOGRAFIA AXIAL COMPUTADA DE CRANEO SIMPLE',
  fechor_lectura: '2025-10-16 23:10:50',
  lectura: 'Hallazgos...',
  medico: {
    reg_medico: '',
    num_docu: '93284421',
    name: 'JOSE FABIO MARIN CASTAÑO',
    firma_medico: '',
  },
  tipo_resultado: 1,
};

const body = JSON.stringify(lectura);
const ts = String(Math.floor(Date.now() / 1000));
const bodySha = crypto.createHash('sha256').update(body).digest('hex');
const stringToSign = ['POST', path, ts, bodySha].join('\n');
const signature = crypto.createHmac('sha256', secret).update(stringToSign).digest('base64');

const url = new URL(path, baseUrl);
const options = {
  hostname: url.hostname,
  path: url.pathname,
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-Timestamp': ts,
    'X-Signature': signature,
    'Content-Length': Buffer.byteLength(body),
  },
};

const req = https.request(options, (res) => {
  let data = '';
  res.on('data', (chunk) => (data += chunk));
  res.on('end', () => console.log(res.statusCode, data));
});
req.on('error', console.error);
req.write(body);
req.end();
```

## Replay protection

El timestamp no puede diferir más de **5 minutos** respecto al servidor. Si expira, se responde `401`.

## Respuestas

- `200`: Lectura procesada correctamente.
- `400`: Datos inválidos.
- `401`: Firma inválida o headers faltantes.
- `404`: Estudio o producto no encontrado.
- `503`: HMAC no configurado en el servidor.
