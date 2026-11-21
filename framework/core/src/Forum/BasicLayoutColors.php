<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum;

/** @internal */
final class BasicLayoutColors
{
    private const MIN_TEXT_CONTRAST = 4.5;

    /** @return array{primaryColor: string, buttonTextColor: string, lightLinkColor: string, darkLinkColor: string} */
    public static function forPrimaryColor(string $primary, string $darkBackground): array
    {
        $primary = ltrim(trim($primary), '#');
        $primaryLuminance = self::hexLuminance($primary);
        $primaryColor = $primaryLuminance === null ? '#000' : '#'.$primary;
        $primaryLuminance ??= 0.0;
        $darkLuminance = self::darkBackgroundLuminance($darkBackground);

        return [
            'primaryColor' => $primaryColor,
            'buttonTextColor' => self::contrast($primaryLuminance, 1.0) >= self::contrast($primaryLuminance, 0.0) ? '#fff' : '#000',
            'lightLinkColor' => self::contrast($primaryLuminance, 1.0) >= self::MIN_TEXT_CONTRAST ? $primaryColor : 'var(--text-color)',
            'darkLinkColor' => $darkLuminance !== null && self::contrast($primaryLuminance, $darkLuminance) >= self::MIN_TEXT_CONTRAST
                ? $primaryColor
                : 'var(--text-color)',
        ];
    }

    private static function hexLuminance(string $hex): ?float
    {
        if (! in_array(strlen($hex), [3, 6], true) || ! ctype_xdigit($hex)) {
            return null;
        }

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return self::luminance(hexdec(substr($hex, 0, 2)) / 255, hexdec(substr($hex, 2, 2)) / 255, hexdec(substr($hex, 4, 2)) / 255);
    }

    private static function darkBackgroundLuminance(string $background): ?float
    {
        // The generated dark background is HSL, with a hex fallback.
        if (! preg_match('/^hsl\((\d+),(\d+)%,(\d+)%\)$/', $background, $parts)) {
            return self::hexLuminance(ltrim($background, '#'));
        }

        $hue = (int) $parts[1];
        $lightness = (int) $parts[3] / 100;
        $amplitude = (int) $parts[2] / 100 * min($lightness, 1 - $lightness);

        // Convert HSL to sRGB using the CSS Color channel formula.
        $channel = static function (int $offset) use ($hue, $lightness, $amplitude): float {
            $position = fmod($offset + $hue / 30, 12);

            return $lightness - $amplitude * max(-1, min($position - 3, 9 - $position, 1));
        };

        return self::luminance($channel(0), $channel(8), $channel(4));
    }

    private static function luminance(float $red, float $green, float $blue): float
    {
        $linear = static fn (float $channel): float => $channel <= 0.04045
            ? $channel / 12.92
            : (($channel + 0.055) / 1.055) ** 2.4;

        return 0.2126 * $linear($red) + 0.7152 * $linear($green) + 0.0722 * $linear($blue);
    }

    private static function contrast(float $first, float $second): float
    {
        return (max($first, $second) + 0.05) / (min($first, $second) + 0.05);
    }
}
