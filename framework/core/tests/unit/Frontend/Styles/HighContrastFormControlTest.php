<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Frontend\Styles;

use Flarum\Testing\unit\TestCase;
use Less_Parser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

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

/**
 * Core's colour schemes compiled for one forum palette, with CSS variables
 * resolved — `var()` fallbacks and `color-mix()` included — to the colours a
 * browser would use in a given scheme.
 */
class CompiledColors
{
    /**
     * The selectors whose variables apply in each scheme. They all weigh the
     * same, so where two set a variable the later rule wins.
     */
    public const SCHEMES = [
        'light' => [':root', '[data-theme^=light]'],
        'light-hc' => [':root', '[data-theme^=light]', '[data-theme=light-hc]'],
        'dark' => [':root', '[data-theme^=dark]'],
        'dark-hc' => [':root', '[data-theme^=dark]', '[data-theme=dark-hc]'],
    ];

    /** @var array<array{selectors: string[], declarations: array<string, string>}> */
    protected array $rules = [];

    /**
     * @param string[] $files Stylesheets under less/common to compile with the schemes.
     */
    public function __construct(string $primary, string $secondary, array $files)
    {
        $common = realpath(__DIR__.'/../../../../less/common');

        $source = '@import "variables.less"; @import "mixins.less";'
            ."@config-primary-color: $primary; @config-secondary-color: $secondary;"
            .'@import "root.less";'
            .implode('', array_map(fn (string $file) => "@import \"$file\";", $files));

        $parser = new Less_Parser(['compress' => false, 'strictMath' => false, 'import_dirs' => [$common => '']]);
        $parser->parse($source, $common.'/colours-test.less');

        $css = preg_replace('#/\*.*?\*/#s', '', $parser->getCss());

        preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $matches, PREG_SET_ORDER);

