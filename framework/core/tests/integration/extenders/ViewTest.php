<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Extension\Extension;
use Flarum\Testing\integration\TestCase;
use Illuminate\Contracts\View\Factory;
use PHPUnit\Framework\Attributes\Test;

class ViewTest extends TestCase
{
    #[Test]
    public function custom_view_namespace_does_not_exist_by_default()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->app()->getContainer()->make(Factory::class)->make('integration.test::test');
    }

    #[Test]
    public function custom_view_namespace_can_be_added_by_extender()
    {
        $this->extend(
            (new Extend\View)
                ->namespace('integration.test', dirname(__FILE__, 3).'/fixtures/views')
        );

        $this->assertEquals('<html><body>Hello World!</body></html>', trim($this->app()->getContainer()->make(Factory::class)->make('integration.test::test')->render()));
    }

    #[Test]
    public function can_add_view_to_namespace_by_prepend_extender()
    {
        $this->extend(
            (new Extend\View)
                ->extendNamespace('flarum', dirname(__FILE__, 3).'/fixtures/views')
        );

        $this->assertEquals('<html><body>Hello World!</body></html>', trim($this->app()->getContainer()->make(Factory::class)->make('flarum::test')->render()));
    }

    #[Test]
    public function can_override_view_in_namespace_by_prepend_extender()
    {
        $this->extend(
            (new Extend\View)
                ->extendNamespace('flarum', dirname(__FILE__, 3).'/fixtures/views/override')
        );

        $response = $this->send(
            $this->request('GET', '/')
        );

        $this->assertEquals('<html><body>We have overridden the core app view.</body></html>', trim($response->getBody()->getContents()));
    }

    #[Test]
    public function an_entry_that_cannot_be_deleted_does_not_stop_the_views_clear()
    {
        // Another process can delete a listed file before the sweep reaches it
        // (a concurrent clear, or Blade renaming its temporary file). A
        // directory fails unlink() the same way, deterministically.
        $views = $this->tmpDir().'/storage/views';
        mkdir($views.'/a-entry-that-cannot-be-deleted');

        $container = $this->app()->getContainer();
        $extension = new Extension($this->tmpDir(), ['name' => 'acme/views', 'extra' => ['flarum-extension' => ['title' => 'Views']]]);

        // Collect every warning that would be reported.
        $warnings = [];
        $reporting = error_reporting(E_ALL);
        set_error_handler(function (int $level, string $message) use (&$warnings) {
            if (error_reporting() & $level) {
                $warnings[] = $message;
            }

            return true;
        });

        try {
            foreach (['onEnable', 'onDisable'] as $event) {
                touch($views.'/compiled.php');
                (new Extend\View)->$event($container, $extension);
                $this->assertFileDoesNotExist($views.'/compiled.php', $event);
            }
        } finally {
            restore_error_handler();
            error_reporting($reporting);
            rmdir($views.'/a-entry-that-cannot-be-deleted');
        }

        $this->assertSame([], $warnings);
    }
}
