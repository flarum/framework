<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Frontend\Compiler;

use Flarum\Frontend\Compiler\FileVersioner;
use Flarum\Frontend\Compiler\LessCompiler;
use Flarum\Frontend\Compiler\Source\SourceCollector;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\unit\TestCase;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Filesystem;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;

/**
 * The stylesheet is cache-busted (`forum.css?v=<rev>`), but the font URLs
 * inside it were not: `url("./fonts/fa-solid-900.woff2")`, served with a long
 * max-age. On a FontAwesome major upgrade every browser therefore picked up
 * the new CSS immediately while continuing to serve the *old* font file from
 * cache under the unchanged URL. The new CSS asks for codepoints that don't
 * exist in the old font, so every icon rendered as a placeholder box until the
 * font cache happened to expire.
 *
 * Font URLs must therefore carry a revision derived from the font files
 * themselves, so that changing a font always changes its URL.
 */
class LessFontUrlTest extends TestCase
{
    private string $tmpDir;
    private string $fontsDir;

    /** @var array<string, string> */
    private array $written = [];

    /** @var array<string, string|null> */
    private array $manifest = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir().'/flarum-lessfonts-'.uniqid();
        $this->fontsDir = $this->tmpDir.'/webfonts';

        @mkdir($this->tmpDir.'/cache', 0777, true);
        @mkdir($this->fontsDir, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir.'/{,*/}*', GLOB_BRACE) ?: [] as $path) {
            is_dir($path) ? @rmdir($path) : @unlink($path);
        }
        @rmdir($this->tmpDir);

        parent::tearDown();
    }

    private function font(string $name, string $content): void
    {
        file_put_contents($this->fontsDir.'/'.$name, $content);
    }

    private function assetsDir(): Cloud
    {
        $assetsDir = m::mock(Cloud::class);
        $assetsDir->shouldReceive('put')->andReturnUsing(function ($file, $content) {
            $this->written[$file] = $content;

            return true;
        });
        $assetsDir->shouldReceive('exists')->andReturnUsing(fn ($file) => isset($this->written[$file]));
        $assetsDir->shouldReceive('url')->andReturnUsing(fn ($file) => '/assets/'.$file);

        return $assetsDir;
    }

    private function versioner(): FileVersioner
    {
        $manifestFs = m::mock(Filesystem::class);
        $manifestFs->shouldReceive('exists')->with(FileVersioner::REV_MANIFEST)->andReturnUsing(fn () => $this->manifest !== []);
        $manifestFs->shouldReceive('get')->with(FileVersioner::REV_MANIFEST)->andReturnUsing(fn () => json_encode($this->manifest));
        $manifestFs->shouldReceive('put')->with(FileVersioner::REV_MANIFEST, m::type('string'))
            ->andReturnUsing(function ($_, $json) {
                $this->manifest = json_decode($json, true);

                return true;
            });

        return new FileVersioner($manifestFs);
    }

    /**
     * Compile a stylesheet whose source references the webfonts the way
     * FontAwesome's own CSS does, and return the compiled output.
     */
    private function compile(string $less = '@font-face { font-family: "Test"; src: url("../webfonts/fa-solid-900.woff2"); }'): string
    {
        $source = $this->tmpDir.'/source-'.uniqid().'.less';
        file_put_contents($source, $less);

        $settings = m::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')->andReturn(null);
        $settings->shouldReceive('delete')->andReturnNull();

        $compiler = new LessCompiler(
            $this->assetsDir(),
            'forum.css',
            $settings,
            $this->versioner()
        );

        $compiler->setCacheDir($this->tmpDir.'/cache');
        $compiler->setFontsDir($this->fontsDir);
        $compiler->addSources(function (SourceCollector $sources) use ($source) {
            $sources->addFile($source);
        });
        $compiler->commit(true);

        return $this->written['forum.css'] ?? '';
    }

    private function fontUrls(string $css): array
    {
        preg_match_all('/url\("([^"]+\.woff2[^"]*)"\)/', $css, $matches);

        return $matches[1];
    }

    #[Test]
    public function font_urls_are_rewritten_to_the_published_fonts_directory()
    {
        $this->font('fa-solid-900.woff2', 'FA7-SOLID');

        $urls = $this->fontUrls($this->compile());

        $this->assertNotEmpty($urls, 'The compiled CSS must still reference the font.');
        $this->assertStringStartsWith('./fonts/fa-solid-900.woff2', $urls[0]);
    }

    #[Test]
    public function font_urls_carry_a_revision()
    {
        $this->font('fa-solid-900.woff2', 'FA7-SOLID');

        $urls = $this->fontUrls($this->compile());

        $this->assertMatchesRegularExpression(
            '~^\./fonts/fa-solid-900\.woff2\?v=[a-f0-9]+$~',
            $urls[0],
            'Without a revision in the URL, a cached font from a previous FontAwesome version is served against the new CSS.'
        );
    }

    #[Test]
    public function the_revision_changes_when_the_font_file_changes()
    {
        $this->font('fa-solid-900.woff2', 'FA5-SOLID');
        $before = $this->fontUrls($this->compile())[0];

        // The FontAwesome upgrade: same filename, different bytes.
        $this->font('fa-solid-900.woff2', 'FA7-SOLID');
        $after = $this->fontUrls($this->compile())[0];

        $this->assertNotSame($before, $after, 'A changed font must produce a different URL, or caches keep the old file.');
    }

    #[Test]
    public function the_revision_is_stable_when_nothing_changes()
    {
        $this->font('fa-solid-900.woff2', 'FA7-SOLID');

        $this->assertSame(
            $this->fontUrls($this->compile())[0],
            $this->fontUrls($this->compile())[0],
            'Recompiling unchanged input must not churn font URLs and needlessly bust client caches.'
        );
    }

    #[Test]
    public function unrelated_css_changes_do_not_change_the_font_revision()
    {
        $this->font('fa-solid-900.woff2', 'FA7-SOLID');

        $base = '@font-face { font-family: "Test"; src: url("../webfonts/fa-solid-900.woff2"); }';

        $before = $this->fontUrls($this->compile($base))[0];
        $after = $this->fontUrls($this->compile($base.' .a { color: red; }'))[0];

        $this->assertSame($before, $after, 'The font revision must track the font files, not the stylesheet.');
    }

    #[Test]
    public function compilation_still_succeeds_when_the_fonts_directory_is_missing()
    {
        // Fonts are published separately; a compile must never hard-fail just
        // because the directory isn't there (e.g. before assets:publish).
        $urls = $this->fontUrls($this->compile());

        $this->assertNotEmpty($urls);
        $this->assertStringStartsWith('./fonts/fa-solid-900.woff2', $urls[0]);
    }
}
