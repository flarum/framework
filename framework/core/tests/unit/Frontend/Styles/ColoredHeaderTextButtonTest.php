<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Frontend\Styles;

use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

// The monorepo's test autoloader doesn't map Flarum\Tests to core's tests.
require_once __DIR__.'/CompiledColors.php';

/**
 * Text buttons (Sign Up, Log In, the language selector) have no background to
 * change, so in a coloured header they show hover, focus and an open dropdown
 * by their colour; in the high-contrast schemes, where that colour barely
 * differs, by an underline as well. Inside the header's dropdown panels, which
 * have the page background, they take the link colour as they do elsewhere on
 * the page.
 */
class ColoredHeaderTextButtonTest extends TestCase
{
    protected const PRIMARIES = [
        'default' => '#536F90',
        'Flarum orange' => '#E7672E',
        'yellow' => '#F1C40F',
        'pale pink' => '#F8D7DA',
        'black' => '#000000',
    ];

    /** The coloured header's variables, set on top of each scheme's. */
    protected const COLORED = [
        'light' => ['[data-colored-header=true][data-theme^=light]'],
        'light-hc' => ['[data-colored-header=true][data-theme^=light]', '[data-colored-header=true][data-theme=light-hc]'],
        'dark' => ['[data-colored-header=true][data-theme^=dark]'],
        'dark-hc' => ['[data-colored-header=true][data-theme^=dark]', '[data-colored-header=true][data-theme=dark-hc]'],
    ];

    protected const CONTAINERS = ['.App-header', '.App-drawer'];

    protected const STATES = [':hover', ':active', '.active', ':focus-visible', '.focus'];

    /** @var array<string, CompiledColors> */
    protected static array $compiled = [];

    /**
     * The high-contrast schemes are left out: there the text and control
     * colours are nearly the same, so the underline does the work instead.
     */
    public static function coloredSchemes(): iterable
    {
        foreach (array_keys(self::PRIMARIES) as $primary) {
            foreach (['light', 'dark'] as $scheme) {
                foreach (self::CONTAINERS as $container) {
                    yield "$primary, $scheme, $container" => [$primary, $scheme, $container];
                }
            }
        }
    }

    #[Test]
    #[DataProvider('coloredSchemes')]
    public function interaction_changes_the_colour_without_losing_contrast(string $primary, string $scheme, string $container): void
    {
        $colours = $this->compiled($primary);
        $local = $this->coloredHeader($colours, $scheme);
        $prefix = "[data-colored-header=true] $container";

        $background = $colours->colour($scheme, 'var(--header-bg)', $local);
        $rest = $colours->colour($scheme, $this->colour($colours, "$prefix .Button--link"), $local);

        $selectors = array_map(fn (string $state) => "$prefix .Button--link$state", self::STATES);
        $selectors[] = "$prefix .open > .Dropdown-toggle.Button--link";

        foreach ($selectors as $selector) {
            $state = $colours->colour($scheme, $this->colour($colours, $selector), $local);

            $this->assertNotEquals($rest, $state, $selector);
            $this->assertGreaterThanOrEqual(
                CompiledColors::contrast($rest, $background),
                CompiledColors::contrast($state, $background) + 0.001,
                $selector
            );
        }
    }

    public static function containers(): iterable
    {
        foreach (self::CONTAINERS as $container) {
            yield $container => [$container];
        }
    }

    #[Test]
    #[DataProvider('containers')]
    public function text_buttons_in_dropdown_panels_use_the_link_colour(string $container): void
    {
        $colours = $this->compiled('default');
        $prefix = "[data-colored-header=true] $container .Dropdown-menu";

        $selectors = array_map(fn (string $state) => "$prefix .Button--link$state", self::STATES);
        $selectors[] = "$prefix .open > .Dropdown-toggle.Button--link";

        foreach ($selectors as $selector) {
            $this->assertSame('var(--link-color)', $this->colour($colours, $selector), $selector);
        }
    }

    #[Test]
    public function text_buttons_in_dropdown_panels_stay_visible_on_the_panel(): void
    {
        // The header's text colour is chosen for the coloured background, and
        // in the light and dark schemes it is the page background itself.
        $colours = $this->compiled('default');

        foreach (array_keys(CompiledColors::SCHEMES) as $scheme) {
            $local = $this->coloredHeader($colours, $scheme);
            $panel = $colours->colour($scheme, 'var(--body-bg)', $local);
            $hover = $colours->colour($scheme, $this->colour($colours, '[data-colored-header=true] .App-header .Dropdown-menu .Button--link:hover'), $local);

            $this->assertGreaterThanOrEqual(3, CompiledColors::contrast($hover, $panel), $scheme);
        }
    }

    #[Test]
    public function high_contrast_schemes_underline_the_label(): void
    {
        $colours = $this->compiled('default');
        $item = '[data-colored-header=true][data-theme$=-hc] .Header-controls > li >';

        $selectors = [];

        foreach (self::STATES as $state) {
            $selectors[] = "$item .Button--link$state .Button-label";
            $selectors[] = "$item .Dropdown > .Dropdown-toggle.Button--link$state .Button-label";
        }

        $selectors[] = "$item .Dropdown.open > .Dropdown-toggle.Button--link .Button-label";

        foreach ($selectors as $selector) {
            $this->assertSame('underline', $colours->declarations($selector)['text-decoration'] ?? null, $selector);
        }

        // The overflow menu's toggle is only an icon, with no label to underline.
        $toggles = array_map(fn (string $state) => "$item .OverflowingList-dropdown > .Dropdown-toggle.Button--link$state", self::STATES);
        $toggles[] = "$item .OverflowingList-dropdown.open > .Dropdown-toggle.Button--link";

        foreach ($toggles as $selector) {
            $this->assertSame('inset 0 -2px 0 currentColor', $colours->declarations($selector)['box-shadow'] ?? null, $selector);
        }
    }

    protected function colour(CompiledColors $colours, string $selector): string
    {
        $declarations = $colours->declarations($selector);

        $this->assertArrayHasKey('color', $declarations, "$selector sets no colour");

        return $declarations['color'];
    }

    /**
     * @return array<string, string>
     */
    protected function coloredHeader(CompiledColors $colours, string $scheme): array
    {
        return array_merge(...array_map(fn (string $selector) => $colours->declarations($selector), self::COLORED[$scheme]));
    }

    protected function compiled(string $primary): CompiledColors
    {
        return self::$compiled[$primary] ??= new CompiledColors(self::PRIMARIES[$primary], '#536F90', files: ['Avatar.less', 'Button.less', 'App.less']);
    }
}
