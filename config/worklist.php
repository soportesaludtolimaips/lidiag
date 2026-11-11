<?php

/**
 * Lista de trabajo (Modality Worklist) hacia el PACS de cada sede.
 *
 * Lidiag envía un HL7 ORM^O01 por MLLP al dcm4chee de la sede (2.x o 5.x)
 * cuando una cita se asigna, se reprograma o se cancela. El equipo (DX, CR,
 * ecógrafo...) consulta luego la lista de trabajo del PACS por DICOM y toma
 * los datos del paciente sin que el tecnólogo los digite.
 *
 * La activación, el puerto HL7 y el AET se configuran por sede
 * (Configuración > Sedes). Aquí solo van los valores comunes.
 *
 * @author Davide Rivera (Luis Castaño)
 *
 * @date 2026-10-11
 */
return [

    // Nombre de la aplicación emisora (MSH-3).
    'aplicacion' => env('WORKLIST_APLICACION', 'LIDIAG'),

    // Prefijo del número de acceso: C + id de la cita (máx. 16 caracteres).
    'prefijo_acceso' => env('WORKLIST_PREFIJO_ACCESO', 'C'),

    // Tiempos de espera en segundos; la cita nunca se bloquea por el PACS.
    'timeout_conexion' => (float) env('WORKLIST_TIMEOUT_CONEXION', 3),
    'timeout_respuesta' => (float) env('WORKLIST_TIMEOUT_RESPUESTA', 5),

    /*
     * Modalidad DICOM según el prefijo del código CUPS del producto.
     * Gana el prefijo más largo. Los productos sin modalidad (consultas,
     * laboratorio...) no se envían a la lista de trabajo.
     */
    'modalidades' => [
        '870' => 'DX', // Radiografía cráneo, cara y cuello
        '871' => 'DX', // Radiografía de tórax
        '872' => 'DX',
        '873' => 'DX', // Radiografía columna, pelvis y extremidades
        '874' => 'DX', // Radiografía de abdomen
        '875' => 'DX',
        '876' => 'DX',
        '8768' => 'MG', // Mamografía
        '879' => 'CT', // Tomografía
        '881' => 'US', // Ecografía
        '882' => 'US', // Ecografía doppler
        '883' => 'MR', // Resonancia magnética
    ],
];
