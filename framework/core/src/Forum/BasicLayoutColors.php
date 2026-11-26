<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum;

use Flarum\Frontend\Colour;
use Flarum\Frontend\DarkBodyBackground;
use LogicException;

/** @internal */
final class BasicLayoutColors
{
    private const MIN_TEXT_CONTRAST = 4.5;

    /** @return array{primaryColor: string, buttonTextColor: string, lightLinkColor: string, darkLinkColor: string} */
    public static function forPrimaryColor(string $primary, string $secondary, string $darkBackground, bool $highContrast): array
    {
        $primary = ltrim(trim($primary), '#');
        $primaryChannels = Colour::fromHex($primary);
        $primaryColor = $primaryChannels === null ? '#000' : '#'.$primary;
        $primaryLuminance = $primaryChannels === null ? 0.0 : Colour::rgbLuminance(...$primaryChannels);
        // Links are measured against the brighter of the declared and rounded
        // backgrounds. Buttons compare against the declared colour, as the
        // forum's LESS does; it is black in high-contrast themes.
        $darkLuminance = DarkBodyBackground::luminance($secondary);
        $buttonLuminance = $highContrast ? 0.0 : DarkBodyBackground::declaredLuminance($secondary);

        return [
            'primaryColor' => $primaryColor,
            // The forum's primary buttons choose between white and --text-on-light.
            'buttonTextColor' => Colour::contrast($primaryLuminance, 1.0) >= Colour::contrast($primaryLuminance, $buttonLuminance)
                ? '#fff'
                : ($highContrast ? '#000' : $darkBackground),
            'lightLinkColor' => Colour::contrast($primaryLuminance, 1.0) >= self::MIN_TEXT_CONTRAST
                ? $primaryColor
                : 'var(--text-color)',
            'darkLinkColor' => $primaryChannels === null
                ? 'var(--text-color)'
                : (Colour::contrast($primaryLuminance, $darkLuminance) >= self::MIN_TEXT_CONTRAST
                    ? $primaryColor
                    : self::lightenToContrast($primaryChannels, $darkLuminance)),
        ];
    }

    /**
     * The primary colour lightened, keeping its hue and saturation, until it
     * reaches the minimum contrast against the dark background, so dark mode
     * keeps the brand colour. It steps by one point of HSL lightness, as
     * Less's lighten() would.
     *
     * @param array{float, float, float} $channels
     */
    private static function lightenToContrast(array $channels, float $backgroundLuminance): string
    {
        [$hue, $saturation, $lightness] = Colour::hsl($channels);

        for ($step = 1; $step <= 100; $step++) {
            $rgb = Colour::hslRgb($hue, $saturation, min(1.0, $lightness + $step / 100));
            // The browser receives hex, so measure the same rounded channels we emit.
            $bytes = array_map(static fn (float $channel): int => (int) round($channel * 255), $rgb);
            [$red, $green, $blue] = $bytes;

            if (Colour::contrast(Colour::rgbLuminance($red / 255, $green / 255, $blue / 255), $backgroundLuminance) >= self::MIN_TEXT_CONTRAST) {
                return sprintf('#%02x%02x%02x', ...$bytes);
            }
        }

        // White clears the minimum contrast against this background, so the
        // loop always returns on or before the last step.
        throw new LogicException('The lightened primary colour never reached the minimum contrast against the dark background.');
    }
}