        foreach ($matches as [, $selectors, $body]) {
            preg_match_all('/([\w-]+)\s*:\s*([^;]+);/', $body, $declarations, PREG_SET_ORDER);

            $this->rules[] = [
                'selectors' => array_map(fn (string $selector) => str_replace(['"', "'"], '', trim($selector)), explode(',', $selectors)),
                'declarations' => array_column(array_map(fn (array $d) => [$d[1], trim($d[2])], $declarations), 1, 0),
            ];
        }
    }

    /**
     * The custom properties a scheme ends up with on the root element.
     *
     * @return array<string, string>
     */
    public function variables(string $scheme): array
    {
        $variables = [];

        foreach ($this->rules as $rule) {
            if (array_intersect(self::SCHEMES[$scheme], $rule['selectors'])) {
                foreach ($rule['declarations'] as $name => $value) {
                    if (str_starts_with($name, '--')) {
                        $variables[$name] = $value;
                    }
                }
            }
        }

        return $variables;
    }

    /**
     * The declarations of the rules for a selector, later ones winning.
     *
     * @return array<string, string>
     */
    public function declarations(string $selector): array
    {
        $declarations = [];

        foreach ($this->rules as $rule) {
            if (in_array($selector, $rule['selectors'], true)) {
                $declarations = array_merge($declarations, $rule['declarations']);
            }
        }

        return $declarations;
    }

    /**
     * A colour value resolved against a scheme's variables, plus any set on
     * the element itself. An element's `inherit` takes the root's value.
     *
     * @param array<string, string> $local
     * @param array{0: float, 1: float, 2: float, 3: float}|null $current The element's `color`, for `currentColor`.
     * @return array{0: float, 1: float, 2: float, 3: float} RGBA, alpha 0–1.
     */
    public function colour(string $scheme, string $value, array $local = [], ?array $current = null): array
    {
        $local = array_filter($local, fn (string $value) => ! in_array($value, ['inherit', 'unset'], true));

        return $this->resolve(array_merge($this->variables($scheme), $local), $value, $current);
    }

    protected function resolve(array $variables, string $value, ?array $current, int $depth = 0): array
    {
        if ($depth > 20) {
            throw new RuntimeException("Variable loop at $value");
        }

        $value = trim($value);

        if (strtolower($value) === 'currentcolor') {
            return $current ?? throw new RuntimeException('currentColor needs the element colour');
        }

        if (preg_match('/^var\(\s*(--[\w-]+)\s*(?:,(.*))?\)$/s', $value, $reference)) {
            if (isset($variables[$reference[1]])) {
                return $this->resolve($variables, $variables[$reference[1]], $current, $depth + 1);
            }

            if (! isset($reference[2])) {
                throw new RuntimeException("{$reference[1]} is not defined");
            }

            return $this->resolve($variables, $reference[2], $current, $depth + 1);
        }

        if (preg_match('/^color-mix\(\s*in srgb\s*,(.*)\)$/s', $value, $mix)) {
            [$a, $b] = array_map(function (string $part) use ($variables, $current, $depth) {
                preg_match('/^(.*?)(?:\s+([\d.]+)%)?$/s', trim($part), $m);

                return ['colour' => $this->resolve($variables, $m[1], $current, $depth + 1), 'share' => isset($m[2]) ? $m[2] / 100 : null];
            }, $this->splitTopLevel($mix[1]));

            $shareOfA = $a['share'] ?? ($b['share'] === null ? 0.5 : 1 - $b['share']);

            return array_map(fn ($x, $y) => $x * $shareOfA + $y * (1 - $shareOfA), $a['colour'], $b['colour']);
        }

        return $this->parse($value);
    }

    protected function parse(string $value): array
    {
        $value = strtolower($value);

        if ($value === 'transparent') {
            return [0, 0, 0, 0];
        }

        if ($value === 'white' || $value === 'black') {
            return $value === 'white' ? [255, 255, 255, 1] : [0, 0, 0, 1];
        }

        if (preg_match('/^#([0-9a-f]{3,8})$/', $value, $hex)) {
            $h = strlen($hex[1]) <= 4 ? implode('', array_map(fn ($c) => $c.$c, str_split($hex[1]))) : $hex[1];

            return [hexdec(substr($h, 0, 2)), hexdec(substr($h, 2, 2)), hexdec(substr($h, 4, 2)), strlen($h) === 8 ? hexdec(substr($h, 6, 2)) / 255 : 1];
        }

        if (preg_match('/^rgba?\(([^)]+)\)$/', $value, $rgb)) {
            $parts = array_map('floatval', preg_split('/[\s,\/]+/', trim($rgb[1])));

            return [$parts[0], $parts[1], $parts[2], $parts[3] ?? 1];
        }

        if (preg_match('/^hsla?\(([^)]+)\)$/', $value, $hsl)) {
            $parts = array_map('floatval', preg_split('/[\s,\/]+/', trim($hsl[1])));
            [$h, $s, $l] = [fmod($parts[0], 360) / 360, $parts[1] / 100, $parts[2] / 100];

            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $channel = function (float $t) use ($p, $q): float {
                $t = $t < 0 ? $t + 1 : ($t > 1 ? $t - 1 : $t);

                return 255 * match (true) {
                    $t < 1 / 6 => $p + ($q - $p) * 6 * $t,
                    $t < 1 / 2 => $q,
                    $t < 2 / 3 => $p + ($q - $p) * (2 / 3 - $t) * 6,
                    default => $p,
                };
            };

            return [$channel($h + 1 / 3), $channel($h), $channel($h - 1 / 3), $parts[3] ?? 1];
        }

        throw new RuntimeException("Cannot read the colour $value");
    }

    protected function splitTopLevel(string $list): array
    {
        $parts = [''];
        $depth = 0;

        foreach (str_split($list) as $char) {
            $depth += $char === '(' ? 1 : ($char === ')' ? -1 : 0);

            if ($char === ',' && $depth === 0) {
                $parts[] = '';
            } else {
                $parts[count($parts) - 1] .= $char;
            }
        }

        return $parts;
    }

    /**
     * The WCAG contrast ratio of a colour over a background, compositing any
     * transparency onto the background first.
     */
    public static function contrast(array $colour, array $background): float
    {
        $over = array_map(fn ($c, $b) => $c * $colour[3] + $b * (1 - $colour[3]), array_slice($colour, 0, 3), array_slice($background, 0, 3));

        $luminance = function (array $rgb): float {
            [$r, $g, $b] = array_map(function ($channel) {
                $channel /= 255;

                return $channel <= 0.03928 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
            }, $rgb);

            return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
        };

        $a = $luminance($over);
        $b = $luminance(array_slice($background, 0, 3));

        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    }
}
