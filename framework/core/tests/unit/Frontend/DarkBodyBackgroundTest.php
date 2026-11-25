<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Frontend;

use Flarum\Frontend\Colour;
use Flarum\Frontend\DarkBodyBackground;
use Flarum\Testing\CompiledColors;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * The dark body background is hsl(secondary-hue, min(20%, secondary-sat), 10%).
 * The standalone pages and the critical CSS paint it from here, the forum's own
 * CSS from @body-bg-dark in less/common/variables.less, and dark-mode links on
 * it are checked against the declared colour or the bytes it rounds to,
 * whichever is brighter.
 */
class DarkBodyBackgroundTest extends TestCase
{
    #[Test]
    #[DataProvider('secondaryColors')]
    public function css_colour_is_the_formula_applied_to_the_secondary_colour(string $secondary, string $expected): void
    {
        $this->assertSame($expected, DarkBodyBackground::cssColor($secondary));
    }

    /**
     * Saturation is capped at 20% and lightness is always 10%, with the hue
     * and the capped saturation rounded to whole numbers: 13.706% is written
     * as 14%, and a hue of 359.764 degrees wraps to 360 rather than 0. A
     * secondary colour that is not a 3- or 6-digit hex falls back to #1a2333.
     *
     * @return array<string, array{string, string}>
     */
    public static function secondaryColors(): array
    {
        return [
            'the LESS default' => ['#536F90', 'hsl(212,20%,10%)'],
            'the install default' => ['#4D698E', 'hsl(214,20%,10%)'],
            'lowercase' => ['#4d698e', 'hsl(214,20%,10%)'],
            'without the hash' => ['4d698e', 'hsl(214,20%,10%)'],
            'short hex with the hash' => ['#00f', 'hsl(240,20%,10%)'],
            'short hex without the hash' => ['00f', 'hsl(240,20%,10%)'],
            'green' => ['#0f0', 'hsl(120,20%,10%)'],
            'red' => ['#f00', 'hsl(0,20%,10%)'],
            'grey has no hue or saturation' => ['#777', 'hsl(0,0%,10%)'],
            'white' => ['#fff', 'hsl(0,0%,10%)'],
            'black' => ['#000', 'hsl(0,0%,10%)'],
            'yellow caps the saturation' => ['#ff0', 'hsl(60,20%,10%)'],
            'magenta' => ['#f0f', 'hsl(300,20%,10%)'],
            'cyan' => ['#0ff', 'hsl(180,20%,10%)'],
            'a saturation that rounds up' => ['#556070', 'hsl(216,14%,10%)'],
            'a hue that rounds up to the wrap' => ['#ff0001', 'hsl(360,20%,10%)'],
            'a red hue just below the wrap' => ['#ed1c24', 'hsl(358,20%,10%)'],
            'a mostly saturated dark colour' => ['#012345', 'hsl(210,20%,10%)'],
            'a named colour falls back' => ['red', '#1a2333'],
            'an empty value falls back' => ['', '#1a2333'],
            '4-digit hex falls back' => ['#abcd', '#1a2333'],
            '8-digit hex falls back' => ['#aabbccdd', '#1a2333'],
            'a value that is not hex falls back' => ['#12345g', '#1a2333'],
        ];
    }

    #[Test]
    public function channels_are_the_declared_colour(): void
    {
        // hsl(212,20%,10%), unrounded.
        $this->assertEqualsWithDelta(
            [20.4 / 255, 25.16 / 255, 30.6 / 255],
            DarkBodyBackground::channels('#536F90'),
            1e-9,
        );

        // The fallback colour, #1a2333.
        $this->assertEqualsWithDelta(
            [0x1A / 255, 0x23 / 255, 0x33 / 255],
            DarkBodyBackground::channels('red'),
            1e-9,
        );
    }

    #[Test]
    public function luminance_uses_the_declared_colour_when_it_is_the_brighter_one(): void
    {
        // hsl(212,20%,10%) declares rgb(20.4, 25.16, 30.6), brighter than the
        // rgb(20, 25, 31) it rounds to, so a link has to clear 4.5:1 against it.
        $declared = Colour::rgbLuminance(20.4 / 255, 25.16 / 255, 30.6 / 255);
        $rounded = Colour::rgbLuminance(20 / 255, 25 / 255, 31 / 255);

        $this->assertGreaterThan($rounded, $declared);
        $this->assertEqualsWithDelta($declared, DarkBodyBackground::luminance('#536F90'), 1e-12);
    }

    #[Test]
    public function luminance_uses_the_rounded_bytes_when_they_are_the_brighter_ones(): void
    {
        // hsl(120,20%,10%) declares rgb(20.4, 30.6, 20.4), darker than the
        // rgb(20, 31, 20) it rounds to, so a link has to clear 4.5:1 against
        // the rounded bytes. Buttons keep the declared model, as the forum's
        // LESS compares against the colour it declares.
        $declared = Colour::rgbLuminance(20.4 / 255, 30.6 / 255, 20.4 / 255);
        $rounded = Colour::rgbLuminance(20 / 255, 31 / 255, 20 / 255);

        $this->assertGreaterThan($declared, $rounded);
        $this->assertEqualsWithDelta($rounded, DarkBodyBackground::luminance('#0f0'), 1e-12);
        $this->assertEqualsWithDelta($declared, DarkBodyBackground::declaredLuminance('#0f0'), 1e-12);
    }

    #[Test]
    public function the_less_body_background_rounds_to_the_declared_channels(): void
    {
        // variables.less declares @body-bg-dark with the same formula. LESS
        // keeps the hue fractional (hsl(212.45901639,20%,10%) for the default
        // colour), so compare the bytes both models paint rather than the
        // channels themselves.
        foreach (['#536F90', '#4D698E', '#0f0', '#ff0', '#00f', '#f00'] as $secondary) {
            $compiled = (new CompiledColors('#000', $secondary, []))->colour('dark', 'var(--body-bg)');

            $this->assertSame(
                array_map(static fn (float $channel): int => (int) round($channel * 255), DarkBodyBackground::channels($secondary)),
                array_map(static fn (float $channel): int => (int) round($channel), array_slice($compiled, 0, 3)),
                $secondary,
            );
        }
    }
}
