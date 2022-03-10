<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Foundation;

use Flarum\Foundation\Paths;
use Flarum\Testing\unit\TestCase;
use InvalidArgumentException;

class PathsTest extends TestCase
{
    /** @test */
    public function it_complains_when_paths_are_missing()
    {
        $this->expectException(InvalidArgumentException::class);

        new Paths([
            'base' => '/var/www/flarum',
        ]);
    }

    /** @test */
    public function it_makes_paths_available_as_properties()
    {
        $paths = new Paths([
            'base' => '/var/www/flarum',
            'public' => '/var/www/flarum/public',
            'storage' => '/var/www/flarum/storage',
        ]);

        $this->assertEquals('/var/www/flarum', $paths->base);
        $this->assertEquals('/var/www/flarum/public', $paths->public);
        $this->assertEquals('/var/www/flarum/storage', $paths->storage);
    }

    /** @test */
    public function it_derives_the_vendor_dir_from_the_base_path()
    {
        $paths = new Paths([
            'base' => '/var/www/flarum',
            'public' => '/var/www/flarum/public',
            'storage' => '/var/www/flarum/storage',
        ]);

        $this->assertEquals('/var/www/flarum/vendor', $paths->vendor);
    }

    /** @test */
    public function it_allows_setting_a_custom_vendor_dir()
    {
        $paths = new Paths([
            'base' => '/var/www/flarum',
            'public' => '/var/www/flarum/public',
            'storage' => '/var/www/flarum/storage',
            'vendor' => '/share/composer-vendor',
        ]);

        $this->assertEquals('/share/composer-vendor', $paths->vendor);
    }

    /** @test */
    public function it_strips_trailing_forward_slashes_from_paths()
    {
        $paths = new Paths([
            'base' => '/var/www/flarum/',
            'public' => '/var/www/flarum/public/',
            'storage' => '/var/www/flarum/storage/',
        ]);

        $this->assertEquals('/var/www/flarum', $paths->base);
        $this->assertEquals('/var/www/flarum/public', $paths->public);
        $this->assertEquals('/var/www/flarum/storage', $paths->storage);
    }

    /** @test */
    public function it_strips_trailing_backslashes_from_paths()
    {
        $paths = new Paths([
            'base' => 'C:\\flarum\\',
            'public' => 'C:\\flarum\\public\\',
            'storage' => 'C:\\flarum\\storage\\',
        ]);

        $this->assertEquals('C:\\flarum', $paths->base);
        $this->assertEquals('C:\\flarum\\public', $paths->public);
        $this->assertEquals('C:\\flarum\\storage', $paths->storage);
    }
}
