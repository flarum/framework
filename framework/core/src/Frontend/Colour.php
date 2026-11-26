<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend;

/**
 * The colour maths behind the CSS Flarum derives from a forum's colours.
 *
 * @internal
 */
final class Colour
{
    /**
     * The red, green and blue channels from 0 to 1 of a 3- or 6-digit hex
     * colour, with or without the hash, or null when it is not one.
     *
     * @return array{float, float, float}|null
     */
    public static function fromHex(string $hex): ?array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return null;
        }

        return [
            hexdec(substr($hex, 0, 2)) / 255,
            hexdec(substr($hex, 2, 2)) / 255,
            hexdec(substr($hex, 4, 2)) / 255,
        ];
    }

    /**
     * The hue in degrees and the saturation and lightness from 0 to 1 of the
     * channels from fromHex().
     *
     * @param array{float, float, float} $channels
     * @return array{float, float, float}
     */
    public static function hsl(array $channels): array
    {
        [$red, $green, $blue] = $channels;

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

    /** @return array{float, float, float} */
    public static function hslRgb(float $hue, float $saturation, float $lightness): array
    {
        $amplitude = $saturation * min($lightness, 1 - $lightness);

        // Convert HSL to sRGB using the CSS Color channel formula.
        $channel = static function (int $offset) use ($hue, $lightness, $amplitude): float {
            $position = fmod($offset + $hue / 30, 12);

            return $lightness - $amplitude * max(-1, min($position - 3, 9 - $position, 1));
        };

        return [$channel(0), $channel(8), $channel(4)];
    }

    public static function rgbLuminance(float $red, float $green, float $blue): float
    {
        $linear = static fn (float $channel): float => $channel <= 0.04045
            ? $channel / 12.92
            : (($channel + 0.055) / 1.055) ** 2.4;

        return 0.2126 * $linear($red) + 0.7152 * $linear($green) + 0.0722 * $linear($blue);
    }

    public static function contrast(float $first, float $second): float
    {
        return (max($first, $second) + 0.05) / (min($first, $second) + 0.05);
    }
}
