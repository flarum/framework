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

    // The dark body background is hsl(secondary-hue, min(20%, secondary-sat), 10%),
    // as FrontendServiceProvider::computeDarkBodyBg() builds it.
    private const DARK_BACKGROUND_LIGHTNESS = 0.10;
    private const DARK_BACKGROUND_MAX_SATURATION = 0.20;
    private const FALLBACK_DARK_BACKGROUND = '1a2333';

    /** @return array{primaryColor: string, buttonTextColor: string, lightLinkColor: string, darkLinkColor: string} */
    public static function forPrimaryColor(string $primary, string $secondary, string $darkBackground): array
    {
        $primary = ltrim(trim($primary), '#');
        $primaryHex = self::expandHex($primary);
        $primaryColor = $primaryHex === null ? '#000' : '#'.$primary;
        $primaryLuminance = $primaryHex === null ? 0.0 : self::hexLuminance($primaryHex);
        $darkLuminance = self::darkBackgroundLuminance($secondary);

        return [
            'primaryColor' => $primaryColor,
            // The forum's primary buttons choose between white and --text-on-light,
            // the dark body background (see the JS isDark utility).
            'buttonTextColor' => self::contrast($primaryLuminance, 1.0) >= self::contrast($primaryLuminance, $darkLuminance)
                ? '#fff'
                : $darkBackground,
            'lightLinkColor' => self::contrast($primaryLuminance, 1.0) >= self::MIN_TEXT_CONTRAST
                ? $primaryColor
                : 'var(--text-color)',
            'darkLinkColor' => $primaryHex === null
                ? 'var(--text-color)'
                : (self::contrast($primaryLuminance, $darkLuminance) >= self::MIN_TEXT_CONTRAST
                    ? $primaryColor
                    : self::lightenToContrast($primaryHex, $darkLuminance)),
        ];
    }

    /**
     * The primary colour lightened, keeping its hue and saturation, until it
     * reaches the minimum contrast against the dark background, so dark mode
     * keeps the brand colour. It steps by one point of HSL lightness, as
     * Less's lighten() would.
     */
    private static function lightenToContrast(string $hex, float $backgroundLuminance): string
    {
        [$hue, $saturation, $lightness] = self::hexToHsl($hex);

        for ($step = 1; $step <= 100; $step++) {
            $rgb = self::hslRgb($hue, $saturation, min(1.0, $lightness + $step / 100));

            if (self::contrast(self::rgbLuminance($rgb), $backgroundLuminance) >= self::MIN_TEXT_CONTRAST) {
                return sprintf('#%02x%02x%02x', ...$rgb);
            }
        }

        return '#fff';
    }

    private static function expandHex(string $hex): ?string
    {
        if (! in_array(strlen($hex), [3, 6], true) || ! ctype_xdigit($hex)) {
            return null;
        }

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return $hex;
    }

    private static function hexLuminance(string $hex): float
    {
        return self::luminance(hexdec(substr($hex, 0, 2)) / 255, hexdec(substr($hex, 2, 2)) / 255, hexdec(substr($hex, 4, 2)) / 255);
    }

    /**
     * The luminance of the dark body background, computed from the secondary
     * colour itself so it does not depend on the string format that
     * FrontendServiceProvider::computeDarkBodyBg() returns.
     */
    private static function darkBackgroundLuminance(string $secondary): float
    {
        $hex = self::expandHex(ltrim(trim($secondary), '#'));

        if ($hex === null) {
            // computeDarkBodyBg() falls back to this colour too.
            return self::hexLuminance(self::FALLBACK_DARK_BACKGROUND);
        }

        [$hue, $saturation] = self::hexToHsl($hex);

        // computeDarkBodyBg() rounds the hue and saturation to whole numbers.
        return self::rgbLuminance(self::hslRgb(
            round($hue),
            round(min(self::DARK_BACKGROUND_MAX_SATURATION, $saturation) * 100) / 100,
            self::DARK_BACKGROUND_LIGHTNESS
        ));
    }

    /** @return array{float, float, float} Hue in degrees, saturation and lightness from 0 to 1. */
    private static function hexToHsl(string $hex): array
    {
        $red = hexdec(substr($hex, 0, 2)) / 255;
        $green = hexdec(substr($hex, 2, 2)) / 255;
        $blue = hexdec(substr($hex, 4, 2)) / 255;

        $max = max($red, $green, $blue);
        $min = min($red, $green, $blue);
        $delta = $max - $min;
        $lightness = ($max + $min) / 2;
        $saturation = $delta == 0 ? 0.0 : $delta / (1 - abs(2 * $lightness - 1));

        if ($delta == 0) {
            $hue = 0.0;
        } elseif ($max === $red) {
            $hue = 60 * fmod(($green - $blue) / $delta, 6);
        } elseif ($max === $green) {
            $hue = 60 * (($blue - $red) / $delta + 2);
        } else {
            $hue = 60 * (($red - $green) / $delta + 4);
        }

        return [$hue < 0 ? $hue + 360 : $hue, $saturation, $lightness];
    }

    /** @return array{int, int, int} */
    private static function hslRgb(float $hue, float $saturation, float $lightness): array
    {
        $amplitude = $saturation * min($lightness, 1 - $lightness);

        // Convert HSL to sRGB using the CSS Color channel formula.
        $channel = static function (int $offset) use ($hue, $lightness, $amplitude): int {
            $position = fmod($offset + $hue / 30, 12);

            return (int) round(255 * ($lightness - $amplitude * max(-1, min($position - 3, 9 - $position, 1))));
        };

        return [$channel(0), $channel(8), $channel(4)];
    }

    /** @param array{int, int, int} $rgb */
    private static function rgbLuminance(array $rgb): float
    {
        return self::luminance($rgb[0] / 255, $rgb[1] / 255, $rgb[2] / 255);
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
