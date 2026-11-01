<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Locale;

use Composer\Autoload\ClassLoader;
use Flarum\Locale\CatalogueCache;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Config\Resource\FileResource;

class CatalogueCacheTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir().'/flarum-catalogue-'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->root.'/locale/*') ?: [] as $file) {
            is_dir($file) ? rmdir($file) : unlink($file);
        }

        is_file($this->root.'/locale') ? unlink($this->root.'/locale') : @rmdir($this->root.'/locale');
        @unlink($this->root.'/en.yml');

        foreach (glob($this->root.'/*.php') ?: [] as $script) {
            unlink($script);
        }
        @rmdir($this->root);

        parent::tearDown();
    }

    #[Test]
    public function it_creates_a_missing_cache_directory()
    {
        $file = $this->root.'/locale/catalogue.en.php';
        $cache = new CatalogueCache($file, 'rev-1');

        $cache->write('<?php return [];');

        $this->assertSame('<?php return [];', file_get_contents($cache->getPath()));
        $this->assertTrue($cache->isFresh());
    }

    #[Test]
    public function a_catalogue_written_for_another_revision_is_stale()
    {
        $file = $this->root.'/locale/catalogue.en.php';
        $written = new CatalogueCache($file, 'rev-1');
        $written->write('<?php return [];');

        $this->assertTrue($written->isFresh(), 'Precondition: the catalogue was written');
        $this->assertFalse((new CatalogueCache($file, 'rev-2'))->isFresh());
    }

    #[Test]
    public function it_leaves_no_temporary_files_behind()
    {
        $file = $this->root.'/locale/catalogue.en.php';
        $cache = new CatalogueCache($file, 'rev-1');

        $cache->write('<?php return ["a" => "b"];');
        $cache->write('<?php return ["a" => "c"];');

        $this->assertSame([$cache->getPath()], glob($this->root.'/locale/*'));
        $this->assertSame('<?php return ["a" => "c"];', file_get_contents($cache->getPath()));
    }

    #[Test]
    public function a_directory_that_cannot_be_created_is_a_cache_miss_not_an_error()
    {
        // A file where the directory should be: mkdir fails even for root,
        // which a read-only directory would not stop.
        mkdir($this->root);
        touch($this->root.'/locale');

        $cache = new CatalogueCache($this->root.'/locale/catalogue.en.php', 'rev-1');
        $cache->write('<?php return [];');

        $this->assertFalse($cache->isFresh());
    }

    #[Test]
    public function a_catalogue_that_cannot_be_written_is_a_cache_miss()
    {
        // A directory where the catalogue should be: the rename over it fails.
        $cache = new CatalogueCache($this->root.'/locale/catalogue.en.php', 'rev-1');
        mkdir($cache->getPath(), 0775, true);

        $cache->write('<?php return [];');

        $this->assertFalse($cache->isFresh());
        $this->assertSame([$cache->getPath()], glob($this->root.'/locale/*'), 'The temporary file was removed');
    }

    #[Test]
    public function outside_debug_mode_the_resources_behind_a_catalogue_are_never_checked()
    {
        $file = $this->root.'/locale/catalogue.en.php';
        $translations = $this->root.'/en.yml';
        mkdir($this->root, 0775, true);
        touch($translations);

        (new CatalogueCache($file, 'rev-1'))->write('<?php return [];', [new FileResource($translations)]);
        touch($translations, time() + 5);
        clearstatcache();

        $this->assertTrue((new CatalogueCache($file, 'rev-1'))->isFresh());
        $this->assertFalse((new CatalogueCache($file, 'rev-1', true))->isFresh(), 'Debug mode does check them');
    }

    #[Test]
    public function catalogues_written_for_different_revisions_do_not_replace_each_other()
    {
        // Two processes on different revisions rebuilding at once, such as
        // old and new servers during a rolling deploy: each must keep getting
        // the catalogue built for its own revision.
        $file = $this->root.'/locale/catalogue.en.php';
        $old = new CatalogueCache($file, 'rev-1');
        $new = new CatalogueCache($file, 'rev-2');

        $old->write('<?php return "built for rev-1";');
        $new->write('<?php return "built for rev-2";');

        $this->assertTrue($old->isFresh());
        $this->assertSame('built for rev-1', include $old->getPath());
        $this->assertTrue($new->isFresh());
        $this->assertSame('built for rev-2', include $new->getPath());
    }

    #[Test]
    public function a_catalogue_rebuilt_by_another_process_is_not_hidden_by_opcache()
    {
        if (! extension_loaded('Zend OPcache')) {
            $this->markTestSkipped('Needs opcache.');
        }

        mkdir($this->root.'/locale', 0775, true);
        $autoload = dirname((new \ReflectionClass(ClassLoader::class))->getFileName(), 2).'/autoload.php';
        $file = $this->root.'/locale/catalogue.en.php';

        // Another process (a queue worker, another server sharing storage):
        // its opcache_invalidate() cannot reach this one's.
        file_put_contents($this->root.'/build.php', <<<'PHP'
            <?php
            require $argv[1];
            (new Flarum\Locale\CatalogueCache($argv[2], $argv[3]))->write('<?php return '.var_export($argv[3], true).';');
            PHP);

        // A long-lived process with an opcache of its own, never told to reset
        // it, as with opcache.validate_timestamps=0.
        file_put_contents($this->root.'/server.php', <<<'PHP'
            <?php
            [, $autoload, $file, $build] = $argv;
            require $autoload;

            $rebuild = fn (string $revision) => exec(implode(' ', array_map('escapeshellarg', [
                PHP_BINARY, '-d', 'opcache.enable_cli=0', $build, $autoload, $file, $revision,
            ])));

            $rebuild('rev-1');
            $before = new Flarum\Locale\CatalogueCache($file, 'rev-1');
            include $before->getPath();

            $rebuild('rev-2');
            $after = new Flarum\Locale\CatalogueCache($file, 'rev-2');

            echo json_encode([
                'compiled' => opcache_is_script_cached($before->getPath()),
                'fresh' => $after->isFresh(),
                'catalogue' => include $after->getPath(),
            ]);
            PHP);

        $output = shell_exec(implode(' ', array_map('escapeshellarg', [
            PHP_BINARY,
            '-d', 'opcache.enable_cli=1',
            '-d', 'opcache.validate_timestamps=0',
            '-d', 'opcache.file_update_protection=0',
            $this->root.'/server.php', $autoload, $file, $this->root.'/build.php',
        ])));
        $result = json_decode((string) $output, true);

        if (! ($result['compiled'] ?? false)) {
            $this->markTestSkipped('Opcache did not compile the catalogue: '.$output);
        }

        $this->assertTrue($result['fresh'], (string) $output);
        $this->assertSame('rev-2', $result['catalogue'], 'The rebuilt catalogue, not the one this process compiled before');
    }
}
