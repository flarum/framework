<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use LogicException;

/**
 * The dark-mode body background, hsl(secondary-hue, min(20%, secondary-sat), 10%),
 * for the critical CSS and the standalone pages, which have no compiled
 * stylesheet, together with the colour maths needed to keep their text readable
 * against it.
 *
 * less/common/variables.less declares the same formula for the forum's own CSS.
 * DarkBodyBackgroundTest compiles @body-bg-dark and compares it, so the two
 * cannot drift apart.
 *
 * @internal
 */
final class DarkBodyBackground
{
    /** The formula's percentages, as the LESS writes them. */
    private const MAX_SATURATION_PERCENT = 20;
    private const LIGHTNESS_PERCENT = 10;

    /** The safe fallback for a secondary colour that is not a hex. */
    private const FALLBACK = '#1a2333';

    /**
     * The colour the standalone pages paint, or the fallback for a secondary
     * colour that is not a hex.
     */
    public static function cssColor(string $hex): string
    {
        $hueSaturation = self::declaredHueSaturation($hex);

        if ($hueSaturation === null) {
            return self::FALLBACK;
        }

        return 'hsl('.(int) $hueSaturation[0].','.(int) $hueSaturation[1].'%,'.self::LIGHTNESS_PERCENT.'%)';
    }

    /**
     * The red, green and blue channels of the colour cssColor() declares, from
     * 0 to 1, before they are rounded to the bytes the browser paints.
     *
     * @return array{float, float, float}
     */
    public static function channels(string $hex): array
    {
        $hueSaturation = self::declaredHueSaturation($hex);

        if ($hueSaturation === null) {
            return Colour::fromHex(self::FALLBACK)
                ?? throw new LogicException('The fallback dark body background is not a hex colour.');
        }

        return Colour::hslRgb($hueSaturation[0], $hueSaturation[1] / 100, self::LIGHTNESS_PERCENT / 100);
    }

    /**
     * The luminance of the colour cssColor() declares: the whole-number hsl()
     * the standalone pages paint, with the fallback for a value that is not a
     * hex.
     *
     * This is the model the forum's LESS compares its primary buttons against,
     * so button labels are chosen from it.
     */
    public static function declaredLuminance(string $hex): float
    {
        return Colour::rgbLuminance(...self::channels($hex));
    }

    /**
     * The background luminance to measure light text against: the brighter of
     * the declared colour and the bytes it rounds to.
     *
     * The declared hsl() resolves to channels that are not whole bytes, so a
     * colour can reach the minimum contrast against one model and miss it
     * against the other. The brighter of the two is the worst case for light
     * text on the dark background.
     */
    public static function luminance(string $hex): float
    {
        $channels = self::channels($hex);

        return max(
            Colour::rgbLuminance(...$channels),
            Colour::rgbLuminance(...array_map(
                static fn (float $channel): float => round($channel * 255) / 255,
                $channels
            )),
        );
    }

    /**
     * The hue in whole degrees and the capped saturation in whole percent that
     * cssColor() writes, or null when the colour is not a hex. Both the CSS text
     * and the channels are derived from these values, so they cannot drift apart.
     *
     * @return array{float, float}|null
     */
    private static function declaredHueSaturation(string $hex): ?array
    {
        $channels = Colour::fromHex($hex);

        if ($channels === null) {
            return null;
        }

        [$hue, $saturation] = Colour::hsl($channels);

        return [round($hue), round(min(self::MAX_SATURATION_PERCENT, $saturation * 100))];
    }
}
