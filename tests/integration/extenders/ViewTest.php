<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Testing\integration\TestCase;
use Illuminate\Contracts\View\Factory;

class ViewTest extends TestCase
{
    /**
     * @test
     */
    public function custom_view_namespace_does_not_exist_by_default()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->app()->getContainer()->make(Factory::class)->make('integration.test::test');
    }

    /**
     * @test
     */
    public function custom_view_namespace_can_be_added_by_extender()
    {
        $this->extend(
            (new Extend\View)
                ->namespace('integration.test', dirname(__FILE__, 3).'/fixtures/views')
        );

        $this->assertEquals('<html><body>Hello World!</body></html>', trim($this->app()->getContainer()->make(Factory::class)->make('integration.test::test')->render()));
    }

    /**
     * @test
     */
    public function can_replace_view_namespace_by_extender()
    {
        $this->extend(
            (new Extend\View)
                ->replace('flarum', dirname(__FILE__, 3).'/fixtures/views')
        );

        $viewFactory = $this->app()->getContainer()->make(Factory::class);

        $this->assertEquals('<html><body>Hello World!</body></html>', trim($viewFactory->make('flarum::test')->render()));

        // Expect to fail -- original namespace hint has been replaced
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/(View \[.*\] not found\.)/");
        $viewFactory->make('flarum.forum::frontend.app')->render();
    }

    /**
     * @test
     */
    public function can_prepend_view_namespace_by_extender()
    {
        $this->extend(
            (new Extend\View)
                ->prepend('flarum', dirname(__FILE__, 3).'/fixtures/views')
        );

        $this->assertEquals('<html><body>Hello World!</body></html>', trim($this->app()->getContainer()->make(Factory::class)->make('flarum::test')->render()));
    }
}
