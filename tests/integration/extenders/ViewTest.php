<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Tests\integration\TestCase;
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
}
