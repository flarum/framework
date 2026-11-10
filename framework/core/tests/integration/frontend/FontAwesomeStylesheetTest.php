<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\frontend;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Which FontAwesome fonts the compiled stylesheet binds.
 *
 * The icon glyph definitions resolve their font through a chain that ends in a
 * hardcoded 'Font Awesome 7 Free'. Local icons bind that name to the bundled
 * font files. When the icons come from a Kit or CDN instead, binding the
 * bundled files means every icon resolves twice — first against them, then
 * again when the remote stylesheet lands — which readers see as the icons
 * flickering. And leaving the name unbound is worse: the browser falls through
 * to the default font and draws each icon's codepoint as a visible box.
 *
 * So remote sources bind the name to a blank font instead — a real font whose
 * glyphs draw nothing — and the remote stylesheet takes the name over when it
 * arrives, because same-descriptor faces declared later win.
 */
class FontAwesomeStylesheetTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * The blank font travels as an inlined data URI, so its presence in the
     * compiled CSS is unmistakable.
     */
    private const BLANK_FONT = 'data:font/woff2;base64,';

    /**
     * The bundled Free files, referenced only by the real bindings.
     */
    private const BUNDLED_REGULAR = 'fa-regular-400.woff2';
    private const BUNDLED_SOLID = 'fa-solid-900.woff2';

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    /**
     * The compiled forum stylesheet.
     */
    private function css(): string
    {
        // Boot and render, so the asset is compiled the way a real request
        // compiles it rather than through a hand-assembled compiler.
        $this->send($this->request('GET', '/'));

        $assets = $this->app()->getContainer()->make('flarum.assets.forum');
        $compiler = $assets->makeCss();
        $compiler->commit(true);

        $filesystem = $this->app()->getContainer()->make('filesystem')->disk('flarum-assets');

        return $filesystem->get($compiler->getFilename());
    }

    private function configureSource(string $source, ?string $forcedStyle): void
    {
        $this->setting('fontawesome_source', $source);
        $this->setting('fontawesome_cdn_url', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css');
        $this->setting('fontawesome_kit_url', 'https://kit.fontawesome.com/0000000000.js');

        if ($forcedStyle !== null) {
            $this->setting('fontawesome_forced_style', $forcedStyle);
        }
    }

    /**
     * source, forced style, whether the bundled fonts should be bound.
     *
     * @return array<string, array{string, string|null, bool}>
     */
    public static function sources(): array
    {
        return [
            'local, no forced style' => ['local', null, true],
            'local, forced regular' => ['local', 'fa-regular', true],
            'cdn, no forced style' => ['cdn', null, false],
            'cdn, forced regular' => ['cdn', 'fa-regular', false],
            'kit, no forced style' => ['kit', null, false],
            'kit, forced regular' => ['kit', 'fa-regular', false],
        ];
    }

    #[Test]
    #[DataProvider('sources')]
    public function the_classic_binding_matches_the_source(string $source, ?string $forcedStyle, bool $bundled): void
    {
        $this->configureSource($source, $forcedStyle);

        $css = $this->css();

        if ($bundled) {
            $this->assertStringContainsString(self::BUNDLED_REGULAR, $css);
            $this->assertStringContainsString(self::BUNDLED_SOLID, $css);
            $this->assertStringNotContainsString(self::BLANK_FONT, $css, 'Local icons must draw immediately, not wait behind a placeholder.');
        } else {
            $this->assertStringContainsString(self::BLANK_FONT, $css, 'Without the placeholder, remote icons first paint in the bundled font and repaint when the remote one lands.');
            $this->assertStringNotContainsString(self::BUNDLED_REGULAR, $css, 'Binding the bundled fonts alongside a remote source is what makes every icon resolve twice.');
            $this->assertStringNotContainsString(self::BUNDLED_SOLID, $css);
        }
    }

    /**
     * The same cases, for assertions that hold whichever binding is chosen.
     *
     * @return array<string, array{string, string|null}>
     */
    public static function sourcesAlone(): array
    {
        return array_map(fn (array $case) => [$case[0], $case[1]], self::sources());
    }

    /**
     * The glyph definitions say *which* character an icon is; they name no font
     * and are needed whichever source supplies one.
     */
    #[Test]
    #[DataProvider('sourcesAlone')]
    public function glyph_definitions_are_compiled_in_for_every_source(string $source, ?string $forcedStyle): void
    {
        $this->configureSource($source, $forcedStyle);

        $css = $this->css();

        $this->assertStringContainsString('.fa-gavel', $css);
        $this->assertStringContainsString('.fa-house', $css);
    }

    /**
     * Brands bind their own family, so they neither collide with a remote
     * source's fonts nor need a placeholder.
     */
    #[Test]
    #[DataProvider('sourcesAlone')]
    public function brands_are_compiled_in_for_every_source(string $source, ?string $forcedStyle): void
    {
        $this->configureSource($source, $forcedStyle);

        $this->assertStringContainsString('fa-brands-400.woff2', $this->css());
    }

    /**
     * An empty URL means the source cannot actually deliver anything, so the
     * bundled fonts have to stay: a placeholder would leave such a forum with
     * no icons at all rather than with icons that arrive late.
     */
    #[Test]
    public function a_kit_with_no_url_keeps_the_bundled_fonts(): void
    {
        $this->setting('fontawesome_source', 'kit');
        $this->setting('fontawesome_kit_url', '');

        $css = $this->css();

        $this->assertStringContainsString(self::BUNDLED_REGULAR, $css);
        $this->assertStringNotContainsString(self::BLANK_FONT, $css);
    }

    #[Test]
    public function a_cdn_with_no_url_keeps_the_bundled_fonts(): void
    {
        $this->setting('fontawesome_source', 'cdn');
        $this->setting('fontawesome_cdn_url', '');

        $css = $this->css();

        $this->assertStringContainsString(self::BUNDLED_REGULAR, $css);
        $this->assertStringNotContainsString(self::BLANK_FONT, $css);
    }

    /**
     * If the remote source never delivers — a blocked kit, a dead CDN — the
     * placeholder must not be the end of the story. The page carries a rescue
     * that rebinds the icons to the bundled fonts, wired to the remote tags'
     * error events and to a frontend watchdog for failures that produce none.
     */
    #[Test]
    public function remote_sources_carry_the_rescue(): void
    {
        $this->setting('fontawesome_source', 'kit');
        $this->setting('fontawesome_kit_url', 'https://kit.fontawesome.com/0000000000.js');

        $body = $this->send($this->request('GET', '/'))->getBody()->getContents();

        $this->assertStringContainsString('window.flarumRescueIconFonts=function()', $body);
        $this->assertStringContainsString(self::BUNDLED_REGULAR, $body, 'The rescue must know where the bundled fonts live.');
        $this->assertStringContainsString('defer onerror="window.flarumRescueIconFonts&&window.flarumRescueIconFonts()"', $body);
    }

    #[Test]
    public function the_cdn_stylesheet_carries_the_rescue_too(): void
    {
        $this->setting('fontawesome_source', 'cdn');
        $this->setting('fontawesome_cdn_url', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css');

        $body = $this->send($this->request('GET', '/'))->getBody()->getContents();

        $this->assertStringContainsString('window.flarumRescueIconFonts=function()', $body);
        $this->assertStringContainsString('onerror="window.flarumRescueIconFonts&&window.flarumRescueIconFonts()">', $body);
    }

    /**
     * Local icons cannot fail to arrive, so they get no rescue machinery.
     */
    #[Test]
    public function local_icons_carry_no_rescue(): void
    {
        $body = $this->send($this->request('GET', '/'))->getBody()->getContents();

        $this->assertStringNotContainsString('flarumRescueIconFonts', $body);
    }
}
