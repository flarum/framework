<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Locale;

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
        @rmdir($this->root);

        parent::tearDown();
    }

    #[Test]
    public function it_creates_a_missing_cache_directory()
    {
        $file = $this->root.'/locale/catalogue.en.php';
        $cache = new CatalogueCache($file, 'rev-1');

        $cache->write('<?php return [];');

        $this->assertFileExists($file);
        $this->assertSame('<?php return [];', file_get_contents($file));
        $this->assertTrue($cache->isFresh(), 'The catalogue and its revision were both written');
    }

    #[Test]
    public function a_catalogue_written_for_another_revision_is_stale()
    {
        $file = $this->root.'/locale/catalogue.en.php';
        (new CatalogueCache($file, 'rev-1'))->write('<?php return [];');

        $this->assertFalse((new CatalogueCache($file, 'rev-2'))->isFresh());
    }

    #[Test]
    public function it_leaves_no_temporary_files_behind()
    {
        $file = $this->root.'/locale/catalogue.en.php';
        $cache = new CatalogueCache($file, 'rev-1');

        $cache->write('<?php return ["a" => "b"];');
        $cache->write('<?php return ["a" => "c"];');

        $this->assertSame(
            ['catalogue.en.php', 'catalogue.en.php.revision'],
            array_map('basename', glob($this->root.'/locale/*'))
        );
        $this->assertSame('<?php return ["a" => "c"];', file_get_contents($file));
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
    public function a_catalogue_that_cannot_be_written_is_a_cache_miss_and_gets_no_revision()
    {
        // A directory where the catalogue should be: the rename over it fails.
        $file = $this->root.'/locale/catalogue.en.php';
        mkdir($file, 0775, true);

        $cache = new CatalogueCache($file, 'rev-1');
        $cache->write('<?php return [];');

        $this->assertFalse($cache->isFresh());
        $this->assertFileDoesNotExist($file.'.revision', 'No revision for a catalogue that was not written');
        $this->assertSame(['catalogue.en.php'], array_map('basename', glob($this->root.'/locale/*')), 'The temporary file was removed');
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
}
