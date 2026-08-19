<?php

namespace App\Helpers;

use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Exception\Exception;

class HtmlToPhpWordHelper
{
    private static array $normalStyle = ['size' => 10, 'name' => 'Arial'];

    /**
     * Agrega contenido HTML a una sección de PhpWord, interpretando etiquetas básicas como <p>, <br>, <strong>, <em>, <ul>, <ol> y <li>.
     *
     * @param  Section  $section  - La sección de PhpWord a la que se agregará el contenido HTML.
     * @param  string  $html  - El contenido HTML que se desea agregar a la sección.
     * @param  array|null  $baseStyle  - Un arreglo opcional que define el estilo base para el texto agregado. Si no se proporciona, se utilizará un estilo predeterminado.
     *
     * @throws Exception - Si ocurre un error al agregar el contenido a la sección de PhpWord.
     * @throws \InvalidArgumentException - Si los parámetros proporcionados no son válidos o no cumplen con los requisitos esperados.
     * @throws \RuntimeException - Si ocurre un error en tiempo de ejecución al procesar el contenido HTML o al interactuar con la sección de PhpWord.
     * @throws \UnexpectedValueException - Si se encuentra un valor inesperado o no válido en el contenido HTML que se está procesando.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-06-10
     */
    public static function addHtmlToSection(Section $section, string $html, ?array $baseStyle = null): void
    {
        $html = self::cleanHtml($html);

        if (empty(trim($html))) {
            return;
        }

        $style = $baseStyle ?? self::$normalStyle;

        $blocks = self::splitIntoBlocks($html);

        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') {
                continue;
            }

