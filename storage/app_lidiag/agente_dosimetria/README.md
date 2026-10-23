# Agente Edge de Dosimetría Radiológica — LIDiag

Script de extracción desatendida de dosis de radiación ionizante para cumplimiento de la **Resolución 482 de 2018** (Colombia) y lineamientos de seguridad **ISO/IEC 27001**.

## Características
1. **Anonimización Estricta:** El `PatientID` se convierte en un hash SHA-256 irreversible con salt antes de salir del hospital local. Ninguna Información de Salud Protegida (PHI) viaja en texto claro.
2. **Extracción Multimodal:** Extrae métricas clínicas según la modalidad:
   - **CT (Tomografía):** `CTDIvol` (mGy) y `DLP` (mGy·cm)
   - **MG (Mamografía):** `DGM` (mGy)
   - **DX / CR (Rayos X Digital):** `DAP` (Gy·cm²)
3. **Resiliencia de Red:** Envío en lotes (bulk) con HTTPAdapter y política de reintentos exponencial (backoff) ante caídas de enlace.
4. **Caché Incremental:** Evita procesar o transmitir estudios ya computados.

## Requisitos en la Sede Local
- Python 3.8+
- Paquetes pip:
  ```bash
  pip install pydicom requests mysql-connector-python
  ```

## Configuración
El agente comparte el archivo `config.json` ubicado en `../agente_backups_dcm/config.json`.
Asegúrese de incluir las claves:
```json
{
  "sede_id": 1,
  "api_url": "https://lidiag.test/api/dosimetria/registros",
  "api_token": "TU_TOKEN_SANCTUM_AQUI",
  "local_images_dir": "D:\\img_dcm",
  "db_host": "127.0.0.1",
  "db_port": 3306,
  "db_name": "pacsdb",
  "db_user": "root",
  "db_password": ""
}
```

## Ejecución
- **Prueba manual:** `python agente_dosimetria.py --dry-run`
- **Instalación como servicio en Windows:** Ejecutar como Administrador `instalar_tarea_sede.bat`.
