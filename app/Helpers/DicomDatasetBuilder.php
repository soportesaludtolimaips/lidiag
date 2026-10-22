<?php

namespace App\Helpers;

/**
 * Constructor de Datasets binarios DICOM estándar (Little Endian Explicit VR)
 * para atributos de Worklist (mwl_item.item_attrs) y Paciente (patient.pat_attrs).
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-21
 */
class DicomDatasetBuilder
{
    /**
     * VRs de 2 bytes de longitud en Little Endian Explicit VR.
     */
    private const SHORT_VRS = [
        'AE', 'AS', 'AT', 'CS', 'DA', 'DS', 'DT', 'FL', 'FD',
        'IS', 'LO', 'LT', 'PN', 'SH', 'SL', 'SS', 'ST', 'TM', 'UI', 'UL', 'US',
    ];

    /**
     * Codifica un dataset para Modality Worklist (mwl_item.item_attrs).
     * Dcm4chee almacena los atributos comprimidos con Transfer Syntax Deflated Explicit VR Little Endian (1.2.840.10008.1.2.1.99).
     */
    public static function buildMwlItemAttrs(array $data): string
    {
        $buffer = '';

        // (0008,0005) Specific Character Set: ISO_IR 100 (Latin-1)
        $buffer .= self::element(0x0008, 0x0005, 'CS', 'ISO_IR 100');

        // (0008,0050) Accession Number
        $buffer .= self::element(0x0008, 0x0050, 'SH', $data['accession_no'] ?? '');

        // (0008,0090) Referring Physician's Name
        if (! empty($data['referring_physician'])) {
            $buffer .= self::element(0x0008, 0x0090, 'PN', $data['referring_physician']);
        }

        // (0008,1030) Study Description
        $buffer .= self::element(0x0008, 0x1030, 'LO', $data['study_desc'] ?? '');

        // (0010,0010) Patient's Name
        $buffer .= self::element(0x0010, 0x0010, 'PN', $data['pat_name'] ?? '');

        // (0010,0020) Patient ID
        $buffer .= self::element(0x0010, 0x0020, 'LO', $data['pat_id'] ?? '');

        // (0010,0030) Patient's Birth Date (YYYYMMDD)
        $buffer .= self::element(0x0010, 0x0030, 'DA', $data['pat_birthdate'] ?? '');

        // (0010,0040) Patient's Sex (M/F/O)
        $buffer .= self::element(0x0010, 0x0040, 'CS', $data['pat_sex'] ?? 'O');

        // (0020,000D) Study Instance UID
        $buffer .= self::element(0x0020, 0x000D, 'UI', $data['study_iuid'] ?? '');

        // (0032,1060) Requested Procedure Description
        $buffer .= self::element(0x0032, 0x1060, 'LO', $data['study_desc'] ?? '');

        // (0040,0100) Scheduled Procedure Step Sequence (SQ)
        $stationAet = $data['station_aet'] ?? 'DXM';
        $stationName = $data['station_name'] ?? $stationAet;
        $spsItemContent = '';
        $spsItemContent .= self::element(0x0008, 0x0060, 'CS', $data['modality'] ?? 'CR');
        $spsItemContent .= self::element(0x0040, 0x0001, 'SH', $stationAet);
        $spsItemContent .= self::element(0x0040, 0x0002, 'DA', $data['sps_date'] ?? date('Ymd'));
        $spsItemContent .= self::element(0x0040, 0x0003, 'TM', $data['sps_time'] ?? date('His'));
        $spsItemContent .= self::element(0x0040, 0x0007, 'LO', $data['study_desc'] ?? '');
        $spsItemContent .= self::element(0x0040, 0x0009, 'SH', (string) ($data['sps_id'] ?? '1'));
        $spsItemContent .= self::element(0x0040, 0x0010, 'SH', $stationName);
        $spsItemContent .= self::element(0x0040, 0x0020, 'CS', 'SCHEDULED');

        // Sequence Item (FFFE,E000)
        $spsSequence = self::sequenceItem($spsItemContent);

        // Sequence Element (0040,0100)
        $buffer .= self::sequence(0x0040, 0x0100, $spsSequence);

        // (0040,1001) Requested Procedure ID
        $buffer .= self::element(0x0040, 0x1001, 'SH', $data['req_proc_id'] ?? '');

        // Cabecera File Meta Information estándar de Dcm4chee (Deflated Explicit VR Little Endian)
        // (0002,0000) UL: longitud 4, valor 30
        // (0002,0010) UI: longitud 22, valor '1.2.840.10008.1.2.1.99'
        $fmiHeader = pack('vv', 0x0002, 0x0000).'UL'.pack('v', 4).pack('V', 30)
            .pack('vv', 0x0002, 0x0010).'UI'.pack('v', 22).'1.2.840.10008.1.2.1.99';

        $compressedPayload = gzdeflate($buffer);

        return $fmiHeader.$compressedPayload;
    }

    /**
     * Codifica un dataset para paciente (patient.pat_attrs).
     */
    public static function buildPatientAttrs(array $data): string
    {
        $buffer = '';
        $buffer .= self::element(0x0008, 0x0005, 'CS', 'ISO_IR 100');
        $buffer .= self::element(0x0010, 0x0010, 'PN', $data['pat_name'] ?? '');
        $buffer .= self::element(0x0010, 0x0020, 'LO', $data['pat_id'] ?? '');
        $buffer .= self::element(0x0010, 0x0030, 'DA', $data['pat_birthdate'] ?? '');
        $buffer .= self::element(0x0010, 0x0040, 'CS', $data['pat_sex'] ?? 'O');

        return $buffer;
    }

    /**
     * Codifica un elemento DICOM estándar en Little Endian Explicit VR.
     */
    private static function element(int $group, int $element, string $vr, string $value): string
    {
        // Limpieza de caracteres para codificación Latin-1
        $cleanVal = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $value) ?: $value;

        // Longitud en bytes debe ser par en DICOM (relleno con espacio o null)
        $len = strlen($cleanVal);
        if ($len % 2 !== 0) {
            $padChar = ($vr === 'UI') ? "\0" : ' ';
            $cleanVal .= $padChar;
            $len++;
        }

        $tagBytes = pack('vv', $group, $element);

        if (in_array($vr, self::SHORT_VRS, true)) {
            // VR corto: Tag (4B) + VR (2B) + Length (2B uint16) + Value
            return $tagBytes.$vr.pack('v', $len).$cleanVal;
        }

        // VR largo (OB, OW, OF, SQ, UT, UN): Tag (4B) + VR (2B) + Reservado (2B=0) + Length (4B uint32) + Value
        return $tagBytes.$vr."\0\0".pack('V', $len).$cleanVal;
    }

    /**
     * Codifica una secuencia DICOM (SQ).
     */
    private static function sequence(int $group, int $element, string $content): string
    {
        $tagBytes = pack('vv', $group, $element);
        $len = strlen($content);

        return $tagBytes.'SQ'."\0\0".pack('V', $len).$content;
    }

    /**
     * Codifica un ítem de secuencia (FFFE, E000).
     */
    private static function sequenceItem(string $itemContent): string
    {
        $itemTag = pack('vv', 0xFFFE, 0xE000);
        $len = strlen($itemContent);

        return $itemTag.pack('V', $len).$itemContent;
    }
}
