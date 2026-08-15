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
 * less.php caches each parsed @import to a `*.lesscache` file in the cache dir.
 * Its built-in `serialize` cache writes the file non-atomically
 * (`file_put_contents(serialize(...))`) and reads it back with an unguarded
 * `unserialize()`. Under concurrent compiles two processes writing the same
 * cache file interleave, leaving a few trailing bytes past the serialized
 * payload; the next reader's `unserialize()` then emits a warning
 * ("Extra data at offset N of N+k") which Flarum's error handler escalates to
 * an uncaught exception — the recurring production failure at
 * `Less/Parser.php:654`, which also killed the `drafts:publish` scheduled run
 * with exit code 255.
 *
 * The compiler must be resilient to a corrupt cache file: read it defensively
 * (treat corruption as a miss and reparse) and write it atomically (so a
 * concurrent reader never sees a half-written file).
 */
class LessCacheIntegrityTest extends TestCase
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
     * Compile a small stylesheet that imports another file, so less.php writes
     * per-import cache files, and return the compiled CSS.
     */
    private function compile(): string
    {
        // A file that @imports another triggers the cached parseFile() path.
        $imported = $this->tmpDir.'/Imported.less';
        file_put_contents($imported, '.imported { color: red; }');

        $source = $this->tmpDir.'/forum.less';
        file_put_contents($source, "@import 'Imported';\n.dummy { color: yellow; }");

        $compiler = new LessCompiler(
            $this->assetsDir(),
            'forum.css',
            $this->settings(),
            $this->versioner()
        );

        $compiler->setCacheDir($this->cacheDir);
        $compiler->setImportDirs([$this->tmpDir => '']);
        $compiler->addSources(function (SourceCollector $sources) use ($source) {
            $sources->addFile($source);
        });
        $compiler->commit(true);

        return $this->written['forum.css'] ?? '';
    }

    /** @return string[] paths of the `*.lesscache` files in the cache dir */
    private function cacheFiles(): array
    {
        return glob($this->cacheDir.'/*.lesscache') ?: [];
    }

    #[Test]
    public function a_corrupt_cache_file_is_treated_as_a_miss_not_a_fatal(): void
    {
        // Prime the cache.
        $first = $this->compile();
        $this->assertStringContainsString('.imported', $first);

        $files = $this->cacheFiles();
        $this->assertNotEmpty($files, 'The compile should have written per-import cache files.');

        // Corrupt every cache file exactly the way a raced, non-atomic write
        // does: valid serialized data followed by a few trailing bytes.
        foreach ($files as $file) {
            file_put_contents($file, file_get_contents($file).'XX');
        }

        // Escalate any warning to an exception, mirroring production where
        // Flarum's error handler turns the unserialize() warning into an
        // uncaught ErrorException. A resilient read must not warn at all.
        set_error_handler(function (int $severity, string $message): bool {
            throw new \ErrorException($message, 0, $severity);
        });

        try {
            // Must not throw (previously: unserialize() "Extra data") and must
            // still produce the CSS by treating the corrupt cache as a miss.
            $second = $this->compile();
        } finally {
            restore_error_handler();
        }

        $this->assertStringContainsString('.imported', $second);
        $this->assertStringContainsString('.dummy', $second);
    }

    #[Test]
    public function cache_files_are_written_atomically_and_leave_no_temporary_files(): void
    {
        $this->compile();

        // Every cache file must be complete and valid — no partial writes.
        foreach ($this->cacheFiles() as $file) {
            $this->assertNotFalse(
                @unserialize(file_get_contents($file)),
                "Cache file $file should contain a complete, valid serialized payload."
            );
        }

        // No stray temporary files left behind by the atomic write.
        $all = glob($this->cacheDir.'/*') ?: [];
        $strays = array_filter($all, fn ($f) => ! str_ends_with($f, '.lesscache'));

        $this->assertSame(
            [],
            array_values($strays),
            'Atomic writes must not leave temporary files behind: '.implode(', ', $strays)
        );
    }

    #[Test]
    public function a_valid_cache_is_used_on_the_next_compile(): void
    {
        // Prime the cache for the imported file.
        $this->assertStringContainsString('.imported', $this->compile());

        $files = $this->cacheFiles();
        $this->assertNotEmpty($files);

        // Round-trip check: the write callback must produce a payload the read
        // callback accepts as a hit (not silently always-miss). Every primed
        // file must therefore read back as a non-null, valid parse result.
        $compiler = new LessCompiler($this->assetsDir(), 'forum.css', $this->settings(), $this->versioner());
        $compiler->setCacheDir($this->cacheDir);

        $read = (new \ReflectionMethod($compiler, 'readCache'))->getClosure($compiler);

        foreach ($files as $file) {
            $this->assertNotNull(
                $read(new \Less_Parser(), '', $file),
                "A freshly written cache file ($file) must read back as a hit."
            );
        }
    }

    #[Test]
    public function expired_cache_files_are_pruned_but_fresh_ones_are_kept(): void
    {
        // A unique cache dir keeps the once-per-request prune guard from having
        // already fired for this path in the test process.
        $dir = $this->tmpDir.'/gc-'.uniqid();
        @mkdir($dir, 0777, true);

        $prefix = \Less_Cache::$prefix;
        $lifetime = \Less_Cache::$gc_lifetime;

        $stale = $dir.'/'.$prefix.'stale.lesscache';
        $fresh = $dir.'/'.$prefix.'fresh.lesscache';
        $foreign = $dir.'/keep-me.txt';

        file_put_contents($stale, serialize(['x']));
        file_put_contents($fresh, serialize(['y']));
        file_put_contents($foreign, 'not ours');

        // Age the stale file well past the lifetime.
        touch($stale, time() - $lifetime - 3600);

        $compiler = new LessCompiler($this->assetsDir(), 'forum.css', $this->settings(), $this->versioner());
        $compiler->setCacheDir($dir);

        (new \ReflectionMethod($compiler, 'pruneCacheOnce'))->invoke($compiler);

        $this->assertFileDoesNotExist($stale, 'A cache file past its lifetime must be pruned.');
        $this->assertFileExists($fresh, 'A recent cache file must be kept.');
        $this->assertFileExists($foreign, 'Files not created by less.php must never be touched.');
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
