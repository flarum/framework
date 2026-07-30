<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Frontend\Compiler;

use Flarum\Frontend\Compiler\FileVersioner;
use Flarum\Frontend\Compiler\JsCompiler;
use Flarum\Frontend\Compiler\RevisionCompiler;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\unit\TestCase;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Filesystem;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;

/**
 * The revision is a hash of the compiled OUTPUT, so it changes if and only if
 * the bytes a client would download change. Deriving it from source mtimes got
 * both directions wrong: a redeploy or extension toggle re-touches files and
 * spuriously changed the revision for byte-identical output (firing a needless
 * "new version available" prompt for every visitor), while a genuine change
 * written within the same second kept the same mtime and was missed entirely.
 */
class RevisionCompilerTest extends TestCase
{
    private string $tmpDir;

    /** @var array<string, string> in-memory stand-in for the assets dir */
    private array $written = [];

    /** @var array<string, string|null> shared manifest, persists across compilers like production */
    private array $manifest = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir().'/flarum-revcompiler-'.uniqid();
        @mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tmpDir.'/*') ?: []);
        @rmdir($this->tmpDir);

        parent::tearDown();
    }

    private function sourceFile(string $name, string $content): string
    {
        $path = $this->tmpDir.'/'.$name;
        file_put_contents($path, $content);

        return $path;
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
     * A fresh compiler over the shared in-memory assets dir and manifest, so
     * revisions round-trip across separate instances exactly as across requests.
     */
    private function makeCompiler(): RevisionCompiler
    {
        return new RevisionCompiler($this->assetsDir(), 'target.js', m::mock(SettingsRepositoryInterface::class), $this->versioner());
    }

    private function makeJsCompiler(): JsCompiler
    {
        return new JsCompiler($this->assetsDir(), 'chunk.js', m::mock(SettingsRepositoryInterface::class), $this->versioner());
    }

    #[Test]
    public function recompiling_identical_output_does_not_change_the_revision()
    {
        $path = $this->sourceFile('a.js', 'console.log(1);');

        $compiler = $this->makeCompiler();
        $compiler->addSources(fn ($sources) => $sources->addFile($path));
        $compiler->commit();

        $first = $compiler->getUrl();

        // Simulate a redeploy / extension toggle: the source file's mtime moves
        // on, but its CONTENT is byte-for-byte identical.
        touch($path, time() + 3600);
        clearstatcache(true, $path);

        $compiler2 = $this->makeCompiler();
        $compiler2->addSources(fn ($sources) => $sources->addFile($path));
        $compiler2->commit();

        $this->assertSame(
            $first,
            $compiler2->getUrl(),
            'A rebuild that produces identical output must keep the same revision (no spurious reload prompt).'
        );
    }

    #[Test]
    public function changing_output_changes_the_revision_even_within_the_same_second()
    {
        $path = $this->sourceFile('a.js', 'console.log(1);');

        $compiler = $this->makeCompiler();
        $compiler->addSources(fn ($sources) => $sources->addFile($path));
        $compiler->commit();
        $first = $compiler->getUrl();

        // Genuinely different output — with the mtime pinned, so an mtime-based
        // revision would miss it.
        $mtime = filemtime($path);
        file_put_contents($path, 'console.log(2);');
        touch($path, $mtime);
        clearstatcache(true, $path);

        $compiler2 = $this->makeCompiler();
        $compiler2->addSources(fn ($sources) => $sources->addFile($path));
        $compiler2->commit();

        $this->assertNotSame($first, $compiler2->getUrl(), 'A rebuild that changes output must produce a new revision.');
    }

    /**
     * Extension JS is compiled per-file by JsCompiler (JsDirectoryCompiler
     * expands a DirectorySource into one JsCompiler per file), which inherits
     * the same content-based revision — and its hash covers the written bundle
     * including the sourcemap comment, since that is what clients download.
     */
    #[Test]
    public function js_compiler_revision_is_content_based_and_writes_the_sourcemap()
    {
        $path = $this->sourceFile('chunk.js', 'export const x = 1;');

        $compiler = $this->makeJsCompiler();
        $compiler->addSources(fn ($sources) => $sources->addFile($path));
        $compiler->commit();
        $first = $compiler->getUrl();

        $this->assertArrayHasKey('chunk.js', $this->written);
        $this->assertArrayHasKey('chunk.js.map', $this->written, 'the sourcemap sidecar must still be written');
        $this->assertStringContainsString('sourceMappingURL', $this->written['chunk.js']);

        // Identical content, moved mtime — same revision.
        touch($path, time() + 3600);
        clearstatcache(true, $path);

        $compiler2 = $this->makeJsCompiler();
        $compiler2->addSources(fn ($sources) => $sources->addFile($path));
        $compiler2->commit();

        $this->assertSame($first, $compiler2->getUrl(), 'Identical extension JS must keep its revision.');

        // Different content — new revision.
        file_put_contents($path, 'export const x = 2;');
        clearstatcache(true, $path);

        $compiler3 = $this->makeJsCompiler();
        $compiler3->addSources(fn ($sources) => $sources->addFile($path));
        $compiler3->commit();

        $this->assertNotSame($first, $compiler3->getUrl(), 'Changed extension JS must produce a new revision.');
    }
}