            if (preg_match('/^<p[\s>]/i', $block)) {
                $inner = preg_replace('/^<p[^>]*>/i', '', $block);
                $inner = preg_replace('/<\/p>$/i', '', $inner);
                $textRun = new TextRun($style);
                self::parseInlineToTextRun($textRun, $inner, $style);
                $section->addText($textRun);
            } elseif (preg_match('/^<(ul|ol)[\s>]/i', $block)) {
                $items = preg_split('/<li[^>]*>/i', $block);
                array_shift($items);
                foreach ($items as $item) {
                    $item = preg_replace('/<\/li>.*$/i', '', $item);
                    $item = trim($item);
                    if ($item !== '') {
                        $textRun = new TextRun($style);
                        self::parseInlineToTextRun($textRun, '• '.$item, $style);
                        $section->addText($textRun);
                    }
                }
            } elseif (preg_match('/^<br\s*\/?>/i', $block)) {
                $section->addTextBreak();
            } else {
                $textRun = new TextRun($style);
                self::parseInlineToTextRun($textRun, $block, $style);
                $section->addText($textRun);
            }
        }
    }

    /**
     * Agrega contenido HTML a un TextRun de PhpWord, interpretando etiquetas básicas como <strong>, <em> y <br>.
     *
     * @param  TextRun  $textRun  - El TextRun de PhpWord al que se agregará el contenido HTML.
     * @param  string  $html  - El contenido HTML que se desea agregar al TextRun.
     * @param  array  $baseStyle  - Un arreglo que define el estilo base para el texto agregado.
     *
     * @throws Exception - Si ocurre un error al agregar el contenido al TextRun de PhpWord.
     * @throws \InvalidArgumentException - Si los parámetros proporcionados no son válidos o no cumplen con los requisitos esperados.
     * @throws \RuntimeException - Si ocurre un error en tiempo de ejecución al procesar el contenido HTML o al interactuar con el TextRun de PhpWord.
     * @throws \UnexpectedValueException - Si se encuentra un valor inesperado o no válido en el contenido HTML que se está procesando.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-06-10
     */
    public static function addToTextRun(TextRun $textRun, string $html, array $baseStyle = []): void
    {
        $html = self::cleanHtml($html);

        if (empty(trim($html))) {
            return;
        }

        $style = array_merge(self::$normalStyle, $baseStyle);
        $blocks = self::splitIntoBlocks($html);

        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') {
                continue;
            }

            if (preg_match('/^<p[\s>]/i', $block)) {
                $inner = preg_replace('/^<p[^>]*>/i', '', $block);
                $inner = preg_replace('/<\/p>$/i', '', $inner);
                self::parseInlineToTextRun($textRun, $inner, $style);
                $textRun->addTextBreak();
            } elseif (preg_match('/^<br\s*\/?>/i', $block)) {
                $textRun->addTextBreak();
            } else {
                self::parseInlineToTextRun($textRun, $block, $style);
            }
        }
    }

    /**
     * Parses inline HTML content and adds it to a TextRun, interpreting <strong>, <em>, and <br> tags.
     *
     * @param  TextRun  $textRun  - The TextRun to which the parsed content will be added.
     * @param  string  $html  - The inline HTML content to parse and add to the TextRun.
     * @param  array  $baseStyle  - An array defining the base style for the text being added.
     *
     * @throws Exception - If an error occurs while adding content to the TextRun.
     * @throws \InvalidArgumentException - If the provided parameters are invalid or do not meet the expected requirements.
     * @throws \RuntimeException - If a runtime error occurs while processing the HTML content or interacting with the TextRun.
     * @throws \UnexpectedValueException - If an unexpected or invalid value is encountered in the HTML content being processed.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-06-10
     */
    private static function parseInlineToTextRun(TextRun $textRun, string $html, array $baseStyle): void
    {
        $html = trim($html);
        if ($html === '') {
            return;
        }

        $pattern = '/<(strong|b)[^>]*>(.*?)<\/\1>|<(em|i)[^>]*>(.*?)<\/\3>|<br\s*\/?>|([^<]+)/is';
        preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if (isset($match[2]) && $match[2] !== '') {
                $innerStyle = array_merge($baseStyle, ['bold' => true]);
                $textRun->addText(self::stripAllTags($match[2]), $innerStyle);
            } elseif (isset($match[4]) && $match[4] !== '') {
                $innerStyle = array_merge($baseStyle, ['italic' => true]);
                $textRun->addText(self::stripAllTags($match[4]), $innerStyle);
            } elseif (preg_match('/^<br/i', $match[0])) {
                $textRun->addTextBreak();
            } elseif (isset($match[5]) && $match[5] !== '') {
                $textRun->addText($match[5], $baseStyle);
            }
        }
    }

    /**
     * Splits the HTML content into blocks based on specific tags.
     *
     * @param  string  $html  - The HTML content to split.
     * @return array - An array of HTML blocks.
     *
     * @throws \InvalidArgumentException - If the provided HTML content is not a valid string.
     * @throws \RuntimeException - If an error occurs while processing the HTML content.
     * @throws \UnexpectedValueException - If an unexpected or invalid value is encountered in the HTML content being processed.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-06-10
     */
    private static function splitIntoBlocks(string $html): array
    {
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);

        $blocks = preg_split('/(<(?:p|ul|ol|li|div|blockquote|table|tr|td|th|h[1-6])[^>]*>.*?<\/(?:p|ul|ol|li|div|blockquote|table|tr|td|th|h[1-6])>)/is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        $result = [];
        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block !== '') {
                $result[] = $block;
            }
        }

        return $result;
    }

    /**
     * Cleans the HTML content by removing unwanted tags and decoding entities.
     *
     * @param  string  $html  - The HTML content to clean.
     * @return string - The cleaned HTML content.
     *
     * @throws \InvalidArgumentException - If the provided HTML content is not a valid string.
     * @throws \RuntimeException - If an error occurs while processing the HTML content.
     * @throws \UnexpectedValueException - If an unexpected or invalid value is encountered in the HTML content being processed.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-06-10
     */
    private static function cleanHtml(string $html): string
    {
        $html = strip_tags($html, '<p><br><strong><b><em><i><ul><ol><li>');
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $html = preg_replace('/\s+/', ' ', $html);
        $html = trim($html);

        return $html;
    }

    private static function stripAllTags(string $text): string
    {
        return strip_tags($text);
    }
}
