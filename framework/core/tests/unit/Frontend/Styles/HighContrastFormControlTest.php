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

/**
 * The high contrast themes promise fields that can be found, followed and
 * read whatever colours the forum is configured with: an edge of at least
 * 3:1 against what surrounds the field (WCAG 1.4.11), a focus indicator of
 * at least 3:1 that does not depend on the primary colour, and text of at
 * least 7:1 (WCAG 1.4.6).
 */
class HighContrastFormControlTest extends TestCase
{
    /**
     * Primary and secondary colours. The last two are the secondaries that
     * leave the least room in each high contrast scheme.
     */
    protected const PALETTES = [
        'default' => ['#536F90', '#536F90'],
        'yellow' => ['#FFD200', '#7A6A00'],
        'red and grey' => ['#E2001A', '#5A5A5A'],
        'navy' => ['#1E1E50', '#1E1E50'],
        'green' => ['#2E9E44', '#2E5E3A'],
        'yellow and olive' => ['#FFD200', '#7A7A52'],
        'navy and slate' => ['#1E1E50', '#5C5C70'],
    ];

    /**
     * Where a field sits: the rules that set its variables, and the
     * background around it.
     */
    protected const PLACES = [
        'page' => [[], '--body-bg'],
        'modal' => [['.Modal-body .FormControl'], '--control-bg'],
        'flat modal' => [['.Modal-body .FormControl', '.Modal--flat .Modal-body .FormControl:not(:focus)'], '--body-bg'],
        'icon picker' => [['.Modal-body .FormControl', '.IconPicker .IconPicker-panel .FormControl'], '--body-bg'],
    ];

    /** @var array<string, CompiledColors> */
    protected static array $compiled = [];

    public static function fields(): iterable
    {
        foreach (array_keys(self::PALETTES) as $palette) {
            foreach (['light-hc', 'dark-hc'] as $scheme) {
                foreach (array_keys(self::PLACES) as $place) {
                    yield "$palette, $scheme, $place" => [$palette, $scheme, $place];
                }
            }
        }
    }

    public static function regularSchemes(): iterable
    {
        foreach (array_keys(self::PALETTES) as $palette) {
            foreach (['light', 'dark'] as $scheme) {
                yield "$palette, $scheme" => [$palette, $scheme];
            }
        }
    }

    #[Test]
    #[DataProvider('fields')]
    public function a_field_has_an_edge_that_stands_out(string $palette, string $scheme, string $place): void
    {
        [$colours, $local, $around] = $this->field($palette, $place);

        $edge = $colours->colour($scheme, $this->borderColour($colours->declarations('.FormControl')['border']), $local);

        $this->assertGreaterThanOrEqual(3, CompiledColors::contrast($edge, $colours->colour($scheme, "var($around)")));
    }

    #[Test]
    #[DataProvider('fields')]
    public function a_focused_field_is_marked_whatever_the_primary_colour(string $palette, string $scheme, string $place): void
    {
        [$colours, $local, $around] = $this->field($palette, $place);
        $focus = $colours->declarations('.FormControl:focus');

        $text = $colours->colour($scheme, $focus['color'], $local);
        $fill = $colours->colour($scheme, $focus['background-color'], $local);
        $border = $colours->colour($scheme, $focus['border-color'], $local, $text);
        $background = $colours->colour($scheme, "var($around)");

        $outline = $this->focusOutline($colours, $scheme);
        $this->assertMatchesRegularExpression('/^2px solid /', $outline, 'The focus ring is drawn outside the field');

        $ring = $colours->colour($scheme, substr($outline, strlen('2px solid ')), $local, $text);

        $this->assertGreaterThanOrEqual(3, CompiledColors::contrast($ring, $background), 'The focus ring stands out from what surrounds the field');
        $this->assertGreaterThanOrEqual(3, CompiledColors::contrast($border, $fill), 'The focused border stands out from the field');
    }

    #[Test]
    #[DataProvider('fields')]
    public function a_field_has_text_that_reads_at_7_to_1(string $palette, string $scheme, string $place): void
    {
        [$colours, $local] = $this->field($palette, $place);
        $field = $colours->declarations('.FormControl');
        $focus = $colours->declarations('.FormControl:focus');

        $fill = $colours->colour($scheme, $field['background-color'], $local);

        $this->assertGreaterThanOrEqual(7, CompiledColors::contrast($colours->colour($scheme, $field['color'], $local), $fill), 'Text');
        $this->assertGreaterThanOrEqual(7, CompiledColors::contrast($colours->colour($scheme, $colours->declarations('.FormControl::placeholder')['color'], $local), $fill), 'Placeholder');
        $this->assertGreaterThanOrEqual(7, CompiledColors::contrast($colours->colour($scheme, $focus['color'], $local), $colours->colour($scheme, $focus['background-color'], $local)), 'Text while focused');
    }

    #[Test]
    #[DataProvider('regularSchemes')]
    public function fields_in_the_regular_schemes_keep_their_look(string $palette, string $scheme): void
    {
        [$colours] = $this->field($palette, 'page');
        $field = $colours->declarations('.FormControl');
        $focus = $colours->declarations('.FormControl:focus');
        $text = $colours->colour($scheme, $focus['color']);

        $this->assertEquals([0, 0, 0, 0], $colours->colour($scheme, $this->borderColour($field['border'])));
        $this->assertEquals($colours->colour($scheme, 'var(--control-color)'), $colours->colour($scheme, $field['color']));
        $this->assertEquals($colours->colour($scheme, 'var(--control-color)'), $colours->colour($scheme, $colours->declarations('.FormControl::placeholder')['color']));
        $this->assertEquals($colours->colour($scheme, 'var(--primary-color)'), $colours->colour($scheme, $focus['border-color'], [], $text));
        $this->assertEquals('none', $this->focusOutline($colours, $scheme));
    }

    /**
     * @return array{0: CompiledColors, 1: array<string, string>, 2: string}
     */
    protected function field(string $palette, string $place): array
    {
        $colours = self::$compiled[$palette] ??= new CompiledColors(...self::PALETTES[$palette], files: ['FormControl.less', 'Modal.less', 'IconInput.less']);

        [$rules, $around] = self::PLACES[$place];

        $local = [];

        foreach ($rules as $rule) {
            $local = array_merge($local, array_filter($colours->declarations($rule), fn (string $name) => str_starts_with($name, '--'), ARRAY_FILTER_USE_KEY));
        }

        return [$colours, $local, $around];
    }

    protected function focusOutline(CompiledColors $colours, string $scheme): string
    {
        $outline = $colours->declarations('.FormControl:focus')['outline'];

        if (preg_match('/^var\((--[\w-]+), (.+)\)$/', $outline, $variable)) {
            return $colours->variables($scheme)[$variable[1]] ?? $variable[2];
        }

        return $outline;
    }

    protected function borderColour(string $border): string
    {
        return preg_replace('/^\S+\s+\S+\s+/', '', $border);
    }
}
