<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Foundation;

use Flarum\Http\RouteCollection;
use Flarum\Testing\unit\TestCase;
use RuntimeException;

class RouteCollectionTest extends TestCase
{
    /** @test */
    public function it_errors_when_nonexistent_route_requested()
    {
        $collection = new RouteCollection();

        $this->expectException(RuntimeException::class);

        $collection->getPath('nonexistent');
    }

    /** @test */
    public function it_properly_processes_a_simple_route_with_no_parameters()
    {
        $collection = new RouteCollection();
        // We can use anything for the handler since we're only testing getPath
        $collection->addRoute('GET', '/custom/route', 'custom', '');

        $this->assertEquals('/custom/route', $collection->getPath('custom'));
    }

    /** @test */
    public function it_properly_processes_a_route_with_all_parameters_required()
    {
        $collection = new RouteCollection();
        // We can use anything for the handler since we're only testing getPath
        $collection->addRoute('GET', '/custom/{route}/{has}/{parameters}', 'custom', '');

        $this->assertEquals('/custom/something/something_else/anything_else', $collection->getPath('custom', [
            'route' => 'something',
            'has' => 'something_else',
            'parameters' => 'anything_else'
        ]));
    }

    /** @test */
    public function it_works_if_optional_parameters_are_missing()
    {
        $collection = new RouteCollection();
        // We can use anything for the handler since we're only testing getPath
        $collection->addRoute('GET', '/custom/{route}[/{has}]', 'custom', '');

        $this->assertEquals('/custom/something', $collection->getPath('custom', [
            'route' => 'something'
        ]));
    }

    /** @test */
    public function it_works_with_optional_parameters()
    {
        $collection = new RouteCollection();
        // We can use anything for the handler since we're only testing getPath
        $collection->addRoute('GET', '/custom/{route}[/{has}]', 'custom', '');

        $this->assertEquals('/custom/something/something_else', $collection->getPath('custom', [
            'route' => 'something',
            'has' => 'something_else'
        ]));
    }
}
