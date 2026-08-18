<?php

namespace App\Helpers;

use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\TextRun;

class HtmlToPhpWordHelper
{
    private static array $normalStyle = ['size' => 10, 'name' => 'Arial'];

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
