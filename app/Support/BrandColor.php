<?php

namespace App\Support;

/**
 * A workspace's brand colour and the palette derived from it. The chosen
 * colour becomes brand-600 (buttons, links); lighter and darker shades are
 * mixed from it in the browser, so one colour re-themes the whole site.
 */
class BrandColor
{
    /** Presets that all read well with white text. */
    public const PRESETS = [
        '#7c3aed' => 'Violet (default)',
        '#4f46e5' => 'Indigo',
        '#2563eb' => 'Blue',
        '#0e7490' => 'Teal',
        '#047857' => 'Green',
        '#b45309' => 'Amber',
        '#be123c' => 'Rose',
        '#334155' => 'Slate',
    ];

    public const DEFAULT = '#7c3aed';

    /** Minimum contrast with white text (WCAG AA for normal text). */
    public const MIN_CONTRAST = 4.5;

    /** Mix of the brand colour with white (lighter shades) or black (darker ones). */
    private const SHADES = [
        50 => ['white', 8], 100 => ['white', 15], 200 => ['white', 30], 300 => ['white', 50],
        400 => ['white', 72], 500 => ['white', 88], 700 => ['black', 85], 800 => ['black', 70],
        900 => ['black', 55], 950 => ['black', 35],
    ];

    public static function isValidHex(?string $hex): bool
    {
        return is_string($hex) && preg_match('/^#[0-9a-f]{6}$/i', $hex) === 1;
    }

    /** WCAG contrast ratio between the colour and white. */
    public static function contrastWithWhite(string $hex): float
    {
        return 1.05 / (self::luminance($hex) + 0.05);
    }

    /**
     * CSS custom properties overriding Tailwind's brand palette. Values are
     * only ever built from a validated #rrggbb, so they're safe to inline.
     */
    public static function cssVariables(string $hex): string
    {
        if (! self::isValidHex($hex)) {
            return '';
        }

        $hex = strtolower($hex);
        $vars = ["--color-brand-600: {$hex};"];
        foreach (self::SHADES as $shade => [$with, $percent]) {
            $vars[] = "--color-brand-{$shade}: color-mix(in oklab, {$hex} {$percent}%, {$with});";
        }

        return implode(' ', $vars);
    }

    private static function luminance(string $hex): float
    {
        $channels = array_map(function (string $pair) {
            $value = hexdec($pair) / 255;

            return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, str_split(ltrim($hex, '#'), 2));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }
}
