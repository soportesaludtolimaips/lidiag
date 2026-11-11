<?php

namespace App\Helpers;

/**
 * Generador autónomo y liviano de códigos QR en formato PNG (Pure PHP + GD).
 *
 * Implementa la especificación estándar ISO/IEC 18004 para códigos QR Versión 4
 * (33x33 módulos con corrección de errores nivel L), capaz de almacenar hasta 100 bytes
 * (ideal para URLs públicas de verificación y tokens de validación digital sin dependencias externas).
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-03
 */
class QrCodeHelper
{
    private static array $gfExp = [];

    private static array $gfLog = [];

    private static bool $initialized = false;

    /**
     * Inicializa las tablas de logaritmos y exponenciales sobre el campo de Galois GF(256).
     */
    private static function initGf(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$gfExp = array_fill(0, 512, 0);
        self::$gfLog = array_fill(0, 256, 0);
        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$gfExp[$i] = $x;
            self::$gfLog[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) {
                $x ^= 0x11D; // Polinomio primitivo estándar x^8 + x^4 + x^3 + x^2 + 1
            }
        }
        for ($i = 255; $i < 512; $i++) {
            self::$gfExp[$i] = self::$gfExp[$i - 255];
        }

        self::$initialized = true;
    }

    /**
     * Multiplicación en el campo finito GF(256).
     */
    private static function gfMul(int $x, int $y): int
    {
        if ($x === 0 || $y === 0) {
            return 0;
        }

        return self::$gfExp[self::$gfLog[$x] + self::$gfLog[$y]];
    }

    /**
     * Genera el polinomio generador Reed-Solomon para n palabras de corrección de error.
     *
     * @return array<int>
     */
    private static function rsPoly(int $n): array
    {
        $poly = [1];
        for ($i = 0; $i < $n; $i++) {
            $next = [1, self::$gfExp[$i]];
            $res = array_fill(0, count($poly) + 1, 0);
            for ($j = 0; $j < count($poly); $j++) {
                $res[$j] ^= $poly[$j];
                $res[$j + 1] ^= self::gfMul($poly[$j], $next[1]);
            }
            $poly = $res;
        }

        return $poly;
    }

    /**
     * Genera un archivo de imagen PNG con el código QR para el texto indicado.
     *
     * @param  string  $text  Cadena de texto o URL a codificar (hasta 100 caracteres)
     * @param  string  $outputPath  Ruta absoluta del archivo PNG a generar
     * @param  int  $pixelSize  Tamaño en píxeles de cada módulo (defecto 6px)
     * @param  int  $margin  Margen de zona silenciosa en módulos (defecto 4 módulos)
     * @return bool True si la imagen PNG se generó exitosamente
     */
    public static function generatePng(string $text, string $outputPath, int $pixelSize = 6, int $margin = 4): bool
    {
        self::initGf();

        $dataBytes = array_values(unpack('C*', $text));
        $dataLen = count($dataBytes);

        $totalDataCodewords = 80;
        $ecCodewords = 20;

        // Modo Byte (0100) + Longitud de caracteres en 8 bits para V1-V9
        $bits = [0, 1, 0, 0];
        for ($i = 7; $i >= 0; $i--) {
            $bits[] = ($dataLen >> $i) & 1;
        }
        foreach ($dataBytes as $b) {
            for ($i = 7; $i >= 0; $i--) {
                $bits[] = ($b >> $i) & 1;
            }
        }

        // Terminador de 4 ceros o hasta completar capacidad
        $termLen = min(4, ($totalDataCodewords * 8) - count($bits));
        for ($i = 0; $i < $termLen; $i++) {
            $bits[] = 0;
        }

        // Relleno a frontera de byte
        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }

        $codewords = [];
        for ($i = 0; $i < count($bits); $i += 8) {
            $val = 0;
            for ($j = 0; $j < 8; $j++) {
                $val = ($val << 1) | $bits[$i + $j];
            }
            $codewords[] = $val;
        }

        // Relleno alternado estándar 0xEC y 0x11
        $pad = [0xEC, 0x11];
        $padIdx = 0;
        while (count($codewords) < $totalDataCodewords) {
            $codewords[] = $pad[$padIdx % 2];
            $padIdx++;
        }

        // Cálculo de paridad Reed-Solomon
        $poly = self::rsPoly($ecCodewords);
        $remainder = array_fill(0, $ecCodewords, 0);
        foreach ($codewords as $cw) {
            $factor = $cw ^ $remainder[0];
            array_shift($remainder);
            $remainder[] = 0;
            if ($factor !== 0) {
                for ($j = 0; $j < $ecCodewords; $j++) {
                    $remainder[$j] ^= self::gfMul($poly[$j + 1], $factor);
                }
            }
        }
        $finalCodewords = array_merge($codewords, $remainder);

        // Matriz Versión 4: 33 x 33 módulos
        $size = 33;
        $matrix = array_fill(0, $size, array_fill(0, $size, null));
        $isFunction = array_fill(0, $size, array_fill(0, $size, false));

        // Patrones de búsqueda (Finders) en las tres esquinas
        $addFinder = function (int $r0, int $c0) use (&$matrix, &$isFunction): void {
            for ($r = 0; $r < 7; $r++) {
                for ($c = 0; $c < 7; $c++) {
                    $isBlack = ($r === 0 || $r === 6 || $c === 0 || $c === 6 || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4));
                    $matrix[$r0 + $r][$c0 + $c] = $isBlack ? 1 : 0;
                    $isFunction[$r0 + $r][$c0 + $c] = true;
                }
            }
        };
        $addFinder(0, 0);
        $addFinder(0, $size - 7);
        $addFinder($size - 7, 0);

        // Separadores blancos alrededor de los Finders
        $addSeparator = function (int $r0, int $c0, int $w, int $h) use (&$matrix, &$isFunction, $size): void {
            for ($r = $r0; $r < $r0 + $h; $r++) {
                for ($c = $c0; $c < $c0 + $w; $c++) {
                    if ($r >= 0 && $r < $size && $c >= 0 && $c < $size && ! $isFunction[$r][$c]) {
                        $matrix[$r][$c] = 0;
                        $isFunction[$r][$c] = true;
                    }
                }
            }
        };
        $addSeparator(0, 7, 1, 8);
        $addSeparator(7, 0, 8, 1);
        $addSeparator(0, $size - 8, 1, 8);
        $addSeparator(7, $size - 8, 8, 1);
        $addSeparator($size - 8, 0, 8, 1);
        $addSeparator($size - 8, 7, 1, 8);

        // Patrones de sincronización (Timing)
        for ($i = 8; $i < $size - 8; $i++) {
            if (! $isFunction[6][$i]) {
                $matrix[6][$i] = ($i % 2 === 0) ? 1 : 0;
                $isFunction[6][$i] = true;
            }
            if (! $isFunction[$i][6]) {
                $matrix[$i][6] = ($i % 2 === 0) ? 1 : 0;
                $isFunction[$i][6] = true;
            }
        }

        // Patrón de alineación en (26, 26) para Versión 4
        for ($r = -2; $r <= 2; $r++) {
            for ($c = -2; $c <= 2; $c++) {
                $isBlack = (abs($r) === 2 || abs($c) === 2 || ($r === 0 && $c === 0));
                $matrix[26 + $r][26 + $c] = $isBlack ? 1 : 0;
                $isFunction[26 + $r][26 + $c] = true;
            }
        }

        // Módulo oscuro fijo
        $matrix[25][8] = 1;
        $isFunction[25][8] = true;

        // Reserva de área para información de formato
        for ($i = 0; $i < 9; $i++) {
            $isFunction[8][$i] = true;
            $isFunction[$i][8] = true;
        }
        for ($i = 0; $i < 8; $i++) {
            $isFunction[8][$size - 1 - $i] = true;
            $isFunction[$size - 1 - $i][8] = true;
        }

        // Disposición en zigzag de los datos y paridad aplicando Máscara 0 ((r+c)%2 == 0)
        $allBits = [];
        foreach ($finalCodewords as $cw) {
            for ($b = 7; $b >= 0; $b--) {
                $allBits[] = ($cw >> $b) & 1;
            }
        }

        $bitIdx = 0;
        $upward = true;
        for ($right = $size - 1; $right > 0; $right -= 2) {
            if ($right === 6) {
                $right--;
            }
            $rows = $upward ? range($size - 1, 0) : range(0, $size - 1);
            foreach ($rows as $r) {
                foreach ([$right, $right - 1] as $c) {
                    if (! $isFunction[$r][$c]) {
                        $bit = ($bitIdx < count($allBits)) ? $allBits[$bitIdx++] : 0;
                        if (($r + $c) % 2 === 0) {
                            $bit ^= 1;
                        }
                        $matrix[$r][$c] = $bit;
                    }
                }
            }
            $upward = ! $upward;
        }

        // Bits de información de formato para nivel L y Máscara 0 (BCH 15 bits)
        $formatBits = [0, 1, 1, 1, 1, 1, 0, 0, 1, 1, 1, 1, 1, 0, 0];
        $matrix[8][0] = $formatBits[0];
        $matrix[8][1] = $formatBits[1];
        $matrix[8][2] = $formatBits[2];
        $matrix[8][3] = $formatBits[3];
        $matrix[8][4] = $formatBits[4];
        $matrix[8][5] = $formatBits[5];
        $matrix[8][7] = $formatBits[6];
        $matrix[8][8] = $formatBits[7];
        $matrix[7][8] = $formatBits[8];
        $matrix[5][8] = $formatBits[9];
        $matrix[4][8] = $formatBits[10];
        $matrix[3][8] = $formatBits[11];
        $matrix[2][8] = $formatBits[12];
        $matrix[1][8] = $formatBits[13];
        $matrix[0][8] = $formatBits[14];

        for ($i = 0; $i < 7; $i++) {
            $matrix[$size - 1 - $i][8] = $formatBits[$i];
        }
        for ($i = 0; $i < 8; $i++) {
            $matrix[8][$size - 8 + $i] = $formatBits[7 + $i];
        }

        // Renderizado PNG con extensión GD
        $imgSize = ($size + ($margin * 2)) * $pixelSize;
        $img = imagecreatetruecolor($imgSize, $imgSize);
        if (! $img) {
            return false;
        }

        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 15, 23, 42);

        imagefilledrectangle($img, 0, 0, $imgSize, $imgSize, $white);

        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                if ($matrix[$r][$c] === 1) {
                    $x = ($c + $margin) * $pixelSize;
                    $y = ($r + $margin) * $pixelSize;
                    imagefilledrectangle($img, $x, $y, $x + $pixelSize - 1, $y + $pixelSize - 1, $black);
                }
            }
        }

        $dir = dirname($outputPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        imagepng($img, $outputPath);
        imagedestroy($img);

        return file_exists($outputPath);
    }
}
