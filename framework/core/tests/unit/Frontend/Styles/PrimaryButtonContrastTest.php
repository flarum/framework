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

// The monorepo's test autoloader doesn't map Flarum\Tests to core's tests.
require_once __DIR__.'/CompiledColors.php';

/**
 * The primary button picks its text the way the JS isDark utility picks it
 * for tags and badges: whichever of --text-on-dark and --text-on-light has
 * the higher contrast ratio against the primary colour.
 */
class PrimaryButtonContrastTest extends TestCase
{
    protected const PRIMARIES = [
        'default' => '#536F90',
        'Flarum orange' => '#E7672E',
        'green' => '#00A000',
        'mint' => '#48BF83',
        'grey' => '#777777',
        'yellow' => '#F1C40F',
        'crimson' => '#C41D33',
        'pale pink' => '#F8D7DA',
        'white' => '#FFFFFF',
        'black' => '#000000',
    ];

    /** @var array<string, CompiledColors> */
    protected static array $compiled = [];

    public static function buttons(): iterable
    {
        foreach (array_keys(self::PRIMARIES) as $primary) {
            foreach (array_keys(CompiledColors::SCHEMES) as $scheme) {
                yield "$primary, $scheme" => [$primary, $scheme];
            }
        }
    }

    #[Test]
    #[DataProvider('buttons')]
    public function the_text_is_whichever_contrasts_better(string $primary, string $scheme): void
    {
        $colours = $this->compiled($primary);
        $background = $colours->colour($scheme, 'var(--button-primary-bg)');

        $best = max(
            CompiledColors::contrast($colours->colour($scheme, 'var(--text-on-dark)'), $background),
            CompiledColors::contrast($colours->colour($scheme, 'var(--text-on-light)'), $background),
        );

        $this->assertEqualsWithDelta($best, CompiledColors::contrast($colours->colour($scheme, 'var(--button-primary-color)'), $background), 0.001);
    }

    #[Test]
    #[DataProvider('buttons')]
    public function hover_and_active_keep_the_text_readable(string $primary, string $scheme): void
    {
        $colours = $this->compiled($primary);
        $text = $colours->colour($scheme, 'var(--button-primary-color)');
        $resting = CompiledColors::contrast($text, $colours->colour($scheme, 'var(--button-primary-bg)'));

        foreach (['hover', 'active'] as $state) {
            $contrast = CompiledColors::contrast($text, $colours->colour($scheme, "var(--button-primary-bg-$state)"));

            $this->assertGreaterThanOrEqual(min($resting, 7), $contrast + 0.001, ucfirst($state));
        }
    }

    #[Test]
    public function the_high_contrast_schemes_use_pure_white_and_black(): void
    {
        $colours = $this->compiled('default');

        foreach (['light-hc', 'dark-hc'] as $scheme) {
            $this->assertEquals([255, 255, 255, 1], $colours->colour($scheme, 'var(--text-on-dark)'), $scheme);
            $this->assertEquals([0, 0, 0, 1], $colours->colour($scheme, 'var(--text-on-light)'), $scheme);
        }
    }

    #[Test]
    public function the_high_contrast_text_reaches_the_theme_previews(): void
    {
        // The theme picker previews each scheme in a container with its own
        // data-theme, so the override must not be tied to the root element.
        $this->assertStringContainsString('[data-theme$=-hc]', $this->css(''));
        $this->assertStringNotContainsString(':root[data-theme$=-hc]', $this->css(''));
    }

    #[Test]
    public function text_colours_less_cannot_measure_still_compile(): void
    {
        // A theme may point the text colours at custom properties.
        $css = $this->css('@text-on-dark: ~"var(--theme-on-dark)"; @text-on-light: ~"var(--theme-on-light)";');

        $this->assertMatchesRegularExpression('/--button-primary-color:\s*var\(--theme-on-dark\)/', $css);
    }

    protected function compiled(string $primary): CompiledColors
    {
        return self::$compiled[$primary] ??= new CompiledColors(self::PRIMARIES[$primary], '#536F90', files: []);
    }

    protected function css(string $overrides): string
    {
        $common = realpath(__DIR__.'/../../../../less/common');

        $parser = new Less_Parser(['compress' => false, 'strictMath' => false, 'import_dirs' => [$common => '']]);
        $parser->parse('@import "variables.less"; @import "mixins.less";'.$overrides.'@import "root.less";', $common.'/colours-test.less');

        return $parser->getCss();
    }
}
