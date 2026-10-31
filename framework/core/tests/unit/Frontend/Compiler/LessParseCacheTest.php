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
 * less.php can cache each parsed @import as a serialized tree in a
 * `*.lesscache` file. The compiler does not use that cache: a tree read back
 * with unserialize() carries its own copy of `currentFileInfo` on every node,
 * several times the memory of the tree the parser builds, and reading it back
 * is slower than parsing the file again. On a 128 MB host `cache:clear`, which
 * compiles every frontend and locale in one process, ran out of memory inside
 * that unserialize().
 */
class LessParseCacheTest extends TestCase
{
    private string $tmpDir;
    private string $cacheDir;

    /** @var array<string, string> */
    private array $written = [];

    /** @var array<string, string|null> */
    private array $manifest = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir().'/flarum-lesscache-'.uniqid();
        $this->cacheDir = $this->tmpDir.'/cache';

        @mkdir($this->cacheDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->tmpDir);

        parent::tearDown();
    }

    private function removeTree(string $dir): void
    {
        foreach (glob($dir.'/*') ?: [] as $path) {
            is_dir($path) ? $this->removeTree($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    /**
     * Compile a small stylesheet that imports another file, the path less.php
     * would cache, and return the compiled CSS.
     */
    private function compile(array $customFunctions = []): string
    {
        // A file that @imports another goes through less.php's parseFile(),
        // which is where its per-import cache is read and written.
        $imported = $this->tmpDir.'/Imported.less';
        file_put_contents($imported, '.imported { color: red; }');

        $source = $this->tmpDir.'/forum.less';
        file_put_contents($source, "@import 'Imported';\n.dummy { color: yellow; }".($customFunctions ? "\n.probe { width: probe(); }" : ''));

        $compiler = new LessCompiler(
            $this->assetsDir(),
            'forum.css',
            $this->settings(),
            $this->versioner()
        );

        $compiler->setCacheDir($this->cacheDir);
        $compiler->setImportDirs([$this->tmpDir => '']);
        $compiler->setCustomFunctions($customFunctions);
        $compiler->addSources(function (SourceCollector $sources) use ($source) {
            $sources->addFile($source);
        });
        $compiler->commit(true);

        return $this->written['forum.css'] ?? '';
    }

    #[Test]
    public function compiling_writes_no_parse_cache(): void
    {
        $css = $this->compile();

        $this->assertStringContainsString('.imported', $css);
        $this->assertStringContainsString('.dummy', $css);
        $this->assertSame([], glob($this->cacheDir.'/*') ?: [], 'The compiler must not write a less.php parse cache.');
    }

    #[Test]
    public function a_cache_file_left_by_an_older_version_is_never_read(): void
    {
        // Written by the compiler before this change, and corrupt the way a
        // raced, non-atomic write left it. It must play no part in a compile.
        file_put_contents($this->cacheDir.'/'.\Less_Cache::$prefix.'old.lesscache', serialize(['x']).'XX');

        set_error_handler(function (int $severity, string $message): bool {
            throw new \ErrorException($message, 0, $severity);
        });

        try {
            $css = $this->compile();
        } finally {
            restore_error_handler();
        }

        $this->assertStringContainsString('.imported', $css);
    }

    #[Test]
    public function the_parser_is_released_after_a_compile(): void
    {
        // less.php keeps the parser, and its whole tree, in the static
        // Less_Tree::$parse. Held there, one compile's memory is still in use
        // when the next one begins, so catch the parser in use mid-compile and
        // check that nothing keeps it alive afterwards.
        $parser = null;

        $this->compile(['probe' => function () use (&$parser) {
            $parser = \WeakReference::create(\Less_Tree::$parse);

            return new \Less_Tree_Dimension(1, 'px');
        }]);

        gc_collect_cycles();

        $this->assertInstanceOf(\WeakReference::class, $parser, 'The probe function should have run.');
        $this->assertNull($parser->get(), 'The parser must not outlive its compile.');
    }

    private function settings(): SettingsRepositoryInterface
    {
        $settings = m::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')->andReturn(null);
        $settings->shouldReceive('delete')->andReturnNull();

        return $settings;
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
}
