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
            unlink($file);
        }

        @rmdir($this->root.'/locale');
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
}
